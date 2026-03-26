<?php

declare(strict_types=1);

namespace App\Trello;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;

class TrelloTools
{
    public function __construct(
        private readonly TrelloClient $client,
    ) {}

    // ── Boards ───────────────────────────────────────────────────

    /**
     * List all boards for the authenticated user.
     */
    #[McpTool(name: 'list_boards')]
    public function listBoards(): string
    {
        $result = $this->client->listBoards();

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    // ── Lists ────────────────────────────────────────────────────

    /**
     * Get all lists in a board.
     *
     * @param string $board_id Board ID
     */
    #[McpTool(name: 'get_lists')]
    public function getLists(string $board_id): string
    {
        $result = $this->client->getLists($board_id);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Create a new list in a board.
     *
     * @param string $board_id Board ID
     * @param string $name List name
     * @param string|null $pos Position: top, bottom, or a positive number
     */
    #[McpTool(name: 'create_list')]
    public function createList(
        string $board_id,
        string $name,
        ?string $pos = null,
    ): string {
        $result = $this->client->createList($board_id, $name, $pos);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    // ── Cards ────────────────────────────────────────────────────

    /**
     * Get all cards in a list.
     *
     * @param string $list_id List ID
     */
    #[McpTool(name: 'list_cards')]
    public function listCards(string $list_id): string
    {
        $result = $this->client->listCards($list_id);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Create a new card in a list.
     *
     * @param string $list_id List ID
     * @param string $name Card name
     * @param string|null $desc Card description (markdown supported)
     * @param string|null $due Due date (ISO 8601 format)
     * @param string|null $label_ids Comma-separated label IDs
     */
    #[McpTool(name: 'create_card')]
    public function createCard(
        string $list_id,
        string $name,
        ?string $desc = null,
        ?string $due = null,
        ?string $label_ids = null,
    ): string {
        $result = $this->client->createCard($list_id, $name, $desc, $due, $label_ids);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Get a card by its ID.
     *
     * @param string $card_id Card ID
     */
    #[McpTool(name: 'get_card')]
    public function getCard(string $card_id): string
    {
        $result = $this->client->getCard($card_id);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Update a card's properties.
     *
     * @param string $card_id Card ID
     * @param string|null $name New name
     * @param string|null $desc New description
     * @param string|null $due New due date (ISO 8601)
     * @param bool|null $closed Whether the card is archived
     * @param string|null $list_id Move to this list ID
     * @param string|null $board_id Required when moving to a list in a different board
     */
    #[McpTool(name: 'update_card')]
    public function updateCard(
        string $card_id,
        ?string $name = null,
        ?string $desc = null,
        ?string $due = null,
        ?bool $closed = null,
        ?string $list_id = null,
        ?string $board_id = null,
    ): string {
        $result = $this->client->updateCard($card_id, $name, $desc, $due, $closed, $list_id, $board_id);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Move a card to a different list.
     *
     * @param string $card_id Card ID
     * @param string $list_id Target list ID
     * @param string|null $board_id Required when moving to a list in a different board
     */
    #[McpTool(name: 'move_card')]
    public function moveCard(string $card_id, string $list_id, ?string $board_id = null): string
    {
        $result = $this->client->moveCard($card_id, $list_id, $board_id);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Archive a card (set closed to true).
     *
     * @param string $card_id Card ID
     */
    #[McpTool(name: 'archive_card')]
    public function archiveCard(string $card_id): string
    {
        $result = $this->client->archiveCard($card_id);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Archive cards in a list that have had no activity for more than N days.
     * Returns a summary of archived cards.
     *
     * @param string $list_id List ID to scan
     * @param int $days_inactive Number of days without activity before archiving (default: 7)
     */
    #[McpTool(name: 'archive_stale_cards')]
    public function archiveStaleCards(string $list_id, int $days_inactive = 7): string
    {
        $cards    = $this->client->listCardsWithActivity($list_id);
        $cutoff   = new \DateTimeImmutable("-{$days_inactive} days");
        $archived = [];
        $skipped  = [];

        foreach ($cards as $card) {
            $lastActivity = new \DateTimeImmutable($card['dateLastActivity']);

            if ($lastActivity < $cutoff) {
                $this->client->archiveCard($card['id']);
                $archived[] = ['id' => $card['id'], 'name' => $card['name'], 'last_activity' => $card['dateLastActivity']];
            } else {
                $skipped[] = $card['name'];
            }
        }

        return json_encode([
            'archived_count' => count($archived),
            'skipped_count'  => count($skipped),
            'archived'       => $archived,
        ], JSON_PRETTY_PRINT);
    }

    // ── Labels ───────────────────────────────────────────────────

    /**
     * Get all labels for a board.
     *
     * @param string $board_id Board ID
     */
    #[McpTool(name: 'get_labels')]
    public function getLabels(string $board_id): string
    {
        $result = $this->client->getLabels($board_id);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Add a label to a card.
     *
     * @param string $card_id Card ID
     * @param string $label_id Label ID
     */
    #[McpTool(name: 'add_label_to_card')]
    public function addLabelToCard(string $card_id, string $label_id): string
    {
        $result = $this->client->addLabelToCard($card_id, $label_id);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Remove a label from a card.
     *
     * @param string $card_id Card ID
     * @param string $label_id Label ID
     */
    #[McpTool(name: 'remove_label_from_card')]
    public function removeLabelFromCard(string $card_id, string $label_id): string
    {
        $result = $this->client->removeLabelFromCard($card_id, $label_id);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    // ── Comments ─────────────────────────────────────────────────

    /**
     * Add a comment to a card.
     *
     * @param string $card_id Card ID
     * @param string $text Comment text (markdown supported)
     */
    #[McpTool(name: 'add_comment')]
    public function addComment(string $card_id, string $text): string
    {
        $result = $this->client->addComment($card_id, $text);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    // ── Checklists ───────────────────────────────────────────────

    /**
     * Get a checklist with all its check items.
     *
     * @param string $checklist_id Checklist ID
     */
    #[McpTool(name: 'get_checklist')]
    public function getChecklist(string $checklist_id): string
    {
        $result = $this->client->getChecklist($checklist_id);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Create a checklist on a card.
     *
     * @param string $card_id Card ID
     * @param string $name Checklist name
     */
    #[McpTool(name: 'create_checklist')]
    public function createChecklist(string $card_id, string $name): string
    {
        $result = $this->client->createChecklist($card_id, $name);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Add an item to a checklist.
     *
     * @param string $checklist_id Checklist ID
     * @param string $name Item name
     */
    #[McpTool(name: 'add_check_item')]
    public function addCheckItem(string $checklist_id, string $name): string
    {
        $result = $this->client->addCheckItem($checklist_id, $name);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Update the state of a check item (complete/incomplete).
     *
     * @param string $card_id Card ID that contains the checklist
     * @param string $check_item_id Check item ID
     * @param string $state State: complete or incomplete
     */
    #[McpTool(name: 'update_check_item')]
    public function updateCheckItem(
        string $card_id,
        string $check_item_id,
        #[Schema(enum: ['complete', 'incomplete'])]
        string $state,
    ): string {
        $result = $this->client->updateCheckItemState($card_id, $check_item_id, $state);

        return json_encode($result, JSON_PRETTY_PRINT);
    }
}
