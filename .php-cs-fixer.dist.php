<?php

declare(strict_types=1);

/*
 * This file is part of the CookieConsentBundle symfony bundle.
 * Created by Radoslav Georgiev.
 */

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['vendor', 'node_modules', 'var'])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony'               => true,
        'declare_strict_types'   => true,
        'ordered_imports'        => true,
        'psr_autoloading'        => false,
        'yoda_style'             => false,
        'phpdoc_order'           => true,
        'array_syntax'           => [
            'syntax' => 'short',
        ],
        'binary_operator_spaces' => [
            'operators' => [
                '='  => 'align',
                '=>' => 'align',
            ],
        ],
        'header_comment'         => [
            'header' => <<<EOH
This file is part of the CookieConsentBundle Symfony bundle.
Created by Radoslav Georgiev.
EOH,
        ],
    ])
    ->setFinder($finder);
