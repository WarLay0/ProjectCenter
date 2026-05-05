<?php

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setUnsupportedPhpVersionAllowed(true)
    ->setIndent('  ')
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'one'],
        'phpdoc_align' => ['align' => 'left'],
        'yoda_style' => false,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'ordered_traits' => false,
    ])
    ->setFinder($finder)
;
