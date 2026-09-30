<?php

declare(strict_types=1);

namespace CookieConsentBundle\tests\Bundle;

use CookieConsentBundle\tests\Fixtures\App\AppKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CookieConsentThemeTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return ThemeTestKernel::class;
    }

    public static function themes(): iterable
    {
        foreach (['light', 'dark', 'auto'] as $theme) {
            foreach (['dialog', 'bottom', 'top'] as $position) {
                yield "$theme / $position" => [$theme, $position];
            }
        }
    }

    #[DataProvider('themes')]
    public function testConfiguredThemeIsRendered(string $theme, string $position): void
    {
        $client = self::createClient(['environment' => "theme_{$theme}_{$position}"]);
        $client->request('GET', '/cookie-consent/view');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists(".cookie-consent--{$position}[data-cookie-consent-theme=\"$theme\"]");
        if ($position === 'dialog') {
            self::assertSelectorExists("dialog[data-cookie-consent-theme=\"$theme\"]");
        } else {
            self::assertSelectorNotExists('dialog');
        }
    }
}

class ThemeTestKernel extends AppKernel
{
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);
        [, $theme, $position] = explode('_', $this->environment);
        $container->loadFromExtension('cookie_consent', ['theme' => $theme, 'position' => $position]);
    }
}
