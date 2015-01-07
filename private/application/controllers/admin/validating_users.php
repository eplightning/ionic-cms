<?php

class Admin_Validating_users_Controller extends Admin_Controller {

    public function action_accept($id)
    {
        if (!Auth::can('admin_validating_users') || !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('validating_users')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        $check = DB::table('users')->where('username', '=', $id->username)->or_where('email', '=', $id->email)->or_where('display_name', '=', $id->display_name)->first('id');

        if ($check)
        {
            $this->notice('Takie konto już istnieje w głównej bazie danych. Możliwe ,że zostało dodane ręcznie w panelu administracyjnym. Akceptacja tego konta jest niewykonalna.');

            return Redirect::to('admin/validating_users/index');
        }

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/validating_users/index');
        }

        // First users table
        $id_new = DB::table('users')->insert_get_id(array(
            'username'     => $id->username,
            'password'     => $id->password,
            'email'        => $id->email,
            'display_name' => $id->display_name,
            'group_id'     => 2,
            'slug'         => ionic_tmp_slug('users')
                ));

        DB::table('users')->where('id', '=', $id_new)->update(array('slug' => ionic_find_slug($id->display_name, $id_new, 'users', 30)));

        // Profile
        DB::table('profiles')->insert(array(
            'user_id'    => $id_new,
            'ip'         => $id->ip,
            'created_at' => $id->created_at
        ));

        ionic_mail(1, $id->email, array(':name'    => $id->display_name, ':admin'   => $this->user->display_name, ':website' => \URL::to('/')));

        DB::table('validating_users')->where('id', '=', $id->id)->delete();

        $this->notice('Pomyślnie zaakceptowano to konto');
        $this->log(sprintf('Akceptowano konto: %s', $id->display_name));
        return Redirect::to('admin/validating_users/index');
    }

    /**
     * Autocompletion
     *
     * @param  string   $id
     * @return Response
     */
    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_validating_users'))
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
        if (!Auth::can('admin_validating_users_delete') || !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('validating_users')->where('id', '=', (int) $id)->first('*');
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
                return Redirect::to('admin/validating_users/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('validating_users')->where('id', '=', $id->id)->delete();

        $this->log(sprintf('Odrzucono nieaktywowanego użytkownika: %s', $id->display_name));

        if (!Request::ajax())
        {
            $this->notice('Użytkownik usunięty pomyślnie');
            return Redirect::to('admin/validating_users/index');
        }
        else
        {
            return Response::json(array('status' => true));
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
        if (!Auth::can('admin_validating_users'))
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
        if (!Auth::can('admin_validating_users'))
            return Response::error(403);

        $this->page->set_title('Nieaktywowani użytkownicy');
        $this->page->breadcrumb_append('Nieaktywowani użytkownicy', 'admin/validating_users/index');

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
        if (!Auth::can('admin_validating_users_multi'))
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
        if (!Auth::can('admin_validating_users'))
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
        $grid = new Ionic\Grid('validating_users', 'Nieaktywowani użytkownicy', 'admin/validating_users');

        $grid->add_action('Akceptuj', 'admin/validating_users/accept/%d', 'accept-button');
        if (Auth::can('admin_validating_users_delete'))
            $grid->add_action('Usuń', 'admin/validating_users/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_date('created_at', 'Data założenia');
        $grid->add_filter_search('display_name', 'Nazwa użytkownika');
        $grid->add_filter_search('email', 'E-mail');
        $grid->add_filter_search('ip', 'Adres IP');

        $grid->add_selects(array('validating_users.username'));

        $grid->add_column('id', 'ID', 'id', null, 'validating_users.id');
        $grid->add_column('display_name', 'Nazwa (login)', function($obj) {
                    return $obj->display_name.' <small>('.$obj->username.')</small>';
                }, 'validating_users.display_name', 'validating_users.display_name');
        $grid->add_column('email', 'E-mail', 'email', 'validating_users.email', 'validating_users.email');
        $grid->add_column('created_at', 'Data', 'created_at', 'validating_users.created_at', 'validating_users.created_at');
        $grid->add_column('ip', 'Adres IP', 'ip', 'validating_users.ip', 'validating_users.ip');

        if (Auth::can('admin_validating_users_multi') && Auth::can('admin_validating_users_delete'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczonych', function($ids) use ($id) {
                $affected = DB::table('validating_users')->where_in('id', $ids)->delete();

                if ($affected)
                    \Model\Log::add('Masowo usunięto nieaktywowane konta ('.$affected.')', $id);
            });
        }

        return $grid;
    }

}