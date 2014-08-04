<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;

class Loginbox extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('template' => 'widgets.loginbox'), $this->options);

        return View::make('admin.widgets.widget_loginbox', array(
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

        $options = array_merge(array('template' => 'widgets.loginbox'), $this->options);

        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.loginbox';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('template' => 'widgets.loginbox'), $this->options);

        return View::make($options['template']);
    }

}