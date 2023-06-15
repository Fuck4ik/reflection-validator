<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('vendor')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2' => true,
        '@PhpCsFixer' => true,
        'no_superfluous_phpdoc_tags' => true,
        'concat_space' => ['spacing' => 'one'],
        'cast_spaces' => ['space' => 'none'],
        'array_syntax' => ['syntax' => 'short'],
        'protected_to_private' => false,
        'native_function_invocation' => false,
        'native_constant_invocation' => false,
        'phpdoc_summary' => false,
        'phpdoc_to_comment' => false,
        'function_declaration' => ['closure_function_spacing' => 'none', 'closure_fn_spacing' => 'none'],
        'ordered_imports' => ['imports_order' => ['const', 'class', 'function']],
        'blank_line_between_import_groups' => false,
    ])
    ->setFinder($finder)
;
