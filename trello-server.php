#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->load();

use App\Trello\TrelloClient;
use Mcp\Capability\Registry\Container;
use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;

$container = new Container();
$container->set(TrelloClient::class, new TrelloClient());

$server = Server::builder()
    ->setServerInfo('trello-server', '1.0.0')
    ->setContainer($container)
    ->setDiscovery(__DIR__, ['src/Trello'])
    ->build();

exit($server->run(new StdioTransport()));
