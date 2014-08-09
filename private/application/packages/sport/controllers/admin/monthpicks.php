<?php

class Admin_Monthpicks_Controller extends Admin_Controller {

    public function action_active($id)
    {
        if (!Auth::can('admin_monthpicks_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('monthpicks')->where('id', '=', (int) $id)->first('*');
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
                return Redirect::to('admin/monthpicks/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        if ($id->is_active == 0)
        {
            DB::table('monthpicks')->where('is_active', '=', 1)->update(array('is_active' => 0));
            DB::table('monthpicks')->where('id', '=', $id->id)->update(array('is_active' => 1));
        }
        else
        {
            DB::table('monthpicks')->where('id', '=', $id->id)->update(array('is_active' => 0));
        }

        \Cache::forget('monthpick');

        $this->log(sprintf('Aktywowano/deaktywowano głosowanie: %s', $id->title));

        if (!Request::ajax())
        {
            $this->notice('Operacja wykonana pomyślnie');
            return Redirect::to('admin/monthpicks/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_add()
    {
        if (!Auth::can('admin_monthpicks_add'))
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
            $raw_data = array('title' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title')));

            $rules = array(
                'title' => 'required|max:127'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/monthpicks/add')->with_errors($validator)
                                ->with_input('only', array('title'));
            }
            else
            {
                $prepared_data = array(
                    'title'      => HTML::specialchars($raw_data['title']),
                    'created_at' => date('Y-m-d H:i:s')
                );

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

                        $players[$p] = array('player_id' => $p, 'name'      => $mapping[$p], 'votes'     => 0);
                    }
                }

                if (!empty($players))
                {
                    $prepared_data['options'] = serialize($players);
                }

                $obj_id = DB::table('monthpicks')->insert_get_id($prepared_data);

                \Cache::forget('monthpick');

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano głosowanie: %s', $prepared_data['title']));
                return Redirect::to('admin/monthpicks/index');
            }
        }

        $this->page->set_title('Dodawanie głosowania');

        $this->page->breadcrumb_append('Piłkarz miesiąca', 'admin/monthpicks/index');
        $this->page->breadcrumb_append('Dodawanie głosowania', 'admin/monthpicks/add');

        $this->view = View::make('admin.monthpicks.add');

        $old_data = array('title' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('players', $related);
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_monthpicks'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_monthpicks_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('monthpicks')->where('id', '=', (int) $id)->first('*');
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
                return Redirect::to('admin/monthpicks/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('monthpicks')->where('id', '=', $id->id)->delete();

        \Cache::forget('monthpick');

        $this->log(sprintf('Usunięto głosowanie: %s', $id->title));

        if (!Request::ajax())
        {
            $this->notice('Głosowanie usunięte pomyślnie');
            return Redirect::to('admin/monthpicks/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_monthpicks_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('monthpicks')->where('id', '=', (int) $id)->first('*');
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
            $raw_data = array_merge($raw_data, Input::only(array('title')));

            $rules = array(
                'title' => 'required|max:127'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/monthpicks/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('title'));
            }
            else
            {
                $prepared_data = array(
                    'title' => HTML::specialchars($raw_data['title'])
                );

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

                        $players[$p] = array('player_id' => $p, 'name'      => $mapping[$p], 'votes'     => isset($old_players[$p]) ? $old_players[$p]['votes'] : 0);
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
                        if ($p['votes'] > $score)
                        {
                            $score = $p['votes'];
                            $player = $p['player_id'];
                        }
                    }

                    $prepared_data['best_player_id'] = $player;
                }

                \DB::table('monthpicks')->where('id', '=', $id->id)->update($prepared_data);

                \Cache::forget('monthpick');

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono głosowanie: %s', $prepared_data['title']));
                return Redirect::to('admin/monthpicks/index');
            }
        }

        $this->page->set_title('Edycja głosowania');

        $this->page->breadcrumb_append('Piłkarz miesiąca', 'admin/monthpicks/index');
        $this->page->breadcrumb_append('Edycja głosowania', 'admin/monthpicks/edit/'.$id->id);

        $this->view = View::make('admin.monthpicks.edit');

        $old_data = array('title' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);

        $this->view->with('players', $related);
        $this->view->with('old_players', $old_players);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_monthpicks'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_monthpicks'))
            return Response::error(403);

        $this->page->set_title('Piłkarz miesiąca');
        $this->page->breadcrumb_append('Piłkarz miesiąca', 'admin/monthpicks/index');

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
        if (!Auth::can('admin_monthpicks_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_preview($id)
    {
        if (!Auth::can('admin_monthpicks') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('monthpicks')->where('id', '=', (int) $id)->first('*');
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

        $this->page->set_title('Piłkarz miesiąca');
        $this->page->breadcrumb_append('Piłkarz miesiąca', 'admin/monthpicks/index');
        $this->page->breadcrumb_append('Wyniki', 'admin/monthpicks/preview/'.$id->id);

        $this->view = View::make('admin.monthpicks.preview', array('pick'    => $id, 'options' => $options));
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_monthpicks'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('monthpicks', 'Piłkarz miesiąca', 'admin/monthpicks');

        $grid->add_action('Podgląd', 'admin/monthpicks/preview/%d', 'display-button');
        if (Auth::can('admin_monthpicks_add'))
            $grid->add_button('Dodaj głosowanie', 'admin/monthpicks/add', 'add-button');
        if (Auth::can('admin_monthpicks_edit'))
        {
            $grid->add_action('Aktywuj/deaktywuj', 'admin/monthpicks/active/%d', 'accept-button', Ionic\Grid::ACTION_BOTH);
            $grid->add_action('Edytuj', 'admin/monthpicks/edit/%d', 'edit-button');
        }
        if (Auth::can('admin_monthpicks_delete'))
            $grid->add_action('Usuń', 'admin/monthpicks/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        $grid->add_column('id', 'ID', 'id', null, 'monthpicks.id');
        $grid->add_column('title', 'Tytuł', 'title', 'monthpicks.title', 'monthpicks.title');
        $grid->add_column('created_at', 'Dodano', 'created_at', 'monthpicks.created_at', 'monthpicks.created_at');
        $grid->add_column('votes', 'Głosów', 'votes', 'monthpicks.votes', 'monthpicks.votes');
        $grid->add_column('is_active', 'Aktywne', function($obj) {
            if ($obj->is_active == 1)
                return '<img style="margin: 0px auto; display: block" src="public/img/icons/accept.png" alt="" />';
            return '';
        }, 'monthpicks.is_active', 'monthpicks.is_active');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('title', 'Tytuł');
        $grid->add_filter_date('created_at', 'Data dodania');

        if (Auth::can('admin_monthpicks_delete') and Auth::can('admin_monthpicks_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                $affected = DB::table('monthpicks')->where_in('id', $ids)->delete();

                if ($affected > 0)
                {
                    Model\Log::add('Masowo usunięto głosowania ('.$affected.')', $id);
                    \Cache::forget('monthpick');
                }
            });
        }

        return $grid;
    }

}