<?php

namespace CookieConsentBundle\tests\Service;

use CookieConsentBundle\Entity\CookieConsentLog;
use CookieConsentBundle\Service\CookieConsentService;
use CookieConsentBundle\tests\Fixtures\Configuration\ConsentBundleConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class ConsentPersistenceTest extends TestCase
{
    public function testPersistsActualVendorChoices(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $records = [];
        $manager->expects(self::exactly(5))->method('persist')->willReturnCallback(function ($record) use (&$records) {
            $records[$record->getCookieName()] = $record;
        });
        $manager->expects(self::once())->method('flush');
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->with(CookieConsentLog::class)->willReturn($manager);
        $service = new CookieConsentService(ConsentBundleConfiguration::testCaseConfiguration(), true, $registry);
        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $settings = $service->createDetailedForm();
        $settings->getCategories()->first()->getVendors()->first()->setConsentGiven(true);
        $service->saveConsentSettings($settings, $request);
        self::assertSame(['bookmark' => true, 'shopping_cart' => false], json_decode($records['functional']->getCookieValue(), true));
        self::assertSame('127.0.0.x', $records['functional']->getIpAddress());
        self::assertSame($request->getSession()->get('consent-key'), $records['functional']->getConsentKey());
        self::assertFalse($service->isVendorAllowedByUser('unknown', 'functional', $request));
    }

    public function testExpiredConsentCookieDeniesStaleSessionChoices(): void
    {
        $config = ConsentBundleConfiguration::testCaseConfiguration();
        $config['consent_configuration']['consent_cookie']['name'] = 'custom-consent';
        $service = new CookieConsentService($config, false);
        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $service->acceptAllCookies($request);
        self::assertFalse($service->isCategoryAllowedByUser('analytics', $request));
        $request->cookies->set('custom-consent', 'full-consent');
        self::assertTrue($service->isCategoryAllowedByUser('analytics', $request));
        self::assertTrue($service->isCookieConsentFormSubmittedByUser($request));
    }

    public function testMissingSessionDeniesConsent(): void
    {
        $service = new CookieConsentService(ConsentBundleConfiguration::testCaseConfiguration(), false);
        self::assertFalse($service->isCategoryAllowedByUser('analytics', Request::create('/')));
    }
}
