<?php
return [
    'frontend' => [
        'typo3-forum/forum-ajax-api' => [
            'target' => \Mittwald\Typo3Forum\Middleware\AjaxForumApi::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
    ]
];
