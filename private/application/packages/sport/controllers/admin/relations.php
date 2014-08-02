<?php

class Admin_Relations_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_relations_add'))
            return Response::error(403);

        Asset::add('select2', 'public/css/select2.css');
        Asset::add('select2', 'public/js/select2.min.js', 'jquery');

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('match' => '');
            $raw_data = array_merge($raw_data, Input::only(array('match')));

            $rules = array(
                'match' => 'required|exists:matches,id|unique:relations,match_id'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/relations/add')->with_errors($validator)
                                ->with_input('only', array('match'));
            }
            else
            {
                $prepared_data = array(
                    'match_id' => (int) $raw_data['match']
                );

                $obj_id = DB::table('relations')->insert_get_id($prepared_data);

                \Cache::forget('last-relation');

                $this->notice('Obiekt dodany pomyślnie');
                $this->log('Dodano relacje live');
                return Redirect::to('admin/relations/index');
            }
        }

        $this->page->set_title('Dodawanie relacji');

        $this->page->breadcrumb_append('Relacje live', 'admin/relations/index');
        $this->page->breadcrumb_append('Dodawanie relacji', 'admin/relations/add');

        $this->view = View::make('admin.relations.add');
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_relations'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_autocomplete_match()
    {
        if (!Auth::can('admin_relations'))
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
                        ->where('score', '=', '')
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
                        ->where('score', '=', '')
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
                    ->where('score', '=', '')
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
        if (!Auth::can('admin_relations_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('relations')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/relations/index');
        }

        DB::table('relations')->where('id', '=', $id->id)->delete();

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log('Usunięto relacje live');
        return Redirect::to('admin/relations/index');
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_relations_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('relations')->join('matches', 'matches.id', '=', 'relations.match_id')->where('relations.id', '=', (int) $id)->first(array('relations.*', 'matches.home_id', 'matches.away_id'));
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $score = Input::get('current_score');

            if ($score and preg_match('/^[0-9]{1,2}[\-\:][0-9]{1,2}$/', $score))
            {
                DB::table('relations')->where('id', '=', $id->id)->update(array('current_score' => $score));
            }
            else
            {
                DB::table('relations')->where('id', '=', $id->id)->update(array('current_score' => ''));
            }

            \Cache::forget('last-relation');

            $this->notice('Wynik zapisany');
            return Redirect::to('admin/relations/edit/'.$id->id);
        }

        Asset::add('select2', 'public/css/select2.css');
        Asset::add('select2', 'public/js/select2.min.js', 'jquery');
        Asset::add('jeditable', 'public/js/jquery.jeditable.min.js', 'jquery');

        $default_players = array('home' => array(), 'away' => array());
        $players = array();
        $cards = array(0 => array(), 1 => array());
        $goals = array(0 => array(), 1 => array());
        $changes = array(0 => array(), 1 => array());
        $messages = DB::table('relation_messages')->where('relation_id', '=', $id->id)->order_by('minute', 'desc')->order_by('id', 'desc')->get('*');
        $team_players = array(0 => array(), 1 => array(), 2 => array(), 3 => array());

        foreach (DB::table('players')->where('team_id', '=', $id->home_id)->or_where('team_id', '=', $id->away_id)->get(array('name', 'number', 'team_id')) as $p)
        {
            if ($p->team_id == $id->home_id)
            {
                $default_players['home'][] = $p->number.'. '.HTML::decode(str_replace(',', '', $p->name));
            }
            else
            {
                $default_players['away'][] = $p->number.'. '.HTML::decode(str_replace(',', '', $p->name));
            }
        }

        foreach (DB::table('relation_players')->where('relation_id', '=', $id->id)->order_by('sorting', 'asc')->get('*') as $p)
        {
            $players[$p->id] = $p;
            $team_players[($p->team == 0 ? ($p->squad == 0 ? 0 : 1) : ($p->squad == 0 ? 2 : 3))][$p->id] = $p->number.'. '.str_replace(',', '', $p->name);
        }

        foreach (DB::table('relation_events')->where('relation_id', '=', $id->id)->order_by('minute', 'asc')->get('*') as $e)
        {
            // errors
            if (!isset($players[$e->player_id]))
                continue;
            if (empty($e->data))
                continue;
            $data = unserialize($e->data);
            if (empty($data))
                continue;

            // true part
            if ($e->type == 0) // goal
            {
                $assist = '';

                if (isset($data['assist_player']) and isset($players[$data['assist_player']]))
                {
                    $assist = $players[$data['assist_player']]->name;
                }

                $goals[$players[$e->player_id]->team][$e->id] = array(
                    'name'   => $players[$e->player_id]->name,
                    'type'   => $data['type'],
                    'minute' => $e->minute,
                    'assist' => $assist
                );
            }
            elseif ($e->type == 1) // card
            {
                $cards[$players[$e->player_id]->team][$e->id] = array(
                    'name'   => $players[$e->player_id]->name,
                    'type'   => ($data['type'] == 'red' ? 'red' : 'yellow'),
                    'minute' => $e->minute
                );
            }
            else // change
            {
                // data integrity error
                if (!isset($players[$data['new_player']]))
                    continue;

                $changes[$players[$e->player_id]->team][$e->id] = array(
                    'name'       => $players[$e->player_id]->name,
                    'new_player' => $players[$data['new_player']]->name,
                    'minute'     => $e->minute
                );
            }
        }

        $this->page->set_title('Prowadzenie relacji');
        $this->page->breadcrumb_append('Relacje', 'admin/relations/index');
        $this->page->breadcrumb_append('Prowadzenie relacji', 'admin/relations/edit/'.$id->id);

        $this->view = View::make('admin.relations.edit', array(
                    'relation'        => $id,
                    'default_players' => $default_players,
                    'players'         => $players,
                    'team_players'    => $team_players,
                    'cards'           => $cards,
                    'goals'           => $goals,
                    'changes'         => $changes,
                    'messages'        => $messages,
                    'types'           => array(
                        'standard'          => 'Zwykła',
                        'yellow_card'       => 'Żółta kartka',
                        'red_card'          => 'Czerwona kartka',
                        'doubleyellow_card' => 'Druga żółta kartka',
                        'penalty_kick'      => 'Rzut karny',
                        'free_kick'         => 'Rzut wolny',
                        'corner_kick'       => 'Rzut rożny',
                        'goal'              => 'Bramka',
                        'injury'            => 'Kontuzja',
                        'change'            => 'Zmiana'
                    ),
                    'viewers'         => DB::table('sessions')->where('location_url', '=', 'live/show/'.$id->id)->where('last_activity', '>', (time() - 3600))->count()
                ));
    }

    public function action_event_add($id, $type)
    {
        if (!Auth::can('admin_relations_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('relations')->where('relations.id', '=', (int) $id)->first(array('relations.*'));
        if (!$id)
            return Response::error(500);

        if (Request::forged() or Request::method() != 'POST')
            return Response::error(500);

        if (!in_array($type, array('goal', 'card', 'change')) or !Input::has('minute') or !Input::has('player_id'))
            return Redirect::to('admin/relations/edit/'.$id->id);

        $minute = Input::get('minute');
        $player = Input::get('player_id');

        if (!ctype_digit($minute) or !ctype_digit($player))
            return Redirect::to('admin/relations/edit/'.$id->id);

        $minute = (int) $minute;
        if ($minute > 255)
            $minute = 255;

        $player = DB::table('relation_players')->where('relation_id', '=', $id->id)->where('id', '=', (int) $player)->first('*');

        if (!$player)
            return Redirect::to('admin/relations/edit/'.$id->id);

        $data = array();

        if ($type == 'goal')
        {
            if (!Input::has('goal_type'))
            {
                $data['type'] = 'standard';
            }
            else
            {
                $data['type'] = Input::get('goal_type');

                // just realized I shouldn't have used "suicide" here, too lazy to fix it now
                if ($data['type'] != 'penalty' and $data['type'] != 'suicide')
                {
                    $data['type'] = 'standard';
                }
            }

            if (Input::has('assist') and ctype_digit(Input::get('assist')))
            {
                $assistant = DB::table('relation_players')->where('relation_id', '=', $id->id)->where('id', '=', (int) Input::get('assist'))->first('*');
            
                if ($assistant)
                {
                    $data['assist_player'] = $assistant->id;
                }
            }

            $score = ionic_parse_score($id->current_score);

            if (!$score)
                $score = array(0 => 0, 1 => 0);

            if (($player->team == 0 and $data['type'] != 'suicide') or ($player->team == 1 and $data['type'] == 'suicide'))
            {
                $score[0]++;
            }
            else
            {
                $score[1]++;
            }

            DB::table('relations')->where('id', '=', $id->id)->update(array(
                'current_score' => $score[0].':'.$score[1]
            ));
        }
        elseif ($type == 'card')
        {
            if (!Input::has('card_type'))
            {
                $data['type'] = 'yellow';
            }
            else
            {
                $data['type'] = Input::get('card_type');

                if ($data['type'] != 'red')
                {
                    $data['type'] = 'yellow';
                }
            }
        }
        else
        {
            if (!Input::has('new_player') or !ctype_digit(Input::get('new_player')))
            {
                return Redirect::to('admin/relations/edit/'.$id->id);
            }

            $new_player = DB::table('relation_players')->where('relation_id', '=', $id->id)->where('id', '=', (int) Input::get('new_player'))->first('*');

            if (!$new_player or $new_player->team != $player->team or $new_player->id == $player->id)
            {
                return Redirect::to('admin/relations/edit/'.$id->id);
            }

            $data['new_player'] = $new_player->id;
        }

        DB::table('relation_events')->insert(array(
            'relation_id' => $id->id,
            'player_id'   => $player->id,
            'minute'      => $minute,
            'type'        => ($type == 'goal' ? 0 : ($type == 'card' ? 1 : 2)),
            'data'        => serialize($data)
        ));

        $this->notice('Wydarzenie dodane pomyślnie');

        return Redirect::to('admin/relations/edit/'.$id->id);
    }

    public function action_event_delete()
    {
        if (!Auth::can('admin_relations_edit'))
            return Response::error(403);

        if (Request::forged() or Request::method() != 'POST' or !Request::ajax() or !Input::has('id') or !ctype_digit(Input::get('id')))
            return Response::error(500);

        $id = (int) Input::get('id');
        $id = DB::table('relation_events')->where('id', '=', $id)->first(array('*'));

        if (!$id)
            return Response::error(404);

        if ($id->type == 0)
        {
            $relation = DB::table('relations')->where('id', '=', $id->relation_id)->first('current_score');

            if (!$relation)
                return Response::error(500);

            $score = ionic_parse_score($relation->current_score);
            $is_suicide = false;

            if (!empty($id->data))
            {
                $data = unserialize($id->data);

                if (is_array($data) and isset($data['type']) and $data['type'] == 'suicide')
                {
                    $is_suicide = true;
                }
            }

            if ($score)
            {
                $player = DB::table('relation_players')->where('id', '=', $id->player_id)->first('team');

                if (!$player)
                    return Response::error(500);

                if (($player->team == 0 and !$is_suicide) or ($player->team == 1 and $is_suicide))
                {
                    $score[0]--;
                }
                else
                {
                    $score[1]--;
                }

                if ($score[0] < 0)
                    $score[0] = 0;
                if ($score[1] < 0)
                    $score[1] = 0;

                DB::table('relations')->where('id', '=', $id->relation_id)->update(array(
                    'current_score' => $score[0].':'.$score[1]
                ));
            }
        }

        DB::table('relation_events')->where('id', '=', $id->id)->delete();

        return Response::make('');
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_relations'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_finish($id)
    {
        if (!Auth::can('admin_relations_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('relations')->where('relations.id', '=', (int) $id)->first(array('relations.*'));
        if (!$id)
            return Response::error(500);

        if ($id->is_finished == 1 and $id->current_score)
            return Redirect::to('admin/relations/edit/'.$id->id);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/relations/edit/'.$id->id);
        }

        // Matches
        $match = DB::table('matches')->where('matches.id', '=', $id->match_id)->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                ->first(array('competition_id', 'season_id', 'score'));

        if (!$match)
            return Response::error(500);

        // ----------------
        // Data collection
        // ----------------

        // Common
        $players = array();

        // Report data
        $cards = array(0 => array(), 1 => array());
        $goals = array(0 => array(), 1 => array());
        $changes = array(0 => array(), 1 => array());
        $team_players = array(0 => array(), 1 => array(), 2 => array(), 3 => array());
        
        // Stats
        $player_stats = array();
        $player_minutes = array();

        foreach (DB::table('relation_players')->where('relation_id', '=', $id->id)->order_by('sorting', 'asc')->get('*') as $p)
        {
            // Add players
            $players[$p->id] = $p;
            $team_players[($p->team == 0 ? ($p->squad == 0 ? 0 : 1) : ($p->squad == 0 ? 2 : 3))][$p->id] = $p->number.'. '.str_replace(',', '', $p->name);

            // Minutes played counter
            if ($p->squad == 0)
            {
                $player_minutes[$p->id] = array('from' => 0, 'to' => NULL);
            }
        }

        foreach (DB::table('relation_events')->where('relation_id', '=', $id->id)->order_by('minute', 'asc')->get('*') as $e)
        {
            // Errors
            if (!isset($players[$e->player_id]) or empty($e->data))
                continue;
            $data = unserialize($e->data);
            if (empty($data))
                continue;

            if ($e->type == 0) // goal
            {
                $assist = '';

                if (isset($data['assist_player']) and isset($players[$data['assist_player']]))
                {
                    $assist = $players[$data['assist_player']]->name;
                }

                $goals[$players[$e->player_id]->team][] = array(
                    'name'   => $players[$e->player_id]->name,
                    'type'   => $data['type'],
                    'minute' => $e->minute,
                    'assist' => $assist
                );

                if ($data['type'] != 'suicide')
                {
                    if (!isset($player_stats[$players[$e->player_id]->name]))
                    {
                        $player_stats[$players[$e->player_id]->name] = array('goals' => 0,
                                                                             'red_cards' => 0,
                                                                             'yellow_cards' => 0,
                                                                             'assists' => 0,
                                                                             'minutes' => 0);
                    }

                    $player_stats[$players[$e->player_id]->name]['goals']++;

                    if ($assist)
                    {
                        if (!isset($player_stats[$assist]))
                        {
                            $player_stats[$assist] = array('goals' => 0,
                                                           'red_cards' => 0,
                                                           'yellow_cards' => 0,
                                                           'assists' => 0,
                                                           'minutes' => 0);
                        }

                        $player_stats[$assist]['assists']++;
                    }
                }
            }
            elseif ($e->type == 1) // card
            {
                $cards[$players[$e->player_id]->team][] = array(
                    'name'   => $players[$e->player_id]->name,
                    'type'   => ($data['type'] == 'red' ? 'red' : 'yellow'),
                    'minute' => $e->minute
                );

                if (!isset($player_stats[$players[$e->player_id]->name]))
                {
                    $player_stats[$players[$e->player_id]->name] = array('goals' => 0,
                                                                         'red_cards' => 0,
                                                                         'yellow_cards' => 0,
                                                                         'assists' => 0,
                                                                         'minutes' => 0);
                }

                $player_stats[$players[$e->player_id]->name][($data['type'] == 'red' ? 'red_cards' : 'yellow_cards')]++;
            }
            else // change
            {
                if (!isset($players[$data['new_player']]))
                    continue;

                $changes[$players[$e->player_id]->team][] = array(
                    'name'       => $players[$e->player_id]->name,
                    'new_player' => $players[$data['new_player']]->name,
                    'minute'     => $e->minute
                );

                if (isset($player_minutes[$e->player_id]) and is_null($player_minutes[$e->player_id]['to']))
                {
                    $player_minutes[$e->player_id]['to'] = (int) $e->minute;
                }

                if (!isset($player_minutes[$data['new_player']]))
                {
                    $player_minutes[$data['new_player']] = array('from' => (int) $e->minute, 'to' => NULL);
                }
            }
        }

        $last_minute = DB::table('relation_messages')->where('relation_id', '=', $id->id)->order_by('minute', 'desc')
                                                     ->first('minute');

        if ($last_minute)
        {
            $last_minute = $last_minute->minute;
        }
        else
        {
            // ??
            $last_minute = 100;
        }

        foreach ($player_minutes as $id2 => $time)
        {
            if (!isset($player_stats[$players[$id2]->name]))
            {
                $player_stats[$players[$id2]->name] = array('goals' => 0,
                                                           'red_cards' => 0,
                                                           'yellow_cards' => 0,
                                                           'assists' => 0,
                                                           'minutes' => 0);
            }

            $minute = is_null($time['to']) ? $last_minute : $time['to'];
            $minute -= $time['from'];

            $player_stats[$players[$id2]->name]['minutes'] = ($minute > 0 ? $minute : 0);
        }

        // ----------------
        // Create report, update score and generate related tables
        // ----------------

        $report = array('players' => $team_players,
                        'goals'   => $goals,
                        'cards'   => $cards,
                        'changes' => $changes);

        DB::table('matches')->where('id', '=', $id->match_id)->update(array('score' => $id->current_score,
                                                                            'report_data' => serialize($report)));

        if ($match->score != $id->current_score)
        {
            foreach (DB::table('tables')->where('competition_id', '=', $match->competition_id)
                    ->where('season_id', '=', $match->season_id)
                    ->where('auto_generation', '=', 1)->get('id') as $t)
            {
                Ionic\TableManager::generate($t->id);
            }
        }

        // ----------------
        // Insert new stats or update already exisiting record
        // ----------------

        $true_stats = array();

        foreach (DB::table('player_stats')->join('players', 'players.id', '=', 'player_stats.player_id')
                ->join('relation_players', 'players.name', '=', 'relation_players.name')
                ->where('competition_id', '=', $match->competition_id)
                ->where('season_id', '=', $match->season_id)
                ->get(array('player_stats.goals', 'player_stats.minutes', 'player_stats.assists', 'player_stats.red_cards', 'player_stats.yellow_cards', 'players.id', 'player_stats.id as stat_id')) as $p)
        {
            $true_stats[$p->id] = $p;
        }

        foreach (DB::table('relation_players')->join('players', 'players.name', '=', 'relation_players.name')->get(array('players.id', 'players.name')) as $p)
        {
            if (isset($true_stats[$p->id]))
            {
                if (isset($player_stats[$p->name]))
                {
                    $s = $player_stats[$p->name];

                    $update = array(
                        'matches'      => DB::raw('matches + 1'),
                        'red_cards'    => ($s['red_cards'] + $true_stats[$p->id]->red_cards),
                        'yellow_cards' => ($s['yellow_cards'] + $true_stats[$p->id]->yellow_cards),
                        'goals'        => ($s['goals'] + $true_stats[$p->id]->goals),
                        'assists'      => ($s['assists'] + $true_stats[$p->id]->assists),
                        'minutes'      => ($s['minutes'] + $true_stats[$p->id]->minutes),
                    );
                }
                else
                {
                    $update = array('matches' => DB::raw('matches + 1'));
                }

                DB::table('player_stats')->where('id', '=', $true_stats[$p->id]->stat_id)
                        ->update($update);
            }
            else
            {
                if (isset($player_stats[$p->name]))
                {
                    $s = $player_stats[$p->name];

                    $insert = array(
                        'matches'      => 1,
                        'red_cards'    => $s['red_cards'],
                        'yellow_cards' => $s['yellow_cards'],
                        'goals'        => $s['goals'],
                        'assists'      => $s['assists'],
                        'minutes'      => $s['minutes']
                    );
                }
                else
                {
                    $insert = array('matches' => 1);
                }

                DB::table('player_stats')->insert(array_merge($insert, array(
                            'competition_id' => $match->competition_id,
                            'season_id'      => $match->season_id,
                            'player_id'      => $p->id
                        )));
            }
        }

        // ----------------
        // Finishing touches
        // ----------------

        DB::table('relations')->where('id', '=', $id->id)->update(array('is_finished' => 1));

        \Cache::forget('last-relation');

        $this->notice('Relacja zakończona pomyślnie');
        $this->log('Zakończono relacje live');
        return Redirect::to('admin/relations/index');
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_relations'))
            return Response::error(403);

        $this->page->set_title('Relacje');
        $this->page->breadcrumb_append('Relacje', 'admin/relations/index');

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

    public function action_message_add($id)
    {
        if (!Auth::can('admin_relations_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('relations')->where('relations.id', '=', (int) $id)->first(array('relations.*'));
        if (!$id)
            return Response::error(500);

        if (Request::forged() or Request::method() != 'POST')
            return Response::error(500);

        if (!Input::has('message') or !Input::has('minute') or !ctype_digit(Input::get('minute')) or !Input::has('type'))
            return Redirect::to('admin/relations/edit/'.$id->id);

        $message = HTML::specialchars(Input::get('message'));
        $minute = (int) Input::get('minute');
        if ($minute > 255)
            $minute = 255;
        $minute_display = Str::limit(HTML::specialchars(Input::get('minute_display', '')), 10);
        $type = Input::get('type');

        $types = array(
            'standard'          => 'Zwykła',
            'yellow_card'       => 'Żółta kartka',
            'red_card'          => 'Czerwona kartka',
            'doubleyellow_card' => 'Druga żółta kartka',
            'penalty_kick'      => 'Rzut karny',
            'free_kick'         => 'Rzut wolny',
            'corner_kick'       => 'Rzut rożny',
            'goal'              => 'Bramka',
            'injury'            => 'Kontuzja',
            'change'            => 'Zmiana'
        );

        if (!isset($types[$type]))
            $type = 'standard';

        DB::table('relation_messages')->insert(array(
            'relation_id'    => $id->id,
            'message'        => $message,
            'minute'         => $minute,
            'minute_display' => $minute_display,
            'type'           => $type
        ));

        $this->notice('Wiadomość dodana pomyślnie');
        return Redirect::to('admin/relations/edit/'.$id->id);
    }

    public function action_message_delete()
    {
        if (!Auth::can('admin_relations_edit'))
            return Response::error(403);

        if (Request::forged() or Request::method() != 'POST' or !Request::ajax() or !Input::has('id') or !ctype_digit(Input::get('id')))
            return Response::error(500);

        $id = (int) Input::get('id');
        $id = DB::table('relation_messages')->where('id', '=', $id)->first('id');

        if (!$id)
            return Response::error(404);

        DB::table('relation_messages')->where('id', '=', $id->id)->delete();

        return Response::make('');
    }

    public function action_message_edit()
    {
        if (!Auth::can('admin_relations_edit'))
            return Response::error(403);

        if (Request::forged() or Request::method() != 'POST' or !Request::ajax() or !Input::has('id') or !Input::has('value'))
            return Response::error(500);

        if (!starts_with(Input::get('id'), 'message-content-'))
            return Response::error(500);

        $id = substr(Input::get('id'), 16);

        if (!ctype_digit($id))
            return Response::error(500);

        $id = (int) $id;
        $id = DB::table('relation_messages')->where('id', '=', $id)->first('id');

        $value = HTML::specialchars(Input::get('value'));

        if (!$id)
            return Response::error(404);

        DB::table('relation_messages')->where('id', '=', $id->id)->update(array('message' => $value));

        return Response::make($value);
    }

    public function action_multiaction($name)
    {
        if (!Auth::can('admin_relations_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_players($id)
    {
        if (!Auth::can('admin_relations_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('relations')->where('relations.id', '=', (int) $id)->first(array('relations.*'));
        if (!$id)
            return Response::error(500);

        if (Request::forged() or Request::method() != 'POST')
            return Response::error(500);

        $current_players = array(0 => array(), 1 => array());

        foreach (DB::table('relation_players')->where('relation_id', '=', $id->id)->get(array('name', 'number', 'team', 'squad', 'id', 'sorting')) as $p)
        {
            $current_players[$p->team][$p->name] = $p;
        }

        $home_updatelist = array();
        $home_insertlist = array();
        $deletelist = array();
        $duplicates = array();

        if (Input::has('home_firstsquad'))
        {
            $data = explode(',', Input::get('home_firstsquad'));
            $matches = array();
            $i = 0;

            foreach ($data as $p)
            {
                if (!preg_match('!^([0-9]{1,10})\.([^,]+)$!', $p, $matches))
                    continue;

                $matches[1] = (int) $matches[1];
                $matches[2] = HTML::specialchars(trim($matches[2]));

                if (isset($duplicates[$matches[2]]))
                    continue;
                $duplicates[$matches[2]] = true;

                if (isset($current_players[0][$matches[2]]))
                {
                    $player = $current_players[0][$matches[2]];

                    if ($player->number != $matches[1] or $player->squad != 0 or $player->sorting != $i)
                    {
                        $home_updatelist[$player->id] = array(
                            'number'  => $matches[1],
                            'squad'   => 0,
                            'sorting' => $i
                        );
                    }

                    unset($current_players[0][$matches[2]]);
                }
                else
                {
                    $home_insertlist[] = array(
                        'name'        => $matches[2],
                        'number'      => $matches[1],
                        'squad'       => 0,
                        'sorting'     => $i,
                        'team'        => 0,
                        'relation_id' => $id->id
                    );
                }

                $i++;
            }
        }

        if (Input::has('home_secondsquad'))
        {
            $data = explode(',', Input::get('home_secondsquad'));
            $matches = array();
            $i = 0;

            foreach ($data as $p)
            {
                if (!preg_match('!^([0-9]{1,10})\.([^,]+)$!', $p, $matches))
                    continue;

                $matches[1] = (int) $matches[1];
                $matches[2] = HTML::specialchars(trim($matches[2]));

                if (isset($duplicates[$matches[2]]))
                    continue;
                $duplicates[$matches[2]] = true;

                if (isset($current_players[0][$matches[2]]))
                {
                    $player = $current_players[0][$matches[2]];

                    if ($player->number != $matches[1] or $player->squad != 1 or $player->sorting != $i)
                    {
                        $home_updatelist[$player->id] = array(
                            'number'  => $matches[1],
                            'squad'   => 1,
                            'sorting' => $i
                        );
                    }

                    unset($current_players[0][$matches[2]]);
                }
                else
                {
                    $home_insertlist[] = array(
                        'name'        => $matches[2],
                        'number'      => $matches[1],
                        'squad'       => 1,
                        'sorting'     => $i,
                        'team'        => 0,
                        'relation_id' => $id->id
                    );
                }

                $i++;
            }
        }

        foreach ($current_players[0] as $p)
        {
            $deletelist[] = $p->id;
        }

        $away_updatelist = array();
        $away_insertlist = array();

        if (Input::has('away_firstsquad'))
        {
            $data = explode(',', Input::get('away_firstsquad'));
            $matches = array();
            $i = 0;

            foreach ($data as $p)
            {
                if (!preg_match('!^([0-9]{1,10})\.([^,]+)$!', $p, $matches))
                    continue;

                $matches[1] = (int) $matches[1];
                $matches[2] = HTML::specialchars(trim($matches[2]));

                if (isset($duplicates[$matches[2]]))
                    continue;
                $duplicates[$matches[2]] = true;

                if (isset($current_players[1][$matches[2]]))
                {
                    $player = $current_players[1][$matches[2]];

                    if ($player->number != $matches[1] or $player->squad != 0 or $player->sorting != $i)
                    {
                        $away_updatelist[$player->id] = array(
                            'number'  => $matches[1],
                            'squad'   => 0,
                            'sorting' => $i
                        );
                    }

                    unset($current_players[1][$matches[2]]);
                }
                else
                {
                    $away_insertlist[] = array(
                        'name'        => $matches[2],
                        'number'      => $matches[1],
                        'squad'       => 0,
                        'sorting'     => $i,
                        'team'        => 1,
                        'relation_id' => $id->id
                    );
                }

                $i++;
            }
        }

        if (Input::has('away_secondsquad'))
        {
            $data = explode(',', Input::get('away_secondsquad'));
            $matches = array();
            $i = 0;

            foreach ($data as $p)
            {
                if (!preg_match('!^([0-9]{1,10})\.([^,]+)$!', $p, $matches))
                    continue;

                $matches[1] = (int) $matches[1];
                $matches[2] = HTML::specialchars(trim($matches[2]));

                if (isset($duplicates[$matches[2]]))
                    continue;
                $duplicates[$matches[2]] = true;

                if (isset($current_players[1][$matches[2]]))
                {
                    $player = $current_players[1][$matches[2]];

                    if ($player->number != $matches[1] or $player->squad != 1 or $player->sorting != $i)
                    {
                        $away_updatelist[$player->id] = array(
                            'number'  => $matches[1],
                            'squad'   => 1,
                            'sorting' => $i
                        );
                    }

                    unset($current_players[1][$matches[2]]);
                }
                else
                {
                    $away_insertlist[] = array(
                        'name'        => $matches[2],
                        'number'      => $matches[1],
                        'squad'       => 1,
                        'sorting'     => $i,
                        'team'        => 1,
                        'relation_id' => $id->id
                    );
                }

                $i++;
            }
        }

        foreach ($current_players[1] as $p)
        {
            $deletelist[] = $p->id;
        }

        // Delete stuff first
        if (!empty($deletelist))
        {
            DB::table('relation_players')->where('relation_id', '=', $id->id)->where_in('id', $deletelist)->delete();

            // Preserve data integrity in events
            $events = array();

            foreach (DB::table('relation_events')->where('relation_id', '=', $id->id)->where('type', '=', 2)->get(array('id', 'data')) as $e)
            {
                if (empty($e->data))
                    continue;
                $data = unserialize($e->data);
                if (empty($data) or !is_array($data) or !isset($data['new_player']))
                    continue;

                if (in_array($data['new_player'], $deletelist))
                {
                    $events[] = $e->id;
                }
            }

            if (!empty($events))
            {
                DB::table('relation_events')->where('relation_id', '=', $id->id)->where_in('id', $events)->delete();
            }
        }

        // Update
        foreach ($home_updatelist as $idk => $p)
        {
            DB::table('relation_players')->where('id', '=', $idk)->update($p);
        }

        foreach ($away_updatelist as $idk => $p)
        {
            DB::table('relation_players')->where('id', '=', $idk)->update($p);
        }

        // Insertion
        foreach ($home_insertlist as $p)
        {
            DB::table('relation_players')->insert($p);
        }

        foreach ($away_insertlist as $p)
        {
            DB::table('relation_players')->insert($p);
        }

        $this->notice('Skład pomyślnie zaaktualizowany');
        $this->log('Zaaktualizowano skład relacji live');
        return Redirect::to('admin/relations/edit/'.$id->id);
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_relations'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('relations', 'Relacje live', 'admin/relations');

        $grid->add_related('matches', 'matches.id', '=', 'relations.match_id');
        $grid->add_related('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id', array('home.name as home_name', 'away.name as away_name'));
        $grid->add_related('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id');

        if (Auth::can('admin_relations_add'))
            $grid->add_button('Dodaj relację', 'admin/relations/add', 'add-button');

        if (Auth::can('admin_relations_edit'))
        {
            $grid->add_action('Prowadź', 'admin/relations/edit/%d', 'edit-button');
        }

        if (Auth::can('admin_relations_delete'))
            $grid->add_action('Usuń', 'admin/relations/delete/%d', 'delete-button');

        $grid->add_column('id', 'ID', 'id', null, 'relations.id');
        $grid->add_column('match', 'Mecz', function($obj) {
                    return $obj->home_name.' vs. '.$obj->away_name;
                }, null, 'home.name');
        $grid->add_column('is_finished', 'Zakończona', function($obj) {
                    if ($obj->is_finished == 1)
                        return '<img style="margin: 0px auto; display: block" src="public/img/icons/accept.png" alt="" />';
                    return '';
                }, 'relations.is_finished', 'relations.is_finished');

        $grid->add_filter_perpage(array(20, 30, 50));

        $grid->add_filter_date('date', 'Data meczu', 'matches.date');

        $grid->add_filter_select('is_finished', 'Zakończona', array(
            '_all_' => 'Wszystkie',
            1       => 'Tak',
            0       => 'Nie'
                ), '_all_');

        $grid->add_filter_autocomplete('team', 'Klub', function($str) {
                    $us = DB::table('teams')->take(20)->where('name', 'like', str_replace('%', '', $str).'%')->get('name');

                    $result = array();

                    foreach ($us as $u)
                    {
                        $result[] = $u->name;
                    }

                    return $result;
                }, array('home.name', 'away.name'));

        if (Auth::can('admin_relations_delete') and Auth::can('admin_relations_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                        $affected = DB::table('relations')->where_in('id', $ids)->delete();

                        if ($affected > 0)
                            Model\Log::add('Masowo usunięto relacje live ('.$affected.')', $id);
                    });
        }

        return $grid;
    }

}