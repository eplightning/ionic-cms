<?php

/**
 * Video controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Video_Controller extends Base_Controller {

    /**
     * Category view
     *
     * @param string $cat
     */
    public function action_category($cat)
    {
        $cat = DB::table('video_categories')->where('slug', '=', $cat)->first('*');

        if (!$cat)
            return Response::error(404);

        $this->page->set_title('Kategoria video');
        $this->online('Video - '.$cat->title, 'video/category/'.$cat->slug);

        $this->page->breadcrumb_append('Video', 'video/index');

        foreach (Ionic\Tree::build_path('video_categories', $cat->left, $cat->right, $cat->depth, array(
            'title',
            'slug')) as $parent)
        {
            $this->page->breadcrumb_append($parent->title, 'video/category/'.$parent->slug);
        }

        $this->page->breadcrumb_append($cat->title, 'video/category/'.$cat->slug);

        $this->view = View::make('video.category', array(
                    'cat'           => $cat,
                    'subcategories' => DB::table('video_categories')->where('left', '>', $cat->left)
                            ->where('right', '<', $cat->right)
                            ->where('depth', '=', ($cat->depth + 1))
                            ->left_join('videos', 'videos.id', '=', 'video_categories.last_video_id')
                            ->order_by('left', 'asc')->get(array(
                        'video_categories.title',
                        'video_categories.slug',
                        'video_categories.description',
                        'video_categories.last_video_id',
                        'videos.title as video_title',
                        'videos.slug as video_slug',
                        'videos.created_at as video_created_at',
                        'videos.thumbnail as video_thumb')),
                    'videos'        => DB::table('videos')->where('category_id', '=', $cat->id)
                            ->order_by('id', 'desc')
                            ->paginate(20, array(
                                'title',
                                'thumbnail',
                                'created_at',
                                'description',
                                'comments_count',
                                'slug'))
                ));
    }

    /**
     * Index
     */
    public function action_index()
    {
        $this->page->set_title('Video');
        $this->page->breadcrumb_append('Video', 'video/index');
        $this->online('Video', 'video/index');

        $this->view = View::make('video.index', array(
                    'categories' => DB::table('video_categories')->order_by('left', 'asc')
                            ->where('depth', '=', 0)
                            ->left_join('videos', 'videos.id', '=', 'video_categories.last_video_id')
                            ->get(array(
                                'video_categories.title',
                                'video_categories.slug',
                                'video_categories.description',
                                'video_categories.last_video_id',
                                'videos.title as video_title',
                                'videos.slug as video_slug',
                                'videos.created_at as video_created_at',
                                'videos.thumbnail as video_thumb'))
                ));
    }

    /**
     * Video view
     *
     * @param type $video
     */
    public function action_show($video)
    {
        $video = DB::table('videos')->where('videos.slug', '=', $video)
                ->join('users', 'users.id', '=', 'videos.user_id')
                ->first(array(
            'videos.*',
            'users.display_name',
            'users.slug as user_slug'));

        if (!$video)
            return Response::error(404);

        $cat = DB::table('video_categories')->where('id', '=', $video->category_id)->first(array(
            'title',
            'slug'));

        $this->page->set_title('Film - '.$video->title);
        $this->page->breadcrumb_append('Video', 'video/index');
        $this->page->breadcrumb_append($cat->title, 'video/category/'.$cat->slug);
        $this->page->breadcrumb_append('Film - '.$video->title, 'video/show/'.$video->slug);
        $this->online('Film - '.$video->title, 'video/show/'.$video->slug);

        $this->view = View::make('video.show', array(
                    'video'    => $video,
                    'comments' => $this->page->make_comments($video->id, 'video')
                ));
    }

}