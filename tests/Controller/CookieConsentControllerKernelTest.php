<?php

namespace CookieConsentBundle\tests\Controller;

use CookieConsentBundle\Enum\ConsentType;
use CookieConsentBundle\Enum\CookieName;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class CookieConsentControllerKernelTest extends WebTestCase
{
    #[Test]
    public function shouldRenderTemplateOnRequest(): void
    {
        $this->givenSuccessfulRequest();
    }

    #[Test]
    public function shouldSubmitRequestForSimpleConsentSettingsAsPost(): void
    {
        $crawler = $this->givenSuccessfulRequest();

        $form = $crawler->selectButton('consent_simple[accept_all]')->form();

        $this->assertEquals('POST', $form->getMethod());

        static::getClient()->submit($form, ['consent_simple[accept_all]' => true]);

        $this->allCookiesAllowed();
    }

    #[Test]
    public function shouldRejectAllCookiesAndReturnCookieHeader(): void
    {
        $crawler = $this->givenSuccessfulRequest();

        $form = $crawler->selectButton('consent_simple[reject_all]')->form();

        $this->assertEquals('POST', $form->getMethod());

        static::getClient()->submit($form, ['consent_simple[reject_all]' => true]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseCookieValueSame(CookieName::COOKIE_CONSENT_NAME, ConsentType::NO_CONSENT);
    }

    #[Test]
    public function shouldSubmitRequestForDetailedConsentSettingsAsPost(): void
    {
        $crawler = $this->givenSuccessfulRequest();

        $form = $crawler->selectButton('consent_detailed[accept_all]')->form();

        $this->assertEquals('POST', $form->getMethod());

        static::getClient()->submit($form, ['consent_detailed[accept_all]' => true]);

        $this->allCookiesAllowed();
    }

    #[Test]
    public function shouldRejectMalformedRequests(): void
    {
        $client = static::createClient();
        foreach ([[], ['consent_simple' => [], 'consent_detailed' => []], ['consent_simple' => ['accept_all' => '1']]] as $data) {
            $client->request('POST', '/cookie-consent/update', $data);
            self::assertResponseStatusCodeSame(400);
            self::assertResponseNotHasCookie(CookieName::COOKIE_CONSENT_NAME);
        }
        $client->request('GET', '/cookie-consent/update');
        self::assertResponseStatusCodeSame(405);
    }

    #[Test]
    public function shouldSaveCustomConsentAndHideBanner(): void
    {
        $client = static::createClient([], ['HTTPS' => 'on']);
        $crawler = $client->request('GET', '/cookie-consent/view');
        $form = $crawler->selectButton('consent_detailed[save_consent_settings]')->form();
        $form['consent_detailed[categories][0][vendors][0][consentGiven]']->tick();
        $form['consent_detailed[categories][0][vendors][1][consentGiven]']->untick();
        $client->submit($form);
        self::assertResponseStatusCodeSame(201);
        self::assertResponseCookieValueSame(CookieName::COOKIE_CONSENT_NAME, ConsentType::CUSTOM_CONSENT);
        $client->request('GET', '/cookie-consent/view-if-no-consent');
        self::assertResponseIsSuccessful();
        self::assertSame('', $client->getResponse()->getContent());
        self::assertTrue($client->getResponse()->headers->hasCacheControlDirective('no-store'));
        $crawler = $client->request('GET', '/cookie-consent/view');
        self::assertSame(1, $crawler->filter('input[name="consent_detailed[categories][0][vendors][0][consentGiven]"][checked]')->count());
        self::assertSame(0, $crawler->filter('input[name="consent_detailed[categories][0][vendors][1][consentGiven]"][checked]')->count());
    }

    #[Test]
    public function invalidDetailedSubmissionMustNotChangeSavedConsent(): void
    {
        $client = static::createClient([], ['HTTPS' => 'on']);
        $crawler = $client->request('GET', '/cookie-consent/view');
        $client->submit($crawler->selectButton('consent_simple[reject_all]')->form());
        self::assertResponseStatusCodeSame(201);
        $crawler = $client->request('GET', '/cookie-consent/view');
        $form = $crawler->selectButton('consent_detailed[save_consent_settings]')->form();
        $form['consent_detailed[categories][0][vendors][0][consentGiven]']->tick();
        $form['consent_detailed[_token]'] = 'invalid';
        $client->submit($form);
        self::assertResponseStatusCodeSame(400);
        $crawler = $client->request('GET', '/cookie-consent/view');
        self::assertSame(0, $crawler->filter('input[name="consent_detailed[categories][0][vendors][0][consentGiven]"][checked]')->count());
    }

    /**
     * @return Crawler
     */
    private function givenSuccessfulRequest(): Crawler
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/cookie-consent/view');

        $this->assertResponseIsSuccessful();

        return $crawler;
    }

    /**
     * @return void
     */
    public function allCookiesAllowed(): void
    {
        $this->assertResponseIsSuccessful();
        $this->assertResponseHasCookie(CookieName::COOKIE_CONSENT_NAME);
    }
}