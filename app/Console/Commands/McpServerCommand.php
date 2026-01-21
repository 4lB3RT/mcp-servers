<?php

namespace App\Console\Commands;

use App\Services\TwitterService;
use Illuminate\Console\Command;

class McpServerCommand extends Command
{
    protected $signature = 'mcp:serve {--server=twitter : MCP server to run (twitter)}';
    protected $description = 'Run MCP server for Claude Code integration';

    private array $tools = [];

    public function handle(): int
    {
        $server = $this->option('server');

        $this->registerTools($server);
        $this->log("Starting {$server} MCP Server");

        while ($line = fgets(STDIN)) {
            $line = trim($line);
            if (empty($line)) continue;

            try {
                $request = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                $response = $this->handleRequest($request);
                $this->send($response);
            } catch (\Throwable $e) {
                $this->sendError(-32603, $e->getMessage());
            }
        }

        return 0;
    }

    private function registerTools(string $server): void
    {
        if ($server === 'twitter') {
            $this->tools = [
                'tweet' => [
                    'name' => 'tweet',
                    'description' => 'Post a tweet to Twitter/X',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'text' => ['type' => 'string', 'description' => 'Tweet content (max 280 chars)'],
                        ],
                        'required' => ['text'],
                    ],
                ],
                'get_timeline' => [
                    'name' => 'get_timeline',
                    'description' => 'Get your home timeline',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'max_results' => ['type' => 'integer', 'description' => 'Max tweets to return (default 10)'],
                        ],
                    ],
                ],
                'get_my_tweets' => [
                    'name' => 'get_my_tweets',
                    'description' => 'Get your own tweets',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'max_results' => ['type' => 'integer', 'description' => 'Max tweets to return (default 10)'],
                        ],
                    ],
                ],
            ];
        }
    }

    private function handleRequest(array $request): array
    {
        $method = $request['method'] ?? '';
        $id = $request['id'] ?? null;
        $params = $request['params'] ?? [];

        return match ($method) {
            'initialize' => $this->success($id, [
                'protocolVersion' => '2024-11-05',
                'capabilities' => ['tools' => new \stdClass()],
                'serverInfo' => ['name' => 'mcp-servers', 'version' => '1.0.0'],
            ]),
            'tools/list' => $this->success($id, ['tools' => array_values($this->tools)]),
            'tools/call' => $this->handleToolCall($id, $params),
            'ping' => $this->success($id, ['pong' => true]),
            default => $this->error($id, -32601, "Method not found: {$method}"),
        };
    }

    private function handleToolCall(mixed $id, array $params): array
    {
        $toolName = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (!isset($this->tools[$toolName])) {
            return $this->error($id, -32602, "Unknown tool: {$toolName}");
        }

        try {
            $twitter = app(TwitterService::class);

            $result = match ($toolName) {
                'tweet' => $twitter->tweet($arguments['text']),
                'get_timeline' => $twitter->getTimeline($arguments['max_results'] ?? 10),
                'get_my_tweets' => $twitter->getMyTweets($arguments['max_results'] ?? 10),
                default => ['error' => 'Tool not implemented'],
            };

            return $this->success($id, [
                'content' => [['type' => 'text', 'text' => json_encode($result, JSON_PRETTY_PRINT)]],
            ]);
        } catch (\Throwable $e) {
            return $this->error($id, -32603, $e->getMessage());
        }
    }

    private function success(mixed $id, array $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function error(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }

    private function send(array $response): void
    {
        echo json_encode($response) . "\n";
        flush();
    }

    private function sendError(int $code, string $message): void
    {
        $this->send($this->error(null, $code, $message));
    }

    private function log(string $message): void
    {
        fwrite(STDERR, "[MCP] {$message}\n");
    }
}
