<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TwitterService
{
    private string $apiKey;
    private string $apiSecret;
    private string $accessToken;
    private string $accessTokenSecret;

    public function __construct()
    {
        $this->apiKey = config('services.twitter.api_key');
        $this->apiSecret = config('services.twitter.api_secret');
        $this->accessToken = config('services.twitter.access_token');
        $this->accessTokenSecret = config('services.twitter.access_token_secret');
    }

    public function tweet(string $text, ?string $replyTo = null): array
    {
        $url = 'https://api.twitter.com/2/tweets';

        $payload = ['text' => $text];

        if ($replyTo) {
            $payload['reply'] = ['in_reply_to_tweet_id' => $replyTo];
        }

        $response = Http::timeout(15)->withHeaders([
            'Authorization' => $this->getOAuthHeader('POST', $url),
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        return $response->json() ?? ['error' => 'Empty response'];
    }

    public function getTimeline(int $maxResults = 10): array
    {
        $me = $this->getMe();
        $userId = $me['data']['id'] ?? null;

        if (!$userId) {
            return ['error' => 'Could not get user ID', 'details' => $me];
        }

        $url = "https://api.twitter.com/2/users/{$userId}/reverse_chronological_timeline";
        $params = ['max_results' => $maxResults];

        $response = Http::timeout(15)->withHeaders([
            'Authorization' => $this->getOAuthHeader('GET', $url, $params),
        ])->get($url, $params);

        return $response->json() ?? ['error' => 'Empty response'];
    }

    public function getMyTweets(int $maxResults = 10): array
    {
        $me = $this->getMe();
        $userId = $me['data']['id'] ?? null;

        if (!$userId) {
            return ['error' => 'Could not get user ID', 'details' => $me];
        }

        $url = "https://api.twitter.com/2/users/{$userId}/tweets";
        $params = ['max_results' => $maxResults];

        $response = Http::timeout(15)->withHeaders([
            'Authorization' => $this->getOAuthHeader('GET', $url, $params),
        ])->get($url, $params);

        return $response->json() ?? ['error' => 'Empty response'];
    }

    public function getMe(): array
    {
        $url = 'https://api.twitter.com/2/users/me';

        $response = Http::timeout(15)->withHeaders([
            'Authorization' => $this->getOAuthHeader('GET', $url),
        ])->get($url);

        return $response->json() ?? ['error' => 'Empty response'];
    }

    private function getOAuthHeader(string $method, string $url, array $queryParams = []): string
    {
        $oauth = [
            'oauth_consumer_key' => $this->apiKey,
            'oauth_nonce' => bin2hex(random_bytes(16)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0',
        ];

        // For signature, include query params but NOT body params for POST with JSON
        $signatureParams = array_merge($oauth, $queryParams);
        ksort($signatureParams);

        $paramString = http_build_query($signatureParams, '', '&', PHP_QUERY_RFC3986);
        $baseString = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode($paramString);

        $signingKey = rawurlencode($this->apiSecret) . '&' . rawurlencode($this->accessTokenSecret);
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        $parts = [];
        foreach ($oauth as $key => $value) {
            $parts[] = rawurlencode($key) . '="' . rawurlencode($value) . '"';
        }

        return 'OAuth ' . implode(', ', $parts);
    }
}
