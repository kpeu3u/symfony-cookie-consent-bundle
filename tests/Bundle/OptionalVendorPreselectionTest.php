<?php

declare(strict_types=1);

/*
 * This file is part of the CookieConsentBundle Symfony bundle.
 * Created by Radoslav Georgiev.
 */

namespace CookieConsentBundle\tests\Bundle;

use CookieConsentBundle\Service\CookieConsentService;
use CookieConsentBundle\tests\Fixtures\App\AppKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class OptionalVendorPreselectionTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return OptionalVendorPreselectionKernel::class;
    }

    public static function storageModes(): iterable
    {
        yield 'session' => ['preselect_session'];
        yield 'signed cookie' => ['preselect_signed'];
    }

    #[DataProvider('storageModes')]
    public function testConfiguredPreselectionDoesNotGrantConsentOrOverrideSavedChoices(string $environment): void
    {
        $client  = self::createClient(['environment' => $environment]);
        $crawler = $client->request('GET', 'https://localhost/cookie-consent/view');
        self::assertGreaterThan(0, $crawler->filter('.consent-form-vendors input[checked]')->count());
        $service = self::getContainer()->get(CookieConsentService::class);
        self::assertFalse($service->isVendorAllowedByUser('bookmark', 'functional', $client->getRequest()));
        $form = $crawler->selectButton('consent_detailed[save_consent_settings]')->form();
        $form['consent_detailed[categories][0][vendors][0][consentGiven]']->untick();
        $client->submit($form);
        self::assertResponseStatusCodeSame(201);
        $crawler = $client->request('GET', 'https://localhost/cookie-consent/view');
        self::assertSame(0, $crawler->filter('input[name="consent_detailed[categories][0][vendors][0][consentGiven]"][checked]')->count());
        self::assertSame(1, $crawler->filter('input[name="consent_detailed[categories][0][vendors][1][consentGiven]"][checked]')->count());
        $client->submit($crawler->selectButton('consent_simple[reject_all]')->form());
        self::assertResponseStatusCodeSame(201);
        $crawler = $client->request('GET', 'https://localhost/cookie-consent/view');
        self::assertSame(0, $crawler->filter('input[type="checkbox"][checked]')->count());
    }
}

final class OptionalVendorPreselectionKernel extends AppKernel
{
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);
        $container->loadFromExtension('cookie_consent', [
            'preselect_optional_vendors' => true,
            'preference_storage'         => 'preselect_signed' === $this->environment ? 'signed_cookie' : 'session',
        ]);
    }
}
