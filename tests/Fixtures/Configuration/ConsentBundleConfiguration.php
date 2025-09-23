<?php

namespace CookieConsentBundle\tests\Fixtures\Configuration;


use CookieConsentBundle\Enum\CookieName;
use Symfony\Component\HttpFoundation\Cookie;

class ConsentBundleConfiguration
{
    public static function testCaseConfiguration(): array
    {
        return [
            'consent_configuration' => [
                'consent_cookie' => [
                    'name' => CookieName::COOKIE_CONSENT_NAME,
                    'http_only' => true,
                    'secure' => true,
                    'same_site' => Cookie::SAMESITE_LAX,
                    'domain' => null,
                    'expires' => 'P180D'
                ],
                'consent_categories' => [
                    'functional' => [
                        'bookmark',
                        'shopping_cart',
                    ],
                    'social_media' => [
                        'twitter',
                        'facebook',
                        'instagram',
                        'linkedin',
                        'pinterest',
                        'youtube',
                    ],
                    'analytics' => [
                        'google_analytics',
                    ],
                    'tracking' => [
                        'facebook_pixel',
                    ],
                    'marketing' => [
                        'google_adsense',

                    ]
                ]
            ],
            'position' => 'dialog'
        ];
    }

    public static function kernelTestCaseConfiguration(): array
    {
        return [
            'consent_configuration' => [
                'consent_categories' => [
                    'functional' => [
                        'bookmark',
                        'shopping_cart'
                    ],
                    'social_media' => [
                        'twitter',
                        'facebook',
                    ],
                    'analytics' => [
                        'google_analytics',
                    ],
                    'tracking' => [
                        'facebook_pixel',
                    ],
                    'marketing' => [
                        'google_adsense',
                    ]
                ]
            ],
            'position' => 'dialog'
        ];
    }
}
