<?php

/**
 * Custom fields
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Fields_Controller extends Admin_Controller {

    /**
     * Add action
     *
     * @return Response
     */
    public function action_add()
    {
        if (!Auth::can('admin_fields_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'       => '', 'description' => '', 'type'        => '', 'default'     => '', 'options'     => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'description', 'type', 'default', 'options')));

            $rules = array(
                'title'       => 'required|max:127',
                'description' => 'required|max:255',
                'type'        => 'required|in:text,number,textarea,select,checkbox,date',
                'default'     => 'max:255'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/fields/add')->with_errors($validator)
                                ->with_input('only', array('title', 'description', 'type', 'default', 'options'));
            }
            else
            {
                $prepared_data = array(
                    'title'       => HTML::specialchars($raw_data['title']),
                    'description' => HTML::specialchars($raw_data['description']),
                    'type'        => $raw_data['type'],
                    'default'     => HTML::specialchars($raw_data['default']),
                    'options'     => HTML::specialchars($raw_data['options'])
                );

                if ($prepared_data['type'] == 'select')
                {
                    $options = array();

                    foreach (explode("\n", ionic_normalize_lines($prepared_data['options'])) as $line)
                    {
                        $line = explode('=', $line, 2);

                        if (count($line) != 2)
                            continue;

                        $options[$line[0]] = $line[1];
                    }

                    $prepared_data['options'] = serialize($options);

                    if (in_array($prepared_data['default'], $options))
                    {
                        $prepared_data['default'] = array_search($prepared_data['default'], $options);
                    }
                }
                elseif ($prepared_data['type'] == 'number')
                {
                    $prepared_data['default'] = (int) $prepared_data['default'];
                }
                elseif ($prepared_data['type'] == 'date')
                {
                    if ($prepared_data['default'])
                    {
                        $date = strtotime($prepared_data['default']);

                        if ($date)
                        {
                            $prepared_data['default'] = date('Y-m-d', $date);
                        }
                        else
                        {
                            $prepared_data['default'] = '';
                        }
                    }
                }

                $obj_id = DB::table('fields')->insert_get_id($prepared_data);

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodał pole: %s', $prepared_data['title']));
                return Redirect::to('admin/fields/index');
            }
        }

        $this->page->set_title('Dodawanie pola');

        $this->page->breadcrumb_append('Własne pola', 'admin/fields/index');
        $this->page->breadcrumb_append('Dodawanie pola', 'admin/fields/add');

        $this->view = View::make('admin.fields.add');

        $old_data = array('title'       => '', 'description' => '', 'type'        => '', 'default'     => '', 'options'     => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);
    }

    /**
     * Autocompletion
     *
     * @param  string   $id
     * @return Response
     */
    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_fields'))
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
        if (!Auth::can('admin_fields_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('fields')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/fields/index');
        }

        DB::table('fields')->where('id', '=', $id->id)->delete();

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunął pole: %s', $id->title));
        return Redirect::to('admin/fields/index');
    }

    /**
     * Edit
     *
     * @param  string   $id
     * @return Response
     */
    public function action_edit($id)
    {
        if (!Auth::can('admin_fields_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('fields')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'       => '', 'description' => '', 'type'        => '', 'default'     => '', 'options'     => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'description', 'type', 'default', 'options')));

            $rules = array(
                'title'       => 'required|max:127',
                'description' => 'required|max:255',
                'type'        => 'required|in:text,number,textarea,select,checkbox,date',
                'default'     => 'max:255',
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/fields/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('title', 'description', 'type', 'default', 'options'));
            }
            else
            {
                $prepared_data = array(
                    'title'       => HTML::specialchars($raw_data['title']),
                    'description' => HTML::specialchars($raw_data['description']),
                    'type'        => $raw_data['type'],
                    'default'     => HTML::specialchars($raw_data['default']),
                    'options'     => HTML::specialchars($raw_data['options'])
                );

                if ($prepared_data['type'] == 'select')
                {
                    $options = array();

                    foreach (explode("\n", ionic_normalize_lines($prepared_data['options'])) as $line)
                    {
                        $line = explode('=', $line, 2);

                        if (count($line) != 2)
                            continue;

                        $options[$line[0]] = $line[1];
                    }

                    $prepared_data['options'] = serialize($options);

                    if (in_array($prepared_data['default'], $options))
                    {
                        $prepared_data['default'] = array_search($prepared_data['default'], $options);
                    }
                }
                elseif ($prepared_data['type'] == 'number')
                {
                    $prepared_data['default'] = (int) $prepared_data['default'];
                }
                elseif ($prepared_data['type'] == 'date')
                {
                    if ($prepared_data['default'])
                    {
                        $date = strtotime($prepared_data['default']);

                        if ($date)
                        {
                            $prepared_data['default'] = date('Y-m-d', $date);
                        }
                        else
                        {
                            $prepared_data['default'] = '';
                        }
                    }
                }

                \DB::table('fields')->where('id', '=', $id->id)->update($prepared_data);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmienił pole: %s', $prepared_data['title']));
                return Redirect::to('admin/fields/index');
            }
        }

        $this->page->set_title('Edycja pola');

        $this->page->breadcrumb_append('Własne pola', 'admin/fields/index');
        $this->page->breadcrumb_append('Edycja pola', 'admin/fields/edit/'.$id->id);

        $this->view = View::make('admin.fields.edit');

        $old_data = array('title'       => '', 'description' => '', 'type'        => '', 'default'     => '', 'options'     => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);

        if ($id->type == 'select' and $id->options)
        {
            $options = array();
            $uns = unserialize($id->options);

            if (!empty($uns))
            {
                foreach ($uns as $k => $v)
                {
                    $options[] = $k.'='.$v;
                }

                $this->view->with('options', implode("\n", $options));
            }
            else
            {
                $this->view->with('options', $id->options);
            }
        }
        else
        {
            $this->view->with('options', $id->options);
        }
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
        if (!Auth::can('admin_fields'))
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
        if (!Auth::can('admin_fields'))
            return Response::error(403);

        $this->page->set_title('Własne pola');
        $this->page->breadcrumb_append('Własne pola', 'admin/fields/index');

        $grid = $this->make_grid();

        $result = $grid->handle_index($id);

        if ($result instanceof View)
        {
            $this->view = $result;
        }
        elseif ($result instanceof Response)
        {
            return $result;
        }
    }

    /**
     * Do multiaction
     *
     * @param  string   $name
     * @return Response
     */
    public function action_multiaction($name)
    {
        if (!Auth::can('admin_fields_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    /**
     * Sorting
     *
     * @param  string   $item
     * @return Response
     */
    public function action_sort($item)
    {
        if (!Auth::can('admin_fields'))
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
        $grid = new Ionic\Grid('fields', 'Własne pola', 'admin/fields');

        if (Auth::can('admin_fields_add'))
        {
            $grid->add_button('Dodaj pole', 'admin/fields/add', 'add-button');
        }

        if (Auth::can('admin_fields_edit'))
        {
            $grid->add_action('Edytuj', 'admin/fields/edit/%d', 'edit-button');
        }

        if (Auth::can('admin_fields_delete'))
        {
            $grid->add_action('Usuń', 'admin/fields/delete/%d', 'delete-button');
        }

        $grid->add_column('id', 'ID', 'id', null, 'fields.id');
        $grid->add_column('title', 'Tytuł', 'title', 'fields.title', 'fields.title');
        $grid->add_column('type', 'Rodzaj', function($obj) {
                    $convert = array('text'     => 'Pole tekstowe', 'number'   => 'Liczba', 'textarea' => 'Duże pole tekstowe', 'select'   => 'Pole rozwijane', 'checkbox' => 'Pole wyboru', 'date'     => 'Data');

                    if (isset($convert[$obj->type]))
                        return $convert[$obj->type];

                    return 'Nieznane';
                }, 'fields.type', 'fields.type');

        if (Auth::can('admin_fields_multi') and Auth::can('admin_fields_delete'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                        $affected = DB::table('fields')->where_in('id', $ids)->delete();

                        if ($affected > 0)
                            Model\Log::add('Masowo usunięto pola ('.$affected.')', $id);
                    });
        }

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('title', 'Tytuł', 'fields.title');
        $grid->add_filter_select('type', 'Rodzaj', array('_all_'    => 'Wszystkie', 'text'     => 'Pole tekstowe', 'number'   => 'Liczba', 'textarea' => 'Duże pole tekstowe', 'select'   => 'Pole rozwijane', 'checkbox' => 'Pole wyboru', 'date'     => 'Data'), '_all_', 'fields.type');

        return $grid;
    }

}