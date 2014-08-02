<?php

class Admin_Bet_matches_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_bet_matches_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('home'       => '', 'away'       => '', 'date_start' => '', 'date_end'   => '', 'ratio_home' => '', 'ratio_draw' => '', 'ratio_away' => '');
            $raw_data = array_merge($raw_data, Input::only(array('home', 'away', 'score', 'date_start', 'date_end', 'ratio_home', 'ratio_draw', 'ratio_away')));

            $rules = array(
                'home'       => 'required|max:127',
                'away'       => 'required|max:127',
                'date_start' => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
                'date_end'   => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
                'ratio_home' => 'numeric|min:1',
                'ratio_draw' => 'numeric|min:1',
                'ratio_away' => 'numeric|min:1'
            );

            $raw_data['ratio_home'] = str_replace(',', '.', $raw_data['ratio_home']);
            $raw_data['ratio_draw'] = str_replace(',', '.', $raw_data['ratio_draw']);
            $raw_data['ratio_away'] = str_replace(',', '.', $raw_data['ratio_away']);

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/bet_matches/add')->with_errors($validator)
                                ->with_input('only', array('home', 'away', 'date_start', 'date_end', 'ratio_home', 'ratio_draw', 'ratio_away'));
            }
            else
            {
                $prepared_data = array(
                    'home'       => HTML::specialchars($raw_data['home']),
                    'away'       => HTML::specialchars($raw_data['away']),
                    'date_start' => $raw_data['date_start'],
                    'date_end'   => $raw_data['date_end'],
                    'ratio_home' => round((float) $raw_data['ratio_home'], 2),
                    'ratio_draw' => round((float) $raw_data['ratio_draw'], 2),
                    'ratio_away' => round((float) $raw_data['ratio_away'], 2)
                );

                $obj_id = DB::table('bet_matches')->insert_get_id($prepared_data);

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano mecz typera: %s', $prepared_data['home'].' vs. '.$prepared_data['away']));
                return Redirect::to('admin/bet_matches/index');
            }
        }

        $this->page->set_title('Dodawanie meczu');

        $this->page->breadcrumb_append('Typer', 'admin/bet_matches/index');
        $this->page->breadcrumb_append('Dodawanie meczu', 'admin/bet_matches/add');

        $this->view = View::make('admin.bet_matches.add');

        $old_data = array('home'       => '', 'away'       => '', 'date_start' => '', 'date_end'   => '', 'ratio_home' => '', 'ratio_draw' => '', 'ratio_away' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);
    }

    public function action_add2()
    {
        if (!Auth::can('admin_bet_matches_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST' and Input::has('date_start') and !empty($_POST['matches']))
        {
            if (!preg_match('!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!', Input::get('date_start')))
            {
                $this->notice('Nieprawidłowa data');
                return Redirect::to('admin/bet_matches/add2');
            }

            $start = Input::get('date_start');

            if (!is_array($_POST['matches']))
                return Response::error(500);

            $c = 0;

            foreach ($_POST['matches'] as $m)
            {
                if (!is_array($m) or empty($m['home']) or empty($m['away']) or empty($m['date_end']))
                    continue;
                if (!preg_match('!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!', $m['date_end']))
                    continue;

                if (!empty($m['ratio_home']))
                {
                    $m['ratio_home'] = str_replace(',', '.', $m['ratio_home']);

                    if (is_numeric($m['ratio_home']))
                    {
                        $m['ratio_home'] = round((float) $m['ratio_home'], 2);
                    }
                    else
                    {
                        $m['ratio_home'] = 1.1;
                    }
                }
                else
                {
                    $m['ratio_home'] = 1.1;
                }

                if (!empty($m['ratio_draw']))
                {
                    $m['ratio_draw'] = str_replace(',', '.', $m['ratio_draw']);

                    if (is_numeric($m['ratio_draw']))
                    {
                        $m['ratio_draw'] = round((float) $m['ratio_draw'], 2);
                    }
                    else
                    {
                        $m['ratio_draw'] = 1.1;
                    }
                }
                else
                {
                    $m['ratio_draw'] = 1.1;
                }

                if (!empty($m['ratio_away']))
                {
                    $m['ratio_away'] = str_replace(',', '.', $m['ratio_away']);

                    if (is_numeric($m['ratio_away']))
                    {
                        $m['ratio_away'] = round((float) $m['ratio_away'], 2);
                    }
                    else
                    {
                        $m['ratio_away'] = 1.1;
                    }
                }
                else
                {
                    $m['ratio_away'] = 1.1;
                }

                DB::table('bet_matches')->insert(array(
                    'home'       => HTML::specialchars($m['home']),
                    'away'       => HTML::specialchars($m['away']),
                    'date_start' => $start,
                    'date_end'   => $m['date_end'],
                    'ratio_home' => $m['ratio_home'],
                    'ratio_draw' => $m['ratio_draw'],
                    'ratio_away' => $m['ratio_away']
                ));

                $c++;
            }

            if ($c == 0)
            {
                $this->notice('Żaden z meczy nie mógł zostać dodany');
                return Redirect::to('admin/bet_matches/add2');
            }

            $this->notice('Mecze zostały dodane pomyślnie');
            $this->log('Dodano '.$c.' mecze(ów)');
            return Redirect::to('admin/bet_matches/index');
        }

        $this->page->set_title('Dodawanie meczu');

        $this->page->breadcrumb_append('Typer', 'admin/bet_matches/index');
        $this->page->breadcrumb_append('Dodawanie meczów', 'admin/bet_matches/add2');

        $this->view = View::make('admin.bet_matches.add2');
    }
    
    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_bet_matches'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_bet_matches_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('bet_matches')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/bet_matches/index');
        }

        DB::table('bet_matches')->where('id', '=', $id->id)->delete();

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunięto mecz typera: %s', $id->home.' vs. '.$id->away));
        return Redirect::to('admin/bet_matches/index');
    }

    public function action_autocomplete_fixture()
    {
        if (!Auth::can('admin_bet_matches_add'))
            return Response::error(403);

        if (!Request::ajax() or Request::method() != 'POST' or !Input::has('query') or !Input::has('competition') or !Input::has('season'))
            return Response::error(500);

        if (!ctype_digit(Input::get('competition')) or !ctype_digit(Input::get('season')))
            return Response::error(500);

        $us = DB::table('fixtures')->where('competition_id', '=', (int) Input::get('competition'))
                        ->where('season_id', '=', (int) Input::get('season'))
                        ->take(20)->where('name', 'like', str_replace('%', '', Input::get('query')).'%')->get('name');

        $result = array();

        foreach ($us as $u)
        {
            $result[] = $u->name;
        }

        return Response::json($result);
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_bet_matches_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('bet_matches')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('home'       => '', 'away'       => '', 'score'      => '', 'archive'    => '', 'date_start' => '', 'date_end'   => '', 'ratio_home' => '', 'ratio_draw' => '', 'ratio_away' => '');
            $raw_data = array_merge($raw_data, Input::only(array('home', 'away', 'score', 'archive', 'date_start', 'date_end', 'ratio_home', 'ratio_draw', 'ratio_away')));

            $rules = array(
                'home'       => 'required|max:127',
                'away'       => 'required|max:127',
                'score'      => 'match:"/^[0-9]{1,2}[\-\:][0-9]{1,2}$/"',
                'date_start' => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
                'date_end'   => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
                'ratio_home' => 'numeric|min:1',
                'ratio_draw' => 'numeric|min:1',
                'ratio_away' => 'numeric|min:1'
            );

            $raw_data['ratio_home'] = str_replace(',', '.', $raw_data['ratio_home']);
            $raw_data['ratio_draw'] = str_replace(',', '.', $raw_data['ratio_draw']);
            $raw_data['ratio_away'] = str_replace(',', '.', $raw_data['ratio_away']);

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/bet_matches/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('home', 'away', 'score', 'archive', 'date_start', 'date_end', 'ratio_home', 'ratio_draw', 'ratio_away'));
            }
            else
            {
                $prepared_data = array(
                    'home'       => HTML::specialchars($raw_data['home']),
                    'away'       => HTML::specialchars($raw_data['away']),
                    'score'      => str_replace('-', ':', $raw_data['score']),
                    'archive'    => ($raw_data['archive'] == '1' ? '1' : '0'),
                    'date_start' => $raw_data['date_start'],
                    'date_end'   => $raw_data['date_end'],
                    'ratio_home' => round((float) $raw_data['ratio_home'], 2),
                    'ratio_draw' => round((float) $raw_data['ratio_draw'], 2),
                    'ratio_away' => round((float) $raw_data['ratio_away'], 2)
                );

                if ($id->score != $prepared_data['score'])
                {
                    $handler = new Ionic\BetHandler($id->score, $prepared_data['score'], $id->id);
                    $handler->set_ratios($prepared_data['ratio_home'], $prepared_data['ratio_draw'], $prepared_data['ratio_away']);

                    $prepared_data['archive'] = $handler->handle($prepared_data['archive']);
                }

                \DB::table('bet_matches')->where('id', '=', $id->id)->update($prepared_data);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono mecz typera: %s', $prepared_data['home'].' vs. '.$prepared_data['away']));
                return Redirect::to('admin/bet_matches/index');
            }
        }

        $this->page->set_title('Edycja meczu');

        $this->page->breadcrumb_append('Typer', 'admin/bet_matches/index');
        $this->page->breadcrumb_append('Edycja meczu', 'admin/bet_matches/edit/'.$id->id);

        $this->view = View::make('admin.bet_matches.edit');

        $old_data = array('home'       => '', 'away'       => '', 'score'      => '', 'archive'    => '', 'date_start' => '', 'date_end'   => '', 'ratio_home' => '', 'ratio_draw' => '', 'ratio_away' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_bet_matches'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_bet_matches'))
            return Response::error(403);

        $this->page->set_title('Typer');
        $this->page->breadcrumb_append('Typer', 'admin/bet_matches/index');

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

    public function action_import()
    {
        if (!Auth::can('admin_bet_matches_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST' and Input::has('competition') and Input::has('season') and Input::has('fixture'))
        {
            if (!preg_match('!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!', Input::get('date_start')))
            {
                $this->notice('Nieprawidłowa data');
                return Redirect::to('admin/bet_matches/import');
            }

            $start = Input::get('date_start');
            $competition = Input::get('competition');
            $season = Input::get('season');
            $fixture = Input::get('fixture');

            if (!ctype_digit($competition) or !ctype_digit($season))
            {
                return Response::error(500);
            }

            $competition = DB::table('competitions')->where('id', '=', $competition)->first('id');
            $season = DB::table('seasons')->where('id', '=', $season)->first('id');

            if (!$competition or !$season)
            {
                return Response::error(500);
            }

            $fixture = DB::table('fixtures')->where('name', '=', $fixture)->where('competition_id', '=', $competition->id)
                                            ->where('season_id', '=', $season->id)
                                            ->first('id');

            if (!$fixture)
            {
                $this->notice('Podana kolejka musi istnieć w bazie danych');
                return Redirect::to('admin/bet_matches/import');
            }

            $c = 0;

            foreach (DB::table('matches')->where('fixture_id', '=', $fixture->id)
                                         ->join('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id')
                                         ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                                         ->take(30)
                                         ->order_by('matches.date', 'asc')
                                         ->get(array('matches.date', 'home.name as home_name', 'away.name as away_name')) as $match)
            {
                DB::table('bet_matches')->insert(array(
                    'home'       => $match->home_name,
                    'away'       => $match->away_name,
                    'date_start' => $start,
                    'date_end'   => $match->date,
                    'ratio_home' => 1.1,
                    'ratio_draw' => 1.1,
                    'ratio_away' => 1.1
                ));

                $c++;
            }

            if ($c == 0)
            {
                $this->notice('Żaden z meczy nie mógł zostać dodany');
                return Redirect::to('admin/bet_matches/import');
            }

            $this->notice('Mecze zostały dodane pomyślnie');
            $this->log('Zaimportowano '.$c.' mecze(ów)');
            return Redirect::to('admin/bet_matches/index');
        }

        $this->page->set_title('Import meczów');

        $this->page->breadcrumb_append('Typer', 'admin/bet_matches/index');
        $this->page->breadcrumb_append('Import meczów', 'admin/bet_matches/import');

        $this->view = View::make('admin.bet_matches.import');

        $related = array();

        foreach (DB::table('competitions')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $this->view->with('related_competition_id', $related);

        $related = array();

        foreach (DB::table('seasons')->get(array('id', 'year')) as $v)
        {
            $related[$v->id] = $v->year.' / '.($v->year + 1);
        }

        $this->view->with('related_season_id', $related);
    }

    public function action_inline()
    {
        if (!Auth::can('admin_bet_matches_edit'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_inline();
    }

    public function action_multiaction($name)
    {
        if (!Auth::can('admin_bet_matches_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_reset()
    {
        if (!Auth::can('admin_bet_matches_delete'))
            return Response::error(403);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/bet_matches/index');
        }

        DB::table('bet_matches')->delete();
        DB::table('bets')->delete();
        DB::table('profiles')->update(array('bet_points' => 0));

        $this->log('Zresetowano typera');
        $this->notice('Zresetowano typera');
        return Redirect::to('admin/bet_matches/index');
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_bet_matches'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('bet_matches', 'Typer', 'admin/bet_matches');

        if (Auth::can('admin_bet_matches_add'))
        {
            $grid->add_button('Dodaj mecz', 'admin/bet_matches/add', 'add-button');
            $grid->add_button('Dodaj mecze', 'admin/bet_matches/add2', 'add-button');
            $grid->add_button('Import', 'admin/bet_matches/import', 'add-button');
        }
        
        if (Auth::can('admin_bet_matches_edit'))
        {
            $grid->add_action('Edytuj', 'admin/bet_matches/edit/%d', 'edit-button');

            $id = $this->user->id;

            $grid->add_inline_edit('score', function($object, $new_value) use ($id) {
                        if ($new_value == '-:-' or !preg_match("/^[0-9]{1,2}[\-\:][0-9]{1,2}$/", $new_value))
                            $new_value = '';
                        $archive = $object->archive;

                        if ($object->score != $new_value)
                        {
                            $handler = new Ionic\BetHandler($object->score, $new_value, $object->id);
                            $handler->set_ratios($object->ratio_home, $object->ratio_draw, $object->ratio_away);

                            $archive = $handler->handle($archive);
                        }

                        DB::table('bet_matches')->where('id', '=', $object->id)->update(array('score'   => $new_value, 'archive' => $archive));

                        return Response::make($new_value ? : '-:-');
                    });
        }
        if (Auth::can('admin_bet_matches_delete'))
        {
            $grid->add_action('Usuń', 'admin/bet_matches/delete/%d', 'delete-button');

            $grid->add_button('Reset typera', 'admin/bet_matches/reset', 'clear-button');
        }

        $grid->add_column('id', 'ID', 'id', null, 'bet_matches.id');
        $grid->add_column('home', 'Gospodarz', 'home', 'bet_matches.home', 'bet_matches.home');
        $grid->add_column('away', 'Gość', 'away', 'bet_matches.away', 'bet_matches.away');
        $grid->add_column('date_start', 'Data rozp.', function($obj) {
                    return ionic_date($obj->date_start);
                }, 'bet_matches.date_start', 'bet_matches.date_start');
        $grid->add_column('date_end', 'Data zak.', function($obj) {
                    return ionic_date($obj->date_end);
                }, 'bet_matches.date_end', 'bet_matches.date_end');
        $grid->add_column('score', 'Wynik', function($obj) {
                    return ($obj->score ? : '-:-');
                }, 'bet_matches.score', 'bet_matches.score');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_date('date_start', 'Data rozp.');
        $grid->add_filter_date('date_end', 'Data zak.');
        $grid->add_filter_search('home', 'Klub', array('bet_matches.home', 'bet_matches.away'));
        $grid->add_filter_select('archive', 'Archiwizowane', array(
            '_all_' => 'Wszystkie',
            1       => 'Tak',
            0       => 'Nie'
                ), '_all_');

        if (Auth::can('admin_bet_matches_delete') and Auth::can('admin_bet_matches_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                        $affected = DB::table('bet_matches')->where_in('id', $ids)->delete();

                        if ($affected > 0)
                            Model\Log::add('Masowo usunięto mecze typera ('.$affected.')', $id);
                    });
        }

        return $grid;
    }

}