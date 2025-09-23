<?php

namespace kpeu3u\CookieConsentBundle\tests\Bundle;

use kpeu3u\CookieConsentBundle\Controller\CookieConsentController;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CookieConsentBundleServicesTest extends KernelTestCase
{

    public function setUp(): void
    {
        self::bootKernel();
    }

    #[Test]
    public function shouldProvideController(): void
    {
        $this->assertInstanceOf(CookieConsentController::class, static::getContainer()->get(CookieConsentController::class));
    }
}
