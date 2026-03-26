<?php

declare(strict_types=1);

namespace App\Trello;

use GuzzleHttp\Client;

class TrelloClient
{
    private string $apiKey;
    private string $apiToken;
    private Client $http;

    public function __construct()
    {
        $this->apiKey = $_ENV['TRELLO_API_KEY'];
        $this->apiToken = $_ENV['TRELLO_API_TOKEN'];
        $this->http = new Client([
            'base_uri' => 'https://api.trello.com/1/',
            'timeout' => 15,
        ]);
    }

    // ── Boards ───────────────────────────────────────────────────

    public function listBoards(): array
    {
        return $this->get('members/me/boards', ['fields' => 'name,url,closed']);
    }

    // ── Lists ────────────────────────────────────────────────────

    public function getLists(string $boardId): array
    {
        return $this->get("boards/{$boardId}/lists", ['fields' => 'name,pos,closed']);
    }

    public function createList(string $boardId, string $name, ?string $pos = null): array
    {
        $payload = ['name' => $name, 'idBoard' => $boardId];

        if ($pos !== null) {
            $payload['pos'] = $pos;
        }

        return $this->post('lists', $payload);
    }

    // ── Cards ────────────────────────────────────────────────────

    public function listCards(string $listId): array
    {
        return $this->get("lists/{$listId}/cards", [
            'fields' => 'name,desc,due,labels,pos,closed',
        ]);
    }

    public function listCardsWithActivity(string $listId): array
    {
        return $this->get("lists/{$listId}/cards", [
            'fields' => 'name,dateLastActivity,closed',
        ]);
    }

    public function createCard(
        string $listId,
        string $name,
        ?string $desc = null,
        ?string $due = null,
        ?string $labelIds = null,
    ): array {
        $payload = ['idList' => $listId, 'name' => $name];

        if ($desc !== null) {
            $payload['desc'] = $desc;
        }

        if ($due !== null) {
            $payload['due'] = $due;
        }

        if ($labelIds !== null) {
            $payload['idLabels'] = $labelIds;
        }

        return $this->post('cards', $payload);
    }

    public function getCard(string $cardId): array
    {
        return $this->get("cards/{$cardId}");
    }

    public function updateCard(
        string $cardId,
        ?string $name = null,
        ?string $desc = null,
        ?string $due = null,
        ?bool $closed = null,
        ?string $listId = null,
        ?string $boardId = null,
    ): array {
        $payload = [];

        if ($name !== null) {
            $payload['name'] = $name;
        }

        if ($desc !== null) {
            $payload['desc'] = $desc;
        }

        if ($due !== null) {
            $payload['due'] = $due;
        }

        if ($closed !== null) {
            $payload['closed'] = $closed;
        }

        if ($boardId !== null) {
            $payload['idBoard'] = $boardId;
        }

        if ($listId !== null) {
            $payload['idList'] = $listId;
        }

        return $this->put("cards/{$cardId}", $payload);
    }

    public function moveCard(string $cardId, string $listId, ?string $boardId = null): array
    {
        $payload = ['idList' => $listId];

        if ($boardId !== null) {
            $payload['idBoard'] = $boardId;
        }

        return $this->put("cards/{$cardId}", $payload);
    }

    public function archiveCard(string $cardId): array
    {
        return $this->put("cards/{$cardId}", ['closed' => true]);
    }

    // ── Labels ───────────────────────────────────────────────────

    public function getLabels(string $boardId): array
    {
        return $this->get("boards/{$boardId}/labels");
    }

    public function addLabelToCard(string $cardId, string $labelId): array
    {
        return $this->post("cards/{$cardId}/idLabels", ['value' => $labelId]);
    }

    public function removeLabelFromCard(string $cardId, string $labelId): array
    {
        return $this->delete("cards/{$cardId}/idLabels/{$labelId}");
    }

    // ── Comments ─────────────────────────────────────────────────

    public function addComment(string $cardId, string $text): array
    {
        return $this->post("cards/{$cardId}/actions/comments", ['text' => $text]);
    }

    // ── Checklists ───────────────────────────────────────────────

    public function getChecklist(string $checklistId): array
    {
        return $this->get("checklists/{$checklistId}", ['fields' => 'name,idCard', 'checkItems' => 'all']);
    }

    public function createChecklist(string $cardId, string $name): array
    {
        return $this->post('checklists', ['idCard' => $cardId, 'name' => $name]);
    }

    public function addCheckItem(string $checklistId, string $name): array
    {
        return $this->post("checklists/{$checklistId}/checkItems", ['name' => $name]);
    }

    public function updateCheckItemState(string $cardId, string $checkItemId, string $state): array
    {
        return $this->put("cards/{$cardId}/checkItem/{$checkItemId}", ['state' => $state]);
    }

    // ── HTTP helpers ─────────────────────────────────────────────

    private function authParams(): array
    {
        return ['key' => $this->apiKey, 'token' => $this->apiToken];
    }

    private function get(string $uri, array $params = []): array
    {
        $response = $this->http->get($uri, [
            'query' => array_merge($this->authParams(), $params),
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? ['error' => 'Empty response'];
    }

    private function post(string $uri, array $payload): array
    {
        $response = $this->http->post($uri, [
            'query' => $this->authParams(),
            'json' => $payload,
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? ['error' => 'Empty response'];
    }

    private function put(string $uri, array $payload): array
    {
        $response = $this->http->put($uri, [
            'query' => $this->authParams(),
            'json' => $payload,
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? ['error' => 'Empty response'];
    }

    private function delete(string $uri): array
    {
        $response = $this->http->delete($uri, [
            'query' => $this->authParams(),
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? ['error' => 'Empty response'];
    }
}
