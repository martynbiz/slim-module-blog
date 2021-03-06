<?php
namespace MartynBiz\Slim\Module\Blog\Controller\Admin;

use MartynBiz\Mongo\Mongo;
use MartynBiz\Slim\Module\Blog\Model\Article;
use MartynBiz\Slim\Module\Blog\Model\Photo;
use MartynBiz\Slim\Module\Blog\Exception\PermissionDenied as PermissionDeniedException;
use MartynBiz\Slim\Module\Core\Traits;
use MartynBiz\Slim\Module\Blog\Controller\BaseController;

class ArticlesController extends BaseController
{
    use Traits\Pagination;

    public function index($request, $response, $args)
    {
        $container = $this->getContainer();
        $currentUser = $container->get('auth')->getCurrentUser();

        // get tags
        $options = $this->getQueryOptions();
        $articles = $container->get('blog.model.article')->findArticlesManagedBy($currentUser, [], $options);

        // set page info for pagination links
        $total = count($container->get('blog.model.article')->findArticlesManagedBy($currentUser, []));
        $path = $container->get('router')->pathFor('admin_articles');
        $pageInfo = $this->getPageInfo($total, $path, $options);

        return $this->render('martynbiz-blog::admin/articles/index', compact('articles', 'pageInfo'));
    }

    /**
     * This view will serve as a review page where the author can re-open for
     * further editing, or an admin/editor can approve to go live.
     */
    public function show($request, $response, $args)
    {
        $container = $this->getContainer();
        $currentUser = $container->get('auth')->getCurrentUser();
        $article = $container->get('blog.model.article')->findOneOrFail(array(
            'id' => (int) $args['id'],
        ));

        // ensure that this user can edit this article
        if (! $article->isViewableBy($currentUser)) {
            throw new PermissionDeniedException('Permission denied to view this article.');
        }

        return $this->render('martynbiz-blog::admin/articles/show', compact('article', 'tags'));
    }

    /**
     * This works where by a user will select to create an article, and instantly
     * a new draft article is created. This way we can auto save the article before
     * they submit it. or they can keep it as a draft. Upon immediate creation, it
     * should redirect to the edit article form (although it will intially be empty)
     */
    public function post($request, $response, $args)
    {
        $container = $this->getContainer();
        $currentUser = $container->get('auth')->getCurrentUser();
        $article = $container->get('blog.model.article')->factory();

        // for security reasons, some properties are not on the whitelist but
        // we can directly assign
        $article->status = Article::STATUS_DRAFT;
        $article->type = Article::TYPE_ARTICLE;
        $article->author = $currentUser->getDBRef();

        // if the article saves ok, redirect them to the edit page where they can
        // begin to edit their draft. any errors, forward them back to the index
        if ( $article->save() ) {
            return $response->withRedirect('/admin/articles/' . $article->id . '/edit');
        } else {
            $container->get('flash')->addMessage('errors', $article->getErrors());
            return $this->forward('index');
        }
    }

    /**
     * Upon creation too, the user will be redirect here to edit the article
     */
    public function edit($request, $response, $args)
    {
        $container = $this->getContainer();
        $currentUser = $container->get('auth')->getCurrentUser();
        $id = (int) $args['id'];

        $article = $container->get('blog.model.article')->findOneOrFail(array(
            'id' => $id,
        ));

        // ensure that this user can edit this article
        if (! $article->isEditableBy($currentUser) ) {
            throw new PermissionDeniedException('Permission denied to edit this article.');
        }

        // get tags from cache
        // $cacheId = 'tags';
        // if (! $tags = $container->get('cache')->get($cacheId)) {
            $tags = $container->get('blog.model.tag')->find();
            // $container->get('cache')->set($cacheId, $tags, 1);
        // }

        // we will set the current id in session so that uploaded images know which
        // article to attach to
        $container->get('session')->set('current_article_id', $id);

        return $this->render('martynbiz-blog::admin/articles/edit', compact('article', 'tags'));
    }

    /**
     * This method will update the article (save draft) and 1) if xhr, return json,
     * or 2) redirect back to the edit page (upon which they can then submit when they
     * choose to)
     */
    public function update($request, $response, $args)
    {
        $container = $this->getContainer();
        $currentUser = $container->get('auth')->getCurrentUser();
        $params = $request->getParams();
        $id = (int) $args['id'];

        $article = $container->get('blog.model.article')->findOneOrFail(array(
            'id' => $id,
        ));

        // ensure that this user can edit this article
        if (! $article->isEditableBy($currentUser) ) {
            throw new PermissionDeniedException('Permission denied to edit this article.');
        }

        // tags
        $params['tags'] = $this->getTagsFromTagIds(@$params['tags']);

        // // photos
        // $this->attachPhotosTo($article, @$_FILES['photos']);

        // status
        switch((int) @$params['status']) {
            case Article::STATUS_DRAFT:

                if (! $article->isEditableBy($currentUser))
                    throw new PermissionDeniedException('Permission denied to submit this article.');

                $flashSuccessMessage = 'Draft article saved. Click "submit" when ready to publish.';

                $article->status = Article::STATUS_DRAFT;

                break;
            case Article::STATUS_SUBMITTED:

                if (! $article->isSubmittableBy($currentUser))
                    throw new PermissionDeniedException('Permission denied to submit this article.');

                $flashSuccessMessage = 'Article has been submitted and will be reviewed by an editor shortly.';

                $article->status = Article::STATUS_SUBMITTED;

                break;
            case Article::STATUS_APPROVED:

                if (! $article->isApprovableBy($currentUser))
                    throw new PermissionDeniedException('Permission denied to submit this article.');

                $flashSuccessMessage = 'Article has been approved.';

                $article->status = Article::STATUS_APPROVED;

                break;
        }


        // =====================
        // options

        // check options
        $article->featured = (@$params['featured']) ? 1 : 0;


        if ( $article->save($params) ) {
            $container->get('flash')->addMessage('success', $flashSuccessMessage);
            return $response->withRedirect('/admin/articles/' . $id);
        } else {
            $container->get('flash')->addMessage('errors', $article->getErrors());
            return $this->forward('edit', compact('id'));
        }
    }

    // /**
    //  * This method is used when the contributer has finished editing and ready to
    //  * publish. They will be redirected to the "show" page from there they can
    //  * review and open up for additional changes. from there, an admin user can also
    //  * approve the article to make it live.
    //  */
    // public function submit($id)
    // {
    //     $currentUser = $container->get('auth')->getCurrentUser();
    //     $params = $container->getPost();
    //     $article = $container->get('blog.model.article')->findOneOrFail(array(
    //         'id' => (int) $args['id'],
    //     ));
    //
    //
    //
    //     // set the status of the article to approved, if there are any problems
    //     // with the data send, save() will fail anyway. Using set() here as it is
    //     // more testable as a method :)
    //     $article->set('status', Article::STATUS_SUBMITTED);
    //
    //     // handle photos
    //     $this->attachPhotosTo($article, @$_FILES['photos']);
    //
    //     // set tags from the params tags value submitted
    //     // this will also ensure than only valid tags are used
    //     $params['tags'] = $container->getTagsFromTagIds(@$params['tags']);
    //
    //     // check options
    //     $article->featured = (@$params['featured']) ? 1 : 0;
    //
    //     if ( $article->save( $container->getPost() ) ) {
    //         $container->get('flash')->addMessage('success', );
    //         return $response->withRedirect('/admin/articles/' . $id);
    //     } else {
    //         $container->get('flash')->addMessage('errors', $article->getErrors());
    //         return $this->forward('edit', compact('id'));
    //     }
    // }
    //
    // /**
    //  * Only editor and admin users can approve articles
    //  */
    // public function approve($id)
    // {
    //     $currentUser = $container->get('auth')->getCurrentUser();
    //     $params = $container->getPost();
    //     $article = $container->get('blog.model.article')->findOneOrFail(array(
    //         'id' => (int) $args['id'],
    //     ));
    //
    //     // only top brass can approve
    //     if (! $currentUser->canApprove($article) ) {
    //         throw new PermissionDeniedException('Permission denied to approve this article.');
    //     }
    //
    //     // set the status of the article to approved, if there are any problems
    //     // with the data send, save() will fail anyway. Using set() here as it is
    //     // more testable as a method :)
    //     $article->set('status', Article::STATUS_APPROVED);
    //
    //     // set tags from the params tags value submitted
    //     // this will also ensure than only valid tags are used
    //     $params['tags'] = $container->getTagsFromTagIds(@$params['tags']);
    //
    //     // handle photos
    //     $this->attachPhotosTo($article, @$_FILES['photos']);
    //
    //     // check options
    //     $article->featured = (@$params['featured']) ? 1 : 0;
    //
    //     if ( $article->save( $container->getPost() ) ) {
    //         $container->get('flash')->addMessage('success', );
    //         return $response->withRedirect('/admin/articles/' . $id);
    //     } else {
    //         $container->get('flash')->addMessage('errors', $article->getErrors());
    //         return $this->forward('edit', compact('id'));
    //     }
    // }

    public function delete($request, $response, $args)
    {
        $container = $this->getContainer();
        $currentUser = $container->get('auth')->getCurrentUser();

        $article = $container->get('blog.model.article')->findOneOrFail(array(
            'id' => (int) $args['id'],
        ));

        // only top brass can delete
        if (! $article->isDeletableBy($currentUser) ) {
            throw new PermissionDeniedException('Permission denied to delete this article.');
        }

        if ( $article->delete() ) {
            $container->get('flash')->addMessage('success', 'Article deleted successfully');
            return $response->withRedirect('/admin/articles');
        } else {
            $container->get('flash')->addMessage('errors', $article->getErrors());
            return $this->forward('edit', array( 'id' => $id ));
        }
    }

    /**
     * Fetch tags from the tag $ids
     * @param int|array $ids Array (or single int) of tag ids
     * @return MartynBiz\Mongo\MongoIterator
     */
    protected function getTagsFromTagIds($ids)
    {
        $container = $this->getContainer();

        // if not an array (e.g. single id), set as one
        // makes life easier for the query when use of "$in" can be assumed
        if (! is_array($ids)) $ids = array($ids);

        // ids need to be integers, otherwise they won't fetch anything
        foreach ($ids as $i => $id) {
            $ids[$i] = (int) $id;
        }

        // get tags from tags[] and write back to params['tags']
        return $container->get('blog.model.tag')->find(array(
            'id' => array(
                '$in' => $ids,
            ),
        ));
    }

    // /**
    //  * Create photos in photos collection and attach to Mongo object (e.g. Article)
    //  * @param Mongo $article Article to attach the photos to
    //  * @param array $photos POST param
    //  * @return void
    //  */
    // protected function attachPhotosTo(Mongo $target, $photos)
    // {
    //     $container = $this->app->getContainer();
    //     $settings = $container->get('settings');
    //
    //     if (@$photos['name']) {
    //
    //         // generate the photo dir from the target id
    //         // we'll use Photo::getCurrentDir to generate the dir from date
    //         // useful when managing thousands of photos/articles
    //         // e.g. /var/www/.../data/photos/201601/31/
    //         $dir = $settings['photos_dir']['original'] . '/' . Photo::getNewDir();
    //         $fileExists = $container->get('fs')->fileExists($dir);
    //         if (!$fileExists and !$container->get('fs')->makeDir($dir, 0775, true)) {
    //             throw new \Exception('Could not create directory');
    //         }
    //
    //         // loop through photos and create in photos collection
    //         // also, attach the newly created photo to article
    //         foreach($photos['name'] as $i => $file) {
    //
    //             // get the parameters from the form submission
    //             $name = $photos['name'][$i];
    //             $tmpName = $photos['tmp_name'][$i];
    //             $type = $photos['type'][$i];
    //             $ext = pathinfo($name, PATHINFO_EXTENSION);
    //
    //             // if the file field is blank, move onto the next field
    //             if (empty($file)) continue;
    //
    //             // build the file name and path, we'll store the filename in the db
    //             $file = sprintf('%s.%s', substr(md5_file($tmpName), 0, 10), strtolower($ext));
    //             $destpath = $dir . '/' . $file;
    //
    //             // handle the uploaded file
    //             $container->get('photo_manager')->moveUploadedFile($tmpName, $destpath, $maxWidth=2000, $maxHeight=2000);
    //
    //             // create the photo in collection first so that we have an id to
    //             // name the photo by
    //             $photo = $container->get('Blog\Model\Photo')->create(array(
    //                 'original_file' => $file,
    //                 'type' => $type,
    //                 'width' => $width,
    //                 'height' => $height,
    //             ));
    //
    //             // attach the photo to $article
    //             $target->push( array(
    //                 'photos' => $photo,
    //             ) );
    //
    //         }
    //     }
    // }
}
