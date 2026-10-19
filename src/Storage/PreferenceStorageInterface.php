<?php

declare(strict_types=1);

namespace CookieConsentBundle\Storage;

use CookieConsentBundle\Form\ConsentDetailedTypeModel;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

interface PreferenceStorageInterface
{
    public function read(Request $request): ?ConsentDetailedTypeModel;

    public function write(Request $request, ConsentDetailedTypeModel $settings, Cookie $cookie): Cookie;

    public function clear(Request $request, Cookie $cookie): Cookie;
}
