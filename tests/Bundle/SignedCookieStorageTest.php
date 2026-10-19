<?php

declare(strict_types=1);

namespace CookieConsentBundle\tests\Bundle;

use CookieConsentBundle\tests\Fixtures\App\AppKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SignedCookieStorageTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return SignedCookieStorageKernel::class;
    }

    public static function buttons(): iterable
    {
        yield 'accept' => ['accept_all'];
        yield 'reject' => ['reject_all'];
    }

    #[DataProvider('buttons')]
    public function testBannerRemainsHiddenAfterSessionCookieIsRemoved(string $button): void
    {
        $client = self::createClient(['environment' => 'signed_cookie']);
        $crawler = $client->request('GET', 'https://localhost/cookie-consent/view');
        $client->submit($crawler->selectButton('consent_simple['.$button.']')->form());
        self::assertResponseStatusCodeSame(201);
        $cookies = $client->getCookieJar()->all();
        $client->getCookieJar()->clear();
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'consent') {
                $client->getCookieJar()->set($cookie);
            }
        }
        $client->request('GET', 'https://localhost/cookie-consent/view-if-no-consent');
        self::assertResponseIsSuccessful();
        self::assertSame('', $client->getResponse()->getContent());
    }
}

class SignedCookieStorageKernel extends AppKernel
{
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);
        $container->loadFromExtension('cookie_consent', ['preference_storage' => 'signed_cookie']);
    }
}
