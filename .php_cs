<?php
return PhpCsFixer\Config::create()
    ->setRules([
            '@DoctrineAnnotation' => true,
            '@PSR1' => true,
            '@PSR2' => true,
            '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
;