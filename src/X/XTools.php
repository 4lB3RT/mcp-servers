<?php

declare(strict_types=1);

namespace App\X;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;

class XTools
{
    public function __construct(
        private readonly XClient $client,
    ) {}

    /**
     * Post to X. Use reply_to to reply to a specific post (thread).
     *
     * @param string $text Post content (max 280 chars)
     * @param string|null $reply_to Post ID to reply to (for threads)
     */
    #[McpTool(name: 'tweet')]
    public function tweet(
        string $text,
        ?string $reply_to = null,
    ): string {
        $result = $this->client->post($text, $reply_to);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Get your home timeline.
     *
     * @param int $max_results Max posts to return (default 10)
     */
    #[McpTool(name: 'get_timeline')]
    public function getTimeline(
        #[Schema(minimum: 1, maximum: 100)]
        int $max_results = 10,
    ): string {
        $result = $this->client->getTimeline($max_results);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Get your own posts.
     *
     * @param int $max_results Max posts to return (default 10)
     */
    #[McpTool(name: 'get_my_tweets')]
    public function getMyPosts(
        #[Schema(minimum: 1, maximum: 100)]
        int $max_results = 10,
    ): string {
        $result = $this->client->getMyPosts($max_results);

        return json_encode($result, JSON_PRETTY_PRINT);
    }
}
