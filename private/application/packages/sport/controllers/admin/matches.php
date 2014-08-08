<?php

class Admin_Matches_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_matches_add'))
            return Response::error(403);

        Asset::add('select2', 'public/css/select2.css');
        Asset::add('select2', 'public/js/select2.min.js', 'jquery');

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('competition_id' => '', 'season_id'      => '', 'fixture_id'     => '',
                'home_id'        => '', 'away_id'        => '', 'score'          => '',
                'date'           => '', 'stadium'        => '', 'description'    => '',
                'prematch_slug'  => '', 'report_slug'    => '');
            $raw_data = array_merge($raw_data, Input::only(array('competition_id',
                        'season_id', 'fixture_id', 'home_id', 'away_id', 'score',
                        'date', 'stadium', 'description', 'prematch_slug', 'report_slug')));

            $rules = array(
                'competition_id' => 'required|exists:competitions,id',
                'season_id'      => 'required|exists:seasons,id',
                'fixture_id'     => 'required|max:127',
                'home_id'        => 'required|exists:teams,id',
                'away_id'        => 'required|exists:teams,id',
                'score'          => 'match:"/^[0-9]{1,2}[\-\:][0-9]{1,2}$/"',
                'date'           => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
                'stadium'        => 'max:127',
                'prematch_slug'  => 'exists:news,slug',
                'report_slug'    => 'exists:news,slug'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/matches/add')->with_errors($validator)
                                ->with_input('only', array('competition_id', 'season_id',
                                    'fixture_id', 'home_id', 'away_id', 'score',
                                    'date', 'stadium', 'description', 'prematch_slug',
                                    'report_slug'));
            }
            else
            {
                $prepared_data = array(
                    'fixture_id'    => HTML::specialchars($raw_data['fixture_id']),
                    'home_id'       => (int) $raw_data['home_id'],
                    'away_id'       => (int) $raw_data['away_id'],
                    'score'         => str_replace('-', ':', $raw_data['score']),
                    'date'          => $raw_data['date'],
                    'stadium'       => HTML::specialchars($raw_data['stadium']),
                    'description'   => HTML::specialchars($raw_data['description']),
                    'prematch_slug' => $raw_data['prematch_slug'],
                    'report_slug'   => $raw_data['report_slug'],
                    'slug'          => ionic_tmp_slug('matches')
                );

                $fixture = DB::table('fixtures')->where('competition_id', '=', (int) $raw_data['competition_id'])
                        ->where('season_id', '=', (int) $raw_data['season_id'])
                        ->where('name', '=', $prepared_data['fixture_id'])
                        ->first('id');

                if ($fixture)
                {
                    $prepared_data['fixture_id'] = $fixture->id;
                }
                else
                {
                    $fixture_data = array(
                        'name'           => $prepared_data['fixture_id'],
                        'competition_id' => (int) $raw_data['competition_id'],
                        'season_id'      => (int) $raw_data['season_id']
                    );

                    if (ctype_digit($fixture_data['name']))
                    {
                        $fixture_data['number'] = (int) $fixture_data['name'];
                    }
                    else
                    {
                        $fixture_data['number'] = (int) preg_replace('/[^0-9]*/', '', $fixture_data['name']);
                    }

                    $prepared_data['fixture_id'] = DB::table('fixtures')->insert_get_id($fixture_data);
                }

                $obj_id = DB::table('matches')->insert_get_id($prepared_data);

                if ($prepared_data['score'])
                {
                    foreach (DB::table('tables')->where('competition_id', '=', (int) $raw_data['competition_id'])
                            ->where('season_id', '=', (int) $raw_data['season_id'])
                            ->where('auto_generation', '=', 1)->get('id') as $t)
                    {
                        Ionic\TableManager::generate($t->id);
                    }
                }

                // Match name
                $home = DB::table('teams')->where('id', '=', $prepared_data['home_id'])->first(array(
                    'name', 'stadium'));
                $away = DB::table('teams')->where('id', '=', $prepared_data['away_id'])->first(array(
                    'name'));
                $match_name = '';

                if ($home)
                {
                    $match_name = $home->name.' vs ';
                }
                else
                {
                    $match_name = $prepared_data['home_id'].' vs ';
                }

                if ($away)
                {
                    $match_name .= $away->name;
                }
                else
                {
                    $match_name .= $prepared_data['away_id'];
                }

                $slug = ionic_find_slug($match_name, $obj_id, 'matches', 255);

                if (!$prepared_data['stadium'] and $home->stadium)
                {
                    DB::table('matches')->where('id', '=', $obj_id)->update(array(
                        'slug'    => $slug, 'stadium' => $home->stadium));
                }
                else
                {
                    DB::table('matches')->where('id', '=', $obj_id)->update(array(
                        'slug' => $slug));
                }

                // Set news as report
                if ($prepared_data['report_slug'])
                {
                    DB::table('news')->where('slug', '=', $prepared_data['report_slug'])->update(array(
                        'external_url' => 'match/report/'.$slug
                    ));
                }

                ionic_clear_cache('match-*');
                ionic_clear_cache('timetable-*');

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano mecz: %s', $match_name));
                return Redirect::to('admin/matches/index');
            }
        }

        $this->page->set_title('Dodawanie meczu');

        $this->page->breadcrumb_append('Mecze', 'admin/matches/index');
        $this->page->breadcrumb_append('Dodawanie meczu', 'admin/matches/add');

        $this->view = View::make('admin.matches.add');

        $old_data = array('competition_id' => '', 'season_id'      => '', 'fixture_id'     => '',
            'home_id'        => '', 'away_id'        => '', 'score'          => '',
            'date'           => '', 'stadium'        => '', 'description'    => '',
            'prematch_slug'  => '', 'report_slug'    => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

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

        $related = array();

        foreach (DB::table('teams')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $this->view->with('related_home_id', $related);
    }

    public function action_add2()
    {
        if (!Auth::can('admin_matches_add'))
            return Response::error(403);

        $teams = array();
        $stadiums = array();

        foreach (DB::table('teams')->get(array('stadium', 'id', 'name')) as $v)
        {
            $teams[$v->id] = $v->name;
            $stadiums[$v->id] = $v->stadium;
        }

        if (!Request::forged() and Request::method() == 'POST' and Input::has('competition_id') and Input::has('season_id') and Input::has('fixture_id') and !empty($_POST['matches']))
        {
            if (!is_array($_POST['matches']))
                return Response::error(500);

            $competition = Input::get('competition_id');
            $season = Input::get('season_id');
            $fixture = HTML::specialchars(Input::get('fixture_id'));

            if (!ctype_digit($competition) or !ctype_digit($season))
                return Response::error(500);

            $competition = DB::table('competitions')->where('id', '=', (int) $competition)->first('id');
            $season = DB::table('seasons')->where('id', '=', (int) $season)->first('id');

            if (!$competition or !$season)
                return Response::error(500);

            $fixture2 = DB::table('fixtures')->where('competition_id', '=', $competition->id)
                    ->where('season_id', '=', $season->id)
                    ->where('name', '=', $fixture)
                    ->first('id');

            if ($fixture2)
            {
                $fixture = $fixture2->id;
            }
            else
            {
                $fixture_data = array(
                    'name'           => $fixture,
                    'competition_id' => $competition->id,
                    'season_id'      => $season->id
                );

                if (ctype_digit($fixture_data['name']))
                {
                    $fixture_data['number'] = (int) $fixture_data['name'];
                }
                else
                {
                    $fixture_data['number'] = (int) preg_replace('/[^0-9]*/', '', $fixture_data['name']);
                }

                $fixture = DB::table('fixtures')->insert_get_id($fixture_data);
            }

            $c = 0;
            $any_full_score = false;

            foreach ($_POST['matches'] as $m)
            {
                if (!is_array($m) or !isset($m['home_id']) or !isset($m['away_id']) or !isset($m['date']))
                    continue;

                if (!ctype_digit($m['home_id']) or !ctype_digit($m['away_id']) or !preg_match('!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!', $m['date']))
                    continue;

                $m['home_id'] = (int) $m['home_id'];
                $m['away_id'] = (int) $m['away_id'];

                if (!isset($teams[$m['home_id']]) or !isset($teams[$m['away_id']]))
                    continue;

                if (!empty($m['score']) and preg_match('/^[0-9]{1,2}[\-\:][0-9]{1,2}$/', $m['score']))
                {
                    $score = str_replace('-', ':', $m['score']);
                    $any_full_score = true;
                }
                else
                {
                    $score = '';
                }

                $id = DB::table('matches')->insert_get_id(array(
                    'home_id'    => $m['home_id'],
                    'away_id'    => $m['away_id'],
                    'fixture_id' => $fixture,
                    'date'       => $m['date'],
                    'score'      => $score,
                    'stadium'    => $stadiums[$m['home_id']],
                    'slug'       => $m['home_id'].$m['away_id'].$fixture.Str::random(100) // because we don't want to waste M queries for finding stupid TEMPORARY slug
                        ));

                DB::table('matches')->where('id', '=', $id)->update(array(
                    'slug' => ionic_find_slug($teams[$m['home_id']].' vs '.$teams[$m['away_id']], $id, 'matches', 255)
                ));

                $c++;
            }

            if ($c == 0)
            {
                $this->notice('Żaden z meczy nie mógł zostać dodany');
                return Redirect::to('admin/matches/add2');
            }

            if ($any_full_score)
            {
                foreach (DB::table('tables')->where('competition_id', '=', $competition->id)
                        ->where('season_id', '=', $season->id)
                        ->where('auto_generation', '=', 1)->get('id') as $t)
                {
                    Ionic\TableManager::generate($t->id);
                }
            }

            ionic_clear_cache('match-*');
            ionic_clear_cache('timetable-*');

            $this->notice('Mecze zostały dodane pomyślnie');
            $this->log('Dodano '.$c.' mecze(ów)');
            return Redirect::to('admin/matches/index');
        }

        $this->page->set_title('Dodawanie meczu');

        $this->page->breadcrumb_append('Mecze', 'admin/matches/index');
        $this->page->breadcrumb_append('Dodawanie meczu', 'admin/matches/add2');

        $this->view = View::make('admin.matches.add2');

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

        $related = '';

        foreach ($teams as $k => $v)
        {
            $related .= '<option value="'.$k.'">'.$v.'</option>';
        }

        $this->view->with('related_home_id', $related);
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_matches'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_autocomplete_fixture()
    {
        if (!Auth::can('admin_matches'))
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

    public function action_autocomplete_news()
    {
        if (!Auth::can('admin_matches'))
            return Response::error(403);

        if (!Request::ajax() or Request::method() != 'POST' or !Input::has('query'))
            return Response::error(500);

        $us = DB::table('news')->take(10)->where('title', 'like', str_replace('%', '', Input::get('query')).'%')->get(array(
            'title', 'slug', 'created_at', 'id'));

        $result = array();

        foreach ($us as $u)
        {
            $result[] = array('id'   => $u->slug, 'text' => $u->title.'<br /><small>ID: '.$u->id.'; Dodano: '.ionic_date($u->created_at).'</small>');
        }

        return Response::json($result);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_matches_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('matches')->where('id', '=', (int) $id)->first('*');
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
                return Redirect::to('admin/matches/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('matches')->where('id', '=', $id->id)->delete();

        $home = DB::table('teams')->where('id', '=', $id->home_id)->first('name');
        $away = DB::table('teams')->where('id', '=', $id->away_id)->first('name');
        $match_name = '';

        if ($home)
        {
            $match_name = $home->name.' vs ';
        }
        else
        {
            $match_name = $prepared_data['home_id'].' vs ';
        }

        if ($away)
        {
            $match_name .= $away->name;
        }
        else
        {
            $match_name .= $prepared_data['away_id'];
        }

        ionic_clear_cache('match-*');
        ionic_clear_cache('timetable-*');

        $this->log(sprintf('Usunięto mecz: %s', $match_name));

        if (!Request::ajax())
        {
            $this->notice('Mecz usunięty pomyślnie');
            return Redirect::to('admin/matches/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_matches_edit') or !ctype_digit($id))
            return Response::error(403);

        Asset::add('select2', 'public/css/select2.css');
        Asset::add('select2', 'public/js/select2.min.js', 'jquery');

        $id = DB::table('matches')->join('fixtures', 'matches.fixture_id', '=', 'fixtures.id')->where('matches.id', '=', (int) $id)->first(array(
            'matches.*', 'fixtures.name', 'fixtures.competition_id', 'fixtures.season_id'));
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('competition_id' => '', 'season_id'      => '', 'fixture_id'     => '',
                'home_id'        => '', 'away_id'        => '', 'score'          => '',
                'slug'           => '', 'date'           => '', 'stadium'        => '',
                'description'    => '', 'prematch_slug'  => '', 'report_slug'    => '');
            $raw_data = array_merge($raw_data, Input::only(array('competition_id',
                        'season_id', 'fixture_id', 'home_id', 'away_id', 'score',
                        'slug', 'date', 'stadium', 'description', 'prematch_slug',
                        'report_slug')));

            $rules = array(
                'competition_id' => 'required|exists:competitions,id',
                'season_id'      => 'required|exists:seasons,id',
                'fixture_id'     => 'required|max:127',
                'home_id'        => 'required|exists:teams,id',
                'away_id'        => 'required|exists:teams,id',
                'score'          => 'match:"/^[0-9]{1,2}[\-\:][0-9]{1,2}$/"',
                'slug'           => 'required|max:255|alpha_dash|unique:matches,slug,'.$id->id.'',
                'date'           => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
                'stadium'        => 'max:127',
                'prematch_slug'  => 'exists:news,slug',
                'report_slug'    => 'exists:news,slug'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/matches/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('competition_id', 'season_id',
                                    'fixture_id', 'home_id', 'away_id', 'score',
                                    'slug', 'date', 'stadium', 'description', 'prematch_slug',
                                    'report_slug'));
            }
            else
            {
                $prepared_data = array(
                    'fixture_id'    => HTML::specialchars($raw_data['fixture_id']),
                    'home_id'       => (int) $raw_data['home_id'],
                    'away_id'       => (int) $raw_data['away_id'],
                    'score'         => str_replace('-', ':', $raw_data['score']),
                    'date'          => $raw_data['date'],
                    'stadium'       => HTML::specialchars($raw_data['stadium']),
                    'description'   => HTML::specialchars($raw_data['description']),
                    'prematch_slug' => $raw_data['prematch_slug'],
                    'report_slug'   => $raw_data['report_slug'],
                    'slug'          => HTML::specialchars($raw_data['slug'])
                );

                $fixture = DB::table('fixtures')->where('competition_id', '=', (int) $raw_data['competition_id'])
                        ->where('season_id', '=', (int) $raw_data['season_id'])
                        ->where('name', '=', $prepared_data['fixture_id'])
                        ->first('id');

                if ($fixture)
                {
                    $prepared_data['fixture_id'] = $fixture->id;
                }
                else
                {
                    $fixture_data = array(
                        'name'           => $prepared_data['fixture_id'],
                        'competition_id' => (int) $raw_data['competition_id'],
                        'season_id'      => (int) $raw_data['season_id']
                    );

                    if (ctype_digit($fixture_data['name']))
                    {
                        $fixture_data['number'] = (int) $fixture_data['name'];
                    }
                    else
                    {
                        $fixture_data['number'] = (int) preg_replace('/[^0-9]*/', '', $fixture_data['name']);
                    }

                    $prepared_data['fixture_id'] = DB::table('fixtures')->insert_get_id($fixture_data);
                }

                \DB::table('matches')->where('id', '=', $id->id)->update($prepared_data);

                if ($prepared_data['score'] != $id->score)
                {
                    foreach (DB::table('tables')->where('competition_id', '=', (int) $raw_data['competition_id'])
                            ->where('season_id', '=', (int) $raw_data['season_id'])
                            ->where('auto_generation', '=', 1)->get('id') as $t)
                    {
                        Ionic\TableManager::generate($t->id);
                    }
                }

                // Match name
                $home = DB::table('teams')->where('id', '=', $prepared_data['home_id'])->first('name');
                $away = DB::table('teams')->where('id', '=', $prepared_data['away_id'])->first('name');
                $match_name = '';

                if ($home)
                {
                    $match_name = $home->name.' vs ';
                }
                else
                {
                    $match_name = $prepared_data['home_id'].' vs ';
                }

                if ($away)
                {
                    $match_name .= $away->name;
                }
                else
                {
                    $match_name .= $prepared_data['away_id'];
                }

                if ($id->report_slug and $id->report_slug != $prepared_data['report_slug'])
                {
                    DB::table('news')->where('slug', '=', $id->report_slug)->update(array(
                        'external_url' => ''));
                }

                if ($prepared_data['report_slug'])
                {
                    DB::table('news')->where('slug', '=', $prepared_data['report_slug'])->update(array(
                        'external_url' => 'match/report/'.$prepared_data['slug']));
                }

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono mecz: %s', $match_name));

                ionic_clear_cache('match-*');
                ionic_clear_cache('timetable-*');

                return Redirect::to('admin/matches/index');
            }
        }

        $this->page->set_title('Edycja meczu');

        $this->page->breadcrumb_append('Mecze', 'admin/matches/index');
        $this->page->breadcrumb_append('Edycja meczu', 'admin/matches/edit/'.$id->id);

        $this->view = View::make('admin.matches.edit');

        $old_data = array('competition_id' => '', 'season_id'      => '', 'fixture_id'     => '',
            'home_id'        => '', 'away_id'        => '', 'score'          => '',
            'slug'           => '', 'date'           => '', 'stadium'        => '',
            'description'    => '', 'prematch_slug'  => '', 'report_slug'    => '');
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

        $related = array();

        foreach (DB::table('teams')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $this->view->with('related_home_id', $related);

        $prematch = '';
        $report = '';

        if ($id->prematch_slug)
        {
            $prematch = DB::table('news')->where('slug', '=', $id->prematch_slug)->first('title');

            if ($prematch)
            {
                $prematch = $prematch->title;
            }
            else
            {
                $prematch = '';
            }
        }

        if ($id->report_slug)
        {
            $report = DB::table('news')->where('slug', '=', $id->report_slug)->first('title');

            if ($report)
            {
                $report = $report->title;
            }
            else
            {
                $report = '';
            }
        }

        $this->view->with('prematch', $prematch);
        $this->view->with('report', $report);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_matches'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_matches'))
            return Response::error(403);

        $this->page->set_title('Mecze');
        $this->page->breadcrumb_append('Mecze', 'admin/matches/index');

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

    public function action_inline()
    {
        if (!Auth::can('admin_matches_edit'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_inline();
    }

    public function action_multiaction($name)
    {
        if (!Auth::can('admin_matches_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_report($id, $t = null)
    {
        if (!Auth::can('admin_matches_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('matches')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        $this->page->set_title('Raport pomeczowy');
        $this->page->breadcrumb_append('Mecze', 'admin/matches/index');
        $this->page->breadcrumb_append('Raport pomeczowy', 'admin/matches/report/'.$id->id);

        $data = $id->report_data;

        if (empty($data))
        {
            $data = array(
                'players' => array(0 => array(), 1 => array(), 2 => array(), 3 => array(
                    )),
                'goals'   => array(0 => array(), 1 => array()),
                'cards'   => array(0 => array(), 1 => array()),
                'changes' => array(0 => array(), 1 => array())
            );
        }
        else
        {
            $data = unserialize($data);
        }

        if (Request::method() == 'POST' and !Request::forged() and ($t == 'home' or $t == 'away'))
        {
            $data['players'][($t == 'home' ? 0 : 2)] = array();
            $data['players'][($t == 'home' ? 1 : 3)] = array();

            if (Input::has('firstsquad'))
            {
                foreach (explode("\n", ionic_normalize_lines(Input::get('firstsquad'))) as $l)
                {
                    $l = trim($l);
                    if (empty($l))
                        continue;

                    $data['players'][($t == 'home' ? 0 : 2)][] = HTML::specialchars($l);
                }
            }

            if (Input::has('secondsquad'))
            {
                foreach (explode("\n", ionic_normalize_lines(Input::get('secondsquad'))) as $l)
                {
                    $l = trim($l);
                    if (empty($l))
                        continue;

                    $data['players'][($t == 'home' ? 1 : 3)][] = HTML::specialchars($l);
                }
            }

            DB::table('matches')->where('id', '=', $id->id)->update(array(
                'report_data' => serialize($data)
            ));

            $this->notice('Skład zaaktualizowany');
            $this->log('Zaaktualizował skład w raporcie pomeczowym');
            return Redirect::to('admin/matches/report/'.$id->id);
        }

        $this->view = View::make('admin.matches.report', array('match' => $id, 'data'  => $data));
    }

    public function action_report_add($id, $type, $team)
    {
        if (!Auth::can('admin_matches_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('matches')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!in_array($team, array('home', 'away')) or !in_array($type, array('goal',
                    'card', 'change')) or !Input::has('minute') or !Input::has('player_id'))
            return Redirect::to('admin/matches/report/'.$id->id);

        $data = $id->report_data;

        if (empty($data))
        {
            return Response::error(404);
        }
        else
        {
            $data = unserialize($data);
        }

        $minute = Input::get('minute');
        $player = HTML::specialchars(Input::get('player_id'));
        $player2 = HTML::specialchars(Input::get('new_player'));

        if ($type == 'change' and $player == $player2)
        {
            return Redirect::to('admin/matches/report/'.$id->id);
        }

        if (!ctype_digit($minute))
            return Redirect::to('admin/matches/report/'.$id->id);

        $minute = (int) $minute;
        if ($minute > 255)
            $minute = 255;

        // Try to find this mofo
        if ($team == 'home')
        {
            $found = (in_array($player, $data['players'][0]) or in_array($player, $data['players'][1]));
            $found2 = false;

            if ($player2)
            {
                $found2 = (in_array($player2, $data['players'][0]) or in_array($player2, $data['players'][1]));
            }
        }
        else
        {
            $found = (in_array($player, $data['players'][2]) or in_array($player, $data['players'][3]));
            $found2 = false;

            if ($player2)
            {
                $found2 = (in_array($player2, $data['players'][2]) or in_array($player2, $data['players'][3]));
            }
        }

        if (!$found or ($type == 'change' and !$found2))
            return Redirect::to('admin/matches/report/'.$id->id);

        if ($type == 'goal')
        {
            if (!Input::has('goal_type'))
            {
                $goal_type = 'standard';
            }
            else
            {
                $goal_type = Input::get('goal_type');

                if ($goal_type != 'penalty' and $goal_type != 'suicide')
                {
                    $goal_type = 'standard';
                }
            }

            $data['goals'][($team == 'home' ? 0 : 1)][] = array(
                'name'   => $player,
                'type'   => $goal_type,
                'minute' => $minute,
                'assist' => ''
            );

            usort($data['goals'][($team == 'home' ? 0 : 1)], function ($a, $b)
                    {
                        if ($a['minute'] == $b['minute'])
                        {
                            return 0;
                        }

                        return ($a['minute'] > $b['minute']) ? 1 : -1;
                    });
        }
        elseif ($type == 'card')
        {
            if (!Input::has('card_type'))
            {
                $card_type = 'yellow';
            }
            else
            {
                $card_type = Input::get('card_type');

                if ($card_type != 'red')
                {
                    $card_type = 'yellow';
                }
            }

            $data['cards'][($team == 'home' ? 0 : 1)][] = array(
                'name'   => $player,
                'type'   => $card_type,
                'minute' => $minute
            );

            usort($data['cards'][($team == 'home' ? 0 : 1)], function ($a, $b)
                    {
                        if ($a['minute'] == $b['minute'])
                        {
                            return 0;
                        }

                        return ($a['minute'] > $b['minute']) ? 1 : -1;
                    });
        }
        else
        {
            $data['changes'][($team == 'home' ? 0 : 1)][] = array(
                'name'       => $player,
                'new_player' => $player2,
                'minute'     => $minute
            );

            usort($data['changes'][($team == 'home' ? 0 : 1)], function ($a, $b)
                    {
                        if ($a['minute'] == $b['minute'])
                        {
                            return 0;
                        }

                        return ($a['minute'] > $b['minute']) ? 1 : -1;
                    });
        }

        DB::table('matches')->where('id', '=', $id->id)->update(array(
            'report_data' => serialize($data)
        ));

        $this->notice('Wydarzenie dodane');
        return Redirect::to('admin/matches/report/'.$id->id);
    }

    public function action_report_delete($id, $team)
    {
        if (!Auth::can('admin_matches_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('matches')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!in_array($team, array('home', 'away')))
            return Response::error(500);

        $mid = Input::get('id');
        $type = Input::get('type');

        if (!in_array($type, array('goal', 'card', 'change')) or !ctype_digit($mid))
            return Response::error(500);
        $mid = (int) $mid;

        $data = $id->report_data;

        if (empty($data))
        {
            return Response::error(404);
        }
        else
        {
            $data = unserialize($data);
        }

        if (isset($data[$type.'s'][$team == 'home' ? 0 : 1][$mid]))
        {
            unset($data[$type.'s'][$team == 'home' ? 0 : 1][$mid]);
        }

        DB::table('matches')->where('id', '=', $id->id)->update(array(
            'report_data' => serialize($data)
        ));

        return Response::make('');
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_matches'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    public function action_fixture($id)
    {
        if (!Auth::can('admin_matches') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('fixtures')->where('id', '=', (int) $id)->first('name');

        if (!$id)
            return Redirect::to('admin/matches/index');

        if (Session::has('matches_filters'))
        {
            $applied = Session::get('matches_filters');
        }
        else
        {
            $applied = array();
        }

        $applied['fixture'] = array('type'  => 'exact', 'query' => $id->name);

        Session::put('matches_filters', $applied);

        return Redirect::to('admin/matches/index');
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('matches', 'Mecze', 'admin/matches');

        $grid->add_related('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id', array(
            'home.name as home_name', 'away.name as away_name'));
        $grid->add_related('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id');
        $grid->add_related('fixtures', 'fixtures.id', '=', 'matches.fixture_id');
        $grid->add_related('competitions', 'competitions.id', '=', 'fixtures.competition_id');
        $grid->add_related('seasons', 'seasons.id', '=', 'fixtures.season_id');

        $grid->add_help('score', 'Wynik spotkania może zostać zmodyfikowany bez wchodzenia w edycje - wystarczy kliknąć na wynik przy wybranym meczu');

        $grid->add_column('id', 'ID', 'id', null, 'matches.id');
        $grid->add_column('match', 'Mecz', function($obj)
                {
                    return $obj->home_name.' vs. '.$obj->away_name;
                }, null, 'home.name');
        $grid->add_column('fixture', 'Kolejka', 'fixture_name', 'fixtures.name as fixture_name', 'fixtures.name');
        $grid->add_column('date', 'Data', 'date', 'matches.date', 'matches.date');
        $grid->add_column('score', 'Wynik', function($obj)
                {
                    return ($obj->score ? : '-:-');
                }, 'matches.score', 'matches.score');

        if (Auth::can('admin_matches_add'))
            $grid->add_button('Dodaj mecz', 'admin/matches/add', 'add-button');
        if (Auth::can('admin_matches_add'))
            $grid->add_button('Dodaj mecze', 'admin/matches/add2', 'add-button');
        if (Auth::can('admin_matches_edit'))
        {
            $grid->add_action('Edytuj', 'admin/matches/edit/%d', 'edit-button');
            $grid->add_action('Raport pomeczowy', 'admin/matches/report/%d', 'display-button');

            $id = $this->user->id;

            $grid->add_inline_edit('score', function($object, $new_value) use ($id) {
                if ($new_value == '-:-' or !preg_match("/^[0-9]{1,2}[\-\:][0-9]{1,2}$/", $new_value))
                    $new_value = '';

                $new_value = str_replace('-', ':', $new_value);

                \DB::table('matches')->where('id', '=', $object->id)->update(array(
                    'score' => $new_value));

                if ($new_value != $object->score)
                {
                    $fixture = DB::table('fixtures')->where('id', '=', $object->fixture_id)->first(array(
                        'competition_id', 'season_id'));

                    if (!$fixture)
                        return Response::make('');

                    foreach (DB::table('tables')->where('competition_id', '=', $fixture->competition_id)
                            ->where('season_id', '=', $fixture->season_id)
                            ->where('auto_generation', '=', 1)->get('id') as $t)
                    {
                        Ionic\TableManager::generate($t->id);
                    }

                    ionic_clear_cache('match-*');
                    ionic_clear_cache('timetable-*');

                    \Model\Log::add('Zaaktualizowano wynik meczu', $id);
                }

                return Response::make($new_value ? : '-:-');
            });
        }

        if (Auth::can('admin_matches_delete'))
            $grid->add_action('Usuń', 'admin/matches/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_date('date', 'Data meczu');
        $grid->add_filter_search('fixture', 'Kolejka', 'fixtures.name');

        $grid->add_filter_autocomplete('team', 'Klub', function($str) {
            $us = DB::table('teams')->take(20)->where('name', 'like', str_replace('%', '', $str).'%')->get('name');

            $result = array();

            foreach ($us as $u)
            {
                $result[] = $u->name;
            }

            return $result;
        }, array('home.name', 'away.name'));

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

        if (Auth::can('admin_matches_delete') and Auth::can('admin_matches_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                $affected = DB::table('matches')->where_in('id', $ids)->delete();

                if ($affected > 0)
                    Model\Log::add('Masowo usunięto mecze ('.$affected.')', $id);

                ionic_clear_cache('match-*');
                ionic_clear_cache('timetable-*');
            });
        }

        return $grid;
    }

}