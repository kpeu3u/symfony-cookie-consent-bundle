<?php

namespace CookieConsentBundle\tests\Bundle;

use CookieConsentBundle\tests\Fixtures\Configuration\ConsentBundleConfiguration;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class CookieConsentBundleWebTest extends WebTestCase
{
    private Crawler $crawler;

    public function setUp(): void
    {
        $client = static::createClient();

        $this->crawler = $client->request('GET', '/cookie-consent/view');

        $this->assertResponseIsSuccessful();
    }


    #[Test]
    public function shouldRenderConsentFormInDialog()
    {
        // assert simple form and detailed form are rendered
        $this->assertSelectorExists('dialog .cookie-consent-simple');
        $this->assertSelectorNotExists('.cookie-consent__necessary');
        $this->assertSelectorExists('dialog .cookie-consent-detail');
    }

    #[Test]
    public function shouldRenderReadableLabelsForCategoryAndVendorSwitches(): void
    {
        $this->assertSelectorTextContains('label[for="consent_detailed_categories_2_consentGiven"]', 'Do you want analytical cookies?');
        $this->assertSelectorTextContains(' .cookie-consent__vendor-copy label[for="consent_detailed_categories_2_consentGiven"]', 'Google Analytics');
        $this->assertSelectorTextContains('label[for="consent_detailed_categories_0_vendors_0_consentGiven"]', 'Bookmark');
        self::assertStringNotContainsString('Consent given', $this->crawler->filter('.cookie-consent-detail')->text());
        $this->assertSelectorExists('.js-hide-settings');
        $this->assertSelectorCount(1, '#consent_detailed_categories_2 .cookie-consent__switch');
        $this->assertSelectorExists('#consent_detailed_categories_2_vendors_0_consentGiven[type="checkbox"]');
        $this->assertSelectorExists('#consent_detailed_categories_2 span[hidden] input');
        $this->assertSelectorCount(3, '#consent_detailed_categories_0 .cookie-consent__switch');
    }

    #[Test]
    public function shouldRenderDetailedConsentFormWithConsentCategories(): void
    {
        // expect the form to render a .consent-form-categories element
        $this->assertSelectorCount(1, '.cookie-consent-detail .consent-form-categories');

        // expect the form to contain as many .consent-form-category elements as consent_categories are defined by the bundle config
        $this->assertSelectorCount(sizeof(ConsentBundleConfiguration::kernelTestCaseConfiguration()['consent_configuration']['consent_categories']), '.consent-form-categories .consent-form-category');

        // expect form to contain the same amount of .consent-form-vendors as defined in the bundle config
        $this->assertSelectorCount(count(ConsentBundleConfiguration::kernelTestCaseConfiguration()['consent_configuration']['consent_categories']), '.consent-form-category .consent-form-vendors');

        // assert detailed form contains all categories set in bundle config
        // TODO: Find a nice way to check if the consent categories from the bundle settings are parts of the consent form
//        foreach (ConsentBundleConfiguration::kernelTestCaseConfiguration()['consent_configuration']['consent_categories'] as $key => $category) {
//            $this->assertSelectorExists('.consent-form-category input[name*=' . $key . ']');
//            $this->assertSelectorExists('.cookie-consent-detail .category-' . $key);
//        }
    }
}