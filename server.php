#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->load();

use App\GitHub\GitHubClient;
use App\X\XClient;
use Mcp\Capability\Registry\Container;
use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;

$container = new Container();
$container->set(XClient::class, new XClient());
$container->set(GitHubClient::class, new GitHubClient());

$server = Server::builder()
    ->setServerInfo('mcp-servers', '2.0.0')
    ->setContainer($container)
    ->setDiscovery(__DIR__, ['src'])
    ->build();

exit($server->run(new StdioTransport()));
