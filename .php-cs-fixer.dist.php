<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/config',
    ])
    ->append([
        __DIR__ . '/run.php',
        __DIR__ . '/cli-config.php',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS' => true,
        '@PHP84Migration' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_whitespace_in_blank_line' => true,
        'array_syntax' => ['syntax' => 'short'],
        'cast_spaces' => ['space' => 'single'],
        'concat_space' => ['spacing' => 'one'],
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced'],
    ])
    ->setFinder($finder);
