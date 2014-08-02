<?php

class Admin_Warnings_Controller extends Admin_Controller {

    /**
     * Add action
     *
     * @return Response
     */
    public function action_add()
    {
        if (!Auth::can('admin_warnings_add'))
            return Response::error(403);

        if (!Request::forged() && Request::method() == 'POST')
        {
            $raw_data = array('user'   => '', 'reason' => '');
            $raw_data = array_merge($raw_data, Input::only(array('user', 'reason')));

            $rules = array(
                'user'   => 'required|exists:users,display_name',
                'reason' => 'required|max:255'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/warnings/add')->with_errors($validator)
                                ->with_input('only', array('user', 'reason'));
            }
            else
            {
                $user = DB::table('users')->where('display_name', '=', $raw_data['user'])->first(array('id', 'group_id', 'display_name'));

                if (!$user)
                {
                    return Redirect::to('admin/warnings/add');
                }

                if (!Auth::can('admin_root'))
                {
                    // Get groups he can't touch
                    $roots = Model\Group::with_role('admin_root');

                    if (in_array((int) $user->group_id, $roots))
                    {
                        $this->notice('Nie możesz dodać ostrzeżenia root adminowi!');
                        return Redirect::to('admin/warnings/add')->with_input('only', array('user', 'reason'));
                        ;
                    }
                }

                $prepared_data = array(
                    'user_id'    => $user->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'mod_id'     => $this->user->id,
                    'reason'     => HTML::specialchars($raw_data['reason'])
                );

                $obj_id = DB::table('warnings')->insert_get_id($prepared_data);

                Model\Warning::refresh_count($user->id);

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano ostrzeżenie użytkownikowi %s', $user->display_name));
                return Redirect::to('admin/warnings/index');
            }
        }

        $this->page->set_title('Dodawanie ostrzeżenia');

        $this->page->breadcrumb_append('Ostrzeżenia', 'admin/warnings/index');
        $this->page->breadcrumb_append('Dodawanie ostrzeżenia', 'admin/warnings/add');

        $this->view = View::make('admin.warnings.add');

        $old_data = array('user'   => '', 'reason' => '');
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
        if (!Auth::can('admin_warnings'))
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
        if (!Auth::can('admin_warnings_delete') || !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('warnings')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/warnings/index');
        }

        DB::table('warnings')->where('id', '=', $id->id)->delete();

        Model\Warning::refresh_count($id->user_id);

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunięto ostrzeżenie: %s', $id->id));
        return Redirect::to('admin/warnings/index');
    }

    /**
     * Edit
     *
     * @param  string   $id
     * @return Response
     */
    public function action_edit($id)
    {
        if (!Auth::can('admin_warnings_edit') || !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('warnings')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() && Request::method() == 'POST')
        {
            $raw_data = array('reason' => '');
            $raw_data = array_merge($raw_data, Input::only(array('reason')));

            $rules = array(
                'reason' => 'required|max:255'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/warnings/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('reason'));
            }
            else
            {
                $prepared_data = array(
                    'reason' => HTML::specialchars($raw_data['reason'])
                );

                \DB::table('warnings')->where('id', '=', $id->id)->update($prepared_data);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono ostrzeżenie: %s', $id->id));
                return Redirect::to('admin/warnings/index');
            }
        }

        $this->page->set_title('Edycja ostrzeżenia');

        $this->page->breadcrumb_append('Ostrzeżenia', 'admin/warnings/index');
        $this->page->breadcrumb_append('Edycja ostrzeżenia', 'admin/warnings/edit/'.$id->id);

        $this->view = View::make('admin.warnings.edit');

        $old_data = array('reason' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);
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
        if (!Auth::can('admin_warnings'))
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
        if (!Auth::can('admin_warnings'))
            return Response::error(403);

        $this->page->set_title('Ostrzeżenia');
        $this->page->breadcrumb_append('Ostrzeżenia', 'admin/warnings/index');

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
        if (!Auth::can('admin_warnings_multi'))
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
        if (!Auth::can('admin_warnings'))
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
        $grid = new Ionic\Grid('warnings', 'Ostrzeżenia', 'admin/warnings');

        if (Auth::can('admin_warnings_add'))
            $grid->add_button('Dodaj ostrzeżenie', 'admin/warnings/add', 'add-button');
        if (Auth::can('admin_warnings_edit'))
            $grid->add_action('Edytuj ostrzeżenie', 'admin/warnings/edit/%d', 'edit-button');
        if (Auth::can('admin_warnings_delete'))
            $grid->add_action('Usuń ostrzeżenie', 'admin/warnings/delete/%d', 'delete-button');

        $grid->add_column('id', 'ID', 'id', null, 'warnings.id');
        $grid->add_column('user', 'Użytkownik', 'display_name', 'users.display_name', 'users.display_name');
        $grid->add_column('mod', 'Wystawiający', 'mod_name', 'mod.display_name as mod_name', 'mod.display_name');
        $grid->add_column('reason', 'Powód', function($obj) {
                    return Str::limit($obj->reason, 20);
                }, 'warnings.reason', 'warnings.reason');
        $grid->add_column('created_at', 'Data', 'created_at', 'warnings.created_at', 'warnings.created_at');

        $grid->add_related('users', 'users.id', '=', 'warnings.user_id');
        $grid->add_related('users as '.DB::prefix().'mod', 'mod.id', '=', 'warnings.mod_id');

        $grid->add_filter_perpage(array(20, 30, 50));

        $grid->add_filter_autocomplete('display_name', 'Użytkownik', function($str) {
                    $us = DB::table('users')->take(20)->where('display_name', 'like', str_replace('%', '', $str).'%')->get('display_name');

                    $result = array();

                    foreach ($us as $u)
                    {
                        $result[] = $u->display_name;
                    }

                    return $result;
                }, 'users.display_name');

        $grid->add_filter_autocomplete('moderator', 'Wystawiający', function($str) {
                    $us = DB::table('users')->take(20)->where('display_name', 'like', str_replace('%', '', $str).'%')->get('display_name');

                    $result = array();

                    foreach ($us as $u)
                    {
                        $result[] = $u->display_name;
                    }

                    return $result;
                }, 'mod.display_name');

        $grid->add_filter_date('created_at', 'Data wystawienia');

        if (Auth::can('admin_warnings_delete') && Auth::can('admin_warnings_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {

                        $user_ids = array();

                        foreach (DB::table('warnings')->where_in('id', $ids)->get('user_id') as $u)
                        {
                            if (isset($user_ids[$u->user_id]))
                            {
                                $user_ids[$u->user_id]['how_much']++;
                                continue;
                            }

                            $user_ids[$u->user_id] = array('user_id'  => $u->user_id, 'how_much' => 1);
                        }

                        $affected = DB::table('warnings')->where_in('id', $ids)->delete();

                        if ($affected)
                        {
                            foreach ($user_ids as $u)
                            {
                                DB::table('profiles')->where('user_id', '=', $u['user_id'])->update(array('warnings_count' => DB::raw('warnings_count - '.$u['how_much'])));
                            }

                            \Model\Log::add('Masowo usunięto ostrzeżenia ('.$affected.')', $id);
                        }
                    });
        }

        return $grid;
    }

}