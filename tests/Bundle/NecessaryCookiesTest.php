<?php

declare(strict_types=1);

namespace CookieConsentBundle\tests\Bundle;

use CookieConsentBundle\tests\Fixtures\App\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NecessaryCookiesTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return NecessaryCookiesKernel::class;
    }

    public function testNecessaryCookiesRemainVisibleAfterRejectingOptionalCookies(): void
    {
        $client = self::createClient([], ['HTTPS' => 'on']);
        $crawler = $client->request('GET', '/cookie-consent/view');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.cookie-consent__necessary', 'Session <example>');
        self::assertSelectorTextContains('.cookie-consent__always-active', 'Always active');
        self::assertSelectorNotExists('.cookie-consent__necessary input');
        self::assertSelectorNotExists('.cookie-consent__necessary example');
        $form = $crawler->selectButton('consent_detailed[reject_all]')->form();
        self::assertStringNotContainsString('necessary_cookies', json_encode($form->getPhpValues()));
        $client->submit($form);
        self::assertResponseStatusCodeSame(201);
        $crawler = $client->request('GET', '/cookie-consent/view');
        self::assertSelectorTextContains('.cookie-consent__always-active', 'Always active');
        self::assertSame(0, $crawler->filter('.consent-form-vendors input:checked')->count());
    }
}

class NecessaryCookiesKernel extends AppKernel
{
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);
        $container->loadFromExtension('cookie_consent', [
            'necessary_cookies' => [
                'session' => ['name' => 'Session <example>', 'description' => 'Keeps the session available.'],
            ],
        ]);
    }
}
