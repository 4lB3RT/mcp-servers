# MCP Servers

Collection of MCP (Model Context Protocol) servers for Claude Code integration.

![PHP](https://img.shields.io/badge/PHP-8.4%2B-blue?style=flat-square)
![Laravel](https://img.shields.io/badge/Laravel-12.x-red?style=flat-square)
![Docker](https://img.shields.io/badge/Docker-ready-blue?style=flat-square)

---

## Available Servers

### Twitter MCP
Post tweets and read your timeline directly from Claude Code.

**Tools:**
- `tweet` - Post a tweet to Twitter/X (supports `reply_to` for threads)
- `get_timeline` - Get your home timeline
- `get_my_tweets` - Get your own tweets

### GitHub MCP
Manage GitHub issues and project boards directly from Claude Code.

**Tools:**
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
php artisan key:generate
```

### 3. Add API credentials

**Twitter** — Get your credentials from [Twitter Developer Portal](https://developer.twitter.com/):

```env
TWITTER_API_KEY=your_api_key
TWITTER_API_SECRET=your_api_secret
TWITTER_ACCESS_TOKEN=your_access_token
TWITTER_ACCESS_TOKEN_SECRET=your_access_token_secret
TWITTER_BEARER_TOKEN=your_bearer_token
```

**GitHub** — Create a [Personal Access Token](https://github.com/settings/tokens) with `repo` and `project` scopes:

```env
GITHUB_TOKEN=your_github_token
GITHUB_OWNER=your_username_or_org
GITHUB_REPO=your_repo_name
```

### 4. Configure Claude Code

Add to your Claude Code MCP settings (`~/.claude/.mcp.json`):

```json
{
  "mcpServers": {
    "twitter": {
      "command": "php",
      "args": ["/path/to/mcp-servers/artisan", "mcp:serve", "--server=twitter"]
    },
    "github": {
      "command": "php",
      "args": ["/path/to/mcp-servers/artisan", "mcp:serve", "--server=github"]
    }
  }
}
```

---

## Docker Quick Start

```bash
# Copy .env and set your environment variables
cp .env.example .env

# Start containers (from the docker folder)
docker compose -f docker/docker-compose.yml up -d

# Run artisan or composer inside the container
./docker/commands/artisan.sh migrate
./docker/commands/composer.sh install
```

---

## Commands

### Run MCP Server
```bash
php artisan mcp:serve --server=twitter
php artisan mcp:serve --server=github
```

### Tweet Your Commits
Post a storytelling tweet about your recent git commits:

```bash
# Tweet about last commit in current directory
php artisan twitter:commit

# Tweet about commits in specific repo
php artisan twitter:commit --path=/path/to/your/repo

# Include multiple commits
php artisan twitter:commit --commits=3

# Preview without posting
php artisan twitter:commit --dry-run
```

---

## Adding New MCP Servers

1. Create a new service in `app/Services/`
2. Register tools in `McpServerCommand.php`
3. Add configuration to `config/services.php`
4. Update `.env.example`

---

## Scripts

- `composer run setup` — Full project bootstrap
- `composer run dev` — Start all dev services concurrently
- `composer run test` — Run tests

---

## License

MIT
