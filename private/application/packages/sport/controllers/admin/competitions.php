<?php

class Admin_Competitions_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_competitions_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('name' => '');
            $raw_data = array_merge($raw_data, Input::only(array('name')));

            $rules = array(
                'name' => 'required|max:127|unique:competitions,name'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/competitions/add')->with_errors($validator)
                                ->with_input('only', array('name'));
            }
            else
            {
                $prepared_data = array(
                    'name' => HTML::specialchars($raw_data['name']),
                    'slug' => ionic_tmp_slug('competitions')
                );

                $obj_id = DB::table('competitions')->insert_get_id($prepared_data);

                DB::table('competitions')->where('id', '=', $obj_id)->update(array('slug' => ionic_find_slug($prepared_data['name'], $obj_id, 'competitions')));

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano rozgrywki: %s', $prepared_data['name']));
                return Redirect::to('admin/competitions/index');
            }
        }

        $this->page->set_title('Dodawanie rozgrywek');

        $this->page->breadcrumb_append('Rozgrywki', 'admin/competitions/index');
        $this->page->breadcrumb_append('Dodawanie rozgrywek', 'admin/competitions/add');

        $this->view = View::make('admin.competitions.add');

        $old_data = array('name' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_competitions'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_competitions_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('competitions')->where('id', '=', (int) $id)->first('*');
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
                return Redirect::to('admin/competitions/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('competitions')->where('id', '=', $id->id)->delete();

        $this->log(sprintf('Usunięto rozgrywki: %s', $id->name));

        if (!Request::ajax())
        {
            $this->notice('Rozgrywki usunięte pomyślnie');
            return Redirect::to('admin/competitions/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_competitions_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('competitions')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('name' => '', 'slug' => '');
            $raw_data = array_merge($raw_data, Input::only(array('name', 'slug')));

            $rules = array(
                'name' => 'required|max:127|unique:competitions,name,'.$id->id.'',
                'slug' => 'required|max:127|alpha_dash|unique:competitions,slug,'.$id->id.''
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/competitions/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('name', 'slug'));
            }
            else
            {
                $prepared_data = array(
                    'name' => HTML::specialchars($raw_data['name']),
                    'slug' => HTML::specialchars($raw_data['slug'])
                );

                \DB::table('competitions')->where('id', '=', $id->id)->update($prepared_data);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono rozgrywki: %s', $prepared_data['name']));
                return Redirect::to('admin/competitions/index');
            }
        }

        $this->page->set_title('Edycja rozgrywek');

        $this->page->breadcrumb_append('Rozgrywki', 'admin/competitions/index');
        $this->page->breadcrumb_append('Edycja rozgrywek', 'admin/competitions/edit/'.$id->id);

        $this->view = View::make('admin.competitions.edit');

        $old_data = array('name' => '', 'slug' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_competitions'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_competitions'))
            return Response::error(403);

        $this->page->set_title('Rozgrywki');
        $this->page->breadcrumb_append('Rozgrywki', 'admin/competitions/index');

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
        if (!Auth::can('admin_competitions'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_competitions'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    public function action_teams($id, $season = '')
    {
        if (!Auth::can('admin_competitions_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('competitions')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!$season or !ctype_digit($season))
        {
            $season = IoC::resolve('current_season');
        }
        else
        {
            $season = DB::table('seasons')->where('id', '=', (int) $season)->first(array('id', 'year'));

            if (!$season)
                return Response::error(404);
        }

        $this->page->set_title('Rozgrywki');
        $this->page->breadcrumb_append('Rozgrywki', 'admin/competitions/index');
        $this->page->breadcrumb_append('Kluby', 'admin/competitions/teams/'.$id->id);

        $select = array();
        $current = array();

        foreach (DB::table('competition_teams')
                ->where('competition_teams.competition_id', '=', $id->id)
                ->where('competition_teams.season_id', '=', $season->id)
                ->join('teams', 'teams.id', '=', 'competition_teams.team_id')->get(array('teams.name', 'teams.id')) as $t)
        {
            $current[$t->id] = $t->name;
        }

        foreach (DB::table('teams')->order_by('name', 'asc')->get(array('id', 'name')) as $t)
        {
            if (!isset($current[$t->id]))
                $select[$t->id] = $t->name;
        }

        if (!Request::forged() and Request::method() == 'POST' and !empty($_POST['teams']) and is_array($_POST['teams']))
        {
            foreach ($_POST['teams'] as $v)
            {
                if (!ctype_digit($v))
                    continue;

                $v = (int) $v;

                if (!isset($select[$v]))
                    continue;

                DB::table('competition_teams')->insert(array('competition_id' => $id->id, 'team_id' => $v, 'season_id' => $season->id));

                unset($select[$v]); // Prevents duplicates
            }

            $this->log('Przypisano kluby do rozgrywek: '.$id->name);
            $this->notice('Operacja wykonana pomyślnie');

            return Redirect::to('admin/competitions/teams/'.$id->id.'/'.$season->id);
        }

        Asset::add('select2', 'public/css/select2.css');
        Asset::add('select2', 'public/js/select2.min.js', 'jquery');

        $this->view = View::make('admin.competitions.teams', array(
            'comp' => $id,
            'current' => $current,
            'select' => $select,
            'season' => $season,
            'seasons' => DB::table('seasons')->order_by('year', 'desc')->get(array('id', 'year'))
        ));
    }

    public function action_teams_delete($competition, $team, $season)
    {
        if (!Request::ajax() or Request::forged())
            return Response::error(500);

        if (!Auth::can('admin_competitions_edit'))
            return Response::json(array('status' => false, 'error' => 'Brak odpowiednich uprawnień'));

        if (!ctype_digit($competition) or !ctype_digit($team) or !ctype_digit($season))
            return Response::json(array('status' => false, 'error' => 'Błąd systemu'));

        $entry = DB::table('competition_teams')->where('competition_id', '=', (int) $competition)
                                               ->where('team_id', '=', (int) $team)
                                               ->where('season_id', '=', (int) $season)
                                               ->first(array('team_id'));

        if (!$entry)
        {
            return Response::json(array('status' => false, 'error' => 'Przypisanie nie istnieje, możliwe ,że zostało już usunięte'));
        }

        DB::table('competition_teams')->where('competition_id', '=', (int) $competition)
                                      ->where('season_id', '=', (int) $season)
                                      ->where('team_id', '=', (int) $team)->delete();

        $this->log('Usunięto klub z rozgrywek');

        return Response::json(array('status' => true, 'error' => ''));
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('competitions', 'Rozgrywki', 'admin/competitions');

        if (Auth::can('admin_competitions_add'))
            $grid->add_button('Dodaj rozgrywki', 'admin/competitions/add', 'add-button');

        if (Auth::can('admin_competitions_edit'))
        {
            $grid->add_action('Edytuj', 'admin/competitions/edit/%d', 'edit-button');
            $grid->add_action('Kluby', 'admin/competitions/teams/%d', 'case-button');
        }

        if (Auth::can('admin_tables'))
        {
            $grid->add_action('Tabele', 'admin/tables/competition/%d', 'display-button');
        }

        if (Auth::can('admin_competitions_delete'))
            $grid->add_action('Usuń', 'admin/competitions/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        $grid->add_column('id', 'ID', 'id', null, 'competitions.id');
        $grid->add_column('name', 'Nazwa rozgrywek', 'name', 'competitions.name', 'competitions.name');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('name', 'Nazwa rozgrywek');

        return $grid;
    }

}