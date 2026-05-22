<?php

namespace App\Service;

/**
 * Canonical public base URL for absolute links (email verification, OAuth callbacks).
 * Uses APP_URL, or https://{RAILWAY_PUBLIC_DOMAIN} when deployed on Railway.
 */
final class AppUrlService
{
    public function __construct(
        private readonly string $appUrl = '',
        private readonly string $railwayPublicDomain = '',
    ) {
    }

    public function getBaseUrl(): ?string
    {
        $url = trim($this->appUrl);
        if ($url !== '') {
            return rtrim($url, '/');
        }

        $domain = trim($this->railwayPublicDomain);
        if ($domain !== '') {
            return 'https://'.rtrim($domain, '/');
        }

        return null;
    }
}
