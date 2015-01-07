<?php

/**
 * Group management
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Groups_Controller extends Admin_Controller {

    /**
     * Add action
     *
     * @return Response
     */
    public function action_add()
    {
        if (!Auth::can('admin_groups_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('name'        => '', 'description' => '', 'style' => '');
            $raw_data = array_merge($raw_data, Input::only(array('name', 'description', 'style')));

            $rules = array(
                'name'        => 'required|max:127',
                'description' => 'max:255',
                'style'       => 'max:255',
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/groups/add')->with_errors($validator)
                                ->with_input('only', array('name', 'description', 'style'));
            }
            else
            {
                $prepared_data = array(
                    'name'        => HTML::specialchars($raw_data['name']),
                    'description' => HTML::specialchars($raw_data['description']),
                    'style'       => strip_tags($raw_data['style'])
                );

                $obj_id = DB::table('groups')->insert_get_id($prepared_data);

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodał grupę: %s', $prepared_data['name']));
                return Redirect::to('admin/groups/index');
            }
        }

        $this->page->set_title('Dodawanie grupy');

        $this->page->breadcrumb_append('Grupy', 'admin/groups/index');
        $this->page->breadcrumb_append('Dodawanie grupy', 'admin/groups/add');

        $this->view = View::make('admin.groups.add');

        $old_data = array('name'        => '', 'description' => '', 'style' => '');
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
        if (!Auth::can('admin_groups'))
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
        if (!Auth::can('admin_groups_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('groups')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if ($id->id == 1 or $id->id == 2)
        {
            $this->notice('Usunięcie dwóch głównych grup jest niemożliwe.');
            return Redirect::to('admin/groups/index');
        }

        if ($id->id == $this->user->group_id)
        {
            $this->notice('Nie możesz usunąć grupy ,w której aktualnie się znajdujesz.');
            return Redirect::to('admin/groups/index');
        }

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/groups/index');
        }

        DB::table('users')->where('group_id', '=', $id->id)->update(array('group_id' => 2));
        DB::table('groups')->where('id', '=', $id->id)->delete();

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunął grupę: %s', $id->name));
        return Redirect::to('admin/groups/index');
    }

    /**
     * Edit
     *
     * @param  string   $id
     * @return Response
     */
    public function action_edit($id)
    {
        if (!Auth::can('admin_groups_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('groups')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('name'        => '', 'description' => '', 'style' => '');
            $raw_data = array_merge($raw_data, Input::only(array('name', 'description', 'style')));

            $rules = array(
                'name'        => 'required|max:127',
                'description' => 'max:255',
                'style'       => 'max:255'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/groups/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('name', 'description', 'style'));
            }
            else
            {
                $prepared_data = array(
                    'name'        => HTML::specialchars($raw_data['name']),
                    'description' => HTML::specialchars($raw_data['description']),
                    'style'       => strip_tags($raw_data['style'])
                );

                \DB::table('groups')->where('id', '=', $id->id)->update($prepared_data);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono grupę: %s', $prepared_data['name']));
                return Redirect::to('admin/groups/index');
            }
        }

        $this->page->set_title('Edycja grupy');

        $this->page->breadcrumb_append('Grupy', 'admin/groups/index');
        $this->page->breadcrumb_append('Edycja grupy', 'admin/groups/edit/'.$id->id);

        $this->view = View::make('admin.groups.edit');

        $old_data = array('name'        => '', 'description' => '', 'style' => '');
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
        if (!Auth::can('admin_groups'))
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
        if (!Auth::can('admin_groups'))
            return Response::error(403);

        $this->page->set_title('Grupy');
        $this->page->breadcrumb_append('Grupy', 'admin/groups/index');

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
     * Do multiaction
     *
     * @param  string   $name
     * @return Response
     */
    public function action_multiaction($name)
    {
        if (!Auth::can('admin_groups_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    /**
     * Edit group roles
     *
     * @param  string   $id
     * @return Response
     */
    public function action_roles($id)
    {
        if (!Auth::can('admin_groups_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('groups')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        $roles = array();
        $current = Model\Group::roles($id->id);

        foreach (DB::table('roles')->get('*') as $role)
        {
            if (!isset($roles[$role->section]))
                $roles[$role->section] = array();

            $roles[$role->section][] = array('has'  => isset($current[$role->name]), 'data' => $role);
        }

        if (!Request::forged() and Request::method() == 'POST')
        {
            $to_be_removed = array();
            $to_be_added = array();
            $input_roles = Input::get('roles', array());

            foreach ($roles as $section)
            {
                foreach ($section as $role)
                {
                    if ($role['has'])
                    {
                        if (isset($input_roles[$role['data']->name]) and $input_roles[$role['data']->name] == '1')
                        {
                            continue;
                        }
                        else
                        {
                            $to_be_removed[] = $role['data']->id;
                        }
                    }
                    else
                    {
                        if (isset($input_roles[$role['data']->name]) and $input_roles[$role['data']->name] == '1')
                        {
                            $to_be_added[] = $role['data']->id;
                        }
                    }
                }
            }

            if (!empty($to_be_removed))
            {
                DB::table('permissions')->where('group_id', '=', $id->id)->where_in('role_id', $to_be_removed)->delete();
            }

            foreach ($to_be_added as $id2)
            {
                DB::table('permissions')->insert(array('group_id' => $id->id, 'role_id'  => $id2));
            }

            $this->notice('Uprawnienia zostały pomyślnie zaaktualizowane');
            $this->log(sprintf('Zmienił uprawnienia grupy: %s', $id->name));
            return Redirect::to('admin/groups/index');
        }

        $this->page->set_title('Uprawnienia');

        $this->page->breadcrumb_append('Grupy', 'admin/groups/index');
        $this->page->breadcrumb_append('Uprawnienia', 'admin/groups/roles/'.$id->id);

        $this->view = View::make('admin.groups.roles', array('roles'  => $roles, 'object' => $id));
    }

    /**
     * Sorting
     *
     * @param  string   $item
     * @return Response
     */
    public function action_sort($item)
    {
        if (!Auth::can('admin_groups'))
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
        $grid = new Ionic\Grid('groups', 'Grupy', 'admin/groups');

        if (Auth::can('admin_groups_add'))
        {
            $grid->add_button('Dodaj grupę', 'admin/groups/add', 'add-button');
        }

        if (Auth::can('admin_groups_delete'))
        {
            $grid->add_action('Usuń', 'admin/groups/delete/%d', 'delete-button');
        }

        if (Auth::can('admin_groups_edit'))
        {
            $grid->add_action('Edytuj', 'admin/groups/edit/%d', 'edit-button');
            $grid->add_action('Uprawnienia', 'admin/groups/roles/%d', 'case-button');
        }

        $grid->add_column('id', 'ID', 'id', null, 'groups.id');
        $grid->add_column('name', 'Nazwa', 'name', 'groups.name', 'groups.name');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('name', 'Nazwa', 'groups.name');

        return $grid;
    }

}