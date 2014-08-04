<?php

class Admin_Matchpicks_Controller extends Admin_Controller {

    public function action_active($id)
    {
        if (!Auth::can('admin_matchpicks_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('matchpicks')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/matchpicks/index');
        }

        if ($id->is_active == 0)
        {
            DB::table('matchpicks')->where('is_active', '=', 1)->update(array('is_active' => 0));
            DB::table('matchpicks')->where('id', '=', $id->id)->update(array('is_active' => 1));
        }
        else
        {
            DB::table('matchpicks')->where('id', '=', $id->id)->update(array('is_active' => 0));
        }

        \Cache::forget('matchpick');

        $this->notice('Operacja wykonana pomyślnie');
        $this->log(sprintf('Aktywowano/deaktywowano głosowanie: %s', $id->title));
        return Redirect::to('admin/matchpicks/index');
    }

    public function action_add()
    {
        if (!Auth::can('admin_matchpicks_add'))
            return Response::error(403);

        Asset::add('select2', 'public/css/select2.css');
        Asset::add('select2', 'public/js/select2.min.js', 'jquery');

        $related = array();
        $mapping = array();

        foreach (DB::table('players')->join('teams', 'teams.id', '=', 'players.team_id')->get(array('players.number', 'players.id', 'players.name', 'teams.name as team_name')) as $p)
        {
            if (!isset($related[$p->team_name]))
                $related[$p->team_name] = array();

            $related[$p->team_name][$p->id] = $p->number.'. '.$p->name;
            $mapping[$p->id] = $p->number.'. '.$p->name;
        }

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'   => '', 'expires' => '', 'match'   => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'expires', 'match')));

            $rules = array(
                'title'   => 'required|max:127',
                'expires' => 'match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
                'match'   => 'required|exists:matches,id|unique:matchpicks,match_id'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/matchpicks/add')->with_errors($validator)
                                ->with_input('only', array('title', 'expires', 'match'));
            }
            else
            {
                $prepared_data = array(
                    'title'      => HTML::specialchars($raw_data['title']),
                    'created_at' => date('Y-m-d H:i:s'),
                    'match_id'   => (int) $raw_data['match']
                );

                if ($raw_data['expires'])
                {
                    $prepared_data['expires'] = $raw_data['expires'];
                }
                else
                {
                    $prepared_data['expires'] = '0000-00-00 00:00:00';
                }

                $players = array();

                if (!empty($_POST['players']) and is_array($_POST['players']))
                {
                    foreach ($_POST['players'] as $p)
                    {
                        if (!ctype_digit($p))
                            continue;
                        $p = (int) $p;
                        if (!isset($mapping[$p]))
                            continue;

                        $players[$p] = array('player_id' => $p, 'name'      => $mapping[$p], 'votes'     => 0, 'total'     => 0, 'rating'    => 0.0);
                    }
                }

                if (!empty($players))
                {
                    $prepared_data['options'] = serialize($players);
                }

                $obj_id = DB::table('matchpicks')->insert_get_id($prepared_data);

                \Cache::forget('matchpick');

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano głosowanie: %s', $prepared_data['title']));
                return Redirect::to('admin/matchpicks/index');
            }
        }

        $this->page->set_title('Dodawanie głosowania');

        $this->page->breadcrumb_append('Piłkarz meczu', 'admin/matchpicks/index');
        $this->page->breadcrumb_append('Dodawanie głosowania', 'admin/matchpicks/add');

        $this->view = View::make('admin.matchpicks.add');

        $old_data = array('title'   => '', 'expires' => '', 'match'   => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('players', $related);
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_matchpicks'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_autocomplete_match()
    {
        if (!Auth::can('admin_matchpicks'))
            return Response::error(403);

        if (!Request::ajax() or Request::method() != 'POST' or !Input::has('query'))
            return Response::error(500);

        $query = str_replace('%', '', Input::get('query'));

        if (($pos = stripos($query, ' - ')) !== FALSE)
        {
            $home = trim(substr($query, 0, $pos + 1));
            $away = trim(substr($query, ($pos + 3)));

            if ($away)
            {
                $us = DB::table('matches')->take(10)->join('teams', 'teams.id', '=', 'matches.home_id')
                        ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                        ->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                        ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
                        ->join('seasons', 'seasons.id', '=', 'fixtures.season_id')
                        ->where('teams.name', 'like', $home.'%')
                        ->where('away.name', 'like', $away.'%')->order_by('matches.id', 'desc')
                        ->get(array('matches.id', 'teams.name', 'away.name as away_name', 'competitions.name as comp_name', 'seasons.year', 'fixtures.name as fixture_name'));
            }
            else
            {
                $us = DB::table('matches')->take(10)->join('teams', 'teams.id', '=', 'matches.home_id')
                        ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                        ->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                        ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
                        ->join('seasons', 'seasons.id', '=', 'fixtures.season_id')
                        ->where('teams.name', 'like', $home.'%')->order_by('matches.id', 'desc')
                        ->get(array('matches.id', 'teams.name', 'away.name as away_name', 'competitions.name as comp_name', 'seasons.year', 'fixtures.name as fixture_name'));
            }
        }
        else
        {
            $home = rtrim($query, ' -');

            $us = DB::table('matches')->take(10)->join('teams', 'teams.id', '=', 'matches.home_id')
                    ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                    ->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                    ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
                    ->join('seasons', 'seasons.id', '=', 'fixtures.season_id')
                    ->where('teams.name', 'like', $home.'%')->order_by('matches.id', 'desc')
                    ->get(array('matches.id', 'teams.name', 'away.name as away_name', 'competitions.name as comp_name', 'seasons.year', 'fixtures.name as fixture_name'));
        }

        $result = array();

        foreach ($us as $u)
        {
            $result[] = array('id'   => $u->id, 'text' => $u->name.' - '.$u->away_name.'<br /><small>'.$u->fixture_name.'; '.$u->comp_name.' ('.$u->year.'/'.($u->year + 1).')</small>');
        }

        return Response::json($result);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_matchpicks_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('matchpicks')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/matchpicks/index');
        }

        DB::table('matchpicks')->where('id', '=', $id->id)->delete();

        \Cache::forget('matchpick');

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunięto głosowanie: %s', $id->title));
        return Redirect::to('admin/matchpicks/index');
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_matchpicks_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('matchpicks')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        Asset::add('select2', 'public/css/select2.css');
        Asset::add('select2', 'public/js/select2.min.js', 'jquery');

        $related = array();
        $mapping = array();

        foreach (DB::table('players')->join('teams', 'teams.id', '=', 'players.team_id')->get(array('players.number', 'players.id', 'players.name', 'teams.name as team_name')) as $p)
        {
            if (!isset($related[$p->team_name]))
                $related[$p->team_name] = array();

            $related[$p->team_name][$p->id] = $p->number.'. '.$p->name;
            $mapping[$p->id] = $p->number.'. '.$p->name;
        }

        $old_players = $id->options;

        if (empty($old_players))
        {
            $old_players = array();
        }
        else
        {
            $old_players = unserialize($old_players);
        }

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'expires', 'match')));

            $rules = array(
                'title'   => 'required|max:127',
                'expires' => 'match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
                'match'   => 'required|exists:matches,id|unique:matchpicks,match_id,'.$id->id
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/matchpicks/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('title', 'expires', 'match'));
            }
            else
            {
                $prepared_data = array(
                    'title'    => HTML::specialchars($raw_data['title']),
                    'match_id' => (int) $raw_data['match']
                );

                if ($raw_data['expires'])
                {
                    $prepared_data['expires'] = $raw_data['expires'];
                }
                else
                {
                    $prepared_data['expires'] = '0000-00-00 00:00:00';
                }

                $players = array();

                if (!empty($_POST['players']) and is_array($_POST['players']))
                {
                    foreach ($_POST['players'] as $p)
                    {
                        if (!ctype_digit($p))
                            continue;
                        $p = (int) $p;
                        if (!isset($mapping[$p]))
                            continue;

                        $players[$p] = array(
                            'player_id' => $p,
                            'name'      => $mapping[$p],
                            'votes'     => isset($old_players[$p]) ? $old_players[$p]['votes'] : 0,
                            'total'     => isset($old_players[$p]) ? $old_players[$p]['total'] : 0,
                            'rating'    => isset($old_players[$p]) ? $old_players[$p]['rating'] : 0.0
                        );
                    }
                }

                if (empty($players))
                {
                    $prepared_data['options'] = '';
                    $prepared_data['best_player_id'] = 0;
                }
                else
                {
                    $prepared_data['options'] = serialize($players);

                    $score = 0;
                    $player = 0;

                    foreach ($players as $p)
                    {
                        if ($p['rating'] > $score)
                        {
                            $score = $p['rating'];
                            $player = $p['player_id'];
                        }
                    }

                    $prepared_data['best_player_id'] = $player;
                }

                \DB::table('matchpicks')->where('id', '=', $id->id)->update($prepared_data);

                \Cache::forget('matchpick');

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono głosowanie: %s', $prepared_data['title']));
                return Redirect::to('admin/matchpicks/index');
            }
        }

        $this->page->set_title('Edycja głosowania');

        $this->page->breadcrumb_append('Piłkarz meczu', 'admin/matchpicks/index');
        $this->page->breadcrumb_append('Edycja głosowania', 'admin/matchpicks/edit/'.$id->id);

        $this->view = View::make('admin.matchpicks.edit');

        $old_data = array('title'   => '', 'expires' => '', 'match'   => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);

        $this->view->with('players', $related);
        $this->view->with('old_players', $old_players);

        $us = DB::table('matches')->join('teams', 'teams.id', '=', 'matches.home_id')
                ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                ->first(array('matches.id', 'teams.name', 'away.name as away_name'));

        $this->view->with('match', $us);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_matchpicks'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_matchpicks'))
            return Response::error(403);

        DB::table('matchpicks')->where('is_active', '=', 1)
                ->where('expires', '<>', '0000-00-00 00:00:00')
                ->where('expires', '<=', date('Y-m-d H:i:s'))
                ->update(array('is_active' => 0, 'expires'   => '0000-00-00 00:00:00'));

        $this->page->set_title('Piłkarz meczu');
        $this->page->breadcrumb_append('Piłkarz meczu', 'admin/matchpicks/index');

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
        if (!Auth::can('admin_matchpicks_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_preview($id)
    {
        if (!Auth::can('admin_matchpicks') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('matchpicks')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        $options = $id->options;

        if (empty($options))
        {
            $options = array();
        }
        else
        {
            $options = unserialize($options);
        }

        $this->page->set_title('Piłkarz meczu');
        $this->page->breadcrumb_append('Piłkarz meczu', 'admin/matchpicks/index');
        $this->page->breadcrumb_append('Wyniki', 'admin/matchpicks/preview/'.$id->id);

        $this->view = View::make('admin.matchpicks.preview', array('pick'    => $id, 'options' => $options));
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_matchpicks'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('matchpicks', 'Piłkarz meczu', 'admin/matchpicks');

        $grid->add_action('Podgląd', 'admin/matchpicks/preview/%d', 'display-button');
        if (Auth::can('admin_matchpicks_add'))
            $grid->add_button('Dodaj głosowanie', 'admin/matchpicks/add', 'add-button');
        if (Auth::can('admin_matchpicks_edit'))
        {
            $grid->add_action('Aktywuj/deaktywuj', 'admin/matchpicks/active/%d', 'accept-button');
            $grid->add_action('Edytuj', 'admin/matchpicks/edit/%d', 'edit-button');
        }
        if (Auth::can('admin_matchpicks_delete'))
            $grid->add_action('Usuń', 'admin/matchpicks/delete/%d', 'delete-button');

        $grid->add_column('id', 'ID', 'id', null, 'matchpicks.id');
        $grid->add_column('title', 'Tytuł', 'title', 'matchpicks.title', 'matchpicks.title');
        $grid->add_column('created_at', 'Dodano', 'created_at', 'matchpicks.created_at', 'matchpicks.created_at');
        $grid->add_column('votes', 'Głosów', 'votes', 'matchpicks.votes', 'matchpicks.votes');
        $grid->add_column('is_active', 'Aktywne', function($obj) {
                    if ($obj->is_active == 1)
                        return '<img style="margin: 0px auto; display: block" src="public/img/icons/accept.png" alt="" />';
                    return '';
                }, 'matchpicks.is_active', 'matchpicks.is_active');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('title', 'Tytuł');
        $grid->add_filter_date('created_at', 'Data dodania');

        if (Auth::can('admin_matchpicks_delete') and Auth::can('admin_matchpicks_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                        $affected = DB::table('matchpicks')->where_in('id', $ids)->delete();

                        if ($affected > 0)
                        {
                            Model\Log::add('Masowo usunięto głosowania ('.$affected.')', $id);
                            \Cache::forget('matchpick');
                        }
                    });
        }

        return $grid;
    }

}