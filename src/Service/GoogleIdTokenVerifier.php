<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Verifies Google ID tokens issued to the mobile app (web client ID as serverClientId).
 */
final class GoogleIdTokenVerifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $googleClientId,
    ) {
    }

    /**
     * @return array{email: string, name: string, sub: string}
     */
    public function verify(string $idToken): array
    {
        $idToken = trim($idToken);
        if ($idToken === '') {
            throw new \InvalidArgumentException('Google ID token is required.');
        }

        $response = $this->httpClient->request(
            'GET',
            'https://oauth2.googleapis.com/tokeninfo',
            ['query' => ['id_token' => $idToken]],
        );

        if ($response->getStatusCode() !== 200) {
            throw new \InvalidArgumentException('Invalid or expired Google sign-in. Please try again.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->toArray(false);

        $aud = $payload['aud'] ?? null;
        if (!\is_string($aud) || $aud !== $this->googleClientId) {
            throw new \InvalidArgumentException('Google sign-in configuration mismatch. Contact support.');
        }

        $email = $payload['email'] ?? null;
        if (!\is_string($email) || $email === '') {
            throw new \InvalidArgumentException('Google did not return an email for this account.');
        }

        $emailVerified = $payload['email_verified'] ?? 'false';
        if ($emailVerified !== 'true' && $emailVerified !== true) {
            throw new \InvalidArgumentException('Please verify your Google account email before signing in.');
        }

        $name = $payload['name'] ?? null;
        if (!\is_string($name) || $name === '') {
            $name = strstr($email, '@', true) ?: 'Google User';
        }

        $sub = $payload['sub'] ?? '';
        if (!\is_string($sub)) {
            $sub = '';
        }

        return [
            'email' => $email,
            'name' => $name,
            'sub' => $sub,
        ];
    }
}
