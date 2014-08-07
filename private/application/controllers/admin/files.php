<?php

class Admin_Files_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_files_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'       => '', 'description' => '', 'filename'    => '', 'category_id' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'description', 'filename', 'category_id')));
            $raw_data['filelocation'] = Input::file('filelocation');
            $raw_data['image'] = Input::file('image');

            $rules = array(
                'title'        => 'required|max:127',
                'filename'     => 'max:255|match:!^[\pL\pN\s\-\_\.]+\.[a-zA-Z]+$!u',
                'filelocation' => 'required',
                'image'        => 'image',
                'category_id'  => 'required|exists:file_categories,id'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/files/add')->with_errors($validator)
                                ->with_input('only', array('title', 'description', 'filename'));
            }
            else
            {
                $prepared_data = array(
                    'title'       => HTML::specialchars($raw_data['title']),
                    'description' => HTML::specialchars($raw_data['description']),
                    'filename'    => $raw_data['filename'],
                    'category_id' => (int) $raw_data['category_id'],
                    'user_id'     => $this->user->id,
                    'slug'        => ionic_tmp_slug('files'),
                    'created_at'  => date('Y-m-d H:i:s')
                );

                if (is_array($raw_data['filelocation']) and $raw_data['filelocation']['error'] == UPLOAD_ERR_OK and !empty($raw_data['filelocation']['name']) and !empty($raw_data['filelocation']['tmp_name']))
                {
                    $filename = Str::ascii($raw_data['filelocation']['name']);
                    $filename = preg_replace('![^\.\_\pL\pN\s]+!u', '', $filename);
                    $filename = preg_replace('/\s+/', '_', $filename);
                    $extension = strtolower(substr(strrchr($filename, '.'), 1));

                    if (!$extension)
                    {
                        $extension = 'txt';
                        $filename .= '.txt';
                    }

                    if (!$prepared_data['filename'])
                        $prepared_data['filename'] = $filename;

                    while (file_exists(path('storage').'files'.DS.$filename))
                    {
                        $filename = Str::random(10).'.'.$extension;
                    }

                    move_uploaded_file($raw_data['filelocation']['tmp_name'], path('storage').'files'.DS.$filename);

                    $prepared_data['filelocation'] = $filename;
                }
                else
                {
                    $this->notice('Wystąpił błąd podczas wrzucania pliku');
                    return Redirect::to('admin/files/add');
                }

                if (is_array($raw_data['image']) and $raw_data['image']['error'] == UPLOAD_ERR_OK and !empty($raw_data['image']['name']) and !empty($raw_data['image']['tmp_name']))
                {
                    $filename = Str::ascii($raw_data['image']['name']);
                    $filename = preg_replace('![^\.\_\pL\pN\s]+!u', '', $filename);
                    $filename = preg_replace('/\s+/', '_', $filename);
                    $extension = strtolower(substr(strrchr($filename, '.'), 1));

                    while (file_exists(path('public').'upload'.DS.'files'.DS.$filename))
                    {
                        $filename = Str::random(10).'.'.$extension;
                    }

                    move_uploaded_file($raw_data['image']['tmp_name'], path('public').'upload'.DS.'files'.DS.$filename);

                    $prepared_data['image'] = $filename;
                }

                $obj_id = DB::table('files')->insert_get_id($prepared_data);

                DB::table('files')->where('id', '=', $obj_id)->update(array('slug' => ionic_find_slug($prepared_data['title'], $obj_id, 'files')));

                ionic_clear_cache('files-*');

                $category = DB::table('file_categories')->where('id', '=', $prepared_data['category_id'])->first(array('left', 'right'));

                if ($category)
                {
                    DB::table('file_categories')->where('left', '<=', $category->left)->where('right', '>=', $category->right)->update(array(
                        'last_file_id' => $obj_id
                    ));
                }

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano plik: %s', $prepared_data['title']));
                return Redirect::to('admin/files/index');
            }
        }

        $this->page->set_title('Dodawanie pliku');

        $this->page->breadcrumb_append('Pliki', 'admin/files/index');
        $this->page->breadcrumb_append('Dodawanie pliku', 'admin/files/add');

        $this->view = View::make('admin.files.add');

        $old_data = array('title'       => '', 'description' => '', 'filename'    => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);
        $this->view->with('categories', Ionic\Tree::build_select('file_categories', 'title', ' &raquo; '));

        if (Session::has('files_cfilters'))
        {
            $applied = Session::get('files_cfilters');

            if (isset($applied['category_id']))
            {
                $this->view->with('current_cat', $applied['category_id']['val']);
            }
            else
            {
                $this->view->with('current_cat', 0);
            }
        }
        else
        {
            $this->view->with('current_cat', 0);
        }
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_files'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_cat($id)
    {
        if (!Auth::can('admin_files'))
            return Response::error(403);

        if (!$id or !ctype_digit($id))
            return Response::error(500);

        $id = DB::table('file_categories')->where('id', '=', (int) $id)->first('id');

        if (!$id)
            return Redirect::to('admin/files/index');

        $grid = $this->make_grid();

        $grid->set_manual_filter('category_id', $id->id, 'Kategoria');

        return Redirect::to('admin/files/index');
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_files_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('files')->where('id', '=', (int) $id)->first('*');
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
                return Redirect::to('admin/files/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        if (file_exists(path('storage').'files'.DS.$id->filelocation))
        {
            @unlink(path('storage').'files'.DS.$id->filelocation);
        }

        if ($id->image and is_file(path('public').'upload'.DS.'files'.DS.$id->image))
        {
            @unlink(path('public').'upload'.DS.'files'.DS.$id->image);
            ionic_clear_thumbnails('files', $id->image);
        }

        DB::table('files')->where('id', '=', $id->id)->delete();

        $category = DB::table('file_categories')->where('id', '=', $id->category_id)->first(array('left', 'right'));

        if ($category)
        {
            foreach (DB::table('file_categories')->where('left', '<=', $category->left)
                    ->where('right', '>=', $category->right)
                    ->where('last_file_id', '=', $id->id)->get(array('id', 'left', 'right')) as $c)
            {
                $file = DB::table('files')->order_by('files.id', 'desc')->join('file_categories', 'file_categories.id', '=', 'files.category_id')
                        ->where('file_categories.left', '>=', $c->left)
                        ->where('file_categories.right', '<=', $c->right)
                        ->first('files.id');

                if ($file)
                {
                    DB::table('file_categories')->where('id', '=', $c->id)->update(array('last_file_id' => $file->id));
                }
                else
                {
                    DB::table('file_categories')->where('id', '=', $c->id)->update(array('last_file_id' => 0));
                }
            }
        }

        $user_counts = array();
        $prepared_counts = array();

        foreach (DB::table('comments')->where('content_id', '=', $id->id)->where('content_type', '=', 'file')->get(array('user_id')) as $c)
        {
            if ($c->user_id != null)
            {
                if (!isset($user_counts[$c->user_id]))
                    $user_counts[$c->user_id] = 0;

                $user_counts[$c->user_id]++;
            }
        }

        foreach ($user_counts as $idd => $c)
        {
            if (!isset($prepared_counts[$c]))
                $prepared_counts[$c] = array();

            $prepared_counts[$c][] = $idd;
        }

        foreach ($prepared_counts as $c => $ids)
        {
            DB::table('profiles')->where('comments_count', '>=', $c)->where_in('user_id', $ids)->update(array('comments_count' => DB::raw('comments_count - '.$c)));
        }

        DB::table('comments')->where('content_id', '=', $id->id)->where('content_type', '=', 'file')->delete();

        ionic_clear_cache('files-*');

        $this->log(sprintf('Usunięto plik: %s', $id->title));

        if (!Request::ajax())
        {
            $this->notice('Plik usunięty pomyślnie');
            return Redirect::to('admin/files/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_files_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('files')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'       => '', 'slug'        => '', 'description' => '', 'filename'    => '', 'category_id' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'slug', 'description', 'filename', 'category_id')));
            $raw_data['filelocation'] = Input::file('filelocation');
            $raw_data['image'] = Input::file('image');

            $rules = array(
                'title'       => 'required|max:127',
                'slug'        => 'required|max:127|alpha_dash|unique:files,slug,'.$id->id,
                'filename'    => 'max:255|match:!^[\pL\pN\s\-\_\.]+\.[a-zA-Z]+$!u',
                'image'       => 'image',
                'category_id' => 'required|exists:file_categories,id'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/files/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('title', 'slug', 'description', 'filename'));
            }
            else
            {
                $prepared_data = array(
                    'title'       => HTML::specialchars($raw_data['title']),
                    'slug'        => HTML::specialchars($raw_data['slug']),
                    'description' => HTML::specialchars($raw_data['description']),
                    'filename'    => $raw_data['filename'],
                    'category_id' => (int) $raw_data['category_id']
                );

                if (is_array($raw_data['filelocation']) and $raw_data['filelocation']['error'] == UPLOAD_ERR_OK and !empty($raw_data['filelocation']['name']) and !empty($raw_data['filelocation']['tmp_name']))
                {
                    if (file_exists(path('storage').'files'.DS.$id->filelocation))
                    {
                        @unlink(path('storage').'files'.DS.$id->filelocation);
                    }

                    $filename = Str::ascii($raw_data['filelocation']['name']);
                    $filename = preg_replace('![^\.\_\pL\pN\s]+!u', '', $filename);
                    $filename = preg_replace('/\s+/', '_', $filename);
                    $extension = strtolower(substr(strrchr($filename, '.'), 1));

                    if (!$extension)
                    {
                        $extension = 'txt';
                        $filename .= '.txt';
                    }

                    if (!$prepared_data['filename'])
                        $prepared_data['filename'] = $filename;

                    while (file_exists(path('storage').'files'.DS.$filename))
                    {
                        $filename = Str::random(10).'.'.$extension;
                    }

                    move_uploaded_file($raw_data['filelocation']['tmp_name'], path('storage').'files'.DS.$filename);

                    $prepared_data['filelocation'] = $filename;
                }

                if (is_array($raw_data['image']) and $raw_data['image']['error'] == UPLOAD_ERR_OK and !empty($raw_data['image']['name']) and !empty($raw_data['image']['tmp_name']))
                {
                    if ($id->image and is_file(path('public').'upload'.DS.'files'.DS.$id->image))
                    {
                        @unlink(path('public').'upload'.DS.'files'.DS.$id->image);
                        ionic_clear_thumbnails('files', $id->image);
                    }

                    $filename = Str::ascii($raw_data['image']['name']);
                    $filename = preg_replace('![^\.\_\pL\pN\s]+!u', '', $filename);
                    $filename = preg_replace('/\s+/', '_', $filename);
                    $extension = strtolower(substr(strrchr($filename, '.'), 1));

                    while (file_exists(path('public').'upload'.DS.'files'.DS.$filename))
                    {
                        $filename = Str::random(10).'.'.$extension;
                    }

                    move_uploaded_file($raw_data['image']['tmp_name'], path('public').'upload'.DS.'files'.DS.$filename);

                    $prepared_data['image'] = $filename;
                }

                \DB::table('files')->where('id', '=', $id->id)->update($prepared_data);

                if ($id->category_id != $prepared_data['category_id'])
                {
                    // Old category
                    $category = DB::table('file_categories')->where('id', '=', $id->category_id)->first(array('left', 'right'));

                    if ($category)
                    {
                        foreach (DB::table('file_categories')->where('left', '<=', $category->left)
                                ->where('right', '>=', $category->right)
                                ->where('last_file_id', '=', $id->id)->get(array('id', 'left', 'right')) as $c)
                        {
                            $file = DB::table('files')->order_by('files.id', 'desc')->join('file_categories', 'file_categories.id', '=', 'files.category_id')
                                    ->where('file_categories.left', '>=', $c->left)
                                    ->where('file_categories.right', '<=', $c->right)
                                    ->first('files.id');

                            if ($file)
                            {
                                DB::table('file_categories')->where('id', '=', $c->id)->update(array('last_file_id' => $file->id));
                            }
                            else
                            {
                                DB::table('file_categories')->where('id', '=', $c->id)->update(array('last_file_id' => 0));
                            }
                        }
                    }

                    // New category
                    $category = DB::table('file_categories')->where('id', '=', $prepared_data['category_id'])->first(array('left', 'right'));

                    if ($category)
                    {
                        foreach (DB::table('file_categories')->where('left', '<=', $category->left)
                                ->where('right', '>=', $category->right)->get(array('id', 'left', 'right')) as $c)
                        {
                            $file = DB::table('files')->order_by('files.id', 'desc')->join('file_categories', 'file_categories.id', '=', 'files.category_id')
                                    ->where('file_categories.left', '>=', $c->left)
                                    ->where('file_categories.right', '<=', $c->right)
                                    ->first('files.id');

                            if ($file)
                            {
                                DB::table('file_categories')->where('id', '=', $c->id)->update(array('last_file_id' => $file->id));
                            }
                            else
                            {
                                DB::table('file_categories')->where('id', '=', $c->id)->update(array('last_file_id' => 0));
                            }
                        }
                    }
                }

                if ($prepared_data['slug'] != $id->slug)
                {
                    DB::table('comments')->where('content_type', '=', 'file')->where('content_id', '=', $id->id)->update(array(
                        'content_link' => 'galeria/'.$prepared_data['slug']
                    ));
                }

                ionic_clear_cache('files-*');

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono plik: %s', $prepared_data['title']));
                return Redirect::to('admin/files/index');
            }
        }

        $this->page->set_title('Edycja pliku');

        $this->page->breadcrumb_append('Pliki', 'admin/files/index');
        $this->page->breadcrumb_append('Edycja pliku', 'admin/files/edit/'.$id->id);

        $this->view = View::make('admin.files.edit');

        $old_data = array('title'       => '', 'slug'        => '', 'description' => '', 'filename'    => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);
        $this->view->with('categories', Ionic\Tree::build_select('file_categories', 'title', ' &raquo; '));
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_files'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_files'))
            return Response::error(403);

        $this->page->set_title('Pliki');
        $this->page->breadcrumb_append('Pliki', 'admin/files/index');

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

    public function action_sort($item)
    {
        if (!Auth::can('admin_files'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('files', 'Pliki', 'admin/files');

        $grid->add_related('users', 'users.id', '=', 'files.user_id');

        $grid->add_help('category', 'Aby filtrować według kategorii należy wejść w zarządzanie kategoriami oraz wybrać odpowiednią opcję po kliknięciu PPM na kategorię.');

        if (Auth::can('admin_files_add'))
            $grid->add_button('Dodaj plik', 'admin/files/add', 'add-button');
        if (Auth::can('admin_files_edit'))
            $grid->add_action('Edytuj', 'admin/files/edit/%d', 'edit-button');
        if (Auth::can('admin_files_delete'))
            $grid->add_action('Usuń', 'admin/files/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        $grid->add_column('id', 'ID', 'id', null, 'files.id');
        $grid->add_column('title', 'Tytuł', 'title', 'files.title', 'files.title');
        $grid->add_column('display_name', 'Dodał', 'display_name', 'users.display_name', 'users.display_name');
        $grid->add_column('created_at', 'Data dodania', 'created_at', 'files.created_at', 'files.created_at');
        $grid->add_column('downloads', 'Pobrań', 'downloads', 'files.downloads', 'files.downloads');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_date('created_at', 'Data dodania');
        $grid->add_filter_search('title', 'Tytuł');
        $grid->add_filter_search('display_name', 'Użytkownik', 'users.display_name');

        return $grid;
    }

}