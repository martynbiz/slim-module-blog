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
                'articles' => 'MartynBiz\\Blog\\Model\\Article',
                'photos' => 'MartynBiz\\Blog\\Model\\Photo',
                'tags' => 'MartynBiz\\Blog\\Model\\Tag',
            ],
        ],
    ],
];
