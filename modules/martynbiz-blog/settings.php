<?php
return [
    'settings' => [

        // Renderer settings
        'renderer' => [
            'folders' => [
                APPLICATION_PATH . '/modules/martynbiz-blog/templates',
            ],
        ],

        'photos_dir' => [
            'original' => APPLICATION_PATH . '/data/photos',
            'cache' => APPLICATION_PATH . '/data/photos/cache',
            'public' => '/photos',
        ],

        'mongo' => [
            'classmap' => [
                'articles' => 'MartynBiz\\Slim\\Module\\Blog\\Model\\Article',
                'photos' => 'MartynBiz\\Slim\\Module\\Blog\\Model\\Photo',
                'tags' => 'MartynBiz\\Slim\\Module\\Blog\\Model\\Tag',
            ],
        ],
    ],
];
