<?php

class Admin_Videos_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_videos_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'       => '', 'description' => '', 'thumbnail'   => '', 'link'        => '', 'embed'       => '', 'category_id' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'description', 'thumbnail', 'link', 'embed', 'category_id')));

            $rules = array(
                'title'       => 'required|max:127',
                'thumbnail'   => 'url|max:127',
                'link'        => 'url|max:127',
                'category_id' => 'required|exists:video_categories,id'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/videos/add')->with_errors($validator)
                                ->with_input('only', array('title', 'description', 'thumbnail', 'link', 'embed'));
            }
            else
            {
                $prepared_data = array(
                    'title'       => HTML::specialchars($raw_data['title']),
                    'description' => HTML::specialchars($raw_data['description']),
                    'thumbnail'   => HTML::specialchars($raw_data['thumbnail']),
                    'link'        => HTML::specialchars($raw_data['link']),
                    'embed'       => $raw_data['embed'],
                    'category_id' => (int) $raw_data['category_id'],
                    'user_id'     => $this->user->id,
                    'slug'        => ionic_tmp_slug('videos'),
                    'created_at'  => date('Y-m-d H:i:s')
                );

                if ($prepared_data['link'])
                {
                    $services = new Ionic\VideoEmbed;

                    $services->fetch($prepared_data['link']);

                    if ($services->embed())
                    {
                        $prepared_data['embed'] = $services->embed();
                    }
                    elseif (empty($prepared_data['embed']))
                    {
                        $this->notice('Wymagany jest kod HTML lub prawidłowy link do video');
                        return Redirect::to('admin/videos/add')->with_input('only', array('title', 'description', 'thumbnail', 'link', 'embed'));
                    }

                    if (empty($prepared_data['thumbnail']) and $services->thumbnail())
                    {
                        $prepared_data['thumbnail'] = $services->thumbnail();
                    }
                }
                elseif (empty($prepared_data['embed']))
                {
                    $this->notice('Wymagany jest kod HTML lub prawidłowy link do video');
                    return Redirect::to('admin/videos/add')->with_input('only', array('title', 'description', 'thumbnail', 'link', 'embed'));
                }

                $obj_id = DB::table('videos')->insert_get_id($prepared_data);

                DB::table('videos')->where('id', '=', $obj_id)->update(array('slug' => ionic_find_slug($prepared_data['title'], $obj_id, 'videos')));

                $category = DB::table('video_categories')->where('id', '=', $prepared_data['category_id'])->first(array('left', 'right'));

                if ($category)
                {
                    DB::table('video_categories')->where('left', '<=', $category->left)->where('right', '>=', $category->right)->update(array(
                        'last_video_id' => $obj_id
                    ));
                }

                ionic_clear_cache('videos-*');

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano video: %s', $prepared_data['title']));
                return Redirect::to('admin/videos/index');
            }
        }

        $this->page->set_title('Dodawanie video');

        $this->page->breadcrumb_append('Filmy', 'admin/videos/index');
        $this->page->breadcrumb_append('Dodawanie video', 'admin/videos/add');

        $this->view = View::make('admin.videos.add');

        $old_data = array('title'       => '', 'description' => '', 'thumbnail'   => '', 'link'        => '', 'embed'       => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('categories', Ionic\Tree::build_select('video_categories', 'title', ' &raquo; '));

        if (Session::has('videos_cfilters'))
        {
            $applied = Session::get('videos_cfilters');

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
        if (!Auth::can('admin_videos'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_cat($id)
    {
        if (!Auth::can('admin_videos'))
            return Response::error(403);

        if (!$id or !ctype_digit($id))
            return Response::error(500);

        $id = DB::table('video_categories')->where('id', '=', (int) $id)->first('id');

        if (!$id)
            return Redirect::to('admin/videos/index');

        $grid = $this->make_grid();

        $grid->set_manual_filter('category_id', $id->id, 'Kategoria');

        return Redirect::to('admin/videos/index');
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_videos_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('videos')->where('id', '=', (int) $id)->first('*');
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
                return Redirect::to('admin/videos/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('videos')->where('id', '=', $id->id)->delete();

        $category = DB::table('video_categories')->where('id', '=', $id->category_id)->first(array('left', 'right'));

        if ($category)
        {
            foreach (DB::table('video_categories')->where('left', '<=', $category->left)
                    ->where('right', '>=', $category->right)
                    ->where('last_video_id', '=', $id->id)->get(array('id', 'left', 'right')) as $c)
            {
                $file = DB::table('videos')->order_by('videos.id', 'desc')->join('video_categories', 'video_categories.id', '=', 'videos.category_id')
                        ->where('video_categories.left', '>=', $c->left)
                        ->where('video_categories.right', '<=', $c->right)
                        ->first('videos.id');

                if ($file)
                {
                    DB::table('video_categories')->where('id', '=', $c->id)->update(array('last_video_id' => $file->id));
                }
                else
                {
                    DB::table('video_categories')->where('id', '=', $c->id)->update(array('last_video_id' => 0));
                }
            }
        }

        $user_counts = array();
        $prepared_counts = array();

        foreach (DB::table('comments')->where('content_id', '=', $id->id)->where('content_type', '=', 'video')->get(array('user_id')) as $c)
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

        DB::table('comments')->where('content_id', '=', $id->id)->where('content_type', '=', 'video')->delete();

        ionic_clear_cache('videos-*');

        $this->log(sprintf('Usunięto video: %s', $id->title));

        if (!Request::ajax())
        {
            $this->notice('Film usunięty pomyślnie');
            return Redirect::to('admin/videos/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_videos_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('videos')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'       => '', 'slug'        => '', 'description' => '', 'thumbnail'   => '', 'link'        => '', 'embed'       => '', 'category_id' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'slug', 'description', 'thumbnail', 'link', 'embed', 'category_id')));

            $rules = array(
                'title'       => 'required|max:127',
                'thumbnail'   => 'url|max:127',
                'link'        => 'url|max:127',
                'category_id' => 'required|exists:video_categories,id',
                'slug'        => 'required|max:127|alpha_dash|unique:videos,slug,'.$id->id.'',
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/videos/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('title', 'slug', 'description', 'thumbnail', 'link', 'embed'));
            }
            else
            {
                $prepared_data = array(
                    'title'       => HTML::specialchars($raw_data['title']),
                    'slug'        => HTML::specialchars($raw_data['slug']),
                    'description' => HTML::specialchars($raw_data['description']),
                    'thumbnail'   => HTML::specialchars($raw_data['thumbnail']),
                    'link'        => HTML::specialchars($raw_data['link']),
                    'embed'       => $raw_data['embed'],
                    'category_id' => (int) $raw_data['category_id']
                );

                if ($prepared_data['link'])
                {
                    $services = new Ionic\VideoEmbed;

                    $services->fetch($prepared_data['link']);

                    if ($services->embed())
                    {
                        $prepared_data['embed'] = $services->embed();
                    }
                    elseif (empty($prepared_data['embed']))
                    {
                        $this->notice('Wymagany jest kod HTML lub prawidłowy link do video');
                        return Redirect::to('admin/videos/edit/'.$id->id)->with_input('only', array('title', 'slug', 'description', 'thumbnail', 'link', 'embed'));
                    }

                    if ($services->thumbnail())
                    {
                        $prepared_data['thumbnail'] = $services->thumbnail();
                    }
                }
                elseif (empty($prepared_data['embed']))
                {
                    $this->notice('Wymagany jest kod HTML lub prawidłowy link do video');
                    return Redirect::to('admin/videos/edit/'.$id->id)->with_input('only', array('title', 'slug', 'description', 'thumbnail', 'link', 'embed'));
                }

                \DB::table('videos')->where('id', '=', $id->id)->update($prepared_data);

                if ($id->category_id != $prepared_data['category_id'])
                {
                    // Old category
                    $category = DB::table('video_categories')->where('id', '=', $id->category_id)->first(array('left', 'right'));

                    if ($category)
                    {
                        foreach (DB::table('video_categories')->where('left', '<=', $category->left)
                                ->where('right', '>=', $category->right)
                                ->where('last_video_id', '=', $id->id)->get(array('id', 'left', 'right')) as $c)
                        {
                            $file = DB::table('videos')->order_by('videos.id', 'desc')->join('video_categories', 'video_categories.id', '=', 'videos.category_id')
                                    ->where('video_categories.left', '>=', $c->left)
                                    ->where('video_categories.right', '<=', $c->right)
                                    ->first('videos.id');

                            if ($file)
                            {
                                DB::table('video_categories')->where('id', '=', $c->id)->update(array('last_video_id' => $file->id));
                            }
                            else
                            {
                                DB::table('video_categories')->where('id', '=', $c->id)->update(array('last_video_id' => 0));
                            }
                        }
                    }

                    // New category
                    $category = DB::table('video_categories')->where('id', '=', $prepared_data['category_id'])->first(array('left', 'right'));

                    if ($category)
                    {
                        foreach (DB::table('video_categories')->where('left', '<=', $category->left)
                                ->where('right', '>=', $category->right)->get(array('id', 'left', 'right')) as $c)
                        {
                            $file = DB::table('videos')->order_by('videos.id', 'desc')->join('video_categories', 'video_categories.id', '=', 'videos.category_id')
                                    ->where('video_categories.left', '>=', $c->left)
                                    ->where('video_categories.right', '<=', $c->right)
                                    ->first('videos.id');

                            if ($file)
                            {
                                DB::table('video_categories')->where('id', '=', $c->id)->update(array('last_video_id' => $file->id));
                            }
                            else
                            {
                                DB::table('video_categories')->where('id', '=', $c->id)->update(array('last_video_id' => 0));
                            }
                        }
                    }
                }

                if ($prepared_data['slug'] != $id->slug)
                {
                    DB::table('comments')->where('content_type', '=', 'video')->where('content_id', '=', $id->id)->update(array(
                        'content_link' => 'video/'.$prepared_data['slug']
                    ));
                }

                ionic_clear_cache('videos-*');

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono video: %s', $prepared_data['title']));
                return Redirect::to('admin/videos/index');
            }
        }

        $this->page->set_title('Edycja video');

        $this->page->breadcrumb_append('Filmy', 'admin/videos/index');
        $this->page->breadcrumb_append('Edycja video', 'admin/videos/edit/'.$id->id);

        $this->view = View::make('admin.videos.edit');

        $old_data = array('title'       => '', 'slug'        => '', 'description' => '', 'thumbnail'   => '', 'link'        => '', 'embed'       => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);
        $this->view->with('categories', Ionic\Tree::build_select('video_categories', 'title', ' &raquo; '));
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_videos'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_videos'))
            return Response::error(403);

        $this->page->set_title('Filmy');
        $this->page->breadcrumb_append('Filmy', 'admin/videos/index');

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
        if (!Auth::can('admin_videos_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_videos'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('videos', 'Filmy', 'admin/videos');

        $grid->add_related('users', 'users.id', '=', 'videos.user_id');

        $grid->add_selects(array('videos.link'));

        $grid->add_help('category', 'Aby filtrować według kategorii należy wejść w zarządzanie kategoriami oraz wybrać odpowiednią opcję po kliknięciu PPM na kategorię.');

        if (Auth::can('admin_videos_add'))
            $grid->add_button('Dodaj film', 'admin/videos/add', 'add-button');
        if (Auth::can('admin_videos_edit'))
            $grid->add_action('Edytuj', 'admin/videos/edit/%d', 'edit-button');
        if (Auth::can('admin_videos_delete'))
            $grid->add_action('Usuń', 'admin/videos/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        $grid->add_column('id', 'ID', 'id', null, 'videos.id');
        $grid->add_column('title', 'Tytuł', function($obj) {

                    if ($obj->link)
                    {
                        return '<a href="'.$obj->link.'">'.$obj->title.'</a>';
                    }

                    return $obj->title;
                }, 'videos.title', 'videos.title');
        $grid->add_column('display_name', 'Dodał', 'display_name', 'users.display_name', 'users.display_name');
        $grid->add_column('created_at', 'Data dodania', 'created_at', 'videos.created_at', 'videos.created_at');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_date('created_at', 'Data dodania');
        $grid->add_filter_search('title', 'Tytuł');
        $grid->add_filter_search('display_name', 'Użytkownik', 'users.display_name');

        return $grid;
    }

}