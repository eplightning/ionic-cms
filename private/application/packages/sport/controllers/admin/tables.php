<?php

class Admin_Tables_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_tables_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'           => '', 'sorting_rules'   => '', 'auto_generation' => '', 'competition_id'  => '', 'season_id'       => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'sorting_rules', 'auto_generation', 'competition_id', 'season_id')));

            $rules = array(
                'title'          => 'required|max:127',
                'sorting_rules'  => 'required|in:laliga,standard,ekstraklasa',
                'competition_id' => 'required|exists:competitions,id',
                'season_id'      => 'required|exists:seasons,id'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/tables/add')->with_errors($validator)
                                ->with_input('only', array('title', 'sorting_rules', 'auto_generation', 'competition_id', 'season_id'));
            }
            else
            {
                $prepared_data = array(
                    'title'           => HTML::specialchars($raw_data['title']),
                    'sorting_rules'   => $raw_data['sorting_rules'],
                    'auto_generation' => ($raw_data['auto_generation'] == '1' ? 1 : 0),
                    'competition_id'  => $raw_data['competition_id'],
                    'season_id'       => $raw_data['season_id'],
                    'slug'            => ionic_tmp_slug('tables')
                );

                $obj_id = DB::table('tables')->insert_get_id($prepared_data);

                DB::table('tables')->where('id', '=', $obj_id)->update(array('slug' => ionic_find_slug($prepared_data['title'], $obj_id, 'tables')));

                if ($prepared_data['auto_generation'])
                    Ionic\TableManager::generate($obj_id);

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano tabelę: %s', $prepared_data['title']));
                return Redirect::to('admin/tables/index');
            }
        }

        $this->page->set_title('Dodawanie tabeli');

        $this->page->breadcrumb_append('Tabele', 'admin/tables/index');
        $this->page->breadcrumb_append('Dodawanie tabeli', 'admin/tables/add');

        $this->view = View::make('admin.tables.add');

        $old_data = array('title'           => '', 'sorting_rules'   => '', 'auto_generation' => '', 'competition_id'  => '', 'season_id'       => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

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

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_tables'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_tables_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('tables')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/tables/index');
        }

        DB::table('tables')->where('id', '=', $id->id)->delete();

        ionic_clear_cache('table-'.$id->id.'-*');

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunięto tabelę: %s', $id->title));
        return Redirect::to('admin/tables/index');
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_tables_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('tables')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'           => '', 'slug'            => '', 'sorting_rules'   => '', 'auto_generation' => '', 'competition_id'  => '', 'season_id'       => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'slug', 'sorting_rules', 'auto_generation', 'competition_id', 'season_id')));

            $rules = array(
                'title'          => 'required|max:127',
                'slug'           => 'required|max:127|alpha_dash|unique:tables,slug,'.$id->id.'',
                'sorting_rules'  => 'required|in:laliga,standard,ekstraklasa',
                'competition_id' => 'required|exists:competitions,id',
                'season_id'      => 'required|exists:seasons,id'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/tables/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('title', 'slug', 'sorting_rules', 'auto_generation', 'competition_id', 'season_id'));
            }
            else
            {
                $prepared_data = array(
                    'title'           => HTML::specialchars($raw_data['title']),
                    'slug'            => HTML::specialchars($raw_data['slug']),
                    'sorting_rules'   => $raw_data['sorting_rules'],
                    'auto_generation' => ($raw_data['auto_generation'] == '1' ? 1 : 0),
                    'competition_id'  => $raw_data['competition_id'],
                    'season_id'       => $raw_data['season_id']
                );

                \DB::table('tables')->where('id', '=', $id->id)->update($prepared_data);

                if ($prepared_data['auto_generation'])
                    Ionic\TableManager::generate($id->id);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono tabelę: %s', $prepared_data['title']));
                return Redirect::to('admin/tables/index');
            }
        }

        $this->page->set_title('Edycja tabeli');

        $this->page->breadcrumb_append('Tabele', 'admin/tables/index');
        $this->page->breadcrumb_append('Edycja tabeli', 'admin/tables/edit/'.$id->id);

        $this->view = View::make('admin.tables.edit');

        $old_data = array('title'           => '', 'slug'            => '', 'sorting_rules'   => '', 'auto_generation' => '', 'competition_id'  => '', 'season_id'       => '');
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

        foreach (DB::table('seasons')->get(array('id', 'year')) as $v)
        {
            $related[$v->id] = $v->year.' / '.($v->year + 1);
        }

        $this->view->with('related_season_id', $related);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_tables'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_tables'))
            return Response::error(403);

        $this->page->set_title('Tabele');
        $this->page->breadcrumb_append('Tabele', 'admin/tables/index');

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
        if (!Auth::can('admin_tables_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_show($id)
    {
        if (!Auth::can('admin_tables_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('tables')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        $this->page->set_title('Zarządzaj tabelą');
        $this->page->breadcrumb_append('Tabele', 'admin/tables/index');
        $this->page->breadcrumb_append('Zarządzaj tabelą', 'admin/tables/show/'.$id->id);

        // Assets
        Asset::add('jeditable', 'public/js/jquery.jeditable.min.js', 'jquery');

        $table = array();

        foreach (DB::table('table_positions')->order_by('table_positions.position', 'asc')
                ->join('teams', 'teams.id', '=', 'table_positions.team_id')
                ->where('table_positions.table_id', '=', $id->id)
                ->get(array('table_positions.*', 'teams.name')) as $t)
        {
            $table[] = $t;
        }

        $not_in = array();

        foreach ($table as $t)
        {
            $not_in[] = $t->team_id;
        }

        $teams = array();

        if (empty($not_in))
        {
            foreach (DB::table('teams')->get(array('id', 'name')) as $t)
            {
                $teams[$t->id] = $t->name;
            }
        }
        else
        {
            foreach (DB::table('teams')->where_not_in('id', $not_in)->get(array('id', 'name')) as $t)
            {
                $teams[$t->id] = $t->name;
            }
        }

        $this->view = View::make('admin.tables.show', array(
                    'table'     => $id,
                    'positions' => $table,
                    'teams'     => $teams
                ));
    }

    public function action_show_add($id)
    {
        if (!Auth::can('admin_tables_edit') or !ctype_digit($id) or !Input::has('team_name') or Request::forged())
            return Response::error(403);

        $id = DB::table('tables')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);
        if (!ctype_digit(Input::get('team_name')))
            return Response::error(500);

        $team = DB::table('teams')->where('id', '=', (int) Input::get('team_name'))->first('id');

        if (!$team)
            return Response::error(404);

        if (DB::table('table_positions')->where('table_id', '=', $id->id)->where('team_id', '=', $team->id)->first('team_id'))
        {
            return Redirect::to('admin/tables/show/'.$id->id);
        }

        DB::table('table_positions')->insert(array(
            'team_id'  => $team->id,
            'table_id' => $id->id
        ));

        ionic_clear_cache('table-'.$id->id.'-*');

        $this->log('Dodano klub do tabeli: '.$id->title);
        return Redirect::to('admin/tables/show/'.$id->id);
    }

    public function action_show_delete($id, $team)
    {
        if (!Auth::can('admin_tables_edit') or !ctype_digit($id) or !ctype_digit($team))
            return Response::error(403);

        $id = DB::table('tables')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/tables/show/'.$id->id);
        }

        DB::table('table_positions')->where('table_id', '=', $id->id)->where('team_id', '=', $team)->delete();

        ionic_clear_cache('table-'.$id->id.'-*');

        $this->log('Usunięto klub z tabeli: '.$id->title);
        return Redirect::to('admin/tables/show/'.$id->id);
    }

    public function action_show_edit($id)
    {
        if (!Auth::can('admin_tables_edit') or !ctype_digit($id) or !Request::ajax() or Request::method() != 'POST' or !isset($_POST['value']) or !Input::has('id') or Request::forged())
            return Response::error(403);

        $id = DB::table('tables')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        $value = (int) $_POST['value'];

        $field_id = Input::get('id');
        $field_type = substr($field_id, 0, 3);

        if (!in_array($field_type, array('pos', 'mat', 'win', 'los', 'dra', 'gsh', 'glo', 'pts')))
            return Response::error(500);

        if ($field_type == 'pts')
        {
            if ($value < -32768)
                $value = -32768;
            if ($value > 32767)
                $value = 32767;
        }
        else
        {
            if ($value < 0)
                $value = 0;
            if ($value > 65535)
                $value = 65535;
        }

        $field_id = substr($field_id, 9);

        if (!ctype_digit($field_id))
            return Response::error(500);

        $field_id = (int) $field_id;

        if (!DB::table('table_positions')->where('table_id', '=', $id->id)->where('team_id', '=', $field_id)->first('team_id'))
        {
            return Response::error(500);
        }

        switch ($field_type)
        {
            case 'pos':
                $field_type = 'position';
                break;

            case 'mat':
                $field_type = 'matches';
                break;

            case 'win':
                $field_type = 'wins';
                break;

            case 'los':
                $field_type = 'losses';
                break;

            case 'dra':
                $field_type = 'draws';
                break;

            case 'gsh':
                $field_type = 'goals_shot';
                break;

            case 'glo':
                $field_type = 'goals_lost';
                break;

            default:
                $field_type = 'points';
        }

        DB::table('table_positions')->where('table_id', '=', $id->id)->where('team_id', '=', $field_id)->update(array(
            $field_type => $value
        ));

        ionic_clear_cache('table-'.$id->id.'-*');

        return Response::make($value);
    }

    public function action_show_tools($id, $tool)
    {
        if (!Auth::can('admin_tables_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('tables')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!in_array($tool, array('clear', 'generate', 'reload')))
            return Response::error(404);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/tables/show/'.$id->id);
        }

        switch ($tool)
        {
            case 'clear':
                \Ionic\TableManager::clear($id->id);
                $this->log('Wyczyszczono tabelę: '.$id->title);
                break;

            case 'generate':
                \Ionic\TableManager::generate($id->id);
                $this->log('Wygenerowano tabelę: '.$id->title);
                break;

            default:
                \Ionic\TableManager::reload($id->id);
                $this->log('Przeładowano tabelę: '.$id->title);
        }

        $this->notice('Operacja wykonana pomyślnie');
        return Redirect::to('admin/tables/show/'.$id->id);
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_tables'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    public function action_competition($id)
    {
        if (!Auth::can('admin_tables') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('competitions')->where('id', '=', (int) $id)->first('name');

        if (!$id)
            return Redirect::to('admin/tables/index');

        if (Session::has('tables_filters'))
        {
            $applied = Session::get('tables_filters');
        }
        else
        {
            $applied = array();
        }

        $applied['name'] = $id->name;

        Session::put('tables_filters', $applied);

        return Redirect::to('admin/tables/index');
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('tables', 'Tabele', 'admin/tables');

        $grid->add_related('seasons', 'seasons.id', '=', 'tables.season_id');
        $grid->add_related('competitions', 'competitions.id', '=', 'tables.competition_id');

        $grid->add_column('id', 'ID', 'id', null, 'tables.id');
        $grid->add_column('title', 'Tytuł', 'title', 'tables.title', 'tables.title');
        $grid->add_column('season', 'Sezon', function($obj) {
                    return $obj->year.' / '.($obj->year + 1);
                }, 'seasons.year', 'seasons.year');
        $grid->add_column('competition', 'Rozgrywki', 'name', 'competitions.name', 'competitions.name');

        if (Auth::can('admin_tables_add'))
            $grid->add_button('Dodaj tabelę', 'admin/tables/add', 'add-button');
        if (Auth::can('admin_tables_edit'))
        {
            $grid->add_action('Edytuj', 'admin/tables/edit/%d', 'edit-button');
            $grid->add_action('Zarządzaj', 'admin/tables/show/%d', 'display-button');
        }
        if (Auth::can('admin_tables_delete'))
            $grid->add_action('Usuń', 'admin/tables/delete/%d', 'delete-button');

        if (Auth::can('admin_tables_delete') and Auth::can('admin_tables_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                        $affected = DB::table('tables')->where_in('id', $ids)->delete();

                        if ($affected > 0)
                            Model\Log::add('Masowo usunięto tabele ('.$affected.')', $id);

                        ionic_clear_cache('table-*');
                    });
        }

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('title', 'Tytuł tabeli');

        $grid->add_filter_select('auto_generation', 'Auto generowanie', array(
            '_all_' => 'Wszystkie',
            1       => 'Tak',
            0       => 'Nie'
                ), '_all_');

        $grid->add_filter_autocomplete('name', 'Rozgrywki', function($str) {
                    $us = DB::table('competitions')->take(20)->where('name', 'like', str_replace('%', '', $str).'%')->get('name');

                    $result = array();

                    foreach ($us as $u)
                    {
                        $result[] = $u->name;
                    }

                    return $result;
                }, 'competitions.name');

        $seasons = array('_all_' => 'Wszystkie');

        foreach (DB::table('seasons')->get('year') as $s)
        {
            $seasons[$s->year] = $s->year.' / '.($s->year + 1);
        }

        $grid->add_filter_select('year', 'Sezon', $seasons, '_all_', 'seasons.year');

        return $grid;
    }

}