<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;
use \Cache;

class Comments extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('limit'      => 5, 'sort_item'  => 'created_at', 'sort_order' => 'desc', 'template'   => 'widgets.comments'), $this->options);

        return View::make('admin.widgets.widget_comments', array(
                    'options' => $options,
                    'action'  => \URI::current(),
                    'items'   => array(
                        'created_at' => 'Data dodania',
                        'karma'      => 'Karma'
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

        $options = array_merge(array('limit'      => 5, 'sort_item'  => 'created_at', 'sort_order' => 'desc', 'template'   => 'widgets.comments'), $this->options);

        $items = array(
            'created_at' => 'Data dodania',
            'karma'      => 'Karma'
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
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.comments';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('limit'      => 5, 'sort_item'  => 'created_at', 'sort_order' => 'desc', 'template'   => 'widgets.comments'), $this->options);

        $comments = 'comments-'.$options['limit'].'-'.$options['sort_item'].'-'.$options['sort_order'];

        if ($options['limit'] <= 0)
        {
            return;
        }

        if (($comments = Cache::get($comments)) !== null)
        {
            return $comments;
        }
        else
        {
            $comments = DB::table('comments')->left_join('users', 'users.id', '=', 'comments.user_id')
                    ->order_by('comments.'.$options['sort_item'], $options['sort_order'])
                    ->take($options['limit'])
                    ->where('comments.is_hidden', '=', 0)
                    ->get(array(
                'comments.*',
                'users.display_name', 'users.slug'
                    ));


            $comments = View::make($options['template'], array('comments' => $comments))->render();

            Cache::put('comments-'.$options['limit'].'-'.$options['sort_item'].'-'.$options['sort_order'], $comments);

            return $comments;
        }
    }

}
