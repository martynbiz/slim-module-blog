<?php
// Routes

use MartynBiz\Slim\Modules\Auth\Middleware;
use MartynBiz\Slim\Modules\Auth\Model\User;

$container = $app->getContainer();

$app->get('/', '\MartynBiz\Slim\Modules\Blog\Controller\IndexController:index')->setName('home');

// TODO move these to another module
$app->get('/portfolio', '\MartynBiz\Slim\Modules\Blog\Controller\ArticlesController:portfolio')->setName('portfolio');
$app->get('/contact', '\MartynBiz\Slim\Modules\Blog\Controller\ArticlesController:contact')->setName('contact');

$app->group('/articles', function () {
    $this->get('', '\MartynBiz\Slim\Modules\Blog\Controller\ArticlesController:index')->setName('articles');
    $this->get('/{id:[0-9]+}', '\MartynBiz\Slim\Modules\Blog\Controller\ArticlesController:show')->setName('articles_show');
    $this->get('/{id:[0-9]+}/{slug}', '\MartynBiz\Slim\Modules\Blog\Controller\ArticlesController:show')->setName('articles_show_wslug');
});

$app->group('/photos', function () {
    $this->get('/{path:[0-9]+\/[0-9]+\/[0-9]+\/.+}.jpg', '\MartynBiz\Slim\Modules\Blog\Controller\PhotosController:cached')->setName('photos_cached');
});

$app->group('/admin', function () {

    $this->group('/articles', function () {

        $this->get('', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\ArticlesController:index')->setName('admin_articles');
        $this->get('/{id:[0-9]+}', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\ArticlesController:show')->setName('admin_articles_show');
        $this->get('/{id:[0-9]+}/edit', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\ArticlesController:edit')->setName('admin_articles_edit');
        $this->post('', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\ArticlesController:post')->setName('admin_articles_post');
        $this->delete('/{id:[0-9]+}', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\ArticlesController:delete')->setName('admin_articles_delete');
        $this->put('/{id:[0-9]+}', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\ArticlesController:update')->setName('admin_articles_update');

        $this->post('/upload', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\FilesController:upload')->setName('admin_articles_upload');
    });

    // admin/tags/* routes
    $this->group('/tags', function () {
        $this->get('', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\TagsController:index')->setName('admin_tags');
        // $this->get('/{id:[0-9]+}', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\TagsController:show')->setName('admin_tags_show');
        $this->get('/create', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\TagsController:create')->setName('admin_tags_create');
        $this->get('/{id:[0-9]+}/edit', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\TagsController:edit')->setName('admin_tags_edit');
        $this->post('', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\TagsController:post')->setName('admin_tags_post');
        $this->put('/{id:[0-9]+}', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\TagsController:update')->setName('admin_tags_update');
        $this->delete('/{id:[0-9]+}', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\TagsController:delete')->setName('admin_tags_delete');

    })->add( new Middleware\RoleAccess($this->getContainer(), [ User::ROLE_ADMIN ]) );

    // admin/articles routes
    $this->group('/data', function () {

        $this->map(['GET', 'POST'], '/import', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\DataController:import')->setName('admin_data_import');

    })->add( new Middleware\RoleAccess($this->getContainer(), [ User::ROLE_ADMIN ]) );

})->add( new Middleware\Auth( $container['auth'] ) ); // user must be authenticated
