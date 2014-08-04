<?php

class Admin_Fixtures_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_fixtures_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('name'           => '', 'number'         => '', 'competition_id' => '', 'season_id'      => '');
            $raw_data = array_merge($raw_data, Input::only(array('name', 'number', 'competition_id', 'season_id')));

            Validator::register('uniquefixture', function($attribute, $value, $parameters) use ($raw_data) {

                        // it's error anyway
                        if (!ctype_digit($raw_data['competition_id']) or !ctype_digit($raw_data['season_id']))
                            return true;

                        if (DB::table('fixtures')->where('competition_id', '=', (int) $raw_data['competition_id'])
                                        ->where('season_id', '=', (int) $raw_data['season_id'])
                                        ->where('name', '=', $value)->get('id'))
                        {
                            return false;
                        }

                        return true;
                    });

            $rules = array(
                'name'           => 'required|max:127|uniquefixture',
                'number'         => 'integer|max:255',
                'competition_id' => 'required|exists:competitions,id',
                'season_id'      => 'required|exists:seasons,id'
            );

            $validator = Validator::make($raw_data, $rules, array('uniquefixture' => 'Nazwa kolejki musi być unikalna dla danego sezonu i rozgrywek'));

            if ($validator->fails())
            {
                return Redirect::to('admin/fixtures/add')->with_errors($validator)
                                ->with_input('only', array('name', 'number', 'competition_id', 'season_id'));
            }
            else
            {
                $prepared_data = array(
                    'name'           => HTML::specialchars($raw_data['name']),
                    'number'         => (int) $raw_data['number'],
                    'competition_id' => (int) $raw_data['competition_id'],
                    'season_id'      => (int) $raw_data['season_id']
                );

                if ($raw_data['number'] == '')
                {
                    if (ctype_digit($raw_data['name']))
                    {
                        $prepared_data['number'] = (int) $raw_data['name'];
                    }
                    else
                    {
                        $prepared_data['number'] = (int) preg_replace('/[^0-9]*/', '', $raw_data['name']);
                    }
                }

                $obj_id = DB::table('fixtures')->insert_get_id($prepared_data);

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano kolejkę: %s', $prepared_data['name']));
                return Redirect::to('admin/fixtures/index');
            }
        }

        $this->page->set_title('Dodawanie kolejki');

        $this->page->breadcrumb_append('Kolejki', 'admin/fixtures/index');
        $this->page->breadcrumb_append('Dodawanie kolejki', 'admin/fixtures/add');

        $this->view = View::make('admin.fixtures.add');

        $old_data = array('name'           => '', 'number'         => '', 'competition_id' => '', 'season_id'      => '');
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
        if (!Auth::can('admin_fixtures'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_fixtures_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('fixtures')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/fixtures/index');
        }

        DB::table('fixtures')->where('id', '=', $id->id)->delete();

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunięto kolejkę: %s', $id->name));
        return Redirect::to('admin/fixtures/index');
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_fixtures_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('fixtures')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('name'           => '', 'number'         => '', 'competition_id' => '', 'season_id'      => '');
            $raw_data = array_merge($raw_data, Input::only(array('name', 'number', 'competition_id', 'season_id')));

            Validator::register('uniquefixture', function($attribute, $value, $parameters) use ($raw_data) {

                        // it's error anyway
                        if (!ctype_digit($raw_data['competition_id']) or !ctype_digit($raw_data['season_id']) or !isset($parameters[0]))
                            return true;

                        if (DB::table('fixtures')->where('competition_id', '=', (int) $raw_data['competition_id'])
                                        ->where('season_id', '=', (int) $raw_data['season_id'])
                                        ->where('name', '=', $value)->where('id', '<>', (int) $parameters[0])->get('id'))
                        {
                            return false;
                        }

                        return true;
                    });

            $rules = array(
                'name'           => 'required|max:127|uniquefixture:'.$id->id,
                'number'         => 'integer|max:255',
                'competition_id' => 'required|exists:competitions,id',
                'season_id'      => 'required|exists:seasons,id'
            );

            $validator = Validator::make($raw_data, $rules, array('uniquefixture' => 'Nazwa kolejki musi być unikalna dla danego sezonu i rozgrywek'));

            if ($validator->fails())
            {
                return Redirect::to('admin/fixtures/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('name', 'number', 'competition_id', 'season_id'));
            }
            else
            {
                $prepared_data = array(
                    'name'           => HTML::specialchars($raw_data['name']),
                    'number'         => (int) $raw_data['number'],
                    'competition_id' => (int) $raw_data['competition_id'],
                    'season_id'      => (int) $raw_data['season_id']
                );

                if ($raw_data['number'] == '')
                {
                    if (ctype_digit($raw_data['name']))
                    {
                        $prepared_data['number'] = (int) $raw_data['name'];
                    }
                    else
                    {
                        $prepared_data['number'] = (int) preg_replace('/[^0-9]*/', '', $raw_data['name']);
                    }
                }

                \DB::table('fixtures')->where('id', '=', $id->id)->update($prepared_data);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono kolejkę: %s', $prepared_data['name']));
                return Redirect::to('admin/fixtures/index');
            }
        }

        $this->page->set_title('Edycja kolejki');

        $this->page->breadcrumb_append('Kolejki', 'admin/fixtures/index');
        $this->page->breadcrumb_append('Edycja kolejki', 'admin/fixtures/edit/'.$id->id);

        $this->view = View::make('admin.fixtures.edit');

        $old_data = array('name'           => '', 'number'         => '', 'competition_id' => '', 'season_id'      => '');
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
        if (!Auth::can('admin_fixtures'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_fixtures'))
            return Response::error(403);

        $this->page->set_title('Kolejki');
        $this->page->breadcrumb_append('Kolejki', 'admin/fixtures/index');

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
        if (!Auth::can('admin_fixtures_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_fixtures'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('fixtures', 'Kolejki', 'admin/fixtures');

        $grid->add_related('seasons', 'seasons.id', '=', 'fixtures.season_id');
        $grid->add_related('competitions', 'competitions.id', '=', 'fixtures.competition_id');

        $grid->add_column('id', 'ID', 'id', null, 'fixtures.id');
        $grid->add_column('name', 'Nazwa kolejki', 'name', 'fixtures.name', 'fixtures.name');
        $grid->add_column('season', 'Sezon', function($obj) {
                    return $obj->year.' / '.($obj->year + 1);
                }, 'seasons.year', 'seasons.year');
        $grid->add_column('competition', 'Rozgrywki', 'comp_name', 'competitions.name as comp_name', 'competitions.name');

        if (Auth::can('admin_fixtures_add'))
            $grid->add_button('Dodaj kolejkę', 'admin/fixtures/add', 'add-button');

        if (Auth::can('admin_fixtures_edit'))
            $grid->add_action('Edytuj', 'admin/fixtures/edit/%d', 'edit-button');

        if (Auth::can('admin_tables'))
        {
            $grid->add_action('Terminarz', 'admin/matches/fixture/%d', 'display-button');
        }

        if (Auth::can('admin_fixtures_delete'))
            $grid->add_action('Usuń', 'admin/fixtures/delete/%d', 'delete-button');

        if (Auth::can('admin_fixtures_delete') and Auth::can('admin_fixtures_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                        $affected = DB::table('fixtures')->where_in('id', $ids)->delete();

                        if ($affected > 0)
                            Model\Log::add('Masowo usunięto kolejki ('.$affected.')', $id);
                    });
        }

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('name', 'Nazwa kolejki', 'fixtures.name');

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

        foreach (DB::table('seasons')->get('year') as $s)
        {
            $seasons[$s->year] = $s->year.' / '.($s->year + 1);
        }

        $grid->add_filter_select('year', 'Sezon', $seasons, '_all_', 'seasons.year');

        return $grid;
    }

}