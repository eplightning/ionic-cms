<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;
use \Auth;

class Rotation extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('items' => array()), $this->options);

        return View::make('admin.widgets.widget_rotation', array(
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

        $options = array_merge(array('items' => array()), $this->options);

        $options['items'] = array();

        if (!Auth::can('admin_root'))
        {
            require_once path('app').'vendor'.DS.'htmLawed.php';
        }

        if (isset($_POST['items']) and is_array($_POST['items']))
        {
            foreach ($_POST['items'] as $v)
            {
                if (empty($v))
                    continue;

                if (!Auth::can('admin_root'))
                {
                    $options['items'][] = htmLawed($v, array('safe' => 1));
                }
                else
                {
                    $options['items'][] = $v;
                }
            }
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
        $options = array_merge(array('items' => array()), $this->options);

        $count = count($options['items']);

        if ($count <= 0)
        {
            return '';
        }

        return $options['items'][rand(0, ($count - 1))];
    }

}