<?php

/**
 * User management
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Users_Controller extends Admin_Controller {

    /**
     * Add action
     *
     * @return Response
     */
    public function action_add()
    {
        if (!Auth::can('admin_users_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('username'     => '', 'display_name' => '', 'email'        => '', 'password'     => '', 'group_id'     => '');
            $raw_data = array_merge($raw_data, Input::only(array('username', 'display_name', 'email', 'password', 'group_id')));

            $rules = array(
                'username'     => 'required|max:20|match:!^[\pL\pN\s]+$!u|unique:users,username',
                'display_name' => 'required|max:20|match:!^[\pL\pN\s]+$!u|unique:users,display_name',
                'email'        => 'required|max:70|email|unique:users,email',
                'password'     => 'required',
                'group_id'     => 'required|exists:groups,id',
                'slug'         => ionic_tmp_slug('users')
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/users/add')->with_errors($validator)
                                ->with_input('only', array('username', 'display_name', 'email', 'password', 'group_id'));
            }
            else
            {
                $prepared_data = array(
                    'username'     => HTML::specialchars($raw_data['username']),
                    'display_name' => HTML::specialchars($raw_data['display_name']),
                    'email'        => HTML::specialchars($raw_data['email']),
                    'password'     => Hash::make($raw_data['password']),
                    'group_id'     => $raw_data['group_id'],
                    'slug'         => ionic_tmp_slug('users')
                );

                if (!Auth::can('admin_root'))
                {
                    // Get groups he can't touch
                    $roots = Model\Group::with_role('admin_root');

                    if (in_array((int) $prepared_data['group_id'], $roots))
                    {
                        $this->notice('Nie możesz dodać root administratora!');
                        return Redirect::to('admin/users/index');
                    }
                }

                $obj_id = DB::table('users')->insert_get_id($prepared_data);

                DB::table('profiles')->insert(array(
                    'user_id'    => $obj_id,
                    'ip'         => \Request::ip(),
                    'created_at' => date('Y-m-d H:i:s')
                ));

                DB::table('users')->where('id', '=', $obj_id)->update(array('slug' => ionic_find_slug($prepared_data['display_name'], $obj_id, 'users', 30)));

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano użytkownika: %s', $prepared_data['display_name']));
                return Redirect::to('admin/users/index');
            }
        }

        $this->page->set_title('Dodawanie użytkownika');

        $this->page->breadcrumb_append('Użytkownicy', 'admin/users/index');
        $this->page->breadcrumb_append('Dodawanie użytkownika', 'admin/users/add');

        $this->view = View::make('admin.users.add');

        $old_data = array('username'     => '', 'display_name' => '', 'email'        => '', 'password'     => '', 'group_id'     => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $related = array();

        foreach (DB::table('groups')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $this->view->with('related_group_id', $related);
    }

    /**
     * Autocompletion
     *
     * @param  string   $id
     * @return Response
     */
    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_users'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    /**
     * Ban user
     *
     * @param string $id
     * @return Response
     */
    public function action_ban($id)
    {
        if (!Auth::can('admin_users') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('profiles')->join('users', 'profiles.user_id', '=', 'users.id')->where('user_id', '=', (int) $id)->first(array('users.id', 'users.display_name', 'profiles.is_banned', 'users.group_id'));
        if (!$id)
            return Response::error(500);

        if (!Auth::can('admin_root'))
        {
            // Get groups he can't touch
            $roots = Model\Group::with_role('admin_root');

            if (in_array($id->group_id, $roots))
            {
                $this->notice('Nie możesz wykonać tej akcji na root administratorze!');
                return Redirect::to('admin/users/index');
            }
        }

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/users/index');
        }

        DB::table('profiles')->where('user_id', '=', $id->id)->update(array('is_banned' => ($id->is_banned == 1 ? 0 : 1)));

        $this->notice('Użytkownik pomyślnie zbanowany/odbanowany');
        $this->log(sprintf('Zbanowano/odbanowano użytkownika: %s', $id->display_name));
        return Redirect::to('admin/users/index');
    }

    /**
     * Delete
     *
     * @param  string   $id
     * @return Response
     */
    public function action_delete($id)
    {
        if (!Auth::can('admin_users_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('users')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if ($id->id == $this->user->id)
        {
            $this->notice('Nie możesz usunąć swojego własnego konta!');
            return Redirect::to('admin/users/index');
        }

        if (!Auth::can('admin_root'))
        {
            // Get groups he can't touch
            $roots = Model\Group::with_role('admin_root');

            if (in_array($id->group_id, $roots))
            {
                $this->notice('Nie możesz wykonać tej akcji na root administratorze!');
                return Redirect::to('admin/users/index');
            }
        }

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/users/index');
        }

        DB::table('users')->where('id', '=', $id->id)->delete();

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunięto użytkownika: %s', $id->display_name));
        return Redirect::to('admin/users/index');
    }

    /**
     * Edit
     *
     * @param  string   $id
     * @return Response
     */
    public function action_edit($id)
    {
        if (!Auth::can('admin_users_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('users')->join('profiles', 'profiles.user_id', '=', 'users.id')->where('id', '=', (int) $id)->first(array('users.*', 'profiles.points', 'profiles.real_name', 'profiles.avatar', 'profiles.bet_points'));
        if (!$id)
            return Response::error(500);

        if (!Auth::can('admin_root'))
        {
            // Get groups he can't touch
            $roots = Model\Group::with_role('admin_root');

            if (in_array($id->group_id, $roots))
            {
                $this->notice('Nie możesz wykonać tej akcji na root administratorze!');
                return Redirect::to('admin/users/index');
            }
        }

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('username'     => '', 'display_name' => '', 'email'        => '', 'password'     => '', 'group_id'     => '', 'slug'         => '', 'points'       => '', 'real_name'    => '', 'avatar'       => '', 'bet_points'   => '');
            $raw_data = array_merge($raw_data, Input::only(array('username', 'display_name', 'email', 'password', 'group_id', 'slug', 'points', 'real_name', 'avatar', 'bet_points')));

            $rules = array(
                'username'     => 'required|max:20|match:!^[\pL\pN\s]+$!u|unique:users,username,'.$id->id.'',
                'display_name' => 'required|max:20|match:!^[\pL\pN\s]+$!u|unique:users,display_name,'.$id->id.'',
                'email'        => 'required|max:70|email|unique:users,email,'.$id->id.'',
                'group_id'     => 'required|exists:groups,id',
                'slug'         => 'required|max:30|alpha_dash|unique:users,slug,'.$id->id.'',
                'points'       => 'integer',
                'bet_points'   => 'integer',
                'real_name'    => 'max:127|match:!^[\pL\pN\s]+$!u'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/users/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('username', 'display_name', 'email', 'password', 'group_id', 'slug', 'points', 'real_name', 'avatar', 'bet_points'));
            }
            else
            {
                $prepared_data = array(
                    'username'     => HTML::specialchars($raw_data['username']),
                    'slug'         => HTML::specialchars($raw_data['slug']),
                    'display_name' => HTML::specialchars($raw_data['display_name']),
                    'email'        => HTML::specialchars($raw_data['email']),
                    'group_id'     => $raw_data['group_id'],
                );

                if (!Auth::can('admin_root'))
                {
                    // Get groups he can't touch
                    $roots = Model\Group::with_role('admin_root');

                    if (in_array((int) $prepared_data['group_id'], $roots))
                    {
                        $this->notice('Nie możesz dodać root administratora!');
                        return Redirect::to('admin/users/index');
                    }
                }

                if (Input::has('password') and Input::get('password'))
                {
                    $prepared_data['password'] = Hash::make(Input::get('password'));
                }

                \DB::table('users')->where('id', '=', $id->id)->update($prepared_data);

                $profile_data = array();

                if (isset($raw_data['points']))
                {
                    $profile_data['points'] = (int) $raw_data['points'];
                }

                if (isset($raw_data['bet_points']))
                {
                    $profile_data['bet_points'] = (int) $raw_data['bet_points'];
                }

                if (isset($raw_data['real_name']))
                {
                    $profile_data['real_name'] = HTML::specialchars($raw_data['real_name']);
                }

                if ($raw_data['avatar'] == '1' and $id->avatar)
                {
                    if (file_exists(path('public').'upload'.DS.'avatars'.DS.$id->avatar))
                        @unlink(path('public').'upload'.DS.'avatars'.DS.$id->avatar);

                    $profile_data['avatar'] = '';
                }

                if (!empty($profile_data))
                {
                    DB::table('profiles')->where('user_id', '=', $id->id)->update($profile_data);
                }

                Model\Field::update_fields($id->id);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono użytkownika: %s', $prepared_data['display_name']));
                return Redirect::to('admin/users/index');
            }
        }

        $this->page->set_title('Edycja użytkownika');

        $this->page->breadcrumb_append('Użytkownicy', 'admin/users/index');
        $this->page->breadcrumb_append('Edycja użytkownika', 'admin/users/edit/'.$id->id);

        $this->view = View::make('admin.users.edit');

        $old_data = array('username'     => '', 'display_name' => '', 'email'        => '', 'password'     => '', 'group_id'     => '', 'bet_points'   => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);

        $related = array();

        foreach (DB::table('groups')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $this->view->with('related_group_id', $related);

        $this->view->with('custom_fields', Model\Field::get_fields($id->id));
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
        if (!Auth::can('admin_users'))
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
        if (!Auth::can('admin_users'))
            return Response::error(403);

        $this->page->set_title('Użytkownicy');
        $this->page->breadcrumb_append('Użytkownicy', 'admin/users/index');

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
     * Get user IPs
     *
     * @param string $id
     * @return Response
     */
    public function action_ip($id)
    {
        if (!Auth::can('admin_users') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('users')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        $list = array();

        // Register
        $register = DB::table('profiles')->where('user_id', '=', $id->id)->first(array('ip'));

        if ($register)
        {
            $list[] = array('ip'    => $register->ip, 'where' => 'Rejestracja');
        }

        // Comments
        $register = DB::table('comments')->where('user_id', '=', $id->id)->distinct()->get('ip');

        foreach ($register as $r)
        {
            $list[] = array('ip'    => $r->ip, 'where' => 'Komentarze');
        }

        // Actions
        $register = DB::table('logs')->where('user_id', '=', $id->id)->distinct()->get('ip');

        foreach ($register as $r)
        {
            $list[] = array('ip'    => $r->ip, 'where' => 'Logi administracyjne');
        }

        // Sort
        usort($list, function($a, $b) {
                    if ($a['ip'] == $b['ip'])
                        return 0;

                    return ($a['ip'] > $b['ip']) ? 1 : -1;
                });

        $this->page->set_title('Adresy IP');
        $this->page->breadcrumb_append('Użytkownicy', 'admin/users/index');
        $this->page->breadcrumb_append('Adresy IP', 'admin/users/ip/'.$id->id);

        $this->view = View::make('admin.users.ip', array('list' => $list));
    }

    /**
     * Do multiaction
     *
     * @param  string   $name
     * @return Response
     */
    public function action_multiaction($name)
    {
        if (!Auth::can('admin_users_multi'))
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
        if (!Auth::can('admin_users'))
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
        $grid = new Ionic\Grid('users', 'Użytkownicy', 'admin/users');

        if (Auth::can('admin_users_add'))
            $grid->add_button('Dodaj użytkownika', 'admin/users/add', 'add-button');

        $grid->add_column('id', 'ID', 'id', null, 'users.id');
        $grid->add_column('display_name', 'Nazwa', 'display_name', 'users.display_name', 'users.display_name');
        $grid->add_column('group', 'Grupa', 'name', 'groups.name', 'groups.name');
        $grid->add_column('created_at', 'Data rejestracji', 'created_at', 'profiles.created_at', 'profiles.created_at');
        $grid->add_column('is_banned', 'Ban', function($data) {
                    if ($data->is_banned == '1')
                        return '<img style="margin: 0px auto; display: block" src="public/img/icons/accept.png" alt="" />';
                    return '';
                }, 'profiles.is_banned', 'profiles.is_banned');

        $grid->add_related('profiles', 'profiles.user_id', '=', 'users.id');
        $grid->add_related('groups', 'groups.id', '=', 'users.group_id');

        if (Auth::can('admin_users_delete'))
        {
            $grid->add_action('Usuń', 'admin/users/delete/%d', 'delete-button');
        }

        if (Auth::can('admin_users_edit'))
        {
            $grid->add_action('Edytuj', 'admin/users/edit/%d', 'edit-button');
        }

        $grid->add_action('Pokaż IP', 'admin/users/ip/%d', 'display-button');
        $grid->add_action('Zbanuj/odbanuj', 'admin/users/ban/%d', 'lock-button');

        if (Auth::can('admin_users_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;
            $is_root = Auth::can('admin_root');
            $groups = ($is_root ? Model\Group::with_role('admin_root', false) : array());


            $grid->add_multi_action('ban', 'Zbanuj', function($ids) use ($id, $is_root, $groups) {
                        if (!$is_root and !empty($groups))
                        {
                            $affected = DB::table('profiles')
                                            ->join('users', 'profiles.user_id', '=', 'users.id')
                                            ->where_not_in('users.group_id', $groups)->where_in('user_id', $ids)->update(array('is_banned' => '1'));
                        }
                        else
                        {
                            $affected = DB::table('profiles')->where_in('user_id', $ids)->update(array('is_banned' => '1'));
                        }

                        if ($affected > 0)
                            \Model\Log::add('Masowo zbanowano użytkowników ('.$affected.')', $id);
                    });

            $grid->add_multi_action('unban', 'Odbanuj', function($ids) use ($id, $is_root, $groups) {
                        if (!$is_root and !empty($groups))
                        {
                            $affected = DB::table('profiles')
                                            ->join('users', 'profiles.user_id', '=', 'users.id')
                                            ->where_not_in('users.group_id', $groups)->where_in('user_id', $ids)->update(array('is_banned' => '0'));
                        }
                        else
                        {
                            $affected = DB::table('profiles')->where_in('user_id', $ids)->update(array('is_banned' => '0'));
                        }

                        if ($affected > 0)
                            \Model\Log::add('Masowo odbanowano użytkowników ('.$affected.')', $id);
                    });

            if (Auth::can('admin_users_delete'))
            {
                $grid->add_multi_action('delete', 'Usuń', function($ids) use ($id, $is_root, $groups) {

                            if (!$is_root and !empty($groups))
                            {
                                $affected = DB::table('users')
                                                ->where_not_in('group_id', $groups)->where_in('id', $ids)->delete();
                            }
                            else
                            {
                                $affected = DB::table('users')->where_in('id', $ids)->delete();
                            }

                            if ($affected > 0)
                                \Model\Log::add('Masowo usunięto użytkowników ('.$affected.')', $id);
                        });
            }
        }

        $grid->add_filter_perpage(array(20, 30, 50));

        $grid->add_filter_select('banned', 'Zbanowani', array('_all_' => 'Wszyscy', '1'     => 'Tak', '0'     => 'Nie'), '_all_', 'profiles.is_banned');

        $grid->add_filter_date('created_at', 'Data rejestracji', 'profiles.created_at');

        $grid->add_filter_search('display_name', 'Nazwa użytkownika', 'users.display_name');

        $grid->add_filter_search('email', 'E-mail', 'users.email');

        $grid->add_selects(array('users.email'));

        $grid->add_filter_autocomplete('group', 'Grupa', function($str) {
                    $groups = DB::table('groups')->take(20)->where('name', 'like', str_replace('%', '', $str).'%')->get('name');

                    $result = array();

                    foreach ($groups as $g)
                    {
                        $result[] = $g->name;
                    }

                    return $result;
                }, 'groups.name');

        return $grid;
    }

}