<?php
// DIC configuration

$container = $app->getContainer();

$container['blog.file_system'] = function ($c) {
    return new MartynBiz\Slim\Modules\Blog\FileSystem();
};

$container['blog.image'] = function ($c) {
    return new MartynBiz\Slim\Modules\Blog\Image();
};

$container['blog.photo_manager'] = function ($c) {
    return new MartynBiz\Slim\Modules\Blog\PhotoManager($c['blog.image'], $c['blog.file_system']);
};

// models
$container['blog.model.article'] = function ($c) {
    return new MartynBiz\Slim\Modules\Blog\Model\Article();
};
$container['blog.model.tag'] = function ($c) {
    return new MartynBiz\Slim\Modules\Blog\Model\Tag();
};
$container['blog.model.photo'] = function ($c) {
    return new MartynBiz\Slim\Modules\Blog\Model\Photo();
};
