<?php

return [
    'frontend' => [
        'jweiland/checkmysite/index-checker' => [
            'target' => \JWeiland\Checkmysite\Middleware\IndexPhpCheckerMiddleware::class,
            'before' => [
                'typo3/cms-frontend/site',
            ],
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ],
        ],
    ],
];
