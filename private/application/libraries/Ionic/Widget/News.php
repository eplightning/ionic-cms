<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;

class News extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('limit'      => 5, 'sort_item'  => 'created_at', 'sort_order' => 'desc', 'tag'        => 0, 'template'   => 'widgets.news'), $this->options);

        return View::make('admin.widgets.widget_news', array(
                    'options' => $options,
                    'action'  => \URI::current(),
                    'tags'    => DB::table('tags')->order_by('title', 'asc')->get(array('id', 'title')),
                    'items'   => array(
                        'created_at'     => 'Data dodania',
                        'karma'          => 'Karma',
                        'comments_count' => 'Komentarzy',
                        'views'          => 'Wyświetleń'
                    )
                ));
    }

    /**
     * Prepare options field
     *
     * @return string
     */
    public function prepare_options()
    {
        if (\Request::forged() or \Request::method() != 'POST')
        {
            return false;
        }

        $options = array_merge(array('limit'      => 5, 'sort_item'  => 'created_at', 'sort_order' => 'desc', 'tag'        => 0, 'template'   => 'widgets.news'), $this->options);

        $items = array(
            'created_at'     => 'Data dodania',
            'karma'          => 'Karma',
            'comments_count' => 'Komentarzy',
            'views'          => 'Wyświetleń'
        );

        if (isset($items[Input::get('sort_item', '')]))
        {
            $options['sort_item'] = Input::get('sort_item', '');
        }
        else
        {
            $options['sort_item'] = 'created_at';
        }

        $options['limit'] = (int) Input::get('limit', 0);
        $options['sort_order'] = Input::get('sort_order', 'desc') == 'desc' ? 'desc' : 'asc';

        if (Input::has('tag') and ctype_digit(Input::get('tag')) and Input::get('tag') != '0')
        {
            $tag = (int) Input::get('tag');
            $tag = DB::table('tags')->where('id', '=', $tag)->first('id');

            if ($tag)
            {
                $options['tag'] = $tag->id;
            }
            else
            {
                $options['tag'] = 0;
            }
        }
        else
        {
            $options['tag'] = 0;
        }

        // template
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.news';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('limit'      => 5, 'sort_item'  => 'created_at', 'sort_order' => 'desc', 'tag'        => 0, 'template'   => 'widgets.news'), $this->options);

        $news = 'news-'.$options['limit'].'-'.$options['sort_item'].'-'.$options['sort_order'].'-'.(int) $options['tag'];

        if ($options['limit'] <= 0)
        {
            return;
        }

        if (\Cache::has($news))
        {
            return \Cache::get($news);
        }
        else
        {
            if ($options['tag'])
            {
                $news = DB::table('news_tags')->left_join('news', 'news.id', '=', 'news_tags.news_id')->left_join('users', 'users.id', '=', 'news.user_id')
                        ->order_by('news.'.$options['sort_item'], $options['sort_order'])
                        ->take($options['limit'])
                        ->where(function($q) {
                                    $q->where('news.is_published', '=', 1);
                                    $q->or_where('news.publish_at', '<=', date('Y-m-d H:i:s'));
                                    $q->where('news.publish_at', '<>', '0000-00-00 00:00:00');
                                })
                        ->where('news_tags.tag_id', '=', $options['tag'])
                        ->get(array(
                    'news.*',
                    'users.display_name', 'users.slug as user_slug'
                        ));
            }
            else
            {
                $news = DB::table('news')->left_join('users', 'users.id', '=', 'news.user_id')
                        ->order_by('news.'.$options['sort_item'], $options['sort_order'])
                        ->take($options['limit'])
                        ->where(function($q) {
                                    $q->where('news.is_published', '=', 1);
                                    $q->or_where('news.publish_at', '<=', date('Y-m-d H:i:s'));
                                    $q->where('news.publish_at', '<>', '0000-00-00 00:00:00');
                                })
                        ->get(array(
                    'news.*',
                    'users.display_name', 'users.slug as user_slug'
                        ));
            }

            $news = (string) View::make($options['template'], array('news' => $news));

            \Cache::put('news-'.$options['limit'].'-'.$options['sort_item'].'-'.$options['sort_order'].'-'.(int) $options['tag'], $news);

            return $news;
        }
    }

}