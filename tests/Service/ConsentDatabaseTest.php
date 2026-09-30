<?php

namespace CookieConsentBundle\tests\Service;

use CookieConsentBundle\Entity\CookieConsentLog;
use CookieConsentBundle\Service\CookieConsentService;
use CookieConsentBundle\tests\Fixtures\Configuration\ConsentBundleConfiguration;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class ConsentDatabaseTest extends KernelTestCase
{
    public function testConsentIsWrittenToDatabase(): void
    {
        self::bootKernel();
        $registry = self::getContainer()->get('doctrine');
        $manager = $registry->getManagerForClass(CookieConsentLog::class);
        (new SchemaTool($manager))->createSchema([$manager->getClassMetadata(CookieConsentLog::class)]);
        $service = new CookieConsentService(ConsentBundleConfiguration::testCaseConfiguration(), true, $registry);
        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $service->acceptAllCookies($request);
        $manager->clear();
        $logs = $manager->getRepository(CookieConsentLog::class)->findAll();
        self::assertCount(5, $logs);
        self::assertSame(['bookmark' => true, 'shopping_cart' => true], json_decode($logs[0]->getCookieValue(), true));
        $service->rejectAllCookies($request);
        $manager->clear();
        self::assertCount(10, $manager->getRepository(CookieConsentLog::class)->findAll());
    }
}
