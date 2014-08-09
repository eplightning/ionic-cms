<?php

class Admin_Player_transfers_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_player_transfers_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('player_id'   => '', 'from_team'   => '', 'team_id'     => '', 'date'        => '', 'type'        => '', 'cost'        => '', 'description' => '');
            $raw_data = array_merge($raw_data, Input::only(array('player_id', 'from_team', 'team_id', 'date', 'type', 'cost', 'description')));

            $rules = array(
                'player_id' => 'required|max:127',
                'from_team' => 'max:127',
                'team_id'   => 'required|max:127',
                'date'      => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!',
                'type'      => 'integer|min:0|max:2',
                'cost'      => 'max:127',
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/player_transfers/add')->with_errors($validator)
                                ->with_input('only', array('player_id', 'from_team', 'team_id', 'date', 'type', 'cost', 'description'));
            }
            else
            {
                $prepared_data = array(
                    'player_id'   => HTML::specialchars($raw_data['player_id']),
                    'from_team'   => HTML::specialchars($raw_data['from_team']),
                    'team_id'     => HTML::specialchars($raw_data['team_id']),
                    'date'        => $raw_data['date'],
                    'type'        => (int) $raw_data['type'],
                    'cost'        => HTML::specialchars($raw_data['cost']),
                    'description' => $raw_data['description']
                );

                // To team
                $to_team = DB::table('teams')->where('name', '=', $prepared_data['team_id'])->first('id');

                if (!$to_team)
                {
                    $to_team = (int) DB::table('teams')->insert_get_id(array(
                                'name' => $prepared_data['team_id'],
                                'slug' => ionic_tmp_slug('teams')
                            ));

                    DB::table('teams')->where('id', '=', $to_team)->update(array(
                        'slug' => ionic_find_slug($prepared_data['team_id'], $to_team, 'teams')
                    ));

                    $prepared_data['team_id'] = $to_team;
                }
                else
                {
                    $prepared_data['team_id'] = (int) $to_team->id;
                }

                // Player
                $player = DB::table('players')->where('players.name', '=', $prepared_data['player_id'])
                        ->join('teams', 'teams.id', '=', 'players.team_id')
                        ->first(array('players.id', 'players.name', 'players.team_id', 'teams.name as team_name'));
                $player_name = '';

                if (!$player and empty($prepared_data['from_team']))
                {
                    $this->notice('Pole od jest wymagane w przypadku gdy taki zawodnik nie istnieje w bazie danych');

                    return Redirect::to('admin/player_transfers/add')
                                    ->with_input('only', array('player_id', 'from_team', 'team_id', 'date', 'type', 'cost', 'description'));
                }

                if ($player and $prepared_data['team_id'] == $player->team_id)
                {
                    $this->notice('Nie można transferować zawodnika do tego samego klubu');

                    return Redirect::to('admin/player_transfers/add')
                                    ->with_input('only', array('player_id', 'from_team', 'team_id', 'date', 'type', 'cost', 'description'));
                }

                if (!$player)
                {
                    $from_team = DB::table('teams')->where('name', '=', $prepared_data['from_team'])->first(array('id', 'name'));
                    $from_team_name = '';

                    if (!$from_team)
                    {
                        $from_team = (int) DB::table('teams')->insert_get_id(array(
                                    'name' => $prepared_data['from_team'],
                                    'slug' => ionic_tmp_slug('teams')
                                ));

                        DB::table('teams')->where('id', '=', $from_team)->update(array(
                            'slug' => ionic_find_slug($prepared_data['from_team'], $from_team, 'teams')
                        ));

                        $from_team_name = $prepared_data['from_team'];
                        $prepared_data['from_team'] = $from_team;
                    }
                    else
                    {
                        $from_team_name = $from_team->name;
                        $prepared_data['from_team'] = (int) $from_team->id;
                    }

                    $player = (int) DB::table('players')->insert_get_id(array(
                                'name'       => $prepared_data['player_id'],
                                'slug'       => ionic_tmp_slug('players'),
                                'team_id'    => $prepared_data['team_id'],
                                'cost'       => $prepared_data['cost'],
                                'prev_club'  => $from_team_name,
                                'is_on_loan' => ($prepared_data['type'] == 1 ? 1 : 0)
                            ));

                    DB::table('players')->where('id', '=', $player)->update(array('slug' => ionic_find_slug($prepared_data['player_id'], $player, 'players')));

                    $player_name = $prepared_data['player_id'];
                    $prepared_data['player_id'] = $player;
                }
                else
                {
                    if (Input::has('update_player') and Input::get('update_player') == '1')
                    {
                        DB::table('players')->where('id', '=', $player->id)->update(array(
                            'team_id'    => $prepared_data['team_id'],
                            'cost'       => $prepared_data['cost'],
                            'prev_club'  => $player->team_name,
                            'is_on_loan' => ($prepared_data['type'] == 1 ? 1 : 0)
                        ));
                    }

                    $player_name = $player->name;
                    $prepared_data['player_id'] = $player->id;
                    $prepared_data['from_team'] = $player->team_id;
                }

                if (!Auth::can('admin_root'))
                {
                    require_once path('app').'vendor'.DS.'htmLawed.php';

                    $prepared_data['description'] = htmLawed($prepared_data['description'], array('safe' => 1));
                }

                $obj_id = DB::table('player_transfers')->insert_get_id($prepared_data);

                ionic_clear_cache('transfers-*');

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano transfer: %s', $player_name));
                return Redirect::to('admin/player_transfers/index');
            }
        }

        $this->page->set_title('Dodawanie transferu');

        $this->page->breadcrumb_append('Transfery', 'admin/player_transfers/index');
        $this->page->breadcrumb_append('Dodawanie transferu', 'admin/player_transfers/add');

        $this->view = View::make('admin.player_transfers.add');

        $old_data = array('player_id'   => '', 'from_team'   => '', 'team_id'     => '', 'date'        => '', 'type'        => '', 'cost'        => '', 'description' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        Ionic\Editor::init();
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_player_transfers'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_player_transfers_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('player_transfers')->join('players', 'players.id', '=', 'player_transfers.player_id')
                        ->where('player_transfers.id', '=', (int) $id)->first(array('player_transfers.*', 'players.name'));

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
                return Redirect::to('admin/player_transfers/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('player_transfers')->where('id', '=', $id->id)->delete();

        ionic_clear_cache('transfers-*');

        $this->log(sprintf('Usunięto transfer: %s', $id->name));

        if (!Request::ajax())
        {
            $this->notice('Transfer usunięty pomyślnie');
            return Redirect::to('admin/player_transfers/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_player_transfers_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('player_transfers')->join('players', 'players.id', '=', 'player_transfers.player_id')
                        ->where('player_transfers.id', '=', (int) $id)->first(array('player_transfers.*', 'players.name'));

        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('date'        => '', 'type'        => '', 'cost'        => '', 'description' => '');
            $raw_data = array_merge($raw_data, Input::only(array('date', 'type', 'cost', 'description')));

            $rules = array(
                'date' => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!',
                'type' => 'integer|min:0|max:2',
                'cost' => 'max:127',
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/player_transfers/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('date', 'type', 'cost', 'description'));
            }
            else
            {
                $prepared_data = array(
                    'date'        => $raw_data['date'],
                    'type'        => (int) $raw_data['type'],
                    'cost'        => HTML::specialchars($raw_data['cost']),
                    'description' => $raw_data['description']
                );

                if (!Auth::can('admin_root'))
                {
                    require_once path('app').'vendor'.DS.'htmLawed.php';

                    $prepared_data['description'] = htmLawed($prepared_data['description'], array('safe' => 1));
                }

                \DB::table('player_transfers')->where('id', '=', $id->id)->update($prepared_data);

                ionic_clear_cache('transfers-*');

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono transfer: %s', $id->name));
                return Redirect::to('admin/player_transfers/index');
            }
        }

        $this->page->set_title('Edycja transferu');

        $this->page->breadcrumb_append('Transfery', 'admin/player_transfers/index');
        $this->page->breadcrumb_append('Edycja transferu', 'admin/player_transfers/edit/'.$id->id);

        $this->view = View::make('admin.player_transfers.edit');

        $old_data = array('date'        => '', 'type'        => '', 'cost'        => '', 'description' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);

        Ionic\Editor::init();
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_player_transfers'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_player_transfers'))
            return Response::error(403);

        $this->page->set_title('Transfery');
        $this->page->breadcrumb_append('Transfery', 'admin/player_transfers/index');

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
        if (!Auth::can('admin_player_transfers_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_player_transfers'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('player_transfers', 'Transfery', 'admin/player_transfers');

        $grid->add_related('players', 'players.id', '=', 'player_transfers.player_id');
        $grid->add_related('teams', 'teams.id', '=', 'player_transfers.team_id');
        $grid->add_related('teams as '.DB::prefix().'fromteam', 'fromteam.id', '=', 'player_transfers.from_team');

        $grid->add_column('id', 'ID', 'id', null, 'player_transfers.id');
        $grid->add_column('name', 'Zawodnik', 'name', 'players.name', 'players.name');
        $grid->add_column('fromteam', 'Od', 'fromteam_name', 'fromteam.name as fromteam_name', 'fromteam.name');
        $grid->add_column('team', 'Do', 'team_name', 'teams.name as team_name', 'team.name');

        if (Auth::can('admin_player_transfers_add'))
            $grid->add_button('Dodaj transfer', 'admin/player_transfers/add', 'add-button');
        if (Auth::can('admin_player_transfers_edit'))
            $grid->add_action('Edytuj', 'admin/player_transfers/edit/%d', 'edit-button');
        if (Auth::can('admin_player_transfers_delete'))
            $grid->add_action('Usuń', 'admin/player_transfers/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        if (Auth::can('admin_player_transfers_delete') and Auth::can('admin_player_transfers_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                $affected = DB::table('player_transfers')->where_in('id', $ids)->delete();

                if ($affected > 0)
                    Model\Log::add('Masowo usunięto transfery ('.$affected.')', $id);

                ionic_clear_cache('transfers-*');
            });
        }

        $grid->add_filter_perpage(array(20, 30, 50));

        $grid->add_filter_autocomplete('name', 'Zawodnik', function($str) {
            $us = DB::table('players')->take(20)->where('name', 'like', '%'.str_replace('%', '', $str).'%')->get('name');

            $result = array();

            foreach ($us as $u)
            {
                $result[] = $u->name;
            }

            return $result;
        }, 'players.name');

        $grid->add_filter_autocomplete('team', 'Klub', function($str) {
            $us = DB::table('teams')->take(20)->where('name', 'like', '%'.str_replace('%', '', $str).'%')->get('name');

            $result = array();

            foreach ($us as $u)
            {
                $result[] = $u->name;
            }

            return $result;
        }, array('teams.name', 'fromteam.name'));

        $grid->add_filter_select('type', 'Rodzaj', array(
            '_all_' => 'Wszystkie',
            0       => 'Zwykłe',
            1       => 'Wypożyczenie',
            2       => 'Powrót z wyp.'
        ), '_all_');

        return $grid;
    }

}