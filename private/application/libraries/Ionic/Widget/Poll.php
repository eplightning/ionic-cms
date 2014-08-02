<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;
use \IoC;

/**
 * Widget - Poll
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class Poll extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('template' => 'widgets.poll'), $this->options);

        return View::make('admin.widgets.widget_poll', array(
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

        $options = array_merge(array('template' => 'widgets.poll'), $this->options);

        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.poll';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('template' => 'widgets.poll'), $this->options);

        if (\Cache::has('poll'))
        {
            $poll = \Cache::get('poll');
        }
        else
        {
            $poll = new \Model\Poll;

            \Cache::put('poll', $poll);
        }

        return View::make($options['template'], array('poll' => $poll));
    }

}