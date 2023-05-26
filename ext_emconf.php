<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'checkmysite',
    'description' => 'Check index.php for suspicious modifications',
    'category' => 'misc',
    'author' => 'Stefan Froemken',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'state' => 'stable',
    'version' => '4.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.23-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
