<?php

namespace CookieConsentBundle\tests\Service;

use CookieConsentBundle\Enum\ConsentType;
use CookieConsentBundle\Enum\CookieName;
use CookieConsentBundle\Form\ConsentCategoryTypeModel;
use CookieConsentBundle\Form\ConsentDetailedTypeModel;
use CookieConsentBundle\Form\ConsentVendorTypeModel;
use CookieConsentBundle\Service\CookieConsentService;
use CookieConsentBundle\tests\Fixtures\Configuration\ConsentBundleConfiguration;
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
    public function shouldPreselectNewChoicesWithoutGrantingConsentAndPreserveRejection(): void
    {
        $request = new Request();
        $request->setSession(new \Symfony\Component\HttpFoundation\Session\Session(
            new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()
        ));
        $model = $this->consentService->createDetailedFormForRequest($request);
        foreach ($model->getCategories() as $category) {
            foreach ($category->getVendors() as $vendor) {
                self::assertTrue($vendor->getConsentGiven());
                self::assertFalse($this->consentService->isVendorAllowedByUser($vendor->getName(), $category->getName(), $request));
            }
        }
        self::assertFalse($this->consentService->isCookieConsentFormSubmittedByUser($request));
        $headers = $this->consentService->rejectAllCookies($request);
        foreach ($headers->getCookies() as $cookie) {
            $request->cookies->set($cookie->getName(), $cookie->getValue());
        }
        foreach ($this->consentService->createDetailedFormForRequest($request)->getCategories() as $category) {
            foreach ($category->getVendors() as $vendor) {
                self::assertFalse($vendor->getConsentGiven());
            }
        }
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

        $configuredCategories = $this->getConsentCookieConfiguration()['consent_configuration']['consent_categories'];

        /** @var ConsentCategoryTypeModel $category */
        foreach ($formModel->getCategories() as $category) {
            $this->assertArrayHasKey($category->getName(), $configuredCategories);

            /** @var ConsentVendorTypeModel $vendor */
            foreach ($category->getVendors() as $vendor) {
                $this->assertContains($vendor->getName(), $configuredCategories[$category->getName()]);
            }
        }
    }

    private function getConsentCookieConfiguration(): array
    {
        return ConsentBundleConfiguration::testCaseConfiguration();
    }
}
