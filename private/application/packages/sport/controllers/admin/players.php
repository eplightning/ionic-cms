<?php

class Admin_Players_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_players_add'))
            return Response::error(403);

        $countries = ionic_country_list();

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('name'        => '', 'number'      => '', 'position'    => '', 'team_id'     => '', 'date'        => '', 'height'      => '', 'weight'      => '', 'cost'        => '', 'prev_club'   => '', 'birthplace'  => '', 'country'     => '', 'is_on_loan'  => '', 'description' => '');
            $raw_data = array_merge($raw_data, Input::only(array('name', 'number', 'position', 'team_id', 'date', 'height', 'weight', 'cost', 'prev_club', 'birthplace', 'country', 'is_on_loan', 'description')));
            $raw_data['image'] = Input::file('image');

            $rules = array(
                'name'       => 'required|max:127|unique:players,name',
                'number'     => 'required|integer|max:255',
                'position'   => 'required|max:127',
                'team_id'    => 'required|exists:teams,id',
                'image'      => 'image',
                'date'       => 'match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!',
                'height'     => 'integer',
                'weight'     => 'integer',
                'cost'       => 'max:127',
                'prev_club'  => 'max:127',
                'birthplace' => 'max:127',
                'country'    => 'max:10',
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/players/add')->with_errors($validator)
                                ->with_input('only', array('name', 'number', 'position', 'team_id', 'date', 'height', 'weight', 'cost', 'prev_club', 'birthplace', 'country', 'is_on_loan', 'description'));
            }
            else
            {
                $prepared_data = array(
                    'name'        => HTML::specialchars($raw_data['name']),
                    'number'      => (int) $raw_data['number'],
                    'position'    => HTML::specialchars($raw_data['position']),
                    'team_id'     => $raw_data['team_id'],
                    'date'        => $raw_data['date'],
                    'height'      => (int) $raw_data['height'],
                    'weight'      => (int) $raw_data['weight'],
                    'cost'        => HTML::specialchars($raw_data['cost']),
                    'prev_club'   => HTML::specialchars($raw_data['prev_club']),
                    'birthplace'  => HTML::specialchars($raw_data['birthplace']),
                    'country'     => HTML::specialchars($raw_data['country']),
                    'is_on_loan'  => ($raw_data['is_on_loan'] == '1' ? '1' : '0'),
                    'description' => $raw_data['description'],
                    'slug'        => ionic_tmp_slug('players')
                );

                if ($prepared_data['country'] and !isset($countries[$prepared_data['country']]))
                {
                    $prepared_data['country'] = '';
                }

                if (!Auth::can('admin_root'))
                {
                    require_once path('app').'vendor'.DS.'htmLawed.php';

                    $prepared_data['description'] = htmLawed($prepared_data['description'], array('safe' => 1));
                }

                if (is_array($raw_data['image']) and $raw_data['image']['error'] == UPLOAD_ERR_OK and !empty($raw_data['image']['name']) and !empty($raw_data['image']['tmp_name']))
                {
                    $filename = Str::ascii($raw_data['image']['name']);
                    $filename = preg_replace('![^\.\_\pL\pN\s]+!u', '', $filename);
                    $filename = preg_replace('/\s+/', '_', $filename);
                    $extension = strtolower(substr(strrchr($filename, '.'), 1));

                    while (file_exists(path('public').'upload'.DS.'players'.DS.$filename))
                    {
                        $filename = Str::random(10).'.'.$extension;
                    }

                    move_uploaded_file($raw_data['image']['tmp_name'], path('public').'upload'.DS.'players'.DS.$filename);

                    $prepared_data['image'] = $filename;
                }

                $obj_id = DB::table('players')->insert_get_id($prepared_data);

                DB::table('players')->where('id', '=', $obj_id)->update(array('slug' => ionic_find_slug($prepared_data['name'], $obj_id, 'players')));

                ionic_clear_cache('birthdays-*');

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano zawodnika: %s', $prepared_data['name']));
                return Redirect::to('admin/players/index');
            }
        }

        $this->page->set_title('Dodawanie zawodnika');

        $this->page->breadcrumb_append('Zawodnicy', 'admin/players/index');
        $this->page->breadcrumb_append('Dodawanie zawodnika', 'admin/players/add');

        $this->view = View::make('admin.players.add');

        $old_data = array('name'        => '', 'number'      => '', 'position'    => '', 'team_id'     => '', 'date'        => '', 'height'      => '', 'weight'      => '', 'cost'        => '', 'prev_club'   => '', 'birthplace'  => '', 'country'     => '', 'is_on_loan'  => '', 'description' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        Ionic\Editor::init();

        $related = array();

        foreach (DB::table('teams')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $this->view->with('related_team_id', $related);
        $this->view->with('countries', $countries);
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_players'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_players_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('players')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/players/index');
        }

        DB::table('players')->where('id', '=', $id->id)->delete();

        ionic_clear_cache('birthdays-*');

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunięto zawodnika: %s', $id->name));
        return Redirect::to('admin/players/index');
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_players_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('players')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        $countries = ionic_country_list();

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('name'        => '', 'slug'        => '', 'number'      => '', 'position'    => '', 'team_id'     => '', 'date'        => '', 'height'      => '', 'weight'      => '', 'cost'        => '', 'prev_club'   => '', 'birthplace'  => '', 'country'     => '', 'is_on_loan'  => '', 'description' => '');
            $raw_data = array_merge($raw_data, Input::only(array('name', 'slug', 'number', 'position', 'team_id', 'date', 'height', 'weight', 'cost', 'prev_club', 'birthplace', 'country', 'is_on_loan', 'description')));
            $raw_data['image'] = Input::file('image');

            $rules = array(
                'name'       => 'required|max:127|unique:players,name,'.$id->id.'',
                'slug'       => 'required|max:127|alpha_dash|unique:players,slug,'.$id->id.'',
                'number'     => 'required|integer|max:255',
                'position'   => 'required|max:127',
                'team_id'    => 'required|exists:teams,id',
                'image'      => 'image',
                'date'       => 'match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!',
                'height'     => 'integer',
                'weight'     => 'integer',
                'cost'       => 'max:127',
                'prev_club'  => 'max:127',
                'birthplace' => 'max:127',
                'country'    => 'max:10',
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/players/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('name', 'slug', 'number', 'position', 'team_id', 'date', 'height', 'weight', 'cost', 'prev_club', 'birthplace', 'country', 'is_on_loan', 'description'));
            }
            else
            {
                $prepared_data = array(
                    'name'        => HTML::specialchars($raw_data['name']),
                    'slug'        => HTML::specialchars($raw_data['slug']),
                    'number'      => (int) $raw_data['number'],
                    'position'    => HTML::specialchars($raw_data['position']),
                    'team_id'     => $raw_data['team_id'],
                    'date'        => $raw_data['date'],
                    'height'      => (int) $raw_data['height'],
                    'weight'      => (int) $raw_data['weight'],
                    'cost'        => HTML::specialchars($raw_data['cost']),
                    'prev_club'   => HTML::specialchars($raw_data['prev_club']),
                    'birthplace'  => HTML::specialchars($raw_data['birthplace']),
                    'country'     => HTML::specialchars($raw_data['country']),
                    'is_on_loan'  => ($raw_data['is_on_loan'] == '1' ? '1' : '0'),
                    'description' => $raw_data['description']
                );

                if ($prepared_data['country'] and !isset($countries[$prepared_data['country']]))
                {
                    $prepared_data['country'] = '';
                }

                if (!Auth::can('admin_root'))
                {
                    require_once path('app').'vendor'.DS.'htmLawed.php';

                    $prepared_data['description'] = htmLawed($prepared_data['description'], array('safe' => 1));
                }

                if (is_array($raw_data['image']) and $raw_data['image']['error'] == UPLOAD_ERR_OK and !empty($raw_data['image']['name']) and !empty($raw_data['image']['tmp_name']))
                {
                    if ($id->image and is_file(path('public').'upload'.DS.'players'.DS.$id->image))
                    {
                        @unlink(path('public').'upload'.DS.'players'.DS.$id->image);
                        ionic_clear_thumbnails('players', $id->image);
                    }

                    $filename = Str::ascii($raw_data['image']['name']);
                    $filename = preg_replace('![^\.\_\pL\pN\s]+!', '', $filename);
                    $filename = preg_replace('/\s+/', '_', $filename);
                    $extension = strtolower(substr(strrchr($filename, '.'), 1));

                    while (file_exists(path('public').'upload'.DS.'players'.DS.$filename))
                    {
                        $filename = Str::random(10).'.'.$extension;
                    }

                    move_uploaded_file($raw_data['image']['tmp_name'], path('public').'upload'.DS.'players'.DS.$filename);

                    $prepared_data['image'] = $filename;
                }

                \DB::table('players')->where('id', '=', $id->id)->update($prepared_data);

                ionic_clear_cache('birthdays-*');

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono zawodnika: %s', $prepared_data['name']));
                return Redirect::to('admin/players/index');
            }
        }

        $this->page->set_title('Edycja zawodnika');

        $this->page->breadcrumb_append('Zawodnicy', 'admin/players/index');
        $this->page->breadcrumb_append('Edycja zawodnika', 'admin/players/edit/'.$id->id);

        $this->view = View::make('admin.players.edit');

        $old_data = array('name'        => '', 'slug'        => '', 'number'      => '', 'position'    => '', 'team_id'     => '', 'date'        => '', 'height'      => '', 'weight'      => '', 'cost'        => '', 'prev_club'   => '', 'birthplace'  => '', 'country'     => '', 'is_on_loan'  => '', 'description' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);

        Ionic\Editor::init();

        $related = array();

        foreach (DB::table('teams')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $this->view->with('related_team_id', $related);
        $this->view->with('countries', $countries);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_players'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_players'))
            return Response::error(403);

        $this->page->set_title('Zawodnicy');
        $this->page->breadcrumb_append('Zawodnicy', 'admin/players/index');

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
        if (!Auth::can('admin_players_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_players'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    public function action_team($id)
    {
        if (!Auth::can('admin_players') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('teams')->where('id', '=', (int) $id)->first('name');

        if (!$id)
            return Redirect::to('admin/players/index');

        if (Session::has('players_filters'))
        {
            $applied = Session::get('players_filters');
        }
        else
        {
            $applied = array();
        }

        $applied['team_name'] = $id->name;

        Session::put('players_filters', $applied);

        return Redirect::to('admin/players/index');
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('players', 'Zawodnicy', 'admin/players');

        $grid->add_related('teams', 'teams.id', '=', 'players.team_id', array('teams.is_distinct'));

        $grid->add_column('id', 'ID', 'id', null, 'players.id');
        $grid->add_column('number', 'Numer', 'number', 'players.number', 'players.number');
        $grid->add_column('name', 'Imię i nazwisko', 'name', 'players.name', 'players.name');
        $grid->add_column('team', 'Klub', 'team_name', 'teams.name as team_name', 'teams.name');

        if (Auth::can('admin_players_add'))
            $grid->add_button('Dodaj zawodnika', 'admin/players/add', 'add-button');
        if (Auth::can('admin_players_edit'))
            $grid->add_action('Edytuj', 'admin/players/edit/%d', 'edit-button');
        if (Auth::can('admin_players_delete'))
            $grid->add_action('Usuń', 'admin/players/delete/%d', 'delete-button');

        if (Auth::can('admin_players_delete') and Auth::can('admin_players_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                        $affected = DB::table('players')->where_in('id', $ids)->delete();

                        if ($affected > 0)
                            Model\Log::add('Masowo usunięto zawodników ('.$affected.')', $id);

                        ionic_clear_cache('birthdays-*');
                    });
        }

        $grid->add_filter_perpage(array(20, 30, 50));

        $grid->add_filter_search('name', 'Imię i nazwisko', 'players.name');

        $grid->add_filter_autocomplete('team_name', 'Klub', function($str) {
                    $us = DB::table('teams')->take(20)->where('name', 'like', str_replace('%', '', $str).'%')->get('name');

                    $result = array();

                    foreach ($us as $u)
                    {
                        $result[] = $u->name;
                    }

                    return $result;
                }, 'teams.name');

        $grid->add_filter_select('is_distinct', 'Wyróżniony klub', array(
            '_all_' => 'Wszystkie',
            1       => 'Tak',
            0       => 'Nie'
                ), '_all_', 'teams.is_distinct');


        return $grid;
    }

}