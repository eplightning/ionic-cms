<?php

class Admin_Photos_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_photos_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST' and Input::get('category_id') and ctype_digit(Input::get('category_id')) and !empty($_FILES))
        {
            $category = DB::table('photo_categories')->where('id', '=', (int) Input::get('category_id'))->first(array('id', 'left', 'right'));

            if (!$category)
            {
                $this->notice('Nieprawidłowa kategoria');
                return Redirect::to('admin/photos/add');
            }

            $use_watermark = (bool) Config::get('gallery.watermark', false);

            if ($use_watermark)
            {
                $watermark_image = path('base').basename(Config::get('gallery.watermark_image'));
                $watermark_left = Config::get('gallery.watermark_horizontal', 'right');
                $watermark_right = Config::get('gallery.watermark_vertical', 'bottom');

                if (file_exists($watermark_image))
                {
                    try {
                        $watermark_image = WideImage::loadFromFile($watermark_image);
                    } catch (Exception $e) {
                        $use_watermark = false;
                    }
                }
                else
                {
                    $use_watermark = false;
                }
            }

            $number = 0;
            $last_id = null;

            if (!empty($_FILES['photos-multi']) and is_array($_FILES['photos-multi']) and isset($_FILES['photos-multi']['tmp_name'][0]))
            {
                $count = count($_FILES['photos-multi']['tmp_name']);

                for ($i = 0; $i < $count; $i++)
                {
                    if (empty($_FILES['photos-multi']['name'][$i]) or empty($_FILES['photos-multi']['tmp_name'][$i]))
                        continue;

                    $file = array(
                        'name'     => $_FILES['photos-multi']['name'][$i],
                        'tmp_name' => $_FILES['photos-multi']['tmp_name'][$i],
                        'error'    => $_FILES['photos-multi']['error'][$i]
                    );

                    if ($file['error'] != UPLOAD_ERR_OK)
                        continue;

                    if (!File::is(array('jpg', 'gif', 'png'), $file['tmp_name'], $file['name']))
                    {
                        continue;
                    }

                    try {
                        $image = WideImage::loadFromFile($file['tmp_name']);

                        if ($use_watermark)
                        {
                            $image = $image->merge($watermark_image, $watermark_left, $watermark_right);
                        }
                    } catch (Exception $e) {
                        continue;
                    }

                    $filename = Str::ascii($file['name']);
                    $filename = preg_replace('![^\.\_\pL\pN\s]+!u', '', $filename);
                    $filename = preg_replace('/\s+/', '_', $filename);
                    $extension = strtolower(substr(strrchr($filename, '.'), 1));

                    if (!$extension)
                    {
                        $filename .= '.png';
                        $extension = 'png';
                    }

                    $used_name = $filename;

                    while (file_exists(path('public').'upload'.DS.'photos'.DS.$used_name))
                    {
                        $used_name = Str::random(10).'.'.$extension;
                    }

                    $image->saveToFile(path('public').'upload'.DS.'photos'.DS.$used_name);

                    $last_id = DB::table('photos')->insert_get_id(array(
                        'user_id'     => $this->user->id,
                        'category_id' => $category->id,
                        'title'       => HTML::specialchars($filename),
                        'created_at'  => date('Y-m-d H:i:s'),
                        'image'       => $used_name
                            ));

                    $number++;
                }
            }

            if (isset($_FILES['photos-multi']))
                unset($_FILES['photos-multi']);

            foreach ($_FILES as $file)
            {
                if (is_array($file) and !empty($file['tmp_name']) and !empty($file['name']) and $file['error'] == UPLOAD_ERR_OK)
                {
                    if (!File::is(array('jpg', 'gif', 'png'), $file['tmp_name'], $file['name']))
                    {
                        continue;
                    }

                    try {
                        $image = WideImage::loadFromFile($file['tmp_name']);

                        if ($use_watermark)
                        {
                            $image = $image->merge($watermark_image, $watermark_left, $watermark_right);
                        }
                    } catch (Exception $e) {
                        continue;
                    }

                    $filename = Str::ascii($file['name']);
                    $filename = preg_replace('![^\.\_\pL\pN\s]+!u', '', $filename);
                    $filename = preg_replace('/\s+/', '_', $filename);
                    $extension = strtolower(substr(strrchr($filename, '.'), 1));

                    if (!$extension)
                    {
                        $filename .= '.png';
                        $extension = 'png';
                    }

                    $used_name = $filename;

                    while (file_exists(path('public').'upload'.DS.'photos'.DS.$used_name))
                    {
                        $used_name = Str::random(10).'.'.$extension;
                    }

                    $image->saveToFile(path('public').'upload'.DS.'photos'.DS.$used_name);

                    $last_id = DB::table('photos')->insert_get_id(array(
                        'user_id'     => $this->user->id,
                        'category_id' => $category->id,
                        'title'       => HTML::specialchars($filename),
                        'created_at'  => date('Y-m-d H:i:s'),
                        'image'       => $used_name
                            ));

                    $number++;
                }
            }

            if (!$number or !$last_id)
            {
                $this->notice('Żaden z obrazów nie mógł zostać dodany');
                return Redirect::to('admin/photos/add');
            }

            DB::table('photo_categories')->where('left', '<=', $category->left)->where('right', '>=', $category->right)->update(array(
                'last_photo_id' => $last_id
            ));

            ionic_clear_cache('photos-*');

            $this->notice('Dodano pomyślnie '.$number.' obrazów do galerii');
            $this->log(sprintf('Dodano obrazy do galerii (%d)', $number));
            return Redirect::to('admin/photos/index');
        }

        $this->page->set_title('Dodawanie zdjęć');

        $this->page->breadcrumb_append('Galeria', 'admin/photos/index');
        $this->page->breadcrumb_append('Dodawanie zdjęć', 'admin/photos/add');

        $this->view = View::make('admin.photos.add');

        $this->view->with('categories', Ionic\Tree::build_select('photo_categories', 'title', ' &raquo; '))->with('max_file_uploads', ini_get('max_file_uploads'));

        if (Session::has('photos_cfilters'))
        {
            $applied = Session::get('photos_cfilters');

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
        if (!Auth::can('admin_photos'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_cat($id)
    {
        if (!Auth::can('admin_photos'))
            return Response::error(403);

        if (!$id or !ctype_digit($id))
            return Response::error(500);

        $id = DB::table('photo_categories')->where('id', '=', (int) $id)->first('id');

        if (!$id)
            return Redirect::to('admin/photos/index');

        $grid = $this->make_grid();

        $grid->set_manual_filter('category_id', $id->id, 'Kategoria');

        return Redirect::to('admin/photos/index');
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_photos_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('photos')->where('id', '=', (int) $id)->first('*');
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
                return Redirect::to('admin/photos/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        if (is_file(path('public').'upload'.DS.'photos'.DS.$id->image))
        {
            @unlink(path('public').'upload'.DS.'photos'.DS.$id->image);
            ionic_clear_thumbnails('photos', $id->image);
        }

        DB::table('photos')->where('id', '=', $id->id)->delete();

        $category = DB::table('photo_categories')->where('id', '=', $id->category_id)->first(array('left', 'right'));

        if ($category)
        {
            foreach (DB::table('photo_categories')->where('left', '<=', $category->left)
                    ->where('right', '>=', $category->right)
                    ->where('last_photo_id', '=', $id->id)->get(array('id', 'left', 'right')) as $c)
            {
                $photo = DB::table('photos')->order_by('photos.id', 'desc')->join('photo_categories', 'photo_categories.id', '=', 'photos.category_id')
                        ->where('photo_categories.left', '>=', $c->left)
                        ->where('photo_categories.right', '<=', $c->right)
                        ->first('photos.id');

                if ($photo)
                {
                    DB::table('photo_categories')->where('id', '=', $c->id)->update(array('last_photo_id' => $photo->id));
                }
                else
                {
                    DB::table('photo_categories')->where('id', '=', $c->id)->update(array('last_photo_id' => 0));
                }
            }
        }

        ionic_clear_cache('photos-*');

        $this->log(sprintf('Usunięto zdjęcie: %s', $id->title));

        if (!Request::ajax())
        {
            $this->notice('Zdjęcie usunięte pomyślnie');
            return Redirect::to('admin/photos/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_photos_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('photos')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'       => '', 'category_id' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'category_id')));
            $raw_data['image'] = Input::file('image');

            $rules = array(
                'title'       => 'required|max:127',
                'image'       => 'image',
                'category_id' => 'required|exists:photo_categories,id'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/photos/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('title'));
            }
            else
            {
                $prepared_data = array(
                    'title'       => HTML::specialchars($raw_data['title']),
                    'category_id' => (int) $raw_data['category_id']
                );

                if (is_array($raw_data['image']) and $raw_data['image']['error'] == UPLOAD_ERR_OK and !empty($raw_data['image']['name']) and !empty($raw_data['image']['tmp_name']))
                {
                    if (is_file(path('public').'upload'.DS.'photos'.DS.$id->image))
                    {
                        @unlink(path('public').'upload'.DS.'photos'.DS.$id->image);
                        ionic_clear_thumbnails('photos', $id->image);
                    }

                    $filename = Str::ascii($raw_data['image']['name']);
                    $filename = preg_replace('![^\.\_\pL\pN\s]+!u', '', $filename);
                    $filename = preg_replace('/\s+/', '_', $filename);
                    $extension = strtolower(substr(strrchr($filename, '.'), 1));

                    while (file_exists(path('public').'upload'.DS.'photos'.DS.$filename))
                    {
                        $filename = Str::random(10).'.'.$extension;
                    }

                    if (Config::get('gallery.watermark', false))
                    {
                        $watermark_image = path('base').basename(Config::get('gallery.watermark_image'));

                        if (file_exists($watermark_image))
                        {
                            try {
                                $watermark_image = WideImage::loadFromFile($watermark_image);
                                $image = WideImage::loadFromFile($raw_data['image']['tmp_name']);

                                $image = $image->merge($watermark_image, Config::get('gallery.watermark_horizontal', 'right'), Config::get('gallery.watermark_vertical', 'bottom'));

                                $image->saveToFile(path('public').'upload'.DS.'photos'.DS.$filename);

                                $prepared_data['image'] = $filename;
                            } catch (Exception $e) {
                                move_uploaded_file($raw_data['image']['tmp_name'], path('public').'upload'.DS.'photos'.DS.$filename);

                                $prepared_data['image'] = $filename;
                            }
                        }
                        else
                        {
                            move_uploaded_file($raw_data['image']['tmp_name'], path('public').'upload'.DS.'photos'.DS.$filename);

                            $prepared_data['image'] = $filename;
                        }
                    }
                    else
                    {
                        move_uploaded_file($raw_data['image']['tmp_name'], path('public').'upload'.DS.'photos'.DS.$filename);

                        $prepared_data['image'] = $filename;
                    }
                }

                \DB::table('photos')->where('id', '=', $id->id)->update($prepared_data);

                if ($id->category_id != $prepared_data['category_id'])
                {
                    // Old category
                    $category = DB::table('photo_categories')->where('id', '=', $id->category_id)->first(array('left', 'right'));

                    if ($category)
                    {
                        foreach (DB::table('photo_categories')->where('left', '<=', $category->left)
                                ->where('right', '>=', $category->right)
                                ->where('last_photo_id', '=', $id->id)->get(array('id', 'left', 'right')) as $c)
                        {
                            $photo = DB::table('photos')->order_by('photos.id', 'desc')->join('photo_categories', 'photo_categories.id', '=', 'photos.category_id')
                                    ->where('photo_categories.left', '>=', $c->left)
                                    ->where('photo_categories.right', '<=', $c->right)
                                    ->first('photos.id');

                            if ($photo)
                            {
                                DB::table('photo_categories')->where('id', '=', $c->id)->update(array('last_photo_id' => $photo->id));
                            }
                            else
                            {
                                DB::table('photo_categories')->where('id', '=', $c->id)->update(array('last_photo_id' => 0));
                            }
                        }
                    }

                    // New category
                    $category = DB::table('photo_categories')->where('id', '=', $prepared_data['category_id'])->first(array('left', 'right'));

                    if ($category)
                    {
                        foreach (DB::table('photo_categories')->where('left', '<=', $category->left)
                                ->where('right', '>=', $category->right)->get(array('id', 'left', 'right')) as $c)
                        {
                            $photo = DB::table('photos')->order_by('photos.id', 'desc')->join('photo_categories', 'photo_categories.id', '=', 'photos.category_id')
                                    ->where('photo_categories.left', '>=', $c->left)
                                    ->where('photo_categories.right', '<=', $c->right)
                                    ->first('photos.id');

                            if ($photo)
                            {
                                DB::table('photo_categories')->where('id', '=', $c->id)->update(array('last_photo_id' => $photo->id));
                            }
                            else
                            {
                                DB::table('photo_categories')->where('id', '=', $c->id)->update(array('last_photo_id' => 0));
                            }
                        }
                    }
                }

                ionic_clear_cache('photos-*');

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono zdjęcie: %s', $prepared_data['title']));
                return Redirect::to('admin/photos/index');
            }
        }

        $this->page->set_title('Edycja zdjęcia');

        $this->page->breadcrumb_append('Galeria', 'admin/photos/index');
        $this->page->breadcrumb_append('Edycja zdjęcia', 'admin/photos/edit/'.$id->id);

        $this->view = View::make('admin.photos.edit');

        $old_data = array('title' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);
        $this->view->with('categories', Ionic\Tree::build_select('photo_categories', 'title', ' &raquo; '));
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_photos'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_photos'))
            return Response::error(403);

        $this->page->set_title('Galeria');
        $this->page->breadcrumb_append('Galeria', 'admin/photos/index');

        Asset::add('lightbox', 'public/js/jquery.lightbox.min.js', 'jquery');
        Asset::add('lightbox', 'public/css/jquery.lightbox.css');

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
        if (!Auth::can('admin_photos'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('photos', 'Galeria', 'admin/photos');

        $grid->add_related('users', 'users.id', '=', 'photos.user_id');

        $grid->add_help('category', 'Aby filtrować według kategorii należy wejść w zarządzanie kategoriami oraz wybrać odpowiednią opcję po kliknięciu PPM na kategorię.');

        if (Auth::can('admin_photos_add'))
            $grid->add_button('Dodaj zdjęcia', 'admin/photos/add', 'add-button');
        if (Auth::can('admin_photos_edit'))
            $grid->add_action('Edytuj', 'admin/photos/edit/%d', 'edit-button');
        if (Auth::can('admin_photos_delete'))
            $grid->add_action('Usuń', 'admin/photos/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        $grid->add_selects(array('photos.image'));

        $grid->add_column('id', 'ID', 'id', null, 'photos.id');
        $grid->add_column('title', 'Tytuł', function($obj) {
                    return '<a href="'.URL::base().'/public/upload/photos/'.$obj->image.'" class="lightbox">'.$obj->title.'</a>';
                }, 'photos.title', 'photos.title');
        $grid->add_column('display_name', 'Dodał', 'display_name', 'users.display_name', 'users.display_name');
        $grid->add_column('created_at', 'Data dodania', 'created_at', 'photos.created_at', 'photos.created_at');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_date('created_at', 'Data dodania');
        $grid->add_filter_search('title', 'Tytuł');
        $grid->add_filter_search('display_name', 'Użytkownik', 'users.display_name');

        return $grid;
    }

}