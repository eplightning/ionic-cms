<?php

class Admin_News_Controller extends Admin_Controller {

    public function action_add()
    {
        if (!Auth::can('admin_news_add'))
            return Response::error(403);

        Asset::add('select2', 'public/css/select2.css');
        Asset::add('select2', 'public/js/select2.min.js', 'jquery');

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'        => '', 'source'       => '', 'image_text'   => '', 'news_content' => '', 'news_short'   => '', 'big_image'    => '', 'small_image'  => '', 'tags'         => '', 'publish_at'   => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'source', 'image_text', 'news_content', 'news_short', 'big_image', 'small_image', 'tags', 'publish_at')));

            $rules = array(
                'title'        => 'required|max:127',
                'source'       => 'max:127',
                'image_text'   => 'max:127',
                'news_content' => 'required',
                'publish_at'   => 'match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/news/add')->with_errors($validator)
                                ->with_input('only', array('title', 'source', 'image_text', 'news_content', 'news_short', 'big_image', 'small_image', 'tags', 'publish_at'));
            }
            else
            {
                $prepared_data = array(
                    'title'           => HTML::specialchars($raw_data['title']),
                    'source'          => HTML::specialchars($raw_data['source']),
                    'image_text'      => HTML::specialchars($raw_data['image_text']),
                    'content'         => $raw_data['news_content'],
                    'content_intro'   => $raw_data['news_short'],
                    'user_id'         => $this->user->id,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'slug'            => ionic_tmp_slug('news'),
                    'enable_comments' => Input::get('enable_comments') == '1' ? 1 : 0
                );

                if ($raw_data['publish_at'])
                {
                    $prepared_data['publish_at'] = date('Y-m-d H:i:s', strtotime($raw_data['publish_at']));
                    $prepared_data['is_published'] = 0;
                }
                else
                {
                    $prepared_data['is_published'] = 1;
                }

                if (!Auth::can('admin_xss'))
                {
                    require_once path('app').'vendor'.DS.'htmLawed.php';

                    $prepared_data['content'] = htmLawed($prepared_data['content'], array('safe' => 1));
                }

                if ($prepared_data['content_intro'] and !Auth::can('admin_xss'))
                {
                    $prepared_data['content_intro'] = htmLawed($prepared_data['content_intro'], array('safe' => 1));
                }
                elseif (!$prepared_data['content_intro'])
                {
                    $start_offset = strpos($prepared_data['content'], '<p>');

                    if ($start_offset !== FALSE)
                    {
                        $start_offset += 3;
                        $end_offset = strpos($prepared_data['content'], '</p>', $start_offset);

                        if ($end_offset === FALSE)
                        {
                            // fallback
                            $prepared_data['content_intro'] = Str::limit(strip_tags($prepared_data['content']), 300);
                        }
                        else
                        {
                            $prepared_data['content_intro'] = strip_tags(trim(substr($prepared_data['content'], $start_offset, ($end_offset - $start_offset))));
                        }
                    }
                    else
                    {
                        // fallback
                        $prepared_data['content_intro'] = Str::limit(strip_tags($prepared_data['content']), 300);
                    }
                }

                if ($raw_data['small_image'])
                {
                    $raw_data['small_image'] = urldecode($raw_data['small_image']);
                    $raw_data['small_image'] = str_replace('..', '', $raw_data['small_image']);

                    if (file_exists(path('public').'upload'.DS.'images'.DS.$raw_data['small_image']))
                    {
                        $prepared_data['small_image'] = strtr($raw_data['small_image'], array(
                            "\\"   => '/',
                            "\xEA" => 'ę',
                            "\xF3" => 'ó',
                            "\xB9" => 'ą',
                            "\x9C" => 'ś',
                            "\xB3" => 'ł',
                            "\xBF" => 'ż',
                            "\x9F" => 'ź',
                            "\xE6" => 'ć',
                            "\xF1" => 'ń',
                            "\xCA" => 'Ę',
                            "\xD3" => 'Ó',
                            "\xA5" => 'Ą',
                            "\x8C" => 'Ś',
                            "\xA3" => 'Ł',
                            "\xAF" => 'Ż',
                            "\x8F" => 'Ź',
                            "\xC6" => 'Ć',
                            "\xD1" => 'Ń'));
                    }
                }

                if ($raw_data['big_image'])
                {
                    $raw_data['big_image'] = urldecode($raw_data['big_image']);
                    $raw_data['big_image'] = str_replace('..', '', $raw_data['big_image']);

                    if (file_exists(path('public').'upload'.DS.'images'.DS.$raw_data['big_image']))
                    {
                        $prepared_data['big_image'] = strtr($raw_data['big_image'], array(
                            "\\"   => '/',
                            "\xEA" => 'ę',
                            "\xF3" => 'ó',
                            "\xB9" => 'ą',
                            "\x9C" => 'ś',
                            "\xB3" => 'ł',
                            "\xBF" => 'ż',
                            "\x9F" => 'ź',
                            "\xE6" => 'ć',
                            "\xF1" => 'ń',
                            "\xCA" => 'Ę',
                            "\xD3" => 'Ó',
                            "\xA5" => 'Ą',
                            "\x8C" => 'Ś',
                            "\xA3" => 'Ł',
                            "\xAF" => 'Ż',
                            "\x8F" => 'Ź',
                            "\xC6" => 'Ć',
                            "\xD1" => 'Ń'));
                    }
                }

                $obj_id = DB::table('news')->insert_get_id($prepared_data);

                DB::table('news')->where('id', '=', $obj_id)->update(array('slug' => ionic_find_slug($prepared_data['title'], $obj_id, 'news')));

                if (!empty($_POST['tags']) and is_array($_POST['tags']))
                {
                    foreach ($_POST['tags'] as $v)
                    {
                        if (!ctype_digit($v))
                            continue;

                        $v = (int) $v;

                        $tag = DB::table('tags')->where('id', '=', $v)->first('id');

                        if (!$tag)
                            continue;

                        DB::table('news_tags')->insert(array('news_id' => $obj_id, 'tag_id'  => $tag->id));
                    }
                }

                $add_points = (int) Config::get('points.points_for_news');

                if ($add_points)
                {
                    DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array(
                        'points'     => DB::raw('points + '.$add_points),
                        'news_count' => DB::raw('news_count + 1')
                    ));
                }
                else
                {
                    DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array(
                        'news_count' => DB::raw('news_count + 1')
                    ));
                }

                ionic_clear_cache('news-*');

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano news: %s', $prepared_data['title']));
                return Redirect::to('admin/news/index');
            }
        }

        $this->page->set_title('Dodawanie newsa');

        $this->page->breadcrumb_append('Nowości', 'admin/news/index');
        $this->page->breadcrumb_append('Dodawanie newsa', 'admin/news/add');

        $this->view = View::make('admin.news.add');

        $old_data = array('title'        => '', 'source'       => '', 'image_text'   => '', 'news_content' => '', 'news_short'   => '', 'big_image'    => '', 'small_image'  => '', 'tags'         => '', 'publish_at'   => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        Ionic\Editor::init();

        $tags = array();

        foreach (DB::table('tags')->order_by('title', 'asc')->get(array('title', 'id')) as $t)
        {
            $tags[$t->id] = $t->title;
        }

        $images = array('' => '-- Brak');

        $basedir = path('public').'upload'.DS.'images';
        $basedir_length = strlen($basedir);

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basedir, \RecursiveDirectoryIterator::SKIP_DOTS)) as $f)
        {
            if ($f->isFile())
            {
                $extension = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));

                if ($extension == 'gif' or $extension == 'jpg' or $extension == 'jpeg' or $extension == 'png')
                {
                    $basename = substr($f->getPathname(), ($basedir_length + 1));

                    $images[urlencode($basename)] = HTML::specialchars(strtr($basename, array(
                                        "\\"   => '/',
                                        "\xEA" => 'ę',
                                        "\xF3" => 'ó',
                                        "\xB9" => 'ą',
                                        "\x9C" => 'ś',
                                        "\xB3" => 'ł',
                                        "\xBF" => 'ż',
                                        "\x9F" => 'ź',
                                        "\xE6" => 'ć',
                                        "\xF1" => 'ń',
                                        "\xCA" => 'Ę',
                                        "\xD3" => 'Ó',
                                        "\xA5" => 'Ą',
                                        "\x8C" => 'Ś',
                                        "\xA3" => 'Ł',
                                        "\xAF" => 'Ż',
                                        "\x8F" => 'Ź',
                                        "\xC6" => 'Ć',
                                        "\xD1" => 'Ń')));
                }
            }
        }

        natcasesort($images);

        $this->view->with('image_list', $images);
        $this->view->with('tags', $tags);
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_news'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_news_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('news')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if ($id->user_id != $this->user->id and !Auth::can('admin_news_all'))
            return Response::error(403);

        if (!Request::ajax() or !Config::get('advanced.admin_prefer_ajax', true))
        {
            if (!($status = $this->confirm()))
            {
                return;
            }
            elseif ($status == 2)
            {
                return Redirect::to('admin/news/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('news')->where('id', '=', $id->id)->delete();

        $prof = DB::table('profiles')->where('user_id', '=', $id->user_id)->first(array('points', 'news_count'));

        if ($prof)
        {
            $add_points = (int) Config::get('points.points_for_news');

            $prof->news_count = (int) DB::table('news')->where('user_id', '=', $id->user_id)->count();

            if ($add_points)
            {
                $prof->points -= $add_points;

                if ($prof->points < 0)
                    $prof->points = 0;

                DB::table('profiles')->where('user_id', '=', $id->user_id)->update(array('points'     => $prof->points, 'news_count' => $prof->news_count));
            }
            else
            {
                DB::table('profiles')->where('user_id', '=', $id->user_id)->update(array('news_count' => $prof->news_count));
            }
        }

        DB::table('karma')->where('content_id', '=', $id->id)->where('content_type', '=', 'news')->delete();

        $user_counts = array();
        $prepared_counts = array();

        foreach (DB::table('comments')->where('content_id', '=', $id->id)->where('content_type', '=', 'news')->get(array('user_id')) as $c)
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

        DB::table('comments')->where('content_id', '=', $id->id)->where('content_type', '=', 'news')->delete();
        DB::table('news_tags')->where('news_id', '=', $id->id)->delete();

        ionic_clear_cache('news-*');

        $this->log(sprintf('Usunięto news: %s', $id->title));

        if (!Request::ajax())
        {
            $this->notice('News usunięty pomyślnie');
            return Redirect::to('admin/news/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_news_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('news')->left_join('users', 'users.id', '=', 'news.user_id')->where('news.id', '=', (int) $id)->first(array('news.*', 'users.display_name'));
        if (!$id)
            return Response::error(500);

        if ($id->user_id != $this->user->id and !Auth::can('admin_news_all'))
            return Response::error(403);

        Asset::add('select2', 'public/css/select2.css');
        Asset::add('select2', 'public/js/select2.min.js', 'jquery');

        $current_tags = array();

        foreach (DB::table('news_tags')->where('news_id', '=', $id->id)->get('tag_id') as $tag)
        {
            $current_tags[] = $tag->tag_id;
        }

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('external_url' => '', 'title'        => '', 'slug'         => '', 'source'       => '', 'image_text'   => '', 'news_content' => '', 'news_short'   => '', 'big_image'    => '', 'small_image'  => '', 'created_at'   => '', 'publish_at'   => '', 'user'         => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'slug', 'source', 'image_text', 'news_content', 'news_short', 'big_image', 'small_image', 'created_at', 'publish_at', 'user', 'external_url')));

            $rules = array(
                'title'        => 'required|max:127',
                'slug'         => 'required|max:127|alpha_dash|unique:news,slug,'.$id->id.'',
                'source'       => 'max:127',
                'image_text'   => 'max:127',
                'news_content' => 'required',
                'publish_at'   => 'match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
                'created_at'   => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$!',
                'user'         => 'max:20|match:!^[\pL\pN\s]+$!u|exists:users,display_name',
                'external_url' => 'max:127'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/news/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('title', 'slug', 'source', 'image_text', 'news_content', 'news_short', 'created_at', 'publish_at', 'user', 'external_url'));
            }
            else
            {
                $prepared_data = array(
                    'title'           => HTML::specialchars($raw_data['title']),
                    'slug'            => HTML::specialchars($raw_data['slug']),
                    'source'          => HTML::specialchars($raw_data['source']),
                    'image_text'      => HTML::specialchars($raw_data['image_text']),
                    'content'         => $raw_data['news_content'],
                    'content_intro'   => $raw_data['news_short'],
                    'created_at'      => date('Y-m-d H:i:s', strtotime($raw_data['created_at'])),
                    'enable_comments' => Input::get('enable_comments') == '1' ? 1 : 0,
                    'external_url'    => HTML::specialchars($raw_data['external_url'])
                );

                if ($raw_data['publish_at'] and $id->is_published == 0)
                {
                    $prepared_data['publish_at'] = date('Y-m-d H:i:s', strtotime($raw_data['publish_at']));
                }

                if (!Auth::can('admin_xss'))
                {
                    require_once path('app').'vendor'.DS.'htmLawed.php';

                    $prepared_data['content'] = htmLawed($prepared_data['content'], array('safe' => 1));
                }

                if ($prepared_data['content_intro'] and !Auth::can('admin_xss'))
                {
                    $prepared_data['content_intro'] = htmLawed($prepared_data['content_intro'], array('safe' => 1));
                }
                elseif (!$prepared_data['content_intro'])
                {
                    $start_offset = strpos($prepared_data['content'], '<p>');

                    if ($start_offset !== FALSE)
                    {
                        $start_offset += 3;
                        $end_offset = strpos($prepared_data['content'], '</p>', $start_offset);

                        if ($end_offset === FALSE)
                        {
                            // fallback
                            $prepared_data['content_intro'] = Str::limit(strip_tags($prepared_data['content']), 300);
                        }
                        else
                        {
                            $prepared_data['content_intro'] = strip_tags(trim(substr($prepared_data['content'], $start_offset, ($end_offset - $start_offset))));
                        }
                    }
                    else
                    {
                        // fallback
                        $prepared_data['content_intro'] = Str::limit(strip_tags($prepared_data['content']), 300);
                    }
                }

                if ($raw_data['small_image'])
                {
                    $raw_data['small_image'] = urldecode($raw_data['small_image']);
                    $raw_data['small_image'] = str_replace('..', '', $raw_data['small_image']);

                    if (file_exists(path('public').'upload'.DS.'images'.DS.$raw_data['small_image']))
                    {
                        $prepared_data['small_image'] = strtr($raw_data['small_image'], array(
                            "\\"   => '/',
                            "\xEA" => 'ę',
                            "\xF3" => 'ó',
                            "\xB9" => 'ą',
                            "\x9C" => 'ś',
                            "\xB3" => 'ł',
                            "\xBF" => 'ż',
                            "\x9F" => 'ź',
                            "\xE6" => 'ć',
                            "\xF1" => 'ń',
                            "\xCA" => 'Ę',
                            "\xD3" => 'Ó',
                            "\xA5" => 'Ą',
                            "\x8C" => 'Ś',
                            "\xA3" => 'Ł',
                            "\xAF" => 'Ż',
                            "\x8F" => 'Ź',
                            "\xC6" => 'Ć',
                            "\xD1" => 'Ń'));
                    }
                }

                if ($raw_data['big_image'])
                {
                    $raw_data['big_image'] = urldecode($raw_data['big_image']);
                    $raw_data['big_image'] = str_replace('..', '', $raw_data['big_image']);

                    if (file_exists(path('public').'upload'.DS.'images'.DS.$raw_data['big_image']))
                    {
                        $prepared_data['big_image'] = strtr($raw_data['big_image'], array(
                            "\\"   => '/',
                            "\xEA" => 'ę',
                            "\xF3" => 'ó',
                            "\xB9" => 'ą',
                            "\x9C" => 'ś',
                            "\xB3" => 'ł',
                            "\xBF" => 'ż',
                            "\x9F" => 'ź',
                            "\xE6" => 'ć',
                            "\xF1" => 'ń',
                            "\xCA" => 'Ę',
                            "\xD3" => 'Ó',
                            "\xA5" => 'Ą',
                            "\x8C" => 'Ś',
                            "\xA3" => 'Ł',
                            "\xAF" => 'Ż',
                            "\x8F" => 'Ź',
                            "\xC6" => 'Ć',
                            "\xD1" => 'Ń'));
                    }
                }

                if (Auth::can('admin_news_all') and $raw_data['user'] and $raw_data['user'] != $id->display_name)
                {
                    $find_user = DB::table('users')->where('display_name', '=', $raw_data['user'])->first(array('id', 'display_name'));

                    if ($find_user and $find_user->id != $id->user_id)
                    {
                        $add_points = (int) Config::get('points.points_for_news');

                        if ($add_points)
                        {
                            DB::table('profiles')->where('user_id', '=', $id->user_id)
                                    ->where('news_count', '>', 0)
                                    ->where('points', '>=', $add_points)
                                    ->update(array('points'     => DB::raw('points - '.$add_points), 'news_count' => DB::raw('news_count - 1')));

                            DB::table('profiles')->where('user_id', '=', $find_user->id)
                                    ->update(array('points'     => DB::raw('points + '.$add_points), 'news_count' => DB::raw('news_count + 1')));
                        }
                        else
                        {
                            DB::table('profiles')->where('user_id', '=', $id->user_id)
                                    ->where('news_count', '>', 0)
                                    ->update(array('news_count' => DB::raw('news_count - 1')));

                            DB::table('profiles')->where('user_id', '=', $find_user->id)
                                    ->update(array('news_count' => DB::raw('news_count + 1')));
                        }

                        $prepared_data['user_id'] = $find_user->id;
                    }
                }

                if (Input::get('gen_slug') == '1')
                {
                    $prepared_data['slug'] = ionic_find_slug($prepared_data['title'], $id->id, 'news');
                }

                \DB::table('news')->where('id', '=', $id->id)->update($prepared_data);

                $new_tags = array();

                if (!empty($_POST['tags']) and is_array($_POST['tags']))
                {
                    foreach ($_POST['tags'] as $v)
                    {
                        if (!ctype_digit($v))
                            continue;

                        $v = (int) $v;

                        $tag = DB::table('tags')->where('id', '=', $v)->first('id');

                        if (!$tag)
                            continue;

                        $new_tags[] = $tag->id;
                    }
                }

                foreach ($new_tags as $tag)
                {
                    if (in_array($tag, $current_tags))
                        continue;

                    DB::table('news_tags')->insert(array('news_id' => $id->id, 'tag_id'  => $tag));
                }

                foreach ($current_tags as $k => $tag)
                {
                    if (in_array($tag, $new_tags))
                    {
                        unset($current_tags[$k]);
                    }
                }

                if (!empty($current_tags))
                {
                    DB::table('news_tags')->where('news_id', '=', $id->id)->where_in('tag_id', $current_tags)->delete();
                }

                if ($prepared_data['slug'] != $id->slug)
                {
                    DB::table('comments')->where('content_type', '=', 'newss')->where('content_id', '=', $id->id)->update(array(
                        'content_link' => 'news/'.$prepared_data['slug']
                    ));
                }

                ionic_clear_cache('news-*');

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono news: %s', $prepared_data['title']));
                return Redirect::to('admin/news/index');
            }
        }

        $this->page->set_title('Edycja newsa');

        $this->page->breadcrumb_append('Nowości', 'admin/news/index');
        $this->page->breadcrumb_append('Edycja newsa', 'admin/news/edit/'.$id->id);

        $this->view = View::make('admin.news.edit');

        $old_data = array('external_url' => '', 'title'        => '', 'slug'         => '', 'source'       => '', 'image_text'   => '', 'news_content' => '', 'news_short'   => '', 'created_at'   => '', 'publish_at'   => '', 'user'         => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);

        Ionic\Editor::init();

        $tags = array();

        foreach (DB::table('tags')->order_by('title', 'asc')->get(array('title', 'id')) as $t)
        {
            $tags[$t->id] = $t->title;
        }

        $images = array('' => '-- Brak');

        $basedir = path('public').'upload'.DS.'images';
        $basedir_length = strlen($basedir);

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basedir, \RecursiveDirectoryIterator::SKIP_DOTS)) as $f)
        {
            if ($f->isFile())
            {
                $extension = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));

                if ($extension == 'gif' or $extension == 'jpg' or $extension == 'jpeg' or $extension == 'png')
                {
                    $basename = substr($f->getPathname(), ($basedir_length + 1));

                    $images[urlencode($basename)] = HTML::specialchars(strtr($basename, array(
                                        "\\"   => '/',
                                        "\xEA" => 'ę',
                                        "\xF3" => 'ó',
                                        "\xB9" => 'ą',
                                        "\x9C" => 'ś',
                                        "\xB3" => 'ł',
                                        "\xBF" => 'ż',
                                        "\x9F" => 'ź',
                                        "\xE6" => 'ć',
                                        "\xF1" => 'ń',
                                        "\xCA" => 'Ę',
                                        "\xD3" => 'Ó',
                                        "\xA5" => 'Ą',
                                        "\x8C" => 'Ś',
                                        "\xA3" => 'Ł',
                                        "\xAF" => 'Ż',
                                        "\x8F" => 'Ź',
                                        "\xC6" => 'Ć',
                                        "\xD1" => 'Ń')));
                }
            }
        }

        natcasesort($images);

        $this->view->with('image_list', $images);
        $this->view->with('tags', $tags);
        $this->view->with('current_tags', $current_tags);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_news'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_news'))
            return Response::error(403);

        $this->page->set_title('Nowości');
        $this->page->breadcrumb_append('Nowości', 'admin/news/index');

        DB::table('news')->where('is_published', '=', 0)
                ->where('publish_at', '<>', '0000-00-00 00:00:00')
                ->where('publish_at', '<=', date('Y-m-d H:i:s'))
                ->update(array('is_published' => 1, 'publish_at'   => '0000-00-00 00:00:00'));

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
        if (!Auth::can('admin_news_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    public function action_publish($id)
    {
        if (!Auth::can('admin_news') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('news')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if ($id->user_id != $this->user->id and !Auth::can('admin_news_all'))
            return Response::error(403);

        if (!Request::ajax() or !Config::get('advanced.admin_prefer_ajax', true))
        {
            if (!($status = $this->confirm()))
            {
                return;
            }
            elseif ($status == 2)
            {
                return Redirect::to('admin/news/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        $ajax = Request::ajax();

        if ($id->is_published == 1)
        {
            DB::table('news')->where('id', '=', $id->id)->update(array('is_published' => 0));

            if (!$ajax)
                $this->notice('Pomyślnie ukryto news');

            $this->log(sprintf('Ukryto news: %s', $id->title));
        }
        else
        {
            DB::table('news')->where('id', '=', $id->id)->update(array('is_published' => 1, 'publish_at'   => '0000-00-00 00:00:00'));

            if (!$ajax)
                $this->notice('Pomyślnie opublikowano news');

            $this->log(sprintf('Opublikowano news: %s', $id->title));
        }

        ionic_clear_cache('news-*');

        if ($ajax)
        {
            return Response::json(array('status' => true));
        }
        else
        {
            return Redirect::to('admin/news/index');
        }
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_news'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('news', 'Nowości', 'admin/news');

        $grid->add_column('id', 'ID', 'id', null, 'news.id');
        $grid->add_column('title', 'Tytuł', function($obj) {
            if ($obj->is_published == 0)
                return '<i>'.$obj->title.'</i>';
            return $obj->title;
        }, 'news.title', 'news.title');
        $grid->add_column('display_name', 'Autor', 'display_name', 'users.display_name', 'users.display_name');
        $grid->add_column('created_at', 'Dodano', 'created_at', 'news.created_at', 'news.created_at');
        $grid->add_column('views', 'Odsłon', 'views', 'news.views', 'news.views');

        if (Auth::can('admin_news_add'))
            $grid->add_button('Dodaj news', 'admin/news/add', 'add-button');
        if (Auth::can('admin_news_edit'))
            $grid->add_action('Edytuj', 'admin/news/edit/%d', 'edit-button');

        $grid->add_action('Opublikuj/ukryj', 'admin/news/publish/%d', 'accept-button', Ionic\Grid::ACTION_BOTH);

        if (Auth::can('admin_news_delete'))
            $grid->add_action('Usuń', 'admin/news/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_select('is_published', 'Opublikowany', array('_all_' => 'Wszystkie', 0 => 'Nie', 1 => 'Tak'), '_all_');

        $grid->add_filter_date('created_at', 'Data utworzenia');
        $grid->add_filter_search('title', 'Tytuł');

        if (!Auth::can('admin_news_all'))
        {
            $grid->add_where('news.user_id', '=', $this->user->id);
        }
        else
        {
            $grid->add_filter_autocomplete('display_name', 'Użytkownik', function($str) {
                $us = DB::table('users')->take(20)->where('display_name', 'like', str_replace('%', '', $str).'%')->get('display_name');

                $result = array();

                foreach ($us as $u)
                {
                    $result[] = $u->display_name;
                }

                return $result;
            }, 'users.display_name');
        }

        $grid->add_related('users', 'users.id', '=', 'news.user_id', array(), 'left');
        $grid->add_selects(array('news.is_published'));

        if (Auth::can('admin_news_multi'))
        {
            $id = $this->user->id;
            $all = Auth::can('admin_news_all');

            $grid->enable_checkboxes(true);

            $grid->add_multi_action('publish', 'Opublikuj', function($ids) use ($id, $all) {
                if (!$all)
                {
                    $new_ids = array();

                    foreach (DB::table('news')->where('user_id', '=', $id)->where_in('id', $ids)->get('id') as $n)
                    {
                        $new_ids[] = $n->id;
                    }

                    $ids = $new_ids;
                }

                if (!empty($ids))
                {
                    $affected = DB::table('news')->where_in('id', $ids)->update(array('is_published' => 1, 'publish_at'   => '0000-00-00 00:00:00'));

                    if ($affected > 0)
                    {
                        Model\Log::add('Opublikowano '.$affected.' newsów', $id);
                        ionic_clear_cache('news-*');
                    }
                }
            });


            $grid->add_multi_action('hide', 'Ukryj', function($ids) use ($id, $all) {
                if (!$all)
                {
                    $new_ids = array();

                    foreach (DB::table('news')->where('user_id', '=', $id)->where_in('id', $ids)->get('id') as $n)
                    {
                        $new_ids[] = $n->id;
                    }

                    $ids = $new_ids;
                }

                if (!empty($ids))
                {
                    $affected = DB::table('news')->where_in('id', $ids)->update(array('is_published' => 0));

                    if ($affected > 0)
                    {
                        Model\Log::add('Ukryto '.$affected.' newsów', $id);
                        ionic_clear_cache('news-*');
                    }
                }
            });

            if (Auth::can('admin_news_delete'))
            {
                $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id, $all) {
                    if (!$all)
                    {
                        $new_ids = array();

                        foreach (DB::table('news')->where('user_id', '=', $id)->where_in('id', $ids)->get(array('id', 'user_id')) as $n)
                        {
                            $new_ids[] = $n->id;
                        }

                        $ids = $new_ids;
                    }

                    if (!empty($ids))
                    {
                        $affected = DB::table('news')->where_in('id', $ids)->delete();

                        if ($affected > 0)
                        {
                            DB::table('karma')->where_in('content_id', $ids)->where('content_type', '=', 'news')->delete();

                            $user_counts = array();
                            $prepared_counts = array();

                            foreach (DB::table('comments')->where_in('content_id', $ids)->where('content_type', '=', 'news')->get(array('user_id')) as $c)
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

                            foreach ($prepared_counts as $c => $uids)
                            {
                                DB::table('profiles')->where('comments_count', '>=', $c)->where_in('user_id', $uids)->update(array('comments_count' => DB::raw('comments_count - '.$c)));
                            }

                            DB::table('comments')->where_in('content_id', $ids)->where('content_type', '=', 'news')->delete();
                            DB::table('news_tags')->where_in('news_id', $ids)->delete();

                            ionic_clear_cache('news-*');

                            Model\Log::add('Usunięto '.$affected.' newsów', $id);
                        }
                    }
                });
            }
        }

        return $grid;
    }

}