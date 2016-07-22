<?php
namespace MartynBiz\Slim\Modules\Blog;

use Slim\App;
use Slim\Container;
use Slim\Http\Headers;
use MartynBiz\Mongo\Connection;
use MartynBiz\Slim\Modules\Core\Http\Request;
use MartynBiz\Slim\Modules\Core\Http\Response;
use MartynBiz\Slim\Module\ModuleInterface;

class Module implements ModuleInterface
{
    /**
     * Get config array for this module
     * @return array
     */
    public function initDependencies(Container $container)
    {
        $container['blog.file_system'] = function ($c) {
            return new \MartynBiz\Slim\Modules\Blog\FileSystem();
        };

        $container['blog.image'] = function ($c) {
            return new \MartynBiz\Slim\Modules\Blog\Image();
        };

        $container['blog.photo_manager'] = function ($c) {
            return new \MartynBiz\Slim\Modules\Blog\PhotoManager($c['blog.image'], $c['blog.file_system']);
        };

        // models
        $container['blog.model.article'] = function ($c) {
            return new \MartynBiz\Slim\Modules\Blog\Model\Article();
        };
        $container['blog.model.tag'] = function ($c) {
            return new \MartynBiz\Slim\Modules\Blog\Model\Tag();
        };
        $container['blog.model.photo'] = function ($c) {
            return new \MartynBiz\Slim\Modules\Blog\Model\Photo();
        };
    }
    
    /**
     * Initiate app middleware (route middleware should go in initRoutes)
     * @param App $app
     * @return void
     */
    public function initMiddleware(App $app)
    {
    
    }

    /**
     * Load is run last, when config, dependencies, etc have been initiated
     * Routes ought to go here
     * @param App $app
     * @return void
     */
    public function initRoutes(App $app)
    {
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

            })->add( new \MartynBiz\Slim\Modules\Auth\Middleware\RoleAccess($this->getContainer(), [ \MartynBiz\Slim\Modules\Auth\Model\User::ROLE_ADMIN ]) );

            // admin/articles routes
            $this->group('/data', function () {

                $this->map(['GET', 'POST'], '/import', '\MartynBiz\Slim\Modules\Blog\Controller\Admin\DataController:import')->setName('admin_data_import');

            })->add( new \MartynBiz\Slim\Modules\Auth\Middleware\RoleAccess($this->getContainer(), [ \MartynBiz\Slim\Modules\Auth\Model\User::ROLE_ADMIN ]) );

        })->add( new \MartynBiz\Slim\Modules\Auth\Middleware\Auth( $container['auth'] ) ); // user must be authenticated
    }
}
