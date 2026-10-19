<?php

declare(strict_types=1);

namespace CookieConsentBundle\Storage;

use CookieConsentBundle\Form\ConsentCategoryTypeModel;
use CookieConsentBundle\Form\ConsentDetailedTypeModel;
use CookieConsentBundle\Form\ConsentVendorTypeModel;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

final class SignedCookiePreferenceStorage implements PreferenceStorageInterface
{
    private readonly string $configurationHash;

    public function __construct(
        private readonly string $cookieName,
        private readonly string $secret,
        private readonly array $categories,
        string $version = '1',
    ) {
        if ($secret === '') {
            throw new \InvalidArgumentException('Signed consent cookies require a non-empty application secret.');
        }
        $this->configurationHash = hash('sha256', json_encode([$version, $categories], JSON_THROW_ON_ERROR));
    }

    public function read(Request $request): ?ConsentDetailedTypeModel
    {
        $value = $request->cookies->get($this->cookieName);
        if (!is_string($value) || strlen($value) > 3800) {
            return null;
        }
        $parts = explode('.', $value);
        if (count($parts) !== 2 || !hash_equals($this->sign($parts[0]), $parts[1])) {
            return null;
        }
        $json = base64_decode(strtr($parts[0], '-_', '+/'), true);
        if ($json === false) {
            return null;
        }
        try {
            $data = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        if (!is_array($data) || ($data['configuration'] ?? null) !== $this->configurationHash
            || !is_int($data['expires'] ?? null) || $data['expires'] <= time()
            || !is_array($data['choices'] ?? null)) {
            return null;
        }
        $model = new ConsentDetailedTypeModel();
        $index = 0;
        foreach ($this->categories as $name => $vendors) {
            $category = new ConsentCategoryTypeModel();
            $category->setName($name);
            foreach ($vendors as $name) {
                $allowed = $data['choices'][$index++] ?? null;
                if (!is_bool($allowed)) {
                    return null;
                }
                $vendor = new ConsentVendorTypeModel();
                $vendor->setName($name);
                $vendor->setDescriptionKey($name);
                $vendor->setConsentGiven($allowed);
                $category->getVendors()->add($vendor);
            }
            $model->getCategories()->add($category);
        }

        return count($data['choices']) === $index ? $model : null;
    }

    public function write(Request $request, ConsentDetailedTypeModel $settings, Cookie $cookie): Cookie
    {
        $choices = [];
        foreach ($this->categories as $categoryName => $vendors) {
            foreach ($vendors as $vendorName) {
                $allowed = false;
                foreach ($settings->getCategories() as $category) {
                    if ($category->getName() !== $categoryName) {
                        continue;
                    }
                    foreach ($category->getVendors() as $vendor) {
                        if ($vendor->getName() === $vendorName) {
                            $allowed = $vendor->getConsentGiven() === true;
                        }
                    }
                }
                $choices[] = $allowed;
            }
        }
        $payload = rtrim(strtr(base64_encode(json_encode([
            'configuration' => $this->configurationHash,
            'expires' => $cookie->getExpiresTime(),
            'choices' => $choices,
        ], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $value = $payload.'.'.$this->sign($payload);
        if (strlen(rawurlencode($cookie->getName()).'='.rawurlencode($value)) > 3800) {
            throw new \LengthException('Consent preferences exceed the supported cookie size.');
        }

        return $cookie->withValue($value)->withHttpOnly(true);
    }

    public function clear(Request $request, Cookie $cookie): Cookie
    {
        $request->cookies->remove($this->cookieName);

        return $cookie->withValue('')->withExpires(1)->withHttpOnly(true);
    }

    private function sign(string $payload): string
    {
        return hash_hmac('sha256', 'cookie-consent:'.$this->cookieName.':'.$payload, $this->secret);
    }
}
