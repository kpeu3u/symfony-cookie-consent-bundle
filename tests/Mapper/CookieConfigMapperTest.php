<?php

declare(strict_types=1);

/*
 * This file is part of the CookieConsentBundle Symfony bundle.
 * Created by Radoslav Georgiev.
 */

namespace CookieConsentBundle\tests\Mapper;

use CookieConsentBundle\Mapper\CookieConfigMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CookieConfigMapperTest extends TestCase
{
    #[Test]
    public function shouldReturnDateTime(): void
    {
        $expire = CookieConfigMapper::convertExpireToDate('P180D');
        $this->assertInstanceOf(\DateTimeInterface::class, $expire);
    }

    #[Test]
    public function shouldConvertDateIntervalAsStringToDatetime(): void
    {
        $interval = new \DateInterval('P180D');
        $now      = new \DateTimeImmutable();

        $later = $now->add($interval);

        $this->assertGreaterThan($now, $later);
    }
}
