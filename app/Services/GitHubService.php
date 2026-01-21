<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GitHubService
{
    private string $token;
    private string $owner;
    private string $repo;
    private string $baseUrl = 'https://api.github.com';

    private const STEPS_PROJECT_ID = 'PVT_kwHOAV6_6M4BNKam';
    private const TAREAS_PROJECT_ID = 'PVT_kwHOAV6_6M4BNKbM';

    public function __construct()
    {
        $this->token = config('services.github.token');
        $this->owner = config('services.github.owner');
        $this->repo = config('services.github.repo');
    }

    public function createIssue(string $title, ?string $body = null, array $labels = []): array
    {
        $url = "{$this->baseUrl}/repos/{$this->owner}/{$this->repo}/issues";

        $payload = ['title' => $title];

        if ($body) {
            $payload['body'] = $body;
        }

        if (!empty($labels)) {
            $payload['labels'] = $labels;
        }

        $response = Http::withHeaders($this->getHeaders())->post($url, $payload);

        return $response->json() ?? ['error' => 'Empty response'];
    }

    public function listIssues(string $state = 'open', ?string $labels = null, int $perPage = 10): array
    {
        $url = "{$this->baseUrl}/repos/{$this->owner}/{$this->repo}/issues";

        $params = [
            'state' => $state,
            'per_page' => $perPage,
        ];

        if ($labels) {
            $params['labels'] = $labels;
        }

        $response = Http::withHeaders($this->getHeaders())->get($url, $params);

        return $response->json() ?? ['error' => 'Empty response'];
    }

    public function getIssue(int $issueNumber): array
    {
        $url = "{$this->baseUrl}/repos/{$this->owner}/{$this->repo}/issues/{$issueNumber}";

        $response = Http::withHeaders($this->getHeaders())->get($url);

        return $response->json() ?? ['error' => 'Empty response'];
    }

    public function updateIssue(int $issueNumber, ?string $title = null, ?string $body = null, ?string $state = null, array $labels = []): array
    {
        $url = "{$this->baseUrl}/repos/{$this->owner}/{$this->repo}/issues/{$issueNumber}";

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

        $response = Http::withHeaders($this->getHeaders())->patch($url, $payload);

        return $response->json() ?? ['error' => 'Empty response'];
    }

    public function closeIssue(int $issueNumber): array
    {
        return $this->updateIssue($issueNumber, state: 'closed');
    }

    public function addComment(int $issueNumber, string $body): array
    {
        $url = "{$this->baseUrl}/repos/{$this->owner}/{$this->repo}/issues/{$issueNumber}/comments";

        $response = Http::withHeaders($this->getHeaders())->post($url, ['body' => $body]);

        return $response->json() ?? ['error' => 'Empty response'];
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

        // Actualizar el Step padre para incluir esta tarea
        if (isset($issue['number'])) {
            $this->addTaskToStep($parentStepNumber, $issue['number'], $title);
        }

        return $issue;
    }

    public function addTaskToStep(int $stepNumber, int $taskNumber, string $taskTitle): array
    {
        $step = $this->getIssue($stepNumber);

        if (!isset($step['body'])) {
            return ['error' => 'Step not found'];
        }

        $stepBody = $step['body'];
        $taskLine = "- [ ] #{$taskNumber} {$taskTitle}";

        // Si ya tiene sección de Tareas, añadir al final
        if (str_contains($stepBody, '## Tareas')) {
            $stepBody .= "\n{$taskLine}";
        } else {
            // Si no tiene sección de Tareas, crearla
            $stepBody .= "\n\n## Tareas\n\n{$taskLine}";
        }

        return $this->updateIssue($stepNumber, body: $stepBody);
    }

    public function addToProject(string $contentId, string $projectId): array
    {
        $mutation = "mutation {
            addProjectV2ItemById(input: {
                projectId: \"{$projectId}\"
                contentId: \"{$contentId}\"
            }) {
                item { id }
            }
        }";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->post('https://api.github.com/graphql', ['query' => $mutation]);

        return $response->json() ?? ['error' => 'Empty response'];
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }
}
