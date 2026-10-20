<?php

declare(strict_types=1);

/*
 * This file is part of the CookieConsentBundle Symfony bundle.
 * Created by Radoslav Georgiev.
 */

namespace CookieConsentBundle\Enum;

class ConsentType
{
    public const FULL_CONSENT   = 'full-consent';
    public const NO_CONSENT     = 'no-consent';
    public const CUSTOM_CONSENT = 'custom-consent';
}
