# MCP Servers

Independent MCP (Model Context Protocol) servers for Claude Code integration — one for GitHub, one for X.

![PHP](https://img.shields.io/badge/PHP-8.4%2B-blue?style=flat-square)
![MCP SDK](https://img.shields.io/badge/mcp%2Fsdk-0.4-green?style=flat-square)

---

## Available Tools

### X
Post and read your timeline directly from Claude Code.

- `tweet` — Post to X (supports `reply_to` for threads)
- `get_timeline` — Get your home timeline
- `get_my_tweets` — Get your own posts

### GitHub
Manage GitHub issues and project boards directly from Claude Code.

- `create_issue` — Create a new GitHub issue with optional labels
- `list_issues` — List issues (filter by state, labels)
- `get_issue` — Get a specific issue by number
- `update_issue` — Update title, body, state, or labels
- `close_issue` — Close an issue
- `add_comment` — Add a comment to an issue
- `create_step` — Create a Step (user story) with acceptance criteria and add it to the Steps board
- `create_task` — Create a Task linked to a parent Step and add it to the Tareas board
- `move_task_status` — Move a task in the Tareas board (`todo`, `doing`, `review`, `done`)

### Trello
Manage Trello boards, lists, cards, labels, and checklists directly from Claude Code.

**Boards:** `list_boards`

**Lists:** `get_lists`, `create_list`

**Cards:** `list_cards`, `create_card`, `get_card`, `update_card`, `move_card`, `archive_card`, `archive_stale_cards`

**Labels:** `get_labels`, `add_label_to_card`, `remove_label_from_card`

**Comments:** `add_comment`

**Checklists:** `get_checklist`, `create_checklist`, `add_check_item`, `update_check_item`

---

## Setup

### 1. Install dependencies

```bash
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
```

### 3. Add API credentials

**X** — Get your credentials from [X Developer Portal](https://developer.x.com/):

```env
TWITTER_API_KEY=your_api_key
TWITTER_API_SECRET=your_api_secret
TWITTER_ACCESS_TOKEN=your_access_token
TWITTER_ACCESS_TOKEN_SECRET=your_access_token_secret
```

**GitHub** — Create a [Personal Access Token](https://github.com/settings/tokens) with `repo` and `project` scopes:

```env
GITHUB_TOKEN=your_github_token
GITHUB_OWNER=your_username_or_org
GITHUB_REPO=your_repo_name
```

**Trello** — Get your credentials from the [Trello Power-Ups Admin](https://trello.com/power-ups/admin):

```env
TRELLO_API_KEY=your_api_key
TRELLO_API_TOKEN=your_api_token
```

### 4. Configure Claude Code

Add to `~/.claude.json`:

```json
{
  "mcpServers": {
    "github-server": {
      "command": "php",
      "args": ["/path/to/mcp-servers/github-server.php"]
    },
    "x-server": {
      "command": "php",
      "args": ["/path/to/mcp-servers/x-server.php"]
    },
    "trello-server": {
      "command": "php",
      "args": ["/path/to/mcp-servers/trello-server.php"]
    }
  }
}
```

---

## Architecture

```
github-server.php       ← GitHub entry point (dotenv + DI + MCP server)
x-server.php            ← X entry point (dotenv + DI + MCP server)
trello-server.php       ← Trello entry point (dotenv + DI + MCP server)
src/
├── X/
│   ├── XClient.php     ← OAuth 1.0a HTTP client for X API v2
│   └── XTools.php      ← 3 tools with #[McpTool] attributes
├── GitHub/
│   ├── GitHubClient.php ← REST + GraphQL HTTP client
│   └── GitHubTools.php  ← 9 tools with #[McpTool] attributes
└── Trello/
    ├── TrelloClient.php ← REST HTTP client for Trello API
    └── TrelloTools.php  ← 21 tools with #[McpTool] attributes
```

Tools are auto-discovered via `#[McpTool]` attributes — no manual registration needed.

---

## License

MIT
