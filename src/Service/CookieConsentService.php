<?php

namespace CookieConsentBundle\Service;

use CookieConsentBundle\Enum\ConsentType;
use CookieConsentBundle\Enum\CookieName;
use CookieConsentBundle\Form\ConsentCategoryTypeModel;
use CookieConsentBundle\Form\ConsentDetailedTypeModel;
use CookieConsentBundle\Form\ConsentVendorTypeModel;
use CookieConsentBundle\Mapper\CookieConfigMapper;
use InvalidArgumentException;
use CookieConsentBundle\Cookie\CookieLogger;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CookieConsentService
{
    public function __construct(
        private readonly array $consentConfiguration,
        private readonly bool $persistConsent,
        private readonly ?ManagerRegistry $registry = null)
    {
    }

    /**
     * Check if user permits a given cookie category.
     * @param string $categoryName
     * @param Request $request
     * @return bool
     */
    public function isCategoryAllowedByUser(string $categoryName, Request $request): bool
    {
        $categorySettings = $this->getCategorySettingsFromSession($request, $categoryName);

        if ($categorySettings === null || $categorySettings->getVendors()->isEmpty()) {
            return false;
        }

        $allVendorConsentGiven = $categorySettings->getVendors()->forAll(function (int $key, ConsentVendorTypeModel $value) {
            return $value->getConsentGiven() === true;
        });

        return $allVendorConsentGiven;
    }

    private function getCategorySettingsFromSession(Request $request, string $categoryName): ?ConsentCategoryTypeModel
    {
        /** @var ConsentDetailedTypeModel $consentSettings */
        $consentSettings = $this->getConsentSettingsFromSession($request);

        if ($consentSettings === null) {
            return null;
        }

        /** @var ConsentCategoryTypeModel $categorySettings */
        return $consentSettings->getCategories()->findFirst(function (int $key, ConsentCategoryTypeModel $value) use ($categoryName) {
            return $value->getName() === $categoryName;
        });
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getConsentSettingsFromSession(Request $request): mixed
    {
        $cookieName = $this->consentConfiguration['consent_configuration']['consent_cookie']['name'];
        if (!$request->hasSession() || !$request->cookies->has($cookieName)) {
            return null;
        }
        $settings = $request->getSession()->get('consent-settings');
        return $settings instanceof ConsentDetailedTypeModel ? $settings : null;
    }

    /**
     * Check if user gave consent for vendor in category
     * @param string $vendorName
     * @param string $categoryName
     * @param Request $request
     * @return bool
     */
    public function isVendorAllowedByUser(string $vendorName, string $categoryName, Request $request): bool
    {
        $categorySettings = $this->getCategorySettingsFromSession($request, $categoryName);

        if ($categorySettings === null || $categorySettings->getVendors()->isEmpty()) {
            return false;
        }

        /** @var ConsentVendorTypeModel $vendorSettings */
        $vendorSettings = $categorySettings->getVendors()->findFirst(function (int $key, ConsentVendorTypeModel $value) use ($vendorName) {
            return $value->getName() === $vendorName;
        });

        return $vendorSettings?->getConsentGiven() ?? false;
    }

    /**
     * Check if cookie consent has already been saved.
     * @return bool
     */
    public function isCookieConsentFormSubmittedByUser(Request $request): bool
    {
        $consentSettings = $this->getConsentSettingsFromSession($request);

        return $consentSettings !== null;
    }

    public function saveConsentSettings(ConsentDetailedTypeModel $formData, Request $request): ResponseHeaderBag
    {
        // always set value to true as the user did give the consent to at least some of the cookies
        $consentCookie = CookieConfigMapper::mapToCookie($this->consentConfiguration['consent_configuration']['consent_cookie'], ConsentType::CUSTOM_CONSENT);

        if ($consentCookie == null) {
            throw new InvalidArgumentException("Cookie configuration can't be mapped to a Cookie");
        }

        // save "no-consent" to session
        $this->persistConsentSettings($request, $formData);
        $this->saveConsentSettingsToSession($request, $formData);


        $headerBag = new ResponseHeaderBag();
        $headerBag->setCookie($consentCookie);

        return $headerBag;
    }

    private
    function saveConsentSettingsToSession(Request $request, mixed $value): void
    {
        $session = $request->getSession();

        // save consent settings in session
        $session->set('consent-settings', $value);
    }

    private function persistConsentSettings(Request $request, ConsentDetailedTypeModel $settings): void
    {
        if (!$this->persistConsent) {
            return;
        }
        if ($this->registry === null) {
            throw new \LogicException('Doctrine is required when persist_consent is enabled.');
        }
        $categories = [];
        foreach ($settings->getCategories() as $category) {
            $vendors = [];
            foreach ($category->getVendors() as $vendor) {
                $vendors[$vendor->getName()] = $vendor->getConsentGiven();
            }
            $categories[$category->getName()] = json_encode($vendors, JSON_THROW_ON_ERROR);
        }
        $key = $request->getSession()->get('consent-key');
        if (!is_string($key)) {
            $key = bin2hex(random_bytes(16));
            $request->getSession()->set('consent-key', $key);
        }
        (new CookieLogger($this->registry, $request))->log($categories, $key);
    }

    /**
     * @param Request $request
     * @return ResponseHeaderBag
     * @throws InvalidArgumentException
     */
    public function acceptAllCookies(Request $request): ResponseHeaderBag
    {
        // always set value to true as the user did give the consent to use all cookies
        $consentCookie = CookieConfigMapper::mapToCookie($this->consentConfiguration['consent_configuration']['consent_cookie'], ConsentType::FULL_CONSENT);

        if ($consentCookie == null) {
            throw new InvalidArgumentException("Cookie configuration can't be mapped to a Cookie");
        }

        // save "no-consent" to session
        $settings = $this->createDetailedForm(consentGiven: true);
        $this->persistConsentSettings($request, $settings);
        $this->saveConsentSettingsToSession($request, $settings);


        $headerBag = new ResponseHeaderBag();
        $headerBag->setCookie($consentCookie);

        return $headerBag;
    }

    public function createDetailedFormForRequest(Request $request): ConsentDetailedTypeModel
    {
        // Use fresh configured models: binding an invalid form must never mutate
        // the objects already stored in the session.
        if (!$this->isCookieConsentFormSubmittedByUser($request)) {
            // Preselect the form only; actual permission still requires a saved choice.
            return $this->createDetailedForm(consentGiven: true);
        }
        $model = $this->createDetailedForm();
        foreach ($model->getCategories() as $category) {
            foreach ($category->getVendors() as $vendor) {
                $vendor->setConsentGiven($this->isVendorAllowedByUser($vendor->getName(), $category->getName(), $request));
            }
        }
        return $model;
    }

    public function createDetailedForm($consentGiven = false): ConsentDetailedTypeModel
    {
        $consentConfig = $this->consentConfiguration['consent_configuration'];

        $formModel = new ConsentDetailedTypeModel();

        foreach ($consentConfig['consent_categories'] as $categoryKey => $category) {

            $consentCategory = new ConsentCategoryTypeModel();
            $consentCategory->setName($categoryKey);

            foreach ($category as $vendor) {
                $consentCookie = new ConsentVendorTypeModel();

                // explicitly set fields from formData
                $consentCookie->setName($vendor);
                $consentCookie->setConsentGiven($consentGiven);
                $consentCookie->setDescriptionKey($vendor);

                $consentCategory->getVendors()->add($consentCookie);
            }

            $formModel->getCategories()->add($consentCategory);
        }

        return $formModel;
    }

    /**
     * @param Request $request
     * @return ResponseHeaderBag
     * @throws InvalidArgumentException
     */
    public function rejectAllCookies(Request $request): ResponseHeaderBag
    {
        // always set value to false as the user didn't give the consent to use more cookies than necessary but we use the 'consent' cookie to hide the UI
        $consentCookie = CookieConfigMapper::mapToCookie($this->consentConfiguration['consent_configuration']['consent_cookie'], ConsentType::NO_CONSENT);

        if ($consentCookie == null) {
            throw new InvalidArgumentException("Cookie configuration can't be mapped to a Cookie");
        }

        // save "no-consent" to session
        $settings = $this->createDetailedForm(consentGiven: false);
        $this->persistConsentSettings($request, $settings);
        $this->saveConsentSettingsToSession($request, $settings);


        $headerBag = new ResponseHeaderBag();
        $headerBag->setCookie($consentCookie);

        return $headerBag;
    }
}
