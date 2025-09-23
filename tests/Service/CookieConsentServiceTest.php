<?php

namespace kpeu3u\CookieConsentBundle\tests\Service;

use kpeu3u\CookieConsentBundle\Enum\ConsentType;
use kpeu3u\CookieConsentBundle\Enum\CookieName;
use kpeu3u\CookieConsentBundle\Form\ConsentCategoryTypeModel;
use kpeu3u\CookieConsentBundle\Form\ConsentDetailedTypeModel;
use kpeu3u\CookieConsentBundle\Form\ConsentVendorTypeModel;
use kpeu3u\CookieConsentBundle\Service\CookieConsentService;
use kpeu3u\CookieConsentBundle\tests\Fixtures\Configuration\ConsentBundleConfiguration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CookieConsentServiceTest extends TestCase
{
    private CookieConsentService $consentService;


    public function setUp(): void
    {
        $this->consentService = new CookieConsentService($this->getConsentCookieConfiguration(), false);
    }

    #[Test]
    public function shouldReturnCookieWithNoConsentAfterRejectingConsent(): void
    {
        $request = $this->createMock(Request::class);
        $headerBag = $this->consentService->rejectAllCookies($request);

        $this->assertInstanceOf(ResponseHeaderBag::class, $headerBag);
        $this->assertEquals(ConsentType::NO_CONSENT, $headerBag->getCookies()[0]->getValue());
        $this->assertEquals(CookieName::COOKIE_CONSENT_NAME, $headerBag->getCookies()[0]->getName());
    }

    #[Test]
    public function shouldReturnCookieWithFullConsentAfterGivingConsent(): void
    {
        $request = $this->createMock(Request::class);
        $headerBag = $this->consentService->acceptAllCookies($request);

        $this->assertInstanceOf(ResponseHeaderBag::class, $headerBag);
        $this->assertEquals(ConsentType::FULL_CONSENT, $headerBag->getCookies()[0]->getValue());
        $this->assertEquals(CookieName::COOKIE_CONSENT_NAME, $headerBag->getCookies()[0]->getName());
    }

    #[Test]
    public function shouldReturnCookieWithCustomConsentAfterSubmittingCustomConsent(): void
    {
        $request = $this->createMock(Request::class);
        $consentSettings = $this->consentService->createDetailedForm();

        /** @var ConsentCategoryTypeModel $consentCategory */
        $consentCategory = $consentSettings->getCategories()->get(0);

        /** @var ConsentVendorTypeModel $vendor */
        $vendor = $consentCategory->getVendors()->get(0);

        $vendor->setConsentGiven(true);

        $headerBag = $this->consentService->saveConsentSettings($consentSettings, $request);

        $this->assertInstanceOf(ResponseHeaderBag::class, $headerBag);
        $this->assertEquals(ConsentType::CUSTOM_CONSENT, $headerBag->getCookies()[0]->getValue());
        $this->assertEquals(CookieName::COOKIE_CONSENT_NAME, $headerBag->getCookies()[0]->getName());
    }

    #[Test]
    public function shouldCreateDetailedFormModelFromConfiguration(): void
    {
        $formModel = $this->consentService->createDetailedForm();

        $this->assertInstanceOf(ConsentDetailedTypeModel::class, $formModel);

        $configuredCategories = $this->getConsentCookieConfiguration()['consent_categories'];

        /** @var ConsentCategoryTypeModel $category */
        foreach ($formModel->getCategories() as $category) {
            $this->assertArrayHasKey($category->getName(), $configuredCategories);

            /** @var ConsentVendorTypeModel $vendor */
            foreach ($category->getVendors() as $vendor) {
                $this->assertContains($vendor->getName(), $configuredCategories[$category->getName()]);
            }
        }
    }

    private function getConsentCookieConfiguration()
    {
        return ConsentBundleConfiguration::testCaseConfiguration()['consent_configuration'];
    }
}
