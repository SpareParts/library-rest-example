<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/config',
    ])
    ->name('*.php');

$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'declare_strict_types' => true,
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'no_extra_blank_lines' => true,
        'blank_line_after_opening_tag' => true,
        'blank_line_after_namespace' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);
