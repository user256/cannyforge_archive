<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/cannyforge-archive.php',
        __DIR__ . '/uninstall.php',
    ])
    // Scoped to dead-code detection only (ticket 101): unused methods/properties,
    // dead assignments, redundant conditions. We deliberately do NOT enable the
    // PHP-modernization sets here — their style rewrites (short-array,
    // constructor promotion) conflict with the WPCS `array()` gate. Style is
    // PHPCS's job; Rector's job is finding dead code.
    ->withSets([
        SetList::DEAD_CODE,
    ])
    // WordPress-Docs (PHPCS gate) REQUIRES @return / @var tags that Rector's
    // dead-code set considers redundant against native types. PHPCS owns
    // docblock policy for a wp.org plugin, so we exclude these two rules rather
    // than let the gates contradict each other.
    ->withSkip([
        RemoveUselessReturnTagRector::class,
        RemoveUselessVarTagRector::class,
    ]);
