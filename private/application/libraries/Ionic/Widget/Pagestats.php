<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;

class Pagestats extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('date'     => date('Y-m-d'), 'template' => 'widgets.pagestats'), $this->options);

        return View::make('admin.widgets.widget_pagestats', array(
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

        $options = array_merge(array('date'     => date('Y-m-d'), 'template' => 'widgets.pagestats'), $this->options);

        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.pagestats';

        if (Input::has('date') and strtotime(Input::get('date')))
        {
            $options['date'] = date('Y-m-d', strtotime(Input::get('date')));
        }

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('date'     => date('Y-m-d'), 'template' => 'widgets.pagestats'), $this->options);

        if (\Cache::has('page-stats'))
        {
            $pagestats = \Cache::get('page-stats');
        }
        else
        {
            $pagestats = array(
                'news'      => DB::table('news')->where('is_published', '=', 1)->or_where('publish_at', '<=', date('Y-m-d H:i:s'))->count(),
                'users'     => DB::table('users')->count(),
                'last_user' => DB::table('users')->order_by('id', 'desc')->first(array('id', 'display_name', 'slug'))
            );

            \Cache::put('page-stats', $pagestats);
        }

        $date = new \DateTime($options['date']);

        return View::make($options['template'], array(
                    'pagestats' => $pagestats,
                    'online'    => \IoC::resolve('online'),
                    'exists'    => $date->diff(new \DateTime('now'))
                ));
    }

}