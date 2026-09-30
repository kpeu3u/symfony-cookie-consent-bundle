<?php

declare(strict_types=1);


namespace CookieConsentBundle\tests\Bundle;

use CookieConsentBundle\CookieConsentBundle;
use CookieConsentBundle\tests\Fixtures\Configuration\ConsentBundleConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Parser;

class CookieConsentConfigDefinitonTest extends TestCase
{

    private Processor $processor;
    private ContainerBuilder $containerBuilder;
    private Configuration $configuration;


    public function setUp(): void
    {
        $this->containerBuilder = new ContainerBuilder();
        $this->processor = new Processor();
        $this->configuration = new Configuration(new CookieConsentBundle(), $this->containerBuilder, 'cookie_consent');
    }

    public function tearDown(): void
    {
        unset($this->containerBuilder);
    }

    public function testFullConfiguration(): void
    {
        $processedConfig = $this->processor->processConfiguration($this->configuration, [$this->getFullConfig()]);

        $consentCategories = $processedConfig['consent_configuration']['consent_categories'];

        $this->assertArrayHasKey('functional', $consentCategories);
        $this->assertArrayHasKey('social_media', $consentCategories);
        $this->assertArrayHasKey('marketing', $consentCategories);

        $categoryFunction = $consentCategories['functional'];
        $this->assertContains('bookmark', $categoryFunction);
        $this->assertContains('shopping_cart', $categoryFunction);

        $this->assertContains('twitter', $consentCategories['social_media']);

        $categoryMarketing = $consentCategories['marketing'];
        $this->assertIsArray($categoryMarketing);
        $this->assertCount(1, $categoryMarketing);

        $this->assertEquals('dialog', $processedConfig['position']);
    }

    public function testThemeConfiguration(): void
    {
        $defaults = $this->processor->processConfiguration($this->configuration, []);
        self::assertSame('light', $defaults['theme']);
        foreach (['light', 'dark', 'auto'] as $theme) {
            $config = $this->processor->processConfiguration($this->configuration, [['theme' => $theme]]);
            self::assertSame($theme, $config['theme']);
        }
    }

    public function testUnknownThemeIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->processor->processConfiguration($this->configuration, [['theme' => 'unknown']]);
    }

    public function testNecessaryCookiesConfiguration(): void
    {
        $defaults = $this->processor->processConfiguration($this->configuration, []);
        self::assertSame([], $defaults['necessary_cookies']);
        $cookies = ['session' => ['name' => 'Session', 'description' => 'Retains your session.']];
        $config = $this->processor->processConfiguration($this->configuration, [['necessary_cookies' => $cookies]]);
        self::assertSame($cookies, $config['necessary_cookies']);
    }

    public function testNecessaryCookieRequiresDescription(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->processor->processConfiguration($this->configuration, [['necessary_cookies' => ['session' => ['name' => 'Session']]]]);
    }

    public function testInvalidConfiguration(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->processor->processConfiguration($this->configuration, [$this->getInvalidConfig()]);
    }

    public function testCookieSettingsIsAnArray(): void
    {
        $processedConfig = $this->processor->processConfiguration($this->configuration, [$this->getFullConfig()]);
        $this->assertIsArray($processedConfig);
    }

    /**
     * get full config.
     */
    protected function getFullConfig(): array
    {
        return ConsentBundleConfiguration::testCaseConfiguration();
    }

    /**
     * get invalid config.
     */
    protected function getInvalidConfig(): array
    {
        $yaml = <<<EOF
foo: 'bar'
EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
    }
}
