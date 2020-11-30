<?php
return PhpCsFixer\Config::create()
    ->setRules([
            '@PSR1' => true,
            '@PSR2' => true,
                    'indentation_type' => true,
        // for indentation to work: 1 of 2. Works!

        'array_indentation' => true,
        'array_syntax' => array('syntax' => 'short'),
        'combine_consecutive_unsets' => true,
        'method_separation' => true,
        'no_multiline_whitespace_before_semicolons' => true,
		
        'single_quote' => true,
		    // converts double quotes to single quotes. Works!

        'binary_operator_spaces' => array(
            'align_double_arrow' => false,
            'align_equals' => false,
        ),
		
        'blank_line_after_opening_tag' => true,
        // gives blank line after php tag opening tag. 
        // NOT working for me yet. bug?
		
        'blank_line_before_return' => true,
        'braces' => array(
            'allow_single_line_closure' => true,
        ),
        // 'cast_spaces' => true,
        // 'class_definition' => array('singleLine' => true),
        'concat_space' => array('spacing' => 'one'),
        'declare_equal_normalize' => true,
        'function_typehint_space' => true,
		
        'hash_to_slash_comment' => true,
		    //converts python style # to php style // in comments. Works!
		
        'include' => true,
        'lowercase_cast' => true,
        // 'native_function_casing' => true,
        // 'new_with_braces' => true,
        'no_blank_lines_after_class_opening' => true,
        // 'no_blank_lines_after_phpdoc' => true,

        'no_closing_tag' => true, 
        // php's closing tag omitted from files containing only PHP. Works!
        
        'no_empty_comment' => true,
        //empty comments will be removed. Works!

        // 'no_empty_phpdoc' => true,
        // 'no_empty_statement' => true,
        'no_extra_consecutive_blank_lines' => array(
            'curly_brace_block',
            'extra',
            'parenthesis_brace_block',
            'square_brace_block',
            'throw',
            'use',
        ),
        // 'no_leading_import_slash' => true,
        // 'no_leading_namespace_whitespace' => true,
        // 'no_mixed_echo_print' => array('use' => 'echo'),
        'no_multiline_whitespace_around_double_arrow' => true,
        // 'no_short_bool_cast' => true,
		
        'no_singleline_whitespace_before_semicolons' => true,
		    //removes whitespaces before semi-clons. Works!
		
        'no_spaces_around_offset' => true,
        // 'no_trailing_comma_in_list_call' => true,
        // 'no_trailing_comma_in_singleline_array' => true,
        // 'no_unneeded_control_parentheses' => true,
        // 'no_unused_imports' => true,
        'no_whitespace_before_comma_in_array' => true,
		
        'no_whitespace_in_blank_line' => true,
		    //removes whitespaces in blank lines. Works!
		
        'no_trailing_whitespace_in_comment' => true,
        //removes whitespaces at end of comments. Works!
		
        // 'normalize_index_brace' => true,
        'object_operator_without_whitespace' => true,
        // 'php_unit_fqcn_annotation' => true,
        // 'phpdoc_align' => true,
        // 'phpdoc_annotation_without_dot' => true,
        // 'phpdoc_indent' => true,
        // 'phpdoc_inline_tag' => true,
        // 'phpdoc_no_access' => true,
        // 'phpdoc_no_alias_tag' => true,
        // 'phpdoc_no_empty_return' => true,
        // 'phpdoc_no_package' => true,
        // 'phpdoc_no_useless_inheritdoc' => true,
        // 'phpdoc_return_self_reference' => true,
        // 'phpdoc_scalar' => true,
        // 'phpdoc_separation' => true,
        // 'phpdoc_single_line_var_spacing' => true,
        // 'phpdoc_summary' => true,
        // 'phpdoc_to_comment' => true,
        // 'phpdoc_trim' => true,
        // 'phpdoc_types' => true,
        // 'phpdoc_var_without_name' => true,
        // 'pre_increment' => true,
        // 'return_type_declaration' => true,
        // 'self_accessor' => true,
        // 'short_scalar_cast' => true,
        'single_blank_line_before_namespace' => true,
        // 'single_class_element_per_statement' => true,
		
        'space_after_semicolon' => true,
		    // Fix whitespace after a semicolon. Works!
		
        // 'standardize_not_equals' => true,
        'ternary_operator_spaces' => true,
        // 'trailing_comma_in_multiline_array' => true,
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,
		
        'class_attributes_separation' => true,
        // Methods gets separated with one blank line. Not tested.
        'array_syntax' => ['syntax' => 'short'],
    ])
;