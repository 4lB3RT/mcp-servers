<?php

declare(strict_types=1);

namespace App\GitHub;

use GuzzleHttp\Client;

class GitHubClient
{
    private string $token;
    private string $owner;
    private string $repo;
    private Client $http;

    private const STEPS_PROJECT_ID = 'PVT_kwHOAV6_6M4BNKam';
    private const TAREAS_PROJECT_ID = 'PVT_kwHOAV6_6M4BNKbM';

    private const TAREAS_STATUS_FIELD_ID = 'PVTSSF_lAHOAV6_6M4BNKbMzg8POxU';
    private const TAREAS_STATUS_OPTIONS = [
        'todo' => '91c009c4',
        'doing' => '545952ab',
        'review' => '4ff2a434',
        'done' => 'fecf69ef',
    ];

    public function __construct()
    {
        $this->token = $_ENV['GITHUB_TOKEN'];
        $this->owner = $_ENV['GITHUB_OWNER'];
        $this->repo = $_ENV['GITHUB_REPO'];
        $this->http = new Client([
            'base_uri' => 'https://api.github.com',
            'timeout' => 15,
            'headers' => [
                'Authorization' => "Bearer {$this->token}",
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
            ],
        ]);
    }

    public function createIssue(string $title, ?string $body = null, array $labels = []): array
    {
        $payload = ['title' => $title];

        if ($body) {
            $payload['body'] = $body;
        }

        if (!empty($labels)) {
            $payload['labels'] = $labels;
        }

        return $this->post("/repos/{$this->owner}/{$this->repo}/issues", $payload);
    }

    public function listIssues(string $state = 'open', ?string $labels = null, int $perPage = 10): array
    {
        $params = [
            'state' => $state,
            'per_page' => $perPage,
        ];

        if ($labels) {
            $params['labels'] = $labels;
        }

        return $this->get("/repos/{$this->owner}/{$this->repo}/issues", $params);
    }

    public function getIssue(int $issueNumber): array
    {
        return $this->get("/repos/{$this->owner}/{$this->repo}/issues/{$issueNumber}");
    }

    public function updateIssue(int $issueNumber, ?string $title = null, ?string $body = null, ?string $state = null, array $labels = []): array
    {
        $payload = [];

        if ($title !== null) {
            $payload['title'] = $title;
        }

        if ($body !== null) {
            $payload['body'] = $body;
        }

        if ($state !== null) {
            $payload['state'] = $state;
        }

        if (!empty($labels)) {
            $payload['labels'] = $labels;
        }

        return $this->patch("/repos/{$this->owner}/{$this->repo}/issues/{$issueNumber}", $payload);
    }

    public function closeIssue(int $issueNumber): array
    {
        return $this->updateIssue($issueNumber, state: 'closed');
    }

    public function addComment(int $issueNumber, string $body): array
    {
        return $this->post("/repos/{$this->owner}/{$this->repo}/issues/{$issueNumber}/comments", ['body' => $body]);
    }

    public function createStep(string $title, string $userStory, string $context, array $criteria, string $priority = 'medium'): array
    {
        $body = "## User Story\n\n{$userStory}\n\n## Contexto\n\n{$context}\n\n## Criterios de aceptación\n\n";
        foreach ($criteria as $criterion) {
            $body .= "- [ ] {$criterion}\n";
        }

        $labels = ['step', 'backlog', "priority:{$priority}"];
        $issue = $this->createIssue($title, $body, $labels);

        if (isset($issue['node_id'])) {
            $this->addToProject($issue['node_id'], self::STEPS_PROJECT_ID);
        }

        return $issue;
    }

    public function createTask(string $title, string $description, int $parentStepNumber, string $priority = 'medium'): array
    {
        $body = "## Descripción\n\n{$description}\n\n## Parent Step\n\nResolves #{$parentStepNumber}";

        $labels = ['task', 'todo', "priority:{$priority}"];
        $issue = $this->createIssue($title, $body, $labels);

        if (isset($issue['node_id'])) {
            $this->addToProject($issue['node_id'], self::TAREAS_PROJECT_ID);
        }

        if (isset($issue['number'])) {
            $this->addTaskToStep($parentStepNumber, $issue['number'], $title);
        }

        return $issue;
    }

    public function moveTaskToStatus(int $issueNumber, string $status): array
    {
        $status = strtolower($status);
        if (!isset(self::TAREAS_STATUS_OPTIONS[$status])) {
            return ['error' => "Invalid status: {$status}. Valid: todo, doing, review, done"];
        }

        $itemId = $this->getProjectItemId($issueNumber);
        if (!$itemId) {
            return ['error' => "Task #{$issueNumber} not found in Tareas board"];
        }

        $optionId = self::TAREAS_STATUS_OPTIONS[$status];

        $mutation = "mutation {
            updateProjectV2ItemFieldValue(input: {
                projectId: \"" . self::TAREAS_PROJECT_ID . "\"
                itemId: \"{$itemId}\"
                fieldId: \"" . self::TAREAS_STATUS_FIELD_ID . "\"
                value: { singleSelectOptionId: \"{$optionId}\" }
            }) {
                projectV2Item { id }
            }
        }";

        $result = $this->graphql($mutation);

        if (isset($result['data']['updateProjectV2ItemFieldValue'])) {
            return ['success' => true, 'status' => $status, 'issue' => $issueNumber];
        }

        return $result;
    }

    private function addTaskToStep(int $stepNumber, int $taskNumber, string $taskTitle): array
    {
        $step = $this->getIssue($stepNumber);

        if (!isset($step['body'])) {
            return ['error' => 'Step not found'];
        }

        $stepBody = $step['body'];
        $taskLine = "- [ ] #{$taskNumber} {$taskTitle}";

        if (str_contains($stepBody, '## Tareas')) {
            $stepBody .= "\n{$taskLine}";
        } else {
            $stepBody .= "\n\n## Tareas\n\n{$taskLine}";
        }

        return $this->updateIssue($stepNumber, body: $stepBody);
    }

    private function addToProject(string $contentId, string $projectId): array
    {
        $mutation = "mutation {
            addProjectV2ItemById(input: {
                projectId: \"{$projectId}\"
                contentId: \"{$contentId}\"
            }) {
                item { id }
            }
        }";

        return $this->graphql($mutation);
    }

    private function getProjectItemId(int $issueNumber): ?string
    {
        $issue = $this->getIssue($issueNumber);
        if (!isset($issue['node_id'])) {
            return null;
        }

        $nodeId = $issue['node_id'];

        $query = "query {
            node(id: \"{$nodeId}\") {
                ... on Issue {
                    projectItems(first: 10) {
                        nodes {
                            id
                            project { id }
                        }
                    }
                }
            }
        }";

        $data = $this->graphql($query);
        $items = $data['data']['node']['projectItems']['nodes'] ?? [];

        foreach ($items as $item) {
            if ($item['project']['id'] === self::TAREAS_PROJECT_ID) {
                return $item['id'];
            }
        }

        return null;
    }

    private function get(string $uri, array $params = []): array
    {
        $response = $this->http->get($uri, ['query' => $params]);

        return json_decode($response->getBody()->getContents(), true) ?? ['error' => 'Empty response'];
    }

    private function post(string $uri, array $payload): array
    {
        $response = $this->http->post($uri, ['json' => $payload]);

        return json_decode($response->getBody()->getContents(), true) ?? ['error' => 'Empty response'];
    }

    private function patch(string $uri, array $payload): array
    {
        $response = $this->http->patch($uri, ['json' => $payload]);

        return json_decode($response->getBody()->getContents(), true) ?? ['error' => 'Empty response'];
    }

    private function graphql(string $query): array
    {
        $response = $this->http->post('https://api.github.com/graphql', [
            'json' => ['query' => $query],
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? ['error' => 'Empty response'];
    }
}
