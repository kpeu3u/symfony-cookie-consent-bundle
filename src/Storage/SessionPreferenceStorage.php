<?php

declare(strict_types=1);

/*
 * This file is part of the CookieConsentBundle Symfony bundle.
 * Created by Radoslav Georgiev.
 */

namespace CookieConsentBundle\Storage;

use CookieConsentBundle\Form\ConsentDetailedTypeModel;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

final class SessionPreferenceStorage implements PreferenceStorageInterface
{
    public function __construct(private readonly string $cookieName)
    {
    }

    public function read(Request $request): ?ConsentDetailedTypeModel
    {
        if (!$request->hasSession() || !$request->cookies->has($this->cookieName)) {
            return null;
        }
        $settings = $request->getSession()->get('consent-settings');

        return $settings instanceof ConsentDetailedTypeModel ? $settings : null;
    }

    public function write(Request $request, ConsentDetailedTypeModel $settings, Cookie $cookie): Cookie
    {
        $request->getSession()->set('consent-settings', $settings);

        return $cookie;
    }

    public function clear(Request $request, Cookie $cookie): Cookie
    {
        if ($request->hasSession()) {
            $request->getSession()->remove('consent-settings');
        }
        $request->cookies->remove($this->cookieName);

        return $cookie->withValue('')->withExpires(1);
    }
}
