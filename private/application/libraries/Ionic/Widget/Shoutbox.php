<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;

class Shoutbox extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('template'     => 'widgets.shoutbox', 'auto_refresh' => 0), $this->options);

        return View::make('admin.widgets.widget_shoutbox', array(
                    'action'  => \URI::current(),
                    'options' => $options
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

        $options = array_merge(array('template'     => 'widgets.shoutbox', 'auto_refresh' => 0), $this->options);

        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.shoutbox';
        $options['auto_refresh'] = (int) Input::get('auto_refresh', 0);

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('template'     => 'widgets.shoutbox', 'auto_refresh' => 0), $this->options);

        return View::make($options['template'], array(
                    'list'         => View::make('shoutbox.list', array(
                        'moderation' => (\Auth::can('mod_shoutbox') or \Auth::can('admin_shoutbox')),
                        'posts'      => DB::table('shoutbox')->where('type', '=', 'global')->left_join('users', 'users.id', '=', 'shoutbox.user_id')->take(\Config::get('limits.shoutbox', 10))
                                ->order_by('id', 'desc')
                                ->get(array('shoutbox.*', 'users.display_name', 'users.slug'))
                    )),
                    'can_add'      => ((\Auth::is_logged() and !\Auth::banned()) or (\Auth::is_guest() and \Config::get('guests.shoutbox', false))),
                    'auto_refresh' => $options['auto_refresh']
                ));
    }

}