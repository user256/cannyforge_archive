<?php
/**
 * PHP Insights config (ticket 201).
 *
 * Scope decision: **Insights does NOT police style.** PHPCS/WordPress Coding
 * Standards already owns style and gates on it, and the two disagree by design
 * (WPCS wants `array()`, tabs, snake_case methods, Yoda conditions; Insights'
 * default preset wants the PSR-12 opposite). Letting both run would be two gates
 * contradicting each other. So we keep Insights for what PHPCS does NOT measure:
 *
 *  1. **Complexity & architecture** scoring (gated on minimum scores).
 *  2. The **god-class budget** (ticket 102): hard ceilings on cyclomatic
 *     complexity and method length, calibrated so the reference's `settings`
 *     (6,411 lines) and `calculator` (1,372 lines) anti-patterns fail loudly.
 *
 * The entire Style metric is dropped (min-style: 0) — PHPCS is the style gate.
 *
 * @see https://phpinsights.com
 */

declare(strict_types=1);


return [
    'preset' => 'default',

    'exclude' => [
        'vendor',
        'tests',
    ],

    'add' => [],

    'remove' => [
        // The whole style category is PHPCS's job — drop the sniffs that fight
        // WPCS so Insights and PHPCS never contradict each other.
        \SlevomatCodingStandard\Sniffs\ControlStructures\DisallowYodaComparisonSniff::class,
        \SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff::class,
        \PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff::class,
        \PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\DisallowMultipleStatementsSniff::class,
        \PHP_CodeSniffer\Standards\Squiz\Sniffs\Functions\MultiLineFunctionDeclarationSniff::class,
        \PHP_CodeSniffer\Standards\PSR2\Sniffs\Methods\MethodDeclarationSniff::class,
        \PHP_CodeSniffer\Standards\PSR1\Sniffs\Methods\CamelCapsMethodNameSniff::class,
        \PHP_CodeSniffer\Standards\PSR1\Sniffs\Files\SideEffectsSniff::class,
        \PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\DisallowLongArraySyntaxSniff::class,
        \PHP_CodeSniffer\Standards\Generic\Sniffs\WhiteSpace\DisallowTabIndentSniff::class,
        \PHP_CodeSniffer\Standards\Squiz\Sniffs\Strings\DoubleQuoteUsageSniff::class,
        \SlevomatCodingStandard\Sniffs\ControlStructures\RequireYodaComparisonSniff::class,
    ],

    // NOTE (2026-06-17): PHP Insights is ADVISORY ONLY here — it is NOT in
    // `composer qa` and does not gate. The god-class budget is enforced by
    // **PHPMD** (`composer mess`, phpmd.xml), which HARD-FAILS per-method on
    // cyclomatic/length/class-size. An earlier setup tried to express the budget
    // via Insights sniffs + an aggregate `min-complexity` score; that was a
    // mistake — those Insights sniffs only *advise* (they never failed the
    // build), and the aggregate score drifts as files are added. PHPMD is the
    // purpose-built, deterministic gate. This file is kept for the occasional
    // `composer insights` architecture/quality readout, nothing more.
    'config'       => [],
    'requirements' => [],
];
