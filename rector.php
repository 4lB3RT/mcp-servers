<?php

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withRules([
        TypedPropertyFromStrictConstructorRector::class,
        StrContainsRector::class,
        AddReturnTypeDeclarationRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true
    );
