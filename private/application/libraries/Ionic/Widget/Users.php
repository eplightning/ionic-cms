<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;

class Users extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('limit'      => 5, 'sort_item'  => 'bet_points', 'sort_order' => 'desc', 'template'   => 'widgets.users'), $this->options);

        return View::make('admin.widgets.widget_users', array(
                    'options' => $options,
                    'action'  => \URI::current(),
                    'items'   => array(
                        'bet_points'  => 'Punkty typera',
                        'points'      => 'Punkty',
                        'created_at'  => 'Data rejestracji',
                        'users_count' => 'Ilości komentarzy',
                        'news_count'  => 'Ilości newsów'
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

        $options = array_merge(array('limit'      => 5, 'sort_item'  => 'bet_points', 'sort_order' => 'desc', 'template'   => 'widgets.users'), $this->options);

        $items = array(
            'bet_points'  => 'Punkty typera',
            'points'      => 'Punkty',
            'created_at'  => 'Data rejestracji',
            'users_count' => 'Ilości komentarzy',
            'news_count'  => 'Ilości newsów'
        );

        if (isset($items[Input::get('sort_item', '')]))
        {
            $options['sort_item'] = Input::get('sort_item', '');
        }
        else
        {
            $options['sort_item'] = 'bet_points';
        }

        $options['limit'] = (int) Input::get('limit', 0);
        $options['sort_order'] = Input::get('sort_order', 'desc') == 'desc' ? 'desc' : 'asc';

        // template
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.users';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('limit'      => 5, 'sort_item'  => 'bet_points', 'sort_order' => 'desc', 'template'   => 'widgets.users'), $this->options);

        $users = 'users-'.$options['limit'].'-'.$options['sort_item'].'-'.$options['sort_order'];

        if ($options['limit'] <= 0)
        {
            return;
        }

        if (\Cache::has($users))
        {
            $users = \Cache::get($users);
        }
        else
        {
            $users = DB::table('users')->join('profiles', 'profiles.user_id', '=', 'users.id')
                    ->order_by('profiles.'.$options['sort_item'], $options['sort_order'])->take($options['limit'])
                    ->get(array(
                'users.display_name', 'users.slug', 'users.id', 'users.email',
                'profiles.bet_points', 'profiles.points', 'profiles.comments_count', 'profiles.news_count', 'profiles.avatar', 'profiles.real_name', 'profiles.created_at'
                    ));

            $users = (string) View::make($options['template'], array('users' => $users));

            \Cache::put('users-'.$options['limit'].'-'.$options['sort_item'].'-'.$options['sort_order'], $users);
        }

        return $users;
    }

}