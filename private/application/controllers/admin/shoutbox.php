<?php

class Admin_Shoutbox_Controller extends Admin_Controller {

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_shoutbox'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_shoutbox_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('shoutbox')->where('id', '=', (int) $id)->first('*');
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
                return Redirect::to('admin/shoutbox/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('shoutbox')->where('id', '=', $id->id)->delete();
        $this->log(sprintf('Usunięto wpis w shoutboxie (#%d)', $id->id));

        if (!Request::ajax())
        {
            $this->notice('Wpis usunięty pomyślnie');
            return Redirect::to('admin/shoutbox/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_shoutbox'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_shoutbox'))
            return Response::error(403);

        $this->page->set_title('Shoutbox');
        $this->page->breadcrumb_append('Shoutbox', 'admin/shoutbox/index');

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

    public function action_multiaction($name)
    {
        if (!Auth::can('admin_shoutbox_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_shoutbox'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('shoutbox', 'Shoutbox', 'admin/shoutbox');

        $grid->add_column('id', 'ID', 'id', null, 'shoutbox.id');
        $grid->add_column('display_name', 'Autor', function($obj) {
                    if ($obj->display_name)
                    {
                        return $obj->display_name;
                    }
                    else
                    {
                        return '<i>Anonim</i>';
                    }
                }, 'users.display_name', 'users.display_name');
        $grid->add_column('content', 'Treść', function($obj) {
                    return Str::limit(strip_tags($obj->content), 30);
                }, 'shoutbox.content', 'shoutbox.content');
        $grid->add_column('created_at', 'Dodano', 'created_at', 'shoutbox.created_at', 'shoutbox.created_at');
        $grid->add_column('ip', 'Adres IP', 'ip', 'shoutbox.ip', 'shoutbox.ip');

        if (Auth::can('admin_shoutbox_delete'))
            $grid->add_action('Usuń', 'admin/shoutbox/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        if (Auth::can('admin_shoutbox_delete') and Auth::can('admin_shoutbox_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                $affected = DB::table('shoutbox')->where_in('id', $ids)->delete();

                if ($affected)
                    \Model\Log::add('Masowo usunięto wpisy w shoutboxie ('.$affected.')', $id);
            });
        }

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_date('created_at', 'Data dodania');
        $grid->add_filter_search('display_name', 'Nazwa użytkownika', 'users.display_name');
        $grid->add_filter_search('ip', 'Adres IP');
        
        $grid->add_related('users', 'users.id', '=', 'shoutbox.user_id', array(), 'left');

        return $grid;
    }

}