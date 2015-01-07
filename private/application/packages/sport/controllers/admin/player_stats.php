<?php

class Admin_Player_stats_Controller extends Admin_Controller {

    public function action_update()
    {
        if (!Auth::can('admin_player_stats_edit') or !Auth::can('admin_player_stats_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST' and Input::has('competition') and Input::has('team') and Input::has('season'))
        {
            $competition = Input::get('competition');
            $season = Input::get('season');
            $team = Input::get('team');

            if (!ctype_digit($competition) or !ctype_digit($season) or !ctype_digit($team) or !isset($_POST['players']) or !is_array($_POST['players']))
            {
                return Response::error(500);
            }

            $competition = DB::table('competitions')->where('id', '=', $competition)->first(array('id'));
            $season = DB::table('seasons')->where('id', '=', $season)->first(array('id'));
            $team = DB::table('teams')->where('id', '=', $team)->first(array('id', 'name'));

            if (!$competition or !$season or !$team)
            {
                return Response::error(500);
            }

            $players = array();

            foreach (DB::table('players')->where('team_id', '=', $team->id)->get(array('id')) as $player)
            {
                $players[$player->id] = $player->id;
            }

            if (empty($players))
            {
                $this->notice('Ta drużyna nie posiada zawodników');
                return Redirect::to('admin/player_stats/update');
            }

            foreach (DB::table('player_stats')->where('competition_id', '=', $competition->id)
                                              ->where('season_id', '=', $season->id)
                                              ->where_in('player_id', $players)
                                              ->take(count($players))
                                              ->get(array('id', 'player_id')) as $stat)
            {
                unset($players[$stat->player_id]);

                if (!isset($_POST['players'][$stat->player_id]) or !is_array($_POST['players'][$stat->player_id])) continue;

                DB::table('player_stats')->where('id', '=', $stat->id)->update(array(
                    'goals'          => isset($_POST['players'][$stat->player_id]['goals']) ? (int) $_POST['players'][$stat->player_id]['goals'] : 0,
                    'yellow_cards'   => isset($_POST['players'][$stat->player_id]['yellow_cards']) ? (int) $_POST['players'][$stat->player_id]['yellow_cards'] : 0,
                    'red_cards'      => isset($_POST['players'][$stat->player_id]['red_cards']) ? (int) $_POST['players'][$stat->player_id]['red_cards'] : 0,
                    'matches'        => isset($_POST['players'][$stat->player_id]['matches']) ? (int) $_POST['players'][$stat->player_id]['matches'] : 0,
                    'assists'        => isset($_POST['players'][$stat->player_id]['assists']) ? (int) $_POST['players'][$stat->player_id]['assists'] : 0,
                    'minutes'        => isset($_POST['players'][$stat->player_id]['minutes']) ? (int) $_POST['players'][$stat->player_id]['minutes'] : 0
                ));
            }

            foreach ($players as $player)
            {
                if (!isset($_POST['players'][$player]) or !is_array($_POST['players'][$player])) continue;

                DB::table('player_stats')->insert(array(
                    'competition_id' => $competition->id,
                    'season_id'      => $season->id,
                    'player_id'      => $player,
                    'goals'          => isset($_POST['players'][$player]['goals']) ? (int) $_POST['players'][$player]['goals'] : 0,
                    'yellow_cards'   => isset($_POST['players'][$player]['yellow_cards']) ? (int) $_POST['players'][$player]['yellow_cards'] : 0,
                    'red_cards'      => isset($_POST['players'][$player]['red_cards']) ? (int) $_POST['players'][$player]['red_cards'] : 0,
                    'matches'        => isset($_POST['players'][$player]['matches']) ? (int) $_POST['players'][$player]['matches'] : 0,
                    'assists'        => isset($_POST['players'][$player]['assists']) ? (int) $_POST['players'][$player]['assists'] : 0,
                    'minutes'        => isset($_POST['players'][$player]['minutes']) ? (int) $_POST['players'][$player]['minutes'] : 0
                ));
            }

            $this->log(sprintf('Zaaktualizowano statystyki drużyny: %s', $team->name));
            $this->notice('Statystyki tej drużyny zostały pomyślnie zaaktualizowane');
            return Redirect::to('admin/player_stats/index');
        }

        $this->page->set_title('Aktualizacja statystyk');

        $this->page->breadcrumb_append('Statystyki', 'admin/player_stats/index');
        $this->page->breadcrumb_append('Aktualizacja statystyk', 'admin/player_stats/update');

        $this->view = View::make('admin.player_stats.update');

        $related = array();

        foreach (DB::table('teams')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $this->view->with('related_team_id', $related);

        $related = array();

        foreach (DB::table('competitions')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $this->view->with('related_competition_id', $related);

        $related = array();

        foreach (DB::table('seasons')->order_by('year', 'desc')->get(array('id', 'year')) as $v)
        {
            $related[$v->id] = $v->year.' / '.($v->year + 1);
        }

        $this->view->with('related_season_id', $related);
    }

    public function action_update_ajax()
    {
        if (!Auth::can('admin_player_stats_edit') or !Auth::can('admin_player_stats_add'))
            return Response::error(403);

        if (Request::ajax() and Request::method() == 'POST' and Input::has('competition') and Input::has('season') and Input::has('team'))
        {
            $competition = Input::get('competition');
            $season = Input::get('season');
            $team = Input::get('team');

            if (!ctype_digit($competition) or !ctype_digit($season) or !ctype_digit($team))
            {
                return Response::json(array('status' => false));
            }

            $competition = DB::table('competitions')->where('id', '=', $competition)->first('id');
            $season = DB::table('seasons')->where('id', '=', $season)->first('id');
            $team = DB::table('teams')->where('id', '=', $team)->first('id');

            if (!$competition or !$season or !$team)
            {
                return Response::json(array('status' => false));
            }

            $players = array();

            foreach (DB::table('players')->where('team_id', '=', $team->id)->get(array('name', 'id')) as $player)
            {
                $players[$player->id] = array(
                    'id'             => $player->id,
                    'name'           => $player->name,
                    'goals'          => 0,
                    'yellow_cards'   => 0,
                    'red_cards'      => 0,
                    'matches'        => 0,
                    'assists'        => 0,
                    'minutes'        => 0
                );
            }

            if (!empty($players))
            {
                foreach (DB::table('player_stats')->where('competition_id', '=', $competition->id)
                                                  ->where('season_id', '=', $season->id)
                                                  ->where_in('player_id', array_keys($players))
                                                  ->take(count($players))
                                                  ->get(array('player_id', 'goals', 'yellow_cards', 'red_cards', 'matches', 'assists', 'minutes')) as $stat)
                {
                    $players[$stat->player_id]['goals'] = $stat->goals;
                    $players[$stat->player_id]['yellow_cards'] = $stat->yellow_cards;
                    $players[$stat->player_id]['red_cards'] = $stat->red_cards;
                    $players[$stat->player_id]['matches'] = $stat->matches;
                    $players[$stat->player_id]['assists'] = $stat->assists;
                    $players[$stat->player_id]['minutes'] = $stat->minutes;
                }
            }

            $view = View::make('admin.player_stats.update_info', array('players' => $players));

            return Response::json(array('status' => true, 'content' => $view->render()));
        }

        return Response::json(array('status' => false));
    }

    public function action_add()
    {
        if (!Auth::can('admin_player_stats_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('player_id'      => '', 'team_id'        => '', 'minutes' => '', 'competition_id' => '', 'season_id'      => '', 'goals'          => '', 'yellow_cards'   => '', 'red_cards'      => '', 'matches'        => '', 'assists'        => '');
            $raw_data = array_merge($raw_data, Input::only(array('player_id', 'team_id', 'competition_id', 'season_id', 'goals', 'yellow_cards', 'red_cards', 'matches', 'assists', 'minutes')));

            $rules = array(
                'player_id'      => 'required|max:127',
                'team_id'        => 'required|exists:teams,id',
                'competition_id' => 'required|exists:competitions,id',
                'season_id'      => 'required|exists:seasons,id',
                'goals'          => 'required|integer',
                'yellow_cards'   => 'required|integer',
                'red_cards'      => 'required|integer',
                'matches'        => 'required|integer',
                'assists'        => 'required|integer',
                'minutes'        => 'required|integer'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/player_stats/add')->with_errors($validator)
                                ->with_input('only', array('player_id', 'team_id', 'competition_id', 'season_id', 'goals', 'yellow_cards', 'red_cards', 'matches', 'assists', 'minutes'));
            }
            else
            {
                $prepared_data = array(
                    'competition_id' => (int) $raw_data['competition_id'],
                    'season_id'      => (int) $raw_data['season_id'],
                    'goals'          => (int) $raw_data['goals'],
                    'yellow_cards'   => (int) $raw_data['yellow_cards'],
                    'red_cards'      => (int) $raw_data['red_cards'],
                    'matches'        => (int) $raw_data['matches'],
                    'assists'        => (int) $raw_data['assists'],
                    'minutes'        => (int) $raw_data['minutes']
                );

                $player = DB::table('players')->where('name', '=', HTML::specialchars($raw_data['player_id']))->first('id');

                if (!$player)
                {
                    $player = DB::table('players')->insert_get_id(array(
                        'name'    => HTML::specialchars($raw_data['player_id']),
                        'slug'    => ionic_tmp_slug('players'),
                        'team_id' => (int) $raw_data['team_id']
                            ));

                    DB::table('players')->where('id', '=', $player)->update(array('slug' => ionic_find_slug(HTML::specialchars($raw_data['player_id']), $player, 'players')));

                    $prepared_data['player_id'] = (int) $player;
                }
                else
                {
                    $already_exists = DB::table('player_stats')->where('competition_id', '=', $prepared_data['competition_id'])
                            ->where('season_id', '=', $prepared_data['season_id'])
                            ->where('player_id', '=', $player->id)
                            ->first('id');

                    if ($already_exists)
                    {
                        $this->notice('Statystyki dla tego zawodnika w tym sezonie rozgrywek już istnieją');

                        return Redirect::to('admin/player_stats/add')
                                        ->with_input('only', array('player_id', 'team_id', 'competition_id', 'season_id', 'goals', 'yellow_cards', 'red_cards', 'matches', 'assists'));
                    }

                    $prepared_data['player_id'] = $player->id;
                }

                $obj_id = DB::table('player_stats')->insert_get_id($prepared_data);

                ionic_clear_cache('stats-*');

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano statystyki: %s', HTML::specialchars($raw_data['player_id'])));
                return Redirect::to('admin/player_stats/index');
            }
        }

        $this->page->set_title('Dodawanie statystyk');

        $this->page->breadcrumb_append('Statystyki', 'admin/player_stats/index');
        $this->page->breadcrumb_append('Dodawanie statystyk', 'admin/player_stats/add');

        $this->view = View::make('admin.player_stats.add');

        $old_data = array('player_id'      => '', 'team_id'        => '', 'competition_id' => '', 'season_id'      => '', 'goals'          => 0, 'yellow_cards'   => 0, 'red_cards'      => 0, 'matches'        => 0, 'assists'        => 0, 'minutes' => 0);
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $related = array();

        foreach (DB::table('teams')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $this->view->with('related_team_id', $related);
        $related = array();

        foreach (DB::table('competitions')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $this->view->with('related_competition_id', $related);
        $related = array();

        foreach (DB::table('seasons')->order_by('year', 'desc')->get(array('id', 'year')) as $v)
        {
            $related[$v->id] = $v->year.' / '.($v->year + 1);
        }

        $this->view->with('related_season_id', $related);
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_player_stats'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_player_stats_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('player_stats')->join('players', 'players.id', '=', 'player_stats.player_id')->where('player_stats.id', '=', (int) $id)->first(array('players.name', 'player_stats.*'));
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
                return Redirect::to('admin/player_stats/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('player_stats')->where('id', '=', $id->id)->delete();

        ionic_clear_cache('stats-*');

        $this->log(sprintf('Usunięto statystyki: %s', $id->name));

        if (!Request::ajax())
        {
            $this->notice('Statystyki usunięte pomyślnie');
            return Redirect::to('admin/player_stats/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_player_stats_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('player_stats')->join('players', 'players.id', '=', 'player_stats.player_id')->where('player_stats.id', '=', (int) $id)->first(array('players.name', 'player_stats.*'));
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('competition_id' => '', 'season_id'      => '', 'goals'          => '', 'yellow_cards'   => '', 'red_cards'      => '', 'matches'        => '', 'assists'        => '', 'minutes' => '');
            $raw_data = array_merge($raw_data, Input::only(array('competition_id', 'season_id', 'goals', 'yellow_cards', 'red_cards', 'matches', 'assists', 'minutes')));

            $rules = array(
                'competition_id' => 'required|exists:competitions,id',
                'season_id'      => 'required|exists:seasons,id',
                'goals'          => 'required|integer',
                'yellow_cards'   => 'required|integer',
                'red_cards'      => 'required|integer',
                'matches'        => 'required|integer',
                'assists'        => 'required|integer',
                'minutes'        => 'required|integer'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/player_stats/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('competition_id', 'season_id', 'goals', 'yellow_cards', 'red_cards', 'matches', 'assists', 'minutes'));
            }
            else
            {
                $prepared_data = array(
                    'competition_id' => (int) $raw_data['competition_id'],
                    'season_id'      => (int) $raw_data['season_id'],
                    'goals'          => (int) $raw_data['goals'],
                    'yellow_cards'   => (int) $raw_data['yellow_cards'],
                    'red_cards'      => (int) $raw_data['red_cards'],
                    'matches'        => (int) $raw_data['matches'],
                    'assists'        => (int) $raw_data['assists'],
                    'minutes'        => (int) $raw_data['minutes']
                );

                $already_exists = DB::table('player_stats')->where('competition_id', '=', $prepared_data['competition_id'])
                        ->where('season_id', '=', $prepared_data['season_id'])
                        ->where('player_id', '=', $id->player_id)
                        ->first('id');

                if ($already_exists and $already_exists->id != $id->id)
                {
                    $this->notice('Statystyki dla tego zawodnika w tym sezonie rozgrywek już istnieją');

                    return Redirect::to('admin/player_stats/add')
                                    ->with_input('only', array('competition_id', 'season_id', 'goals', 'yellow_cards', 'red_cards', 'matches', 'assists'));
                }

                \DB::table('player_stats')->where('id', '=', $id->id)->update($prepared_data);

                ionic_clear_cache('stats-*');

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono statystyki: %s', $id->name));
                return Redirect::to('admin/player_stats/index');
            }
        }

        $this->page->set_title('Edycja statystyk');

        $this->page->breadcrumb_append('Statystyki', 'admin/player_stats/index');
        $this->page->breadcrumb_append('Edycja statystyk', 'admin/player_stats/edit/'.$id->id);

        $this->view = View::make('admin.player_stats.edit');

        $old_data = array('competition_id' => '', 'season_id'      => '', 'goals'          => '', 'yellow_cards'   => '', 'red_cards'      => '', 'matches'        => '', 'assists'        => '', 'minutes' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);

        $related = array();

        foreach (DB::table('competitions')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $this->view->with('related_competition_id', $related);
        $related = array();

        foreach (DB::table('seasons')->order_by('year', 'desc')->get(array('id', 'year')) as $v)
        {
            $related[$v->id] = $v->year.' / '.($v->year + 1);
        }

        $this->view->with('related_season_id', $related);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_player_stats'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_player_stats'))
            return Response::error(403);

        $this->page->set_title('Statystyki');
        $this->page->breadcrumb_append('Statystyki', 'admin/player_stats/index');

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

    public function action_multiaction($name)
    {
        if (!Auth::can('admin_player_stats_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_player_stats'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('player_stats', 'Statystyki', 'admin/player_stats');

        $grid->add_related('players', 'players.id', '=', 'player_stats.player_id');
        $grid->add_related('seasons', 'seasons.id', '=', 'player_stats.season_id');
        $grid->add_related('competitions', 'competitions.id', '=', 'player_stats.competition_id');

        $grid->add_help('update', 'Aby masowo zaaktualizować statystyki wybierz opcję `Aktualizacja`.');
        $grid->add_help('live', 'Przy kończeniu relacji live istnieje możliwość automatycznej aktualizacji statystyk.');

        $grid->add_column('id', 'ID', 'id', null, 'player_stats.id');
        $grid->add_column('name', 'Zawodnik', 'name', 'players.name', 'players.name');
        $grid->add_column('season', 'Sezon', function($obj) {
            return $obj->year.' / '.($obj->year + 1);
        }, 'seasons.year', 'seasons.year');
        $grid->add_column('competition', 'Rozgrywki', 'comp_name', 'competitions.name as comp_name', 'competitions.name');
        $grid->add_column('goals', 'Bramek', 'goals', 'player_stats.goals', 'player_stats.goals');

        if (Auth::can('admin_player_stats_add'))
        {
            $grid->add_button('Dodaj statystyki', 'admin/player_stats/add', 'add-button');

            if (Auth::can('admin_player_stats_edit'))
            {
                $grid->add_button('Aktualizacja', 'admin/player_stats/update', 'add-button');
            }
        }

        if (Auth::can('admin_player_stats_edit'))
            $grid->add_action('Edytuj', 'admin/player_stats/edit/%d', 'edit-button');
        if (Auth::can('admin_player_stats_delete'))
            $grid->add_action('Usuń', 'admin/player_stats/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        if (Auth::can('admin_player_stats_delete') and Auth::can('admin_player_stats_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                $affected = DB::table('player_stats')->where_in('id', $ids)->delete();

                if ($affected > 0)
                    Model\Log::add('Masowo usunięto statystyki ('.$affected.')', $id);

                ionic_clear_cache('stats-*');
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

        $seasons = array('_all_' => 'Wszystkie');

        $grid->add_filter_autocomplete('comp_name', 'Rozgrywki', function($str) {
            $us = DB::table('competitions')->take(20)->where('name', 'like', str_replace('%', '', $str).'%')->get('name');

            $result = array();

            foreach ($us as $u)
            {
                $result[] = $u->name;
            }

            return $result;
        }, 'competitions.name');

        $seasons = array('_all_' => 'Wszystkie');

        foreach (DB::table('seasons')->order_by('year', 'desc')->get('year') as $s)
        {
            $seasons[$s->year] = $s->year.' / '.($s->year + 1);
        }

        $grid->add_filter_select('year', 'Sezon', $seasons, '_all_', 'seasons.year');

        return $grid;
    }

}