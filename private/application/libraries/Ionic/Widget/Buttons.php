<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;
use \Cache;

class Buttons extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('template' => 'widgets.buttons', 'buttons'  => array(), 'id'       => 'buttons'), $this->options);

        return View::make('admin.widgets.widget_buttons', array(
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

        $options = array_merge(array('template' => 'widgets.buttons', 'buttons'  => array(), 'id'       => 'buttons'), $this->options);

        $options['buttons'] = array();

        if (isset($_POST['buttons']) and is_array($_POST['buttons']))
        {
            foreach ($_POST['buttons'] as $v)
            {
                if (!is_array($v))
                    continue;

                if (empty($v['title']) and empty($v['link']) and empty($v['image']))
                    continue;

                $options['buttons'][] = array(
                    'title' => !empty($v['title']) ? \HTML::specialchars($v['title']) : '',
                    'link'  => !empty($v['link']) ? \HTML::specialchars($v['link']) : '',
                    'image' => !empty($v['image']) ? \HTML::specialchars($v['image']) : ''
                );
            }
        }

        $options['id'] = \Str::slug(Input::get('id', 'buttons'));
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.buttons';

        ionic_clear_cache('buttons-'.$options['id']);

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('template' => 'widgets.buttons', 'buttons'  => array(), 'id'       => 'buttons'), $this->options);

        if (Cache::has('buttons-'.$options['id']))
        {
            return Cache::get('buttons-'.$options['id']);
        }

        $buttons = (string) View::make($options['template'], array('buttons' => $options['buttons']));

        Cache::put('buttons-'.$options['id'], $buttons);

        return $buttons;
    }

}