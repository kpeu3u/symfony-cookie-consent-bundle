<?php

declare(strict_types=1);

/*
 * This file is part of the CookieConsentBundle Symfony bundle.
 * Created by Radoslav Georgiev.
 */

namespace CookieConsentBundle\tests\Service;

use CookieConsentBundle\Service\CookieConsentService;
use CookieConsentBundle\Storage\SignedCookiePreferenceStorage;
use CookieConsentBundle\tests\Fixtures\Configuration\ConsentBundleConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class SignedCookiePreferencesTest extends TestCase
{
    private function service(?array $config = null, string $secret = 'test-secret', string $version = '1'): CookieConsentService
    {
        $config ??= ConsentBundleConfiguration::testCaseConfiguration();

        return new CookieConsentService($config, false, null, new SignedCookiePreferenceStorage(
            $config['consent_configuration']['consent_cookie']['name'], $secret,
            $config['consent_configuration']['consent_categories'], $version,
        ));
    }

    public static function choices(): iterable
    {
        yield 'accept' => ['acceptAllCookies', true];
        yield 'reject' => ['rejectAllCookies', false];
    }

    #[DataProvider('choices')]
    public function testChoiceSurvivesWithoutSession(string $method, bool $allowed): void
    {
        $service = $this->service();
        $cookie  = $service->$method(Request::create('/'))->getCookies()[0];
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        $request = Request::create('/', cookies: [$cookie->getName() => $cookie->getValue()]);
        self::assertFalse($request->hasSession());
        self::assertTrue($service->isCookieConsentFormSubmittedByUser($request));
        self::assertSame($allowed, $service->isCategoryAllowedByUser('analytics', $request));
        self::assertSame($allowed, $service->isVendorAllowedByUser('google_analytics', 'analytics', $request));
        self::assertNotNull($service->getConsentSettingsFromSession($request));
        $cleared = $service->clearConsentSettings($request)->getCookies()[0];
        self::assertLessThan(time(), $cleared->getExpiresTime());
        self::assertFalse($service->isCookieConsentFormSubmittedByUser($request));
    }

    public function testCustomChoicesAndFormPrefill(): void
    {
        $service  = $this->service();
        $settings = $service->createDetailedForm();
        $settings->getCategories()->first()->getVendors()->first()->setConsentGiven(true);
        $cookie  = $service->saveConsentSettings($settings, Request::create('/'))->getCookies()[0];
        $request = Request::create('/', cookies: [$cookie->getName() => $cookie->getValue()]);
        self::assertTrue($service->isVendorAllowedByUser('bookmark', 'functional', $request));
        self::assertFalse($service->isVendorAllowedByUser('shopping_cart', 'functional', $request));
        self::assertFalse($service->isCategoryAllowedByUser('functional', $request));
        self::assertFalse($service->isVendorAllowedByUser('unknown', 'functional', $request));
        $prefill = $service->createDetailedFormForRequest($request);
        self::assertTrue($prefill->getCategories()->first()->getVendors()->first()->getConsentGiven());
        self::assertFalse($prefill->getCategories()->first()->getVendors()->last()->getConsentGiven());
    }

    public function testInvalidExpiredAndOutdatedCookiesDenyConsent(): void
    {
        $service                                                              = $this->service();
        $cookie                                                               = $service->acceptAllCookies(Request::create('/'))->getCookies()[0];
        $request                                                              = Request::create('/', cookies: [$cookie->getName() => $cookie->getValue()]);
        $config                                                               = ConsentBundleConfiguration::testCaseConfiguration();
        $config['consent_configuration']['consent_categories']['analytics'][] = 'new_vendor';
        foreach ([$this->service(secret: 'changed'), $this->service(version: '2'), $this->service($config)] as $changed) {
            self::assertFalse($changed->isCookieConsentFormSubmittedByUser($request));
            self::assertFalse($changed->isCategoryAllowedByUser('analytics', $request));
        }
        foreach (['full-consent', '', 'invalid.signature', $cookie->getValue().'x', str_repeat('a', 4000)] as $value) {
            $request->cookies->set($cookie->getName(), $value);
            self::assertFalse($service->isCookieConsentFormSubmittedByUser($request));
            self::assertFalse($service->isCategoryAllowedByUser('analytics', $request));
        }
        // Sign expired and malformed payloads to exercise validation after HMAC verification.
        foreach ([['expires' => time() - 1], ['choices' => [true]], ['choices' => ['yes']], ['expires' => 'future']] as $override) {
            [$payload] = explode('.', $cookie->getValue());
            $data      = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
            $payload   = rtrim(strtr(base64_encode(json_encode(array_replace($data, $override))), '+/', '-_'), '=');
            $signature = hash_hmac('sha256', 'cookie-consent:'.$cookie->getName().':'.$payload, 'test-secret');
            $request->cookies->set($cookie->getName(), $payload.'.'.$signature);
            self::assertFalse($service->isCookieConsentFormSubmittedByUser($request));
        }
    }

    public function testOversizedPreferencesFailExplicitly(): void
    {
        $config                                                = ConsentBundleConfiguration::testCaseConfiguration();
        $config['consent_configuration']['consent_categories'] = ['analytics' => array_map(fn ($n) => 'vendor'.$n, range(1, 1000))];
        $this->expectException(\LengthException::class);
        $this->service($config)->acceptAllCookies(Request::create('/'));
    }
}
