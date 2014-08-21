<?php
class Admin_Teams_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_teams_add')) return Response::error(403);

        $countries = ionic_country_list();

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('name' => '', 'is_distinct' => '', 'year' => '', 'address' => '', 'colors' => '', 'stadium' => '', 'chairman' => '', 'coach' => '', 'star' => '', 'webpage' => '', 'description' => '', 'country' => '');
            $raw_data = array_merge($raw_data, Input::only(array('name', 'is_distinct', 'year', 'address', 'colors', 'stadium', 'chairman', 'coach', 'star', 'webpage', 'description', 'country')));
            $raw_data['image'] = Input::file('image');

            $rules = array(
                'name' => 'required|max:127|unique:teams,name',
                'image' => 'image',
                'year' => 'integer|min:1800|max:2100',
                'address' => 'max:127',
                'colors' => 'max:127',
                'stadium' => 'max:127',
                'chairman' => 'max:127',
                'coach' => 'max:127',
                'star' => 'max:127',
                'webpage' => 'max:127|url',
                'country' => 'max:10'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/teams/add')->with_errors($validator)
                               ->with_input('only', array('name', 'is_distinct', 'year', 'address', 'colors', 'stadium', 'chairman', 'coach', 'star', 'webpage', 'description', 'country'));
            }
            else
            {
                $prepared_data = array(
                    'name' => HTML::specialchars($raw_data['name']),
                    'is_distinct' => ($raw_data['is_distinct'] == '1' ? 1 : 0),
                    'year' => (int) $raw_data['year'],
                    'address' => HTML::specialchars($raw_data['address']),
                    'colors' => HTML::specialchars($raw_data['colors']),
                    'stadium' => HTML::specialchars($raw_data['stadium']),
                    'chairman' => HTML::specialchars($raw_data['chairman']),
                    'coach' => HTML::specialchars($raw_data['coach']),
                    'star' => HTML::specialchars($raw_data['star']),
                    'webpage' => HTML::specialchars($raw_data['webpage']),
                    'description' => $raw_data['description'],
                    'slug' => ionic_tmp_slug('teams'),
                    'country' => HTML::specialchars($raw_data['country'])
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

                    while (file_exists(path('public').'upload'.DS.'teams'.DS.$filename))
                    {
                        $filename = Str::random(10).'.'.$extension;
                    }

                    move_uploaded_file($raw_data['image']['tmp_name'], path('public').'upload'.DS.'teams'.DS.$filename);

                    $prepared_data['image'] = $filename;
                }

                $obj_id = DB::table('teams')->insert_get_id($prepared_data);

                DB::table('teams')->where('id', '=', $obj_id)->update(array('slug' => ionic_find_slug($prepared_data['name'], $obj_id, 'teams')));

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano klub: %s', $prepared_data['name']));
                return Redirect::to('admin/teams/index');
            }
        }

        $this->page->set_title('Dodawanie klubu');

        $this->page->breadcrumb_append('Kluby', 'admin/teams/index');
        $this->page->breadcrumb_append('Dodawanie klubu', 'admin/teams/add');

        $this->view = View::make('admin.teams.add');

        $old_data = array('name' => '', 'is_distinct' => '', 'year' => '', 'address' => '', 'colors' => '', 'stadium' => '', 'chairman' => '', 'coach' => '', 'star' => '', 'webpage' => '', 'description' => '', 'country' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        Ionic\Editor::init();

        $this->view->with('countries', $countries);
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_teams')) return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_teams_delete') or !ctype_digit($id)) return Response::error(403);

        $id = DB::table('teams')->where('id', '=', (int) $id)->first('*');
        if (!$id) return Response::error(500);

        if (!Request::ajax() or !Config::get('advanced.admin_prefer_ajax', true))
        {
            if (!($status = $this->confirm()))
            {
                return;
            }
            elseif ($status == 2)
            {
                return Redirect::to('admin/teams/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        if ($id->image and file_exists(path('public').'upload'.DS.'teams'.DS.$id->image))
        {
            @unlink(path('public').'upload'.DS.'teams'.DS.$id->image);
            ionic_clear_thumbnails('teams', $id->image);
        }

        DB::table('teams')->where('id', '=', $id->id)->delete();

        $this->log(sprintf('Usunięto klub: %s', $id->name));

        if (!Request::ajax())
        {
            $this->notice('Rozgrywki usunięte pomyślnie');
            return Redirect::to('admin/teams/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_teams_edit') or !ctype_digit($id)) return Response::error(403);

        $id = DB::table('teams')->where('id', '=', (int) $id)->first('*');
        if (!$id) return Response::error(500);

        $countries = ionic_country_list();

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('name' => '', 'slug' => '', 'is_distinct' => '', 'year' => '', 'address' => '', 'colors' => '', 'stadium' => '', 'chairman' => '', 'coach' => '', 'star' => '', 'webpage' => '', 'description' => '', 'country' => '');
            $raw_data = array_merge($raw_data, Input::only(array('name', 'slug', 'is_distinct', 'year', 'address', 'colors', 'stadium', 'chairman', 'coach', 'star', 'webpage', 'description', 'country')));
            $raw_data['image'] = Input::file('image');

            $rules = array(
                'name' => 'required|max:127|unique:teams,name,'.$id->id,
                'slug' => 'required|max:127|alpha_dash|unique:teams,slug,'.$id->id.'',
                'image' => 'image',
                'year' => 'integer|min:1800|max:2100',
                'address' => 'max:127',
                'colors' => 'max:127',
                'stadium' => 'max:127',
                'chairman' => 'max:127',
                'coach' => 'max:127',
                'star' => 'max:127',
                'webpage' => 'max:127|url',
                'country' => 'max:10'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/teams/edit/'.$id->id)->with_errors($validator)
                               ->with_input('only', array('name', 'slug', 'is_distinct', 'year', 'address', 'colors', 'stadium', 'chairman', 'coach', 'star', 'webpage', 'description', 'country'));
            }
            else
            {
                $prepared_data = array(
                    'name' => HTML::specialchars($raw_data['name']),
                    'slug' => HTML::specialchars($raw_data['slug']),
                    'is_distinct' => ($raw_data['is_distinct'] == '1' ? '1' : '0'),
                    'year' => (int) $raw_data['year'],
                    'address' => HTML::specialchars($raw_data['address']),
                    'colors' => HTML::specialchars($raw_data['colors']),
                    'stadium' => HTML::specialchars($raw_data['stadium']),
                    'chairman' => HTML::specialchars($raw_data['chairman']),
                    'coach' => HTML::specialchars($raw_data['coach']),
                    'star' => HTML::specialchars($raw_data['star']),
                    'webpage' => HTML::specialchars($raw_data['webpage']),
                    'country' => HTML::specialchars($raw_data['country']),
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
                    if ($id->image and file_exists(path('public').'upload'.DS.'teams'.DS.$id->image))
                    {
                        @unlink(path('public').'upload'.DS.'teams'.DS.$id->image);
                        ionic_clear_thumbnails('teams', $id->image);
                    }

                    $filename = Str::ascii($raw_data['image']['name']);
                    $filename = preg_replace('![^\.\_\pL\pN\s]+!', '', $filename);
                    $filename = preg_replace('/\s+/', '_', $filename);
                    $extension = strtolower(substr(strrchr($filename, '.'), 1));

                    while (file_exists(path('public').'upload'.DS.'teams'.DS.$filename))
                    {
                        $filename = Str::random(10).'.'.$extension;
                    }

                    move_uploaded_file($raw_data['image']['tmp_name'], path('public').'upload'.DS.'teams'.DS.$filename);

                    $prepared_data['image'] = $filename;
                }

                \DB::table('teams')->where('id', '=', $id->id)->update($prepared_data);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono klub: %s', $prepared_data['name']));
                return Redirect::to('admin/teams/index');
            }
        }

        $this->page->set_title('Edycja klubu');

        $this->page->breadcrumb_append('Kluby', 'admin/teams/index');
        $this->page->breadcrumb_append('Edycja klubu', 'admin/teams/edit/'.$id->id);

        $this->view = View::make('admin.teams.edit');

        $old_data = array('name' => '', 'slug' => '', 'is_distinct' => '', 'year' => '', 'address' => '', 'colors' => '', 'stadium' => '', 'chairman' => '', 'coach' => '', 'star' => '', 'webpage' => '', 'description' => '', 'country' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);

        Ionic\Editor::init();

        $this->view->with('countries', $countries);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_teams')) return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_teams')) return Response::error(403);

        $this->page->set_title('Kluby');
        $this->page->breadcrumb_append('Kluby', 'admin/teams/index');

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
        if (!Auth::can('admin_teams_multi')) return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_teams')) return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('teams', 'Kluby', 'admin/teams');

        if (Auth::can('admin_teams_add')) $grid->add_button('Dodaj klub', 'admin/teams/add', 'add-button');

        if (Auth::can('admin_teams_edit'))
            $grid->add_action('Edytuj', 'admin/teams/edit/%d', 'edit-button');

        if (Auth::can('admin_players'))
            $grid->add_action('Piłkarze', 'admin/players/team/%d', 'display-button');

        if (Auth::can('admin_teams_delete'))
            $grid->add_action('Usuń', 'admin/teams/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        $grid->add_column('id', 'ID', 'id', null, 'teams.id');
        $grid->add_column('name', 'Nazwa klubu', 'name', 'teams.name', 'teams.name');
        $grid->add_column('is_distinct', 'Wyróżniony', function($obj) {
            if ($obj->is_distinct == 1) return '<img style="margin: 0px auto; display: block" src="public/img/icons/accept.png" alt="" />';
            return '';
        }, 'teams.is_distinct', 'teams.is_distinct');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('name', 'Nazwa klubu');
        $grid->add_filter_select('is_distinct', 'Wyróżniony', array(
            '_all_' => 'Wszystkie',
            1 => 'Tak',
            0 => 'Nie'
        ), '_all_');

        if (Auth::can('admin_teams_delete') and Auth::can('admin_teams_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                $affected = DB::table('teams')->where_in('id', $ids)->delete();

                if ($affected > 0)
                    Model\Log::add('Masowo usunięto kluby ('.$affected.')', $id);
            });
        }

        return $grid;
    }

}
