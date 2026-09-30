<?php

declare(strict_types=1);

namespace CookieConsentBundle\tests\Bundle;

use CookieConsentBundle\tests\Fixtures\App\AppKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CookieConsentRejectButtonTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return RejectButtonTestKernel::class;
    }

    public static function visibility(): iterable
    {
        yield 'default' => ['reject_default', 2];
        yield 'hidden' => ['reject_hidden', 0];
    }

    #[DataProvider('visibility')]
    public function testBothFormsRespectVisibility(string $environment, int $buttons): void
    {
        $client = self::createClient(['environment' => $environment]);
        $crawler = $client->request('GET', '/cookie-consent/view');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount($buttons, '.js-reject-all-cookies');
        self::assertSelectorCount(2, '.js-accept-all-cookies');
        self::assertSelectorExists('.js-save-settings');
        $client->submit($crawler->selectButton('consent_simple[accept_all]')->form());
        self::assertResponseStatusCodeSame(201);
    }

    public function testCanSaveAllVendorsUncheckedWithoutRejectButton(): void
    {
        $client = self::createClient(['environment' => 'reject_hidden'], ['HTTPS' => 'on']);
        $crawler = $client->request('GET', '/cookie-consent/view');
        $client->submit($crawler->selectButton('consent_detailed[save_consent_settings]')->form());
        self::assertResponseStatusCodeSame(201);
        $crawler = $client->request('GET', '/cookie-consent/view');
        self::assertSame(0, $crawler->filter('.consent-form-vendors input:checked')->count());
    }
}

class RejectButtonTestKernel extends AppKernel
{
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);
        if ($this->environment === 'reject_hidden') {
            $container->loadFromExtension('cookie_consent', ['show_reject_all' => false]);
        }
    }
}
