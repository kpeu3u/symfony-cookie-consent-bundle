<?php

declare(strict_types=1);

namespace CookieConsentBundle\Mapper;

use DateInterval;
use Symfony\Component\HttpFoundation\Cookie;

class CookieConfigMapper
{
    /**
     * @throws \Exception
     */
    public static function mapToCookie(mixed $cookieConfiguration, string $value): ?Cookie
    {
        if (!isset($value)) {
            return null;
        }

        $name = $cookieConfiguration['name'] ?? null;
        $http_only = $cookieConfiguration['http_only'] ?? null;
        $secure = $cookieConfiguration['secure'] ?? null;
        $same_site = $cookieConfiguration['same_site'] ?? null;
        $domain = $cookieConfiguration['domain'] ?? null;
        $expires = $cookieConfiguration['expires'] ?? null;

        if ($name === null || $http_only === null || $secure === null || $same_site === null || $expires === null) {
            return null;
        }

        return new Cookie(name: $name, value: $value, expire: self::convertExpireToDate($expires), domain: $domain, secure: $secure, httpOnly: $http_only, raw: false, sameSite: $same_site, partitioned: false);
    }

    /**
     * @throws \Exception
     */
    public static function convertExpireToDate(string $maxAge): \DateTimeInterface
    {
        $now = new \DateTimeImmutable();
        return $now->add(new DateInterval($maxAge));
    }
}