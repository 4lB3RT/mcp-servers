<?php

declare(strict_types=1);

namespace App\GitHub;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;

class GitHubTools
{
    public function __construct(
        private readonly GitHubClient $client,
    ) {}

    /**
     * Create a new GitHub issue (task/story).
     *
     * @param string $title Issue title
     * @param string|null $body Issue body/description (markdown supported)
     * @param string[] $labels Labels to add (e.g., ["bug", "enhancement"])
     */
    #[McpTool(name: 'create_issue')]
    public function createIssue(
        string $title,
        ?string $body = null,
        array $labels = [],
    ): string {
        $result = $this->client->createIssue($title, $body, $labels);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * List GitHub issues.
     *
     * @param string $state Filter by state: open, closed, all (default: open)
     * @param string|null $labels Filter by labels (comma-separated)
     * @param int $per_page Results per page (default: 10)
     */
    #[McpTool(name: 'list_issues')]
    public function listIssues(
        #[Schema(enum: ['open', 'closed', 'all'])]
        string $state = 'open',
        ?string $labels = null,
        #[Schema(minimum: 1, maximum: 100)]
        int $per_page = 10,
    ): string {
        $result = $this->client->listIssues($state, $labels, $per_page);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Get a specific GitHub issue by number.
     *
     * @param int $issue_number Issue number
     */
    #[McpTool(name: 'get_issue')]
    public function getIssue(int $issue_number): string
    {
        $result = $this->client->getIssue($issue_number);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Update a GitHub issue.
     *
     * @param int $issue_number Issue number
     * @param string|null $title New title
     * @param string|null $body New body
     * @param string|null $state State: open or closed
     * @param string[] $labels Labels to set
     */
    #[McpTool(name: 'update_issue')]
    public function updateIssue(
        int $issue_number,
        ?string $title = null,
        ?string $body = null,
        #[Schema(enum: ['open', 'closed'])]
        ?string $state = null,
        array $labels = [],
    ): string {
        $result = $this->client->updateIssue($issue_number, $title, $body, $state, $labels);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Close a GitHub issue.
     *
     * @param int $issue_number Issue number to close
     */
    #[McpTool(name: 'close_issue')]
    public function closeIssue(int $issue_number): string
    {
        $result = $this->client->closeIssue($issue_number);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Add a comment to a GitHub issue.
     *
     * @param int $issue_number Issue number
     * @param string $body Comment body (markdown supported)
     */
    #[McpTool(name: 'add_comment')]
    public function addComment(int $issue_number, string $body): string
    {
        $result = $this->client->addComment($issue_number, $body);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Create a Step (user story) and add it to the Steps board.
     *
     * @param string $title Step title
     * @param string $user_story User story: Como X, quiero Y, para Z
     * @param string $context Context explaining why this is needed
     * @param string[] $criteria Acceptance criteria (Dado/Cuando/Entonces)
     * @param string $priority Priority: high, medium, low (default: medium)
     */
    #[McpTool(name: 'create_step')]
    public function createStep(
        string $title,
        string $user_story,
        string $context,
        array $criteria,
        #[Schema(enum: ['high', 'medium', 'low'])]
        string $priority = 'medium',
    ): string {
        $result = $this->client->createStep($title, $user_story, $context, $criteria, $priority);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Create a Task linked to a parent Step and add it to the Tareas board.
     *
     * @param string $title Task title
     * @param string $description Technical description of the task
     * @param int $parent_step Parent Step issue number
     * @param string $priority Priority: high, medium, low (default: medium)
     */
    #[McpTool(name: 'create_task')]
    public function createTask(
        string $title,
        string $description,
        int $parent_step,
        #[Schema(enum: ['high', 'medium', 'low'])]
        string $priority = 'medium',
    ): string {
        $result = $this->client->createTask($title, $description, $parent_step, $priority);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Move a task to a different status column in the Tareas board.
     *
     * @param int $issue_number Task issue number
     * @param string $status New status: todo, doing, review, done
     */
    #[McpTool(name: 'move_task_status')]
    public function moveTaskStatus(
        int $issue_number,
        #[Schema(enum: ['todo', 'doing', 'review', 'done'])]
        string $status,
    ): string {
        $result = $this->client->moveTaskToStatus($issue_number, $status);

        return json_encode($result, JSON_PRETTY_PRINT);
    }
}
