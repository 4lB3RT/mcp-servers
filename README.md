# MCP Servers

Unified MCP (Model Context Protocol) server for Claude Code integration.

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

### 4. Configure Claude Code

Add to `~/.claude.json`:

```json
{
  "mcpServers": {
    "social": {
      "command": "php",
      "args": ["/path/to/mcp-servers/server.php"]
    }
  }
}
```

---

## Architecture

```
server.php          ← Entry point (dotenv + DI container + MCP server)
src/
├── X/
│   ├── XClient.php     ← OAuth 1.0a HTTP client for X API v2
│   └── XTools.php      ← 3 tools with #[McpTool] attributes
└── GitHub/
    ├── GitHubClient.php ← REST + GraphQL HTTP client
    └── GitHubTools.php  ← 9 tools with #[McpTool] attributes
```

Tools are auto-discovered via `#[McpTool]` attributes — no manual registration needed.

---

## License

MIT
