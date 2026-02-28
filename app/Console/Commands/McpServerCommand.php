<?php

namespace App\Console\Commands;

use App\Services\GitHubService;
use App\Services\TwitterService;
use Illuminate\Console\Command;

class McpServerCommand extends Command
{
    protected $signature = 'mcp:serve {--server=twitter : MCP server to run (twitter, github)}';
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
                if ($response !== null) {
                    $this->send($response);
                }
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
                    'description' => 'Post a tweet to Twitter/X. Use reply_to to reply to a specific tweet (thread).',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'text' => ['type' => 'string', 'description' => 'Tweet content (max 280 chars)'],
                            'reply_to' => ['type' => 'string', 'description' => 'Tweet ID to reply to (for threads)'],
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
        } elseif ($server === 'github') {
            $this->tools = [
                'create_issue' => [
                    'name' => 'create_issue',
                    'description' => 'Create a new GitHub issue (task/story)',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string', 'description' => 'Issue title'],
                            'body' => ['type' => 'string', 'description' => 'Issue body/description (markdown supported)'],
                            'labels' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Labels to add (e.g., ["bug", "enhancement"])'],
                        ],
                        'required' => ['title'],
                    ],
                ],
                'list_issues' => [
                    'name' => 'list_issues',
                    'description' => 'List GitHub issues',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'state' => ['type' => 'string', 'description' => 'Filter by state: open, closed, all (default: open)'],
                            'labels' => ['type' => 'string', 'description' => 'Filter by labels (comma-separated)'],
                            'per_page' => ['type' => 'integer', 'description' => 'Results per page (default: 10)'],
                        ],
                    ],
                ],
                'get_issue' => [
                    'name' => 'get_issue',
                    'description' => 'Get a specific GitHub issue by number',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'issue_number' => ['type' => 'integer', 'description' => 'Issue number'],
                        ],
                        'required' => ['issue_number'],
                    ],
                ],
                'update_issue' => [
                    'name' => 'update_issue',
                    'description' => 'Update a GitHub issue',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'issue_number' => ['type' => 'integer', 'description' => 'Issue number'],
                            'title' => ['type' => 'string', 'description' => 'New title'],
                            'body' => ['type' => 'string', 'description' => 'New body'],
                            'state' => ['type' => 'string', 'description' => 'State: open or closed'],
                            'labels' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Labels to set'],
                        ],
                        'required' => ['issue_number'],
                    ],
                ],
                'close_issue' => [
                    'name' => 'close_issue',
                    'description' => 'Close a GitHub issue',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'issue_number' => ['type' => 'integer', 'description' => 'Issue number to close'],
                        ],
                        'required' => ['issue_number'],
                    ],
                ],
                'add_comment' => [
                    'name' => 'add_comment',
                    'description' => 'Add a comment to a GitHub issue',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'issue_number' => ['type' => 'integer', 'description' => 'Issue number'],
                            'body' => ['type' => 'string', 'description' => 'Comment body (markdown supported)'],
                        ],
                        'required' => ['issue_number', 'body'],
                    ],
                ],
                'create_step' => [
                    'name' => 'create_step',
                    'description' => 'Create a Step (user story) and add it to the Steps board',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string', 'description' => 'Step title'],
                            'user_story' => ['type' => 'string', 'description' => 'User story: Como X, quiero Y, para Z'],
                            'context' => ['type' => 'string', 'description' => 'Context explaining why this is needed'],
                            'criteria' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Acceptance criteria (Dado/Cuando/Entonces)'],
                            'priority' => ['type' => 'string', 'description' => 'Priority: high, medium, low (default: medium)'],
                        ],
                        'required' => ['title', 'user_story', 'context', 'criteria'],
                    ],
                ],
                'create_task' => [
                    'name' => 'create_task',
                    'description' => 'Create a Task linked to a parent Step and add it to the Tareas board',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string', 'description' => 'Task title'],
                            'description' => ['type' => 'string', 'description' => 'Technical description of the task'],
                            'parent_step' => ['type' => 'integer', 'description' => 'Parent Step issue number'],
                            'priority' => ['type' => 'string', 'description' => 'Priority: high, medium, low (default: medium)'],
                        ],
                        'required' => ['title', 'description', 'parent_step'],
                    ],
                ],
                'move_task_status' => [
                    'name' => 'move_task_status',
                    'description' => 'Move a task to a different status column in the Tareas board',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'issue_number' => ['type' => 'integer', 'description' => 'Task issue number'],
                            'status' => ['type' => 'string', 'description' => 'New status: todo, doing, review, done'],
                        ],
                        'required' => ['issue_number', 'status'],
                    ],
                ],
            ];
        }
    }

    private function handleRequest(array $request): ?array
    {
        $method = $request['method'] ?? '';
        $id = $request['id'] ?? null;
        $params = $request['params'] ?? [];

        if ($id === null || str_starts_with($method, 'notifications/')) {
            $this->log("Notification received: {$method}");
            return null;
        }

        return match ($method) {
            'initialize' => $this->success($id, [
                'protocolVersion' => '2024-11-05',
                'capabilities' => ['tools' => new \stdClass()],
                'serverInfo' => ['name' => 'mcp-servers', 'version' => '1.0.0'],
            ]),
            'tools/list' => $this->success($id, ['tools' => array_values($this->tools)]),
            'tools/call' => $this->handleToolCall($id, $params),
            'ping' => $this->success($id, ['pong' => true]),
            default => $this->errorResponse($id, -32601, "Method not found: {$method}"),
        };
    }

    private function handleToolCall(mixed $id, array $params): array
    {
        $toolName = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (!isset($this->tools[$toolName])) {
            return $this->errorResponse($id, -32602, "Unknown tool: {$toolName}");
        }

        try {
            $result = match ($toolName) {
                // Twitter tools
                'tweet' => app(TwitterService::class)->tweet($arguments['text'], $arguments['reply_to'] ?? null),
                'get_timeline' => app(TwitterService::class)->getTimeline($arguments['max_results'] ?? 10),
                'get_my_tweets' => app(TwitterService::class)->getMyTweets($arguments['max_results'] ?? 10),
                // GitHub tools
                'create_issue' => app(GitHubService::class)->createIssue(
                    $arguments['title'],
                    $arguments['body'] ?? null,
                    $arguments['labels'] ?? []
                ),
                'list_issues' => app(GitHubService::class)->listIssues(
                    $arguments['state'] ?? 'open',
                    $arguments['labels'] ?? null,
                    $arguments['per_page'] ?? 10
                ),
                'get_issue' => app(GitHubService::class)->getIssue($arguments['issue_number']),
                'update_issue' => app(GitHubService::class)->updateIssue(
                    $arguments['issue_number'],
                    $arguments['title'] ?? null,
                    $arguments['body'] ?? null,
                    $arguments['state'] ?? null,
                    $arguments['labels'] ?? []
                ),
                'close_issue' => app(GitHubService::class)->closeIssue($arguments['issue_number']),
                'add_comment' => app(GitHubService::class)->addComment($arguments['issue_number'], $arguments['body']),
                'create_step' => app(GitHubService::class)->createStep(
                    $arguments['title'],
                    $arguments['user_story'],
                    $arguments['context'],
                    $arguments['criteria'],
                    $arguments['priority'] ?? 'medium'
                ),
                'create_task' => app(GitHubService::class)->createTask(
                    $arguments['title'],
                    $arguments['description'],
                    $arguments['parent_step'],
                    $arguments['priority'] ?? 'medium'
                ),
                'move_task_status' => app(GitHubService::class)->moveTaskToStatus(
                    $arguments['issue_number'],
                    $arguments['status']
                ),
                default => ['error' => 'Tool not implemented'],
            };

            return $this->success($id, [
                'content' => [['type' => 'text', 'text' => json_encode($result, JSON_PRETTY_PRINT)]],
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($id, -32603, $e->getMessage());
        }
    }

    private function success(mixed $id, array $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function errorResponse(mixed $id, int $code, string $message): array
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
        $this->send($this->errorResponse(null, $code, $message));
    }

    private function log(string $message): void
    {
        fwrite(STDERR, "[MCP] {$message}\n");
    }
}
