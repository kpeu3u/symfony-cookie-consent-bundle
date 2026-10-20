<?php

declare(strict_types=1);

/*
 * This file is part of the CookieConsentBundle Symfony bundle.
 * Created by Radoslav Georgiev.
 */

namespace CookieConsentBundle\tests\Bundle;

use CookieConsentBundle\Controller\CookieConsentController;
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
