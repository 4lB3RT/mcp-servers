<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TwitterService
{
    private string $apiKey;
    private string $apiSecret;
    private string $accessToken;
    private string $accessTokenSecret;
    private string $bearerToken;

    public function __construct()
    {
        $this->apiKey = config('services.twitter.api_key');
        $this->apiSecret = config('services.twitter.api_secret');
        $this->accessToken = config('services.twitter.access_token');
        $this->accessTokenSecret = config('services.twitter.access_token_secret');
        $this->bearerToken = config('services.twitter.bearer_token');
    }

    public function tweet(string $text): array
    {
        $response = Http::withToken($this->bearerToken)
            ->withHeaders($this->getOAuthHeaders('POST', 'https://api.twitter.com/2/tweets', ['text' => $text]))
            ->post('https://api.twitter.com/2/tweets', [
                'text' => $text,
            ]);

        return $response->json();
    }

    public function getTimeline(int $maxResults = 10): array
    {
        $response = Http::withToken($this->bearerToken)
            ->get('https://api.twitter.com/2/users/me/timelines/reverse_chronological', [
                'max_results' => $maxResults,
            ]);

        return $response->json();
    }

    public function getMyTweets(int $maxResults = 10): array
    {
        $me = $this->getMe();
        $userId = $me['data']['id'] ?? null;

        if (!$userId) {
            return ['error' => 'Could not get user ID'];
        }

        $response = Http::withToken($this->bearerToken)
            ->get("https://api.twitter.com/2/users/{$userId}/tweets", [
                'max_results' => $maxResults,
            ]);

        return $response->json();
    }

    public function getMe(): array
    {
        $response = Http::withToken($this->bearerToken)
            ->get('https://api.twitter.com/2/users/me');

        return $response->json();
    }

    private function getOAuthHeaders(string $method, string $url, array $params = []): array
    {
        $oauth = [
            'oauth_consumer_key' => $this->apiKey,
            'oauth_nonce' => bin2hex(random_bytes(16)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0',
        ];

        $baseParams = array_merge($oauth, $params);
        ksort($baseParams);

        $baseString = $method . '&' . rawurlencode($url) . '&' . rawurlencode(http_build_query($baseParams));
        $signingKey = rawurlencode($this->apiSecret) . '&' . rawurlencode($this->accessTokenSecret);
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        $authHeader = 'OAuth ';
        $parts = [];
        foreach ($oauth as $key => $value) {
            $parts[] = rawurlencode($key) . '="' . rawurlencode($value) . '"';
        }
        $authHeader .= implode(', ', $parts);

        return ['Authorization' => $authHeader];
    }
}
