<?php
namespace MartynBiz\Slim\Modules\Blog\Controller;

class IndexController extends BaseController
{
    public function index($request, $response, $args)
    {
        $container = $this->getContainer();

        // $cacheId = 'homepage_articles';
        // if (! $articles = $this->get('cache')->get($cacheId)) {
            $articles = $container->get('blog.model.article')->find([
                //..
            ], [ 'limit' => 5 ]);

        //     $this->get('cache')->set($cacheId, $articles, 3600);
        // }

        // $cacheId = 'homepage_carousel_photos';
        // if (! $carouselPhotos = $this->get('cache')->get($cacheId)) {
            $carouselPhotos = $container->get('blog.model.photo')->find([
                //..
            ], [ 'limit' => 5 ]);

        //     $this->get('cache')->set($cacheId, $carouselPhotos, 3600);
        // }


        $this->render('martynbiz-blog/index/index', compact('articles', 'carouselPhotos'));
    }

    public function portfolio($request, $response, $args)
    {
        return $this->render('martynbiz-blog/index/portfolio');
    }

    public function contact($request, $response, $args)
    {
        return $this->render('martynbiz-blog/index/contact');
    }
}
