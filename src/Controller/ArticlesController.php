<?php
namespace MartynBiz\Slim\Modules\Blog\Controller;

class ArticlesController extends BaseController
{
    public function index($request, $response, $args)
    {
        $container = $this->getContainer();

        // set query
        $query = [];
        if ($search = $request->getQueryParam('search')) {
            $query = array_merge_recursive($query, [
                '$text' => [
                    '$search' => $search,
                ]
            ]);
        }

        // set params
        $limit = (int) $request->getQueryParam('limit', 10);
        $page = (int) $request->getQueryParam('page', 1);
        $skip = $limit * ($page - 1);
        $options = array_intersect_key(array_merge([
            'limit' => $limit,
            'skip' => $skip,
        ], $request->getQueryParams()), array_flip(['limit', 'skip']));

        $articles = $container->get('blog.model.article')->find($query, $options);

        $this->render('martynbiz-blog/articles/index', compact('articles'));
    }

    public function show($request, $response, $args)
    {
        $container = $container->getContainer();

        list($id) = $args;

        $article = $container->get('blog.model.article')->findOneOrFail([
            'id' => (int) $id,
        ]);

        $otherArticles = $container->get('blog.model.article')->find([
            'id' => [ '$ne' => $article->id ],
        ], [ 'limit' => 5 ]);

        $this->render('martynbiz-blog/articles/show', compact('article', 'otherArticles'));
    }
}
