<?php

/**
 * Calendar admin module
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Calendar_Controller extends Admin_Controller {

    /**
     * Add action
     *
     * @return Response
     */
    public function action_add()
    {
        if (!Auth::can('admin_calendar_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title' => '', 'date_start' => '', 'event_content' => '', 'url' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'date_start', 'event_content', 'url')));
            $raw_data['image'] = Input::file('image');

            $rules = array(
                'title'       => 'required|max:127',
                'date_start'  => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!',
                'image'       => 'image',
                'url'         => 'max:127'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/calendar/add')->with_errors($validator)
                                ->with_input('only', array('title', 'date_start', 'event_content', 'url'));
            }
            else
            {
                $prepared_data = array(
                    'title'      => HTML::specialchars($raw_data['title']),
                    'date_start' => $raw_data['date_start'],
                    'date_end'   => $raw_data['date_start'],
                    'handler'    => 'event',
                    'type'       => ''
                );

                $options = array('content' => '', 'image' => '', 'url' => '');

                $options['url'] = HTML::specialchars($raw_data['url']);
                $options['content'] = $raw_data['event_content'];

                if (!Auth::can('admin_xss'))
                {
                    require_once path('app').'vendor'.DS.'htmLawed.php';

                    $options['content'] = htmLawed($options['content'], array('safe' => 1));
                }

                if (is_array($raw_data['image']) and $raw_data['image']['error'] == UPLOAD_ERR_OK and !empty($raw_data['image']['name']) and !empty($raw_data['image']['tmp_name']))
                {
                    $filename = Str::ascii($raw_data['image']['name']);
                    $filename = preg_replace('![^\.\_\pL\pN\s]+!u', '', $filename);
                    $filename = preg_replace('/\s+/', '_', $filename);
                    $extension = strtolower(substr(strrchr($filename, '.'), 1));

                    while (file_exists(path('public').'upload'.DS.'calendar'.DS.$filename))
                    {
                        $filename = Str::random(10).'.'.$extension;
                    }

                    move_uploaded_file($raw_data['image']['tmp_name'], path('public').'upload'.DS.'calendar'.DS.$filename);

                    $options['image'] = $filename;
                }

                $prepared_data['options'] = serialize($options);

                $obj_id = DB::table('calendar')->insert_get_id($prepared_data);

                Cache::forget('calendar');

                $this->notice('Wydarzenie dodane pomyślnie');
                $this->log(sprintf('Dodał wydarzenie: %s', $prepared_data['title']));
                return Redirect::to('admin/calendar/index');
            }
        }

        $this->page->set_title('Dodawanie wydarzenia');

        $this->page->breadcrumb_append('Kalendarz', 'admin/calendar/index');
        $this->page->breadcrumb_append('Dodawanie wydarzenia', 'admin/calendar/add');

        $this->view = View::make('admin.calendar.add');

        Ionic\Editor::init();

        $old_data = array('title' => '', 'date_start' => '', 'event_content' => '', 'url' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);
    }

    /**
     * Add calendar source
     *
     * @param   string      $handler
     * @param   string      $type
     * @return  Response
     */
    public function action_add_source($handler = '', $type = '')
    {
        if (!Auth::can('admin_calendar_add'))
            return Response::error(403);

        $this->page->breadcrumb_append('Kalendarz', 'admin/calendar/index');
        $this->page->breadcrumb_append('Wybór źródła wydarzeń', 'admin/calendar/add_source');

        $handlers = array();

        foreach (Event::fire('ionic.calendar_handler') as $r)
        {
            if (is_array($r))
            {
                $handlers = array_merge($handlers, $r);
            }
        }

        if (!$handler or !$type or !isset($handlers[$handler]))
        {
            $this->page->set_title('Wybór źródła wydarzeń');

            $sources = array();

            foreach ($handlers as $handler)
            {
                $sources = array_merge($sources, $handler->get_sources());
            }

            $this->view = View::make('admin.calendar.add_source_select', array(
                'sources' => $sources
            ));

            return;
        }

        $this->page->set_title('Dodawanie źródła');
        $this->page->breadcrumb_append('Dodawanie źródła', 'admin/calendar/add_source/'.$handler.'/'.HTML::specialchars($type));

        $retval = $handlers[$handler]->admin_add($type, 'admin/calendar/add_source/'.$handler.'/'.$type, $this->page);

        if ($retval instanceof Ionic\View)
        {
            $this->view = $retval;
        }
        elseif (is_array($retval))
        {
            DB::table('calendar')->insert(array(
                'title'      => $retval['title'],
                'date_start' => $retval['date_start'],
                'date_end'   => $retval['date_end'],
                'handler'    => $handler,
                'type'       => isset($retval['type']) ? $retval['type'] : $type,
                'options'    => is_array($retval['options']) ? serialize($retval['options']) : $retval['options']
            ));

            Cache::forget('calendar');

            $this->notice('Źródło wydarzeń dodane pomyślnie');
            $this->log(sprintf('Dodał źródło wydarzeń: %s', $retval['title']));

            return Redirect::to('admin/calendar/index');
        }
        else
        {
            return $retval;
        }
    }

    /**
     * Autocompletion
     *
     * @param  string   $id
     * @return Response
     */
    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_calendar'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    /**
     * Delete
     *
     * @param  string   $id
     * @return Response
     */
    public function action_delete($id)
    {
        if (!Auth::can('admin_calendar_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('calendar')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::ajax() or !Config::get('advanced.admin_prefer_ajax', true))
        {
            if (!($status = $this->confirm()))
            {
                return;
            }
            elseif ($status == 2)
            {
                return Redirect::to('admin/calendar/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        if ($id->handler == 'event' and !empty($id->options))
        {
            $options = unserialize($id->options);

            if ($options['image'] and is_file(path('public').'upload'.DS.'calendar'.DS.$options['image']))
            {
                @unlink(path('public').'upload'.DS.'calendar'.DS.$options['image']);
                ionic_clear_thumbnails('calendar', $options['image']);
            }
        }

        DB::table('calendar')->where('id', '=', $id->id)->delete();

        Cache::forget('calendar');

        if (!Request::ajax())
        {
            $this->notice('Wydarzenie / źródło usunięte pomyślnie');
            return Redirect::to('admin/calendar/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    /**
     * Edit
     *
     * @param  string   $id
     * @return Response
     */
    public function action_edit($id)
    {
        if (!Auth::can('admin_calendar_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('calendar')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        $this->page->set_title('Edycja wydarzenia');

        $this->page->breadcrumb_append('Kalendarz', 'admin/calendar/index');
        $this->page->breadcrumb_append('Edycja wydarzenia', 'admin/calendar/edit/'.$id->id);

        if ($id->handler != 'event')
        {
            $handlers = array();

            foreach (Event::fire('ionic.calendar_handler') as $r)
            {
                if (is_array($r))
                {
                    $handlers = array_merge($handlers, $r);
                }
            }

            if (!isset($handlers[$id->handler]))
                return Response::error(500);

            $retval = $handlers[$id->handler]->admin_edit($id, 'admin/calendar/edit/'.$id->id, $this->page);

            if ($retval instanceof Ionic\View)
            {
                $this->view = $retval;

                return;
            }
            elseif (is_array($retval))
            {
                DB::table('calendar')->where('id', '=', $id->id)->update(array(
                    'title'      => $retval['title'],
                    'date_start' => $retval['date_start'],
                    'date_end'   => $retval['date_end'],
                    'type'       => isset($retval['type']) ? $retval['type'] : $id->type,
                    'options'    => is_array($retval['options']) ? serialize($retval['options']) : $retval['options']
                ));

                Cache::forget('calendar');

                $this->notice('Źródło zaaktualizowane pomyślnie');
                $this->log(sprintf('Zmieniono źródło wydarzeń: %s', $retval['title']));
                return Redirect::to('admin/calendar/index');
            }
            else
            {
                return $retval;
            }
        }

        $old_options = empty($id->options) ? array('image' => '', 'content' => '', 'url' => '') : unserialize($id->options);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title' => '', 'date_start' => '', 'event_content' => '', 'url' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'date_start', 'event_content', 'url')));
            $raw_data['image'] = Input::file('image');

            $rules = array(
                'title'       => 'required|max:127',
                'date_start'  => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!',
                'image'       => 'image',
                'url'         => 'max:127'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/calendar/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('title', 'date_start', 'event_content', 'url'));
            }
            else
            {
                $prepared_data = array(
                    'title'      => HTML::specialchars($raw_data['title']),
                    'date_start' => $raw_data['date_start'],
                    'date_end'   => $raw_data['date_start'],
                    'handler'    => 'event',
                    'type'       => ''
                );

                $options = array('content' => '', 'image' => $old_options['image'], 'url' => '');

                $options['content'] = $raw_data['event_content'];
                $options['url'] = $raw_data['url'];

                if (!Auth::can('admin_xss'))
                {
                    require_once path('app').'vendor'.DS.'htmLawed.php';

                    $options['content'] = htmLawed($options['content'], array('safe' => 1));
                }

                if (is_array($raw_data['image']) and $raw_data['image']['error'] == UPLOAD_ERR_OK and !empty($raw_data['image']['name']) and !empty($raw_data['image']['tmp_name']))
                {
                    if ($old_options['image'] and is_file(path('public').'upload'.DS.'calendar'.DS.$old_options['image']))
                    {
                        @unlink(path('public').'upload'.DS.'calendar'.DS.$old_options['image']);
                        ionic_clear_thumbnails('calendar', $old_options['image']);
                    }

                    $filename = Str::ascii($raw_data['image']['name']);
                    $filename = preg_replace('![^\.\_\pL\pN\s]+!u', '', $filename);
                    $filename = preg_replace('/\s+/', '_', $filename);
                    $extension = strtolower(substr(strrchr($filename, '.'), 1));

                    while (file_exists(path('public').'upload'.DS.'calendar'.DS.$filename))
                    {
                        $filename = Str::random(10).'.'.$extension;
                    }

                    move_uploaded_file($raw_data['image']['tmp_name'], path('public').'upload'.DS.'calendar'.DS.$filename);

                    $options['image'] = $filename;
                }

                $prepared_data['options'] = serialize($options);

                \DB::table('calendar')->where('id', '=', $id->id)->update($prepared_data);

                Cache::forget('calendar');

                $this->notice('Wydarzenie zaaktualizowane pomyślnie');
                $this->log(sprintf('Zmieniono wydarzenie: %s', $prepared_data['title']));
                return Redirect::to('admin/calendar/index');
            }
        }

        $this->view = View::make('admin.calendar.edit');

        Ionic\Editor::init();

        $old_data = array('title' => '', 'date_start' => '', 'event_content' => '', 'url' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);
        $this->view->with('event_content', isset($old_options['content']) ? $old_options['content'] : '');
        $this->view->with('external_url', isset($old_options['url']) ? $old_options['url'] : '');
    }

    /**
     * Set filter
     *
     * @param  string   $id
     * @param  mixed    $value
     * @return Response
     */
    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_calendar'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    /**
     * Listing
     *
     * @param  string  $id
     * @return Response
     */
    public function action_index($id = null)
    {
        if (!Auth::can('admin_calendar'))
            return Response::error(403);

        $this->page->set_title('Kalendarz');
        $this->page->breadcrumb_append('Kalendarz', 'admin/calendar/index');

        $grid = $this->make_grid();

        $result = $grid->handle_index($id);

        if ($result instanceof Ionic\View)
        {
            $this->view = $result;
        }
        elseif ($result instanceof Laravel\Response)
        {
            return $result;
        }
    }

    /**
     * Sorting
     *
     * @param  string   $item
     * @return Response
     */
    public function action_sort($item)
    {
        if (!Auth::can('admin_calendar'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    /**
     * Make grid
     *
     * @return Ionic\Grid
     */
    protected function make_grid()
    {
        $grid = new Ionic\Grid('calendar', 'Kalendarz', 'admin/calendar');

        if (Auth::can('admin_calendar_add'))
        {
            $grid->add_button('Dodaj wydarzenie', 'admin/calendar/add', 'add-button');
            $grid->add_button('Dodaj źródło wydarzeń', 'admin/calendar/add_source', 'add-button');
        }

        if (Auth::can('admin_calendar_delete'))
            $grid->add_action('Usuń', 'admin/calendar/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        if (Auth::can('admin_calendar_edit'))
            $grid->add_action('Edytuj', 'admin/calendar/edit/%d', 'edit-button');

        $grid->add_selects(array('calendar.date_end'));

        $options = array('_all_' => 'Wszystkie', 'event' => 'Wydarzenie');

        foreach (Event::fire('ionic.calendar_handler') as $r)
        {
            if (is_array($r))
            {
                foreach (array_keys($r) as $m)
                {
                    $options[$m] = 'Źródło (moduł '.$m.')';
                }
            }
        }

        $grid->add_column('id', 'ID', 'id', null, 'calendar.id');
        $grid->add_column('title', 'Tytuł', 'title', 'calendar.title', 'calendar.title');
        $grid->add_column('date', 'Data', function($obj) {
            if ($obj->handler == 'event')
                return ionic_date($obj->date_start, 'short');

            return ionic_date($obj->date_start, 'short').' do '.ionic_date($obj->date_end, 'short');
        }, 'calendar.date_start', 'calendar.date_start');
        $grid->add_column('handler', 'Rodzaj', function($obj) use ($options) {
            return isset($options[$obj->handler]) ? $options[$obj->handler] : 'Nieznane';
        }, 'calendar.handler', 'calendar.handler');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('title', 'Tytuł', 'calendar.title');
        $grid->add_filter_date('date_start', 'Data rozpoczęcia', 'calendar.date_start');
        $grid->add_filter_select('handler', 'Rodzaj', $options, '_all_');

        return $grid;
    }

}
