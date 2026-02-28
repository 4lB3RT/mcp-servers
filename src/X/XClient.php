<?php

declare(strict_types=1);

namespace App\X;

use GuzzleHttp\Client;

class XClient
{
    private string $apiKey;
    private string $apiSecret;
    private string $accessToken;
    private string $accessTokenSecret;
    private Client $http;

    public function __construct()
    {
        $this->apiKey = $_ENV['TWITTER_API_KEY'];
        $this->apiSecret = $_ENV['TWITTER_API_SECRET'];
        $this->accessToken = $_ENV['TWITTER_ACCESS_TOKEN'];
        $this->accessTokenSecret = $_ENV['TWITTER_ACCESS_TOKEN_SECRET'];
        $this->http = new Client(['timeout' => 15]);
    }

    public function post(string $text, ?string $replyTo = null): array
    {
        $url = 'https://api.twitter.com/2/tweets';

        $payload = ['text' => $text];

        if ($replyTo) {
            $payload['reply'] = ['in_reply_to_tweet_id' => $replyTo];
        }

        $response = $this->http->post($url, [
            'headers' => [
                'Authorization' => $this->getOAuthHeader('POST', $url),
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? ['error' => 'Empty response'];
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

        $response = $this->http->get($url, [
            'headers' => [
                'Authorization' => $this->getOAuthHeader('GET', $url, $params),
            ],
            'query' => $params,
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? ['error' => 'Empty response'];
    }

    public function getMyPosts(int $maxResults = 10): array
    {
        $me = $this->getMe();
        $userId = $me['data']['id'] ?? null;

        if (!$userId) {
            return ['error' => 'Could not get user ID', 'details' => $me];
        }

        $url = "https://api.twitter.com/2/users/{$userId}/tweets";
        $params = ['max_results' => $maxResults];

        $response = $this->http->get($url, [
            'headers' => [
                'Authorization' => $this->getOAuthHeader('GET', $url, $params),
            ],
            'query' => $params,
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? ['error' => 'Empty response'];
    }

    public function getMe(): array
    {
        $url = 'https://api.twitter.com/2/users/me';

        $response = $this->http->get($url, [
            'headers' => [
                'Authorization' => $this->getOAuthHeader('GET', $url),
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? ['error' => 'Empty response'];
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
