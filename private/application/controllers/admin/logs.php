<?php

/**
 * Logs
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Logs_Controller extends Admin_Controller {

    /**
     * Autocompletion
     *
     * @param  string   $id
     * @return Response
     */
    public function action_autocomplete($id)
    {
        // ACL
        if (!Auth::can('admin_logs'))
            return Response::error(403);

        // Make grid
        $grid = $this->make_grid();

        // Handle
        return $grid->handle_autocomplete($id);
    }

    /**
     * Clear logs
     *
     * @return Response
     */
    public function action_clear()
    {
        // ACL
        if (!Auth::can('admin_logs_delete'))
            return Response::error(403);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/logs/index');
        }

        // Clear
        Model\Log::clear();

        // Notice and redirect
        $this->notice('Pomyślnie wyczyszczono logi');
        return Redirect::to('admin/logs/index');
    }

    /**
     * Delete
     *
     * @param  string   $id
     * @return Response
     */
    public function action_delete($log)
    {
        // ACL
        if (!Auth::can('admin_logs_delete') or !ctype_digit($log))
            return Response::error(403);

        // Type
        if (!Request::ajax() or !Config::get('advanced.admin_prefer_ajax', true))
        {
            if (!($status = $this->confirm()))
            {
                return;
            }
            elseif ($status == 2)
            {
                return Redirect::to('admin/logs/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        $log = DB::table('logs')->join('users', 'users.id', '=', 'logs.user_id')->where('logs.id', '=', (int) $log)->first(array('logs.id', 'users.display_name'));

        if (!$log)
            return Response::error(500);

        DB::table('logs')->where('id', '=', $log->id)->delete();

        $this->log('Usunął wpis w logach użytkownika '.$log->display_name);

        if (!Request::ajax())
        {
            $this->notice('Pomyślnie usunięto wpis');
            return Redirect::to('admin/logs/index');
        }
        else
        {
            return Response::json(array('status' => true));
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
        // ACL
        if (!Auth::can('admin_logs_delete'))
            return Response::error(403);

        // Make grid
        $grid = $this->make_grid();

        // Handle
        return $grid->handle_multiaction($name);
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
        // ACL
        if (!Auth::can('admin_logs'))
            return Response::error(403);

        // Make grid
        $grid = $this->make_grid();

        // Handle
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
        // ACL
        if (!Auth::can('admin_logs'))
            return Response::error(403);

        // Title
        $this->page->set_title('Logi administracyjne');
        $this->page->breadcrumb_append('Logi administracyjne', 'admin/logs/index');

        // Make grid
        $grid = $this->make_grid();

        // Handle request
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
     * Sorting
     *
     * @param  string   $item
     * @return Response
     */
    public function action_sort($item)
    {
        // ACL
        if (!Auth::can('admin_logs'))
            return Response::error(403);

        // Make grid
        $grid = $this->make_grid();

        // Handle
        return $grid->handle_sort($item);
    }

    /**
     * Make grid
     *
     * @return Ionic\Grid
     */
    protected function make_grid()
    {
        $grid = new Ionic\Grid('logs', 'Logi administracyjne', 'admin/logs');

        $grid->add_column('id', 'ID', 'id', null, 'logs.id');
        $grid->add_column('user', 'Użytkownik', 'display_name', 'users.display_name', 'users.display_name');
        $grid->add_column('title', 'Akcja', 'title', 'logs.title', 'logs.title');
        $grid->add_column('created_at', 'Data akcji', 'created_at', 'logs.created_at', 'logs.created_at');
        $grid->add_column('ip', 'IP', 'ip', 'logs.ip', 'logs.ip');

        if (Auth::can('admin_logs_delete'))
        {
            $grid->add_button('Wyczyść logi', 'admin/logs/clear', 'clear-button');
            $grid->add_action('Usuń', 'admin/logs/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);
            $grid->enable_checkboxes();

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function(array $ids) use ($id) {
                $affected = DB::table('logs')->where_in('id', $ids)->delete();

                if ($affected)
                    \Model\Log::add('Masowo usunięto logi ('.$affected.')', $id);
            });
        }

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_date('created_at', 'Data akcji', 'logs.created_at');
        $grid->add_filter_search('title', 'Akcja', 'logs.title');
        $grid->add_filter_search('username', 'Nazwa użytkownika', 'users.display_name');
        $grid->add_filter_search('ip', 'Adres IP', 'logs.ip');

        $grid->add_related('users', 'users.id', '=', 'logs.user_id');

        return $grid;
    }

}
