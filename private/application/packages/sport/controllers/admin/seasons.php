<?php

class Admin_Seasons_Controller extends Admin_Controller {

    public function action_active($id)
    {
        if (!Auth::can('admin_seasons') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('seasons')->where('id', '=', (int) $id)->first('*');
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
                return Redirect::to('admin/seasons/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('seasons')->where('is_active', '=', 1)->update(array('is_active' => 0));
        DB::table('seasons')->where('id', '=', $id->id)->update(array('is_active' => 1));

        Cache::forget('current-season');

        $this->log(sprintf('Ustawiono sezon jako obecny: %s', $id->year));

        if (!Request::ajax())
        {
            $this->notice('Operacja wykonana pomyślnie');
            return Redirect::to('admin/seasons/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_add()
    {
        if (!Auth::can('admin_seasons_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('year' => '');
            $raw_data = array_merge($raw_data, Input::only(array('year')));

            $rules = array(
                'year' => 'integer|min:1800|max:2100|unique:seasons,year'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/seasons/add')->with_errors($validator)
                                ->with_input('only', array('year'));
            }
            else
            {
                $prepared_data = array(
                    'year' => (int) $raw_data['year']
                );

                $obj_id = DB::table('seasons')->insert_get_id($prepared_data);

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano sezon: %s', $prepared_data['year']));
                return Redirect::to('admin/seasons/index');
            }
        }

        $this->page->set_title('Dodawanie sezonu');

        $this->page->breadcrumb_append('Sezony', 'admin/seasons/index');
        $this->page->breadcrumb_append('Dodawanie sezonu', 'admin/seasons/add');

        $this->view = View::make('admin.seasons.add');

        $old_data = array('year' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_seasons'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_seasons_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('seasons')->where('id', '=', (int) $id)->first('*');
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
                return Redirect::to('admin/seasons/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('seasons')->where('id', '=', $id->id)->delete();

        Cache::forget('current-season');

        $this->log(sprintf('Usunięto sezon: %s', $id->year));

        if (!Request::ajax())
        {
            $this->notice('Obiekt usunięty pomyślnie');
            return Redirect::to('admin/seasons/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_seasons_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('seasons')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('year' => '');
            $raw_data = array_merge($raw_data, Input::only(array('year')));

            $rules = array(
                'year' => 'integer|min:1800|max:2100|unique:seasons,year,'.$id->id.''
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/seasons/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('year'));
            }
            else
            {
                $prepared_data = array(
                    'year' => (int) $raw_data['year']
                );

                \DB::table('seasons')->where('id', '=', $id->id)->update($prepared_data);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono sezon: %s', $prepared_data['year']));
                return Redirect::to('admin/seasons/index');
            }
        }

        $this->page->set_title('Edycja sezonu');

        $this->page->breadcrumb_append('Sezony', 'admin/seasons/index');
        $this->page->breadcrumb_append('Edycja sezonu', 'admin/seasons/edit/'.$id->id);

        $this->view = View::make('admin.seasons.edit');

        $old_data = array('year' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_seasons'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_seasons'))
            return Response::error(403);

        $this->page->set_title('Sezony');
        $this->page->breadcrumb_append('Sezony', 'admin/seasons/index');

        $grid = $this->make_grid();

        $result = $grid->handle_index($id);

        if ($result instanceof Ionic\View)
        {
            $this->view = $result;
        }
        elseif ($result instanceof Laravel\Response)
        {
            return $result;
        }
    }

    public function action_multiaction($name)
    {
        if (!Auth::can('admin_seasons'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_seasons'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('seasons', 'Sezony', 'admin/seasons');

        $grid->add_column('id', 'ID', 'id', null, 'seasons.id');
        $grid->add_column('name', 'Sezon', function($obj) {
                    return 'Sezon: '.($obj->year).' / '.($obj->year + 1);
                }, 'seasons.year', 'seasons.year');
        $grid->add_column('is_active', 'Obecny', function($obj) {
                    if ($obj->is_active == 1)
                        return '<img style="margin: 0px auto; display: block" src="public/img/icons/accept.png" alt="" />';
                    return '';
                }, 'seasons.is_active', 'seasons.is_active');

        if (Auth::can('admin_seasons_add'))
            $grid->add_button('Dodaj sezon', 'admin/seasons/add', 'add-button');

        if (Auth::can('admin_seasons_edit'))
            $grid->add_action('Edytuj', 'admin/seasons/edit/%d', 'edit-button');

        $grid->add_action('Ustaw jako obecny', 'admin/seasons/active/%d', 'accept-button', Ionic\Grid::ACTION_BOTH);

        if (Auth::can('admin_seasons_delete'))
            $grid->add_action('Usuń', 'admin/seasons/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_select('is_active', 'Obecny sezon', array(
            '_all_' => 'Wszystkie',
            1       => 'Tak',
            0       => 'Nie'
        ), '_all_');

        return $grid;
    }

}