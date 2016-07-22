<?php
return [
    'settings' => [

        'renderer' => [
            'folders' => [
                APPLICATION_PATH . '/src/martynbiz-blog/templates',
            ],
        ],

        'photos_dir' => [
            'original' => APPLICATION_PATH . '/data/photos',
            'cache' => APPLICATION_PATH . '/data/photos/cache',
            'public' => '/photos',
        ],

        'mongo' => [
            'classmap' => [
                'articles' => 'MartynBiz\\Slim\\Modules\\Blog\\Model\\Article',
                'photos' => 'MartynBiz\\Slim\\Modules\\Blog\\Model\\Photo',
                'tags' => 'MartynBiz\\Slim\\Modules\\Blog\\Model\\Tag',
            ],
        ],
    ],
];
