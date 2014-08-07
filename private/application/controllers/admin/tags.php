<?php

class Admin_Tags_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_tags_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title')));

            $rules = array(
                'title' => 'required|max:127|unique:tags,title'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/tags/add')->with_errors($validator)
                                ->with_input('only', array('title'));
            }
            else
            {
                $prepared_data = array(
                    'title' => HTML::specialchars($raw_data['title']),
                    'slug'  => ionic_tmp_slug('tags')
                );

                $obj_id = DB::table('tags')->insert_get_id($prepared_data);

                DB::table('tags')->where('id', '=', $obj_id)->update(array('slug' => ionic_find_slug($prepared_data['title'], $obj_id, 'tags')));

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano tag: %s', $prepared_data['title']));
                return Redirect::to('admin/tags/index');
            }
        }

        $this->page->set_title('Dodawanie tagu');

        $this->page->breadcrumb_append('Tagi', 'admin/tags/index');
        $this->page->breadcrumb_append('Dodawanie tagu', 'admin/tags/add');

        $this->view = View::make('admin.tags.add');

        $old_data = array('title' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_tags'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_tags_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('tags')->where('id', '=', (int) $id)->first('*');
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
                return Redirect::to('admin/tags/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('tags')->where('id', '=', $id->id)->delete();

        $this->log(sprintf('Usunięto tag: %s', $id->title));

        if (!Request::ajax())
        {
            $this->notice('Tag usunięty pomyślnie');
            return Redirect::to('admin/tags/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_tags_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('tags')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title' => '', 'slug'  => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'slug')));

            $rules = array(
                'title' => 'required|max:127|unique:tags,title,'.$id->id.'',
                'slug'  => 'required|max:127|alpha_dash|unique:tags,slug,'.$id->id.''
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/tags/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('title', 'slug'));
            }
            else
            {
                $prepared_data = array(
                    'title' => HTML::specialchars($raw_data['title']),
                    'slug'  => HTML::specialchars($raw_data['slug'])
                );

                \DB::table('tags')->where('id', '=', $id->id)->update($prepared_data);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono tag: %s', $prepared_data['title']));
                return Redirect::to('admin/tags/index');
            }
        }

        $this->page->set_title('Edycja tagu');

        $this->page->breadcrumb_append('Tagi', 'admin/tags/index');
        $this->page->breadcrumb_append('Edycja tagu', 'admin/tags/edit/'.$id->id);

        $this->view = View::make('admin.tags.edit');

        $old_data = array('title' => '', 'slug'  => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_tags'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_tags'))
            return Response::error(403);

        $this->page->set_title('Tagi');
        $this->page->breadcrumb_append('Tagi', 'admin/tags/index');

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
        if (!Auth::can('admin_tags_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_tags'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('tags', 'Tagi', 'admin/tags');

        if (Auth::can('admin_tags_add'))
            $grid->add_button('Dodaj tag', 'admin/tags/add', 'add-button');
        if (Auth::can('admin_tags_edit'))
            $grid->add_action('Edytuj', 'admin/tags/edit/%d', 'edit-button');
        if (Auth::can('admin_tags_delete'))
            $grid->add_action('Usuń', 'admin/tags/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        $grid->add_column('id', 'ID', 'id', null, 'tags.id');
        $grid->add_column('title', 'Tytuł', 'title', 'tags.title', 'tags.title');

        if (Auth::can('admin_tags_delete') and Auth::can('admin_tags_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                        $affected = DB::table('tags')->where_in('id', $ids)->delete();

                        if ($affected)
                            \Model\Log::add('Masowo usunął tagi ('.$affected.')', $id);
                    });
        }

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('title', 'Tytuł');

        return $grid;
    }

}