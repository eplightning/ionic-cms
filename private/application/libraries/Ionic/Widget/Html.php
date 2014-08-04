<?php
namespace Ionic\Widget;

use \View;
use \Asset;
use \Ionic\Editor;
use \Ionic\Widget;

class HTML extends Widget {

    /**
     * Display options
     *
     * @param  string $opt
     * @param  string $action
     * @return string
     */
    public function display_options($action = '', $opt = '')
    {
        $text = false;

        if (isset($this->options['default_text']) and $this->options['default_text'])
        {
            $text = true;
        }

        if ($opt == 'text')
        {
            $text = true;
        }
        elseif ($opt == 'html')
        {
            $text = false;
        }

        if ($text)
        {
            Editor::init();
        }
        else
        {
            Asset::add('codemirror', 'public/css/codemirror.css');
            Asset::add('codemirror', 'public/js/codemirror.js');
            Asset::add('codemirror_html', 'public/js/codemirror_html.js', 'codemirror');
        }

        if (!isset($this->options['content']))
            $this->options['content'] = '';

        return View::make('admin.widgets.widget_html', array('options' => $this->options, 'action'  => $action, 'text' => $text));
    }

    /**
     * Prepare options field
     *
     * @return string
     */
    public function prepare_options()
    {
        if (\Request::forged() or \Request::method() != 'POST' or !isset($_POST['code']))
        {
            return false;
        }

        $content = \Input::get('code');

        if (!\Auth::can('admin_xss'))
        {
            require_once path('app').'vendor'.DS.'htmLawed.php';

            $content = htmLawed($content, array('safe' => 1));
        }

        return array('content' => $content, 'default_text' => isset($this->options['default_text']) ? $this->options['default_text'] : false);
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        return isset($this->options['content']) ? $this->options['content'] : '';
    }

}