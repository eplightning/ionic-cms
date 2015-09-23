<?php

use Ionic\Editor;

/**
 * Configuration
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Config_Controller extends Admin_Controller {

    /**
     * Category view
     *
     * @param string $id
     */
    public function action_category($id)
    {
        // Permissions
        if (!Auth::can('admin_config'))
            return Response::error(403);

        // Editor
        Ionic\Editor::init();

        // ID
        if (!ctype_digit($id))
            return Response::error(500);

        // Find category he's asking for
        $id = (int) $id;
        $id = Model\Config::retrieve_items($id);

        if (empty($id))
            return Response::error(404);

        // Submitted?
        if (Request::method() == 'POST' and !Request::forged())
        {
            $update_list = array();

            foreach ($id as $section => $items)
            {
                foreach ($items as $item)
                {
                    if ($item['item']->type == 'yesno')
                    {
                        $update_list[$item['item']->id] = (Input::get($item['item']->php_key) == '1') ? '1' : '0';
                        continue;
                    }

                    if (!isset($_POST[$item['item']->php_key]))
                    {
                        continue;
                    }

                    if ($item['item']->type == 'text' or $item['item']->type == 'textarea')
                    {
                        if ($item['item']->options == 'numeric')
                        {
                            $update_list[$item['item']->id] = (int) (Input::get($item['item']->php_key));
                            continue;
                        }

                        $update_list[$item['item']->id] = HTML::specialchars(Input::get($item['item']->php_key));
                    }
                    elseif ($item['item']->type == 'select')
                    {
                        if ($item['item']->options == 'timezone')
                        {
                            $tz = timezone_identifiers_list();

                            if (in_array(Input::get($item['item']->php_key), $tz))
                            {
                                $update_list[$item['item']->id] = Input::get($item['item']->php_key);
                            }
                        }
                        else
                        {
                            foreach (explode("\n", ionic_normalize_lines($item['item']->options)) as $v)
                            {
                                $opt = explode('=', $v, 2);

                                if (Input::get($item['item']->php_key) == $opt[0])
                                {
                                    $update_list[$item['item']->id] = $opt[0];
                                    break;
                                }
                            }
                        }
                    }
                    elseif ($item['item']->type == 'html')
                    {
                        if (!Auth::can('admin_xss'))
                        {
                            require_once path('app').'vendor'.DS.'htmLawed.php';

                            $update_list[$item['item']->id] = htmLawed(Input::get($item['item']->php_key), array('safe' => 1));
                        }
                        else
                        {
                            $update_list[$item['item']->id] = Input::get($item['item']->php_key);
                        }
                    }
                }
            }

            Model\Config::update($update_list);

            Cache::forget('db-config');

            $this->notice('Konfiguracja zapisana pomyślnie');
            $this->log('Zmienił konfiguracje');

            return Redirect::to(URI::current());
        }

        // Generate HTML
        foreach ($id as $section => $items)
        {
            foreach ($items as $k => $item)
            {
                if ($item['item']->type == 'text')
                {
                    $id[$section][$k]['html'] = Form::input('text', $item['item']->php_key, $item['item']->value);
                }
                elseif ($item['item']->type == 'select')
                {
                    $options = array();

                    if ($item['item']->options == 'timezone')
                    {
                        foreach (timezone_identifiers_list() as $v)
                        {
                            $options[$v] = $v;
                        }
                    }
                    else
                    {
                        foreach (explode("\n", ionic_normalize_lines($item['item']->options)) as $v)
                        {
                            $opt = explode('=', $v, 2);

                            $options[$opt[0]] = $opt[1];
                        }
                    }

                    $id[$section][$k]['html'] = Form::select($item['item']->php_key, $options, $item['item']->value);
                }
                elseif ($item['item']->type == 'textarea')
                {
                    $id[$section][$k]['html'] = Form::textarea($item['item']->php_key, $item['item']->value);
                }
                elseif ($item['item']->type == 'html')
                {
                    $id[$section][$k]['html'] = Editor::create($item['item']->php_key, $item['item']->value);
                }
                elseif ($item['item']->type == 'yesno')
                {
                    $id[$section][$k]['html'] = Form::checkbox($item['item']->php_key, '1', $item['item']->value == '1', array('class' => 'checkbox'));
                }
            }
        }

        // Title
        $this->page->set_title('Konfiguracja');

        // Config
        $this->view = new View('admin.config.category');
        $this->view->with('config', $id);
        $this->view->with('action', URI::current());

        // Breadcrumb
        $this->page->breadcrumb_append('Konfiguracja', 'admin/config/index');
        $this->page->breadcrumb_append('Kategoria', URI::current());
    }

    /**
     * Index action
     */
    public function action_index()
    {
        // Permissions
        if (!Auth::can('admin_config'))
            return Response::error(403);

        // Title
        $this->page->set_title('Konfiguracja');

        // View
        $this->view = View::make('admin.config.index');

        // Categories
        $this->view->with('categories', Model\Config::retrieve_categories());

        // Breadcrumb
        $this->page->breadcrumb_append('Konfiguracja', 'admin/config/index');
    }

}
