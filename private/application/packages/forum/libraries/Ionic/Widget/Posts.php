<?php
namespace Ionic\Widget;

use View;
use Ionic\Widget;
use DB;
use Input;
use Cache;
use URI;
use Request;

class Posts extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('limit' => 5, 'template' => 'widgets.posts'), $this->options);

        return View::make('admin.widgets.widget_posts', array(
            'options' => $options,
            'action'  => URI::current()
        ));
    }

    /**
     * Prepare options field
     *
     * @return string
     */
    public function prepare_options()
    {
        if (Request::forged() or Request::method() != 'POST')
            return false;

        $options = array_merge(array('limit' => 5, 'template' => 'widgets.posts'), $this->options);

        $options['limit'] = (int) Input::get('limit', 0);
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.posts';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('limit' => 5, 'template' => 'widgets.posts'), $this->options);

        $posts = 'fposts-'.$options['limit'];

        if (($posts = Cache::get($posts)) === null) {
            $posts = DB::table('forum_threads')->left_join('users', 'users.id', '=', 'forum_threads.last_user_id')
                                               ->join('forum_boards', 'forum_boards.id', '=', 'forum_threads.board_id')
                                               ->take($options['limit'])
                                               ->where('forum_boards.is_private', '=', 0)
                                               ->order_by('forum_threads.last_date', 'desc')
                                               ->get(array(
                                                   'forum_threads.title', 'forum_threads.slug', 'forum_threads.posts_count',
                                                   'forum_threads.last_date', 'forum_threads.last_id',
                                                   'users.display_name', 'users.slug as user_slug',
                                                   'forum_boards.title as board_title'
                                               ));

            Cache::put('fposts-'.$options['limit'], $posts);
        }

        $posts = View::make($options['template'], array('posts' => $posts))->render();

        return $posts;
    }

}
