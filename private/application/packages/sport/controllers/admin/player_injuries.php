<?php

class Admin_Player_injuries_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_player_injuries_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('player_id'     => '', 'injury'        => '', 'recovery_date' => '');
            $raw_data = array_merge($raw_data, Input::only(array('player_id', 'injury', 'recovery_date')));

            $rules = array(
                'player_id'     => 'required|max:127|exists:players,name',
                'injury'        => 'required|max:127',
                'recovery_date' => 'match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/player_injuries/add')->with_errors($validator)
                                ->with_input('only', array('player_id', 'injury', 'recovery_date'));
            }
            else
            {
                $player = DB::table('players')->where('name', '=', $raw_data['player_id'])->first('id');

                if (!$player)
                    return Response::error(500);

                $prepared_data = array(
                    'player_id'     => $player->id,
                    'injury'        => HTML::specialchars($raw_data['injury']),
                    'recovery_date' => $raw_data['recovery_date']
                );

                $obj_id = DB::table('player_injuries')->insert_get_id($prepared_data);

                ionic_clear_cache('injuries-*');

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano kontuzję: %s', $prepared_data['injury']));
                return Redirect::to('admin/player_injuries/index');
            }
        }

        $this->page->set_title('Dodawanie kontuzji');

        $this->page->breadcrumb_append('Kontuzje', 'admin/player_injuries/index');
        $this->page->breadcrumb_append('Dodawanie kontuzji', 'admin/player_injuries/add');

        $this->view = View::make('admin.player_injuries.add');

        $old_data = array('player_id'     => '', 'injury'        => '', 'recovery_date' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_player_injuries'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_player_injuries_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('player_injuries')->where('id', '=', (int) $id)->first('*');
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
                return Redirect::to('admin/player_injuries/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('player_injuries')->where('id', '=', $id->id)->delete();

        ionic_clear_cache('injuries-*');

        $this->log(sprintf('Usunięto kontuzję: %s', $id->injury));

        if (!Request::ajax())
        {
            $this->notice('Kontuzja usunięta pomyślnie');
            return Redirect::to('admin/player_injuries/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_player_injuries_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('player_injuries')->join('players', 'players.id', '=', 'player_injuries.player_id')
                        ->where('player_injuries.id', '=', (int) $id)->first(array('player_injuries.*', 'players.name'));
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('player_id'     => '', 'injury'        => '', 'recovery_date' => '');
            $raw_data = array_merge($raw_data, Input::only(array('player_id', 'injury', 'recovery_date')));

            $rules = array(
                'player_id'     => 'required|max:127|exists:players,name',
                'injury'        => 'required|max:127',
                'recovery_date' => 'match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/player_injuries/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('player_id', 'injury', 'recovery_date'));
            }
            else
            {
                $player = DB::table('players')->where('name', '=', $raw_data['player_id'])->first('id');

                if (!$player)
                    return Response::error(500);

                $prepared_data = array(
                    'player_id'     => $player->id,
                    'injury'        => HTML::specialchars($raw_data['injury']),
                    'recovery_date' => $raw_data['recovery_date']
                );

                \DB::table('player_injuries')->where('id', '=', $id->id)->update($prepared_data);

                ionic_clear_cache('injuries-*');

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono kontuzję: %s', $prepared_data['injury']));
                return Redirect::to('admin/player_injuries/index');
            }
        }

        $this->page->set_title('Edycja kontuzji');

        $this->page->breadcrumb_append('Kontuzje', 'admin/player_injuries/index');
        $this->page->breadcrumb_append('Edycja kontuzji', 'admin/player_injuries/edit/'.$id->id);

        $this->view = View::make('admin.player_injuries.edit');

        $old_data = array('player_id'     => '', 'injury'        => '', 'recovery_date' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_player_injuries'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_player_injuries'))
            return Response::error(403);

        $this->page->set_title('Kontuzje');
        $this->page->breadcrumb_append('Kontuzje', 'admin/player_injuries/index');

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
        if (!Auth::can('admin_player_injuries'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_player_injuries'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('player_injuries', 'Kontuzje', 'admin/player_injuries');

        $grid->add_related('players', 'players.id', '=', 'player_injuries.player_id');

        $grid->add_column('id', 'ID', 'id', null, 'player_injuries.id');
        $grid->add_column('name', 'Zawodnik', 'name', 'players.name', 'players.name');
        $grid->add_column('injury', 'Kontuzja', 'injury', 'player_injuries.injury', 'player_injuries.injury');
        $grid->add_column('recovery_date', 'Data wyg.', function($obj) {
            return ionic_date_special($obj->recovery_date);
        }, 'player_injuries.recovery_date', 'player_injuries.recovery_date');

        if (Auth::can('admin_player_injuries_add'))
            $grid->add_button('Dodaj kontuzję', 'admin/player_injuries/add', 'add-button');
        if (Auth::can('admin_player_injuries_edit'))
            $grid->add_action('Edytuj', 'admin/player_injuries/edit/%d', 'edit-button');
        if (Auth::can('admin_player_injuries_delete'))
            $grid->add_action('Usuń', 'admin/player_injuries/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        if (Auth::can('admin_player_injuries_delete') and Auth::can('admin_player_injuries_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                $affected = DB::table('player_injuries')->where_in('id', $ids)->delete();

                if ($affected > 0)
                    Model\Log::add('Masowo usunięto kontuzje ('.$affected.')', $id);

                ionic_clear_cache('injuries-*');
            });
        }

        $grid->add_filter_perpage(array(20, 30, 50));

        $grid->add_filter_autocomplete('name', 'Zawodnik', function($str) {
            $us = DB::table('players')->take(20)->where('name', 'like', str_replace('%', '', $str).'%')->get('name');

            $result = array();

            foreach ($us as $u)
            {
                $result[] = $u->name;
            }

            return $result;
        }, 'players.name');

        $grid->add_filter_date('recovery_date', 'Data wygaśnięcia');

        return $grid;
    }

}
