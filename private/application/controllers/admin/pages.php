<?php

/**
 * Page management
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Pages_Controller extends Admin_Controller {

    /**
     * Add action
     *
     * @return Response
     */
    public function action_add()
    {
        if (!Auth::can('admin_page_add'))
            return Response::error(403);

        $layouts = array();

        foreach (new \FilesystemIterator(path('app').'views'.DS.'layouts', \FilesystemIterator::SKIP_DOTS) as $file)
        {
            if ($file->isFile() and pathinfo($file->getPathname(), PATHINFO_EXTENSION) == 'twig')
            {
                $layouts[] = basename($file->getFilename(), '.twig');
            }
        }

        if (empty($layouts))
        {
            $layouts = array('main');
        }

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'            => '', 'page_content'     => '', 'meta_title'       => '', 'meta_keys'        => '', 'meta_description' => '', 'layout' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'page_content', 'meta_title', 'meta_keys', 'meta_description', 'layout')));

            $rules = array(
                'title'            => 'required|max:127|unique:pages,title',
                'page_content'     => 'required',
                'meta_title'       => 'max:127',
                'meta_keys'        => 'max:255',
                'meta_description' => 'max:255',
                'layout'           => 'in:'.implode(',', $layouts)
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/pages/add')->with_errors($validator)
                                ->with_input('only', array('title', 'page_content', 'meta_title', 'meta_keys', 'meta_description', 'layout'));
            }
            else
            {
                $prepared_data = array(
                    'title'            => HTML::specialchars($raw_data['title']),
                    'user_id'          => $this->user->id,
                    'created_at'       => date('Y-m-d H:i:s'),
                    'meta_title'       => HTML::specialchars($raw_data['meta_title']),
                    'meta_keys'        => HTML::specialchars($raw_data['meta_keys']),
                    'meta_description' => HTML::specialchars($raw_data['meta_description']),
                    'slug'             => ionic_tmp_slug('pages'),
                    'layout'           => $raw_data['layout']
                );

                if (!Auth::can('admin_xss'))
                {
                    require_once path('app').'vendor'.DS.'htmLawed.php';

                    $raw_data['page_content'] = htmLawed($raw_data['page_content'], array('safe' => 1));
                }

                $obj_id = DB::table('pages')->insert_get_id($prepared_data);
                $slug = ionic_find_slug($prepared_data['title'], $obj_id, 'pages');
                DB::table('pages')->where('id', '=', $obj_id)->update(array('slug' => $slug));

                if (Input::has('menu_id') and ctype_digit(Input::get('menu_id')) and Input::get('menu_id') != '0')
                {
                    $tree = new Ionic\Tree('menu');

                    $tree->create_node((int) Input::get('menu_id'), array(
                        'title' => $prepared_data['title'],
                        'link'  => 'page/show/'.$slug
                    ));
                }

                DB::table('page_content')->insert(array(
                    'page_id'    => $obj_id,
                    'content'    => $raw_data['page_content'],
                    'user_id'    => $this->user->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'current'    => 1
                ));

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodał podstronę: %s', $prepared_data['title']));
                return Redirect::to('admin/pages/index');
            }
        }

        $this->page->set_title('Dodawanie podstrony');

        $this->page->breadcrumb_append('Podstrony', 'admin/pages/index');
        $this->page->breadcrumb_append('Dodawanie podstrony', 'admin/pages/add');

        $this->view = View::make('admin.pages.add');

        $old_data = array('title'            => '', 'page_content'     => '', 'meta_title'       => '', 'meta_keys'        => '', 'meta_description' => '', 'layout' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $menu = Ionic\Tree::build_select('menu', 'title', ' &raquo; ');
        $menu[0] = '[Nie dodawaj]';

        $this->view->with('menu', $menu);
        $this->view->with('layouts', $layouts);

        Ionic\Editor::init();
    }

    /**
     * Autocompletion
     *
     * @param  string   $id
     * @return Response
     */
    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_page'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    /**
     * Delete
     *
     * @param  string   $id
     * @return Response
     */
    public function action_delete($id)
    {
        if (!Auth::can('admin_page_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('pages')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/pages/index');
        }

        DB::table('pages')->where('id', '=', $id->id)->delete();
        DB::table('page_content')->where('page_id', '=', $id->id)->delete();

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunął podstronę: %s', $id->title));
        return Redirect::to('admin/pages/index');
    }

    /**
     * Edit
     *
     * @param  string   $id
     * @return Response
     */
    public function action_edit($id)
    {
        if (!Auth::can('admin_page_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('pages')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        $layouts = array();

        foreach (new \FilesystemIterator(path('app').'views'.DS.'layouts', \FilesystemIterator::SKIP_DOTS) as $file)
        {
            if ($file->isFile() and pathinfo($file->getPathname(), PATHINFO_EXTENSION) == 'twig')
            {
                $layouts[] = basename($file->getFilename(), '.twig');
            }
        }

        if (empty($layouts))
        {
            $layouts = array('main');
        }

        $content = DB::table('page_content')->where('page_id', '=', $id->id)->where('current', '=', 1)->only('content');
        if (!$content)
            $content = '';

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'            => '', 'slug'             => '', 'page_content'     => '', 'meta_title'       => '', 'meta_keys'        => '', 'meta_description' => '', 'layout' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'slug', 'page_content', 'meta_title', 'meta_keys', 'meta_description', 'layout')));

            $rules = array(
                'title'            => 'required|max:127|unique:pages,title,'.$id->id.'',
                'slug'             => 'required|max:127|alpha_dash|unique:pages,slug,'.$id->id.'',
                'page_content'     => 'required',
                'meta_title'       => 'max:127',
                'meta_keys'        => 'max:255',
                'meta_description' => 'max:255',
                'layout'           => 'in:'.implode(',', $layouts)
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/pages/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('title', 'slug', 'page_content', 'meta_title', 'meta_keys', 'meta_description', 'layout'));
            }
            else
            {
                $prepared_data = array(
                    'title'            => HTML::specialchars($raw_data['title']),
                    'slug'             => HTML::specialchars($raw_data['slug']),
                    'meta_title'       => HTML::specialchars($raw_data['meta_title']),
                    'meta_keys'        => HTML::specialchars($raw_data['meta_keys']),
                    'meta_description' => HTML::specialchars($raw_data['meta_description']),
                    'updated_at'       => date('Y-m-d H:i:s'),
                    'layout'           => $raw_data['layout']
                );

                if (!Auth::can('admin_xss'))
                {
                    require_once path('app').'vendor'.DS.'htmLawed.php';

                    $raw_data['page_content'] = htmLawed($raw_data['page_content'], array('safe' => 1));
                }

                \DB::table('pages')->where('id', '=', $id->id)->update($prepared_data);

                if ($content and $content != $raw_data['page_content'])
                {
                    $max_versions = Config::get('page.max_versions');
                    if (!is_int($max_versions) or $max_versions <= 0)
                        $max_versions = 1;

                    if ($max_versions == 1)
                    {
                        DB::table('page_content')->where('page_id', '=', $id->id)->delete();

                        DB::table('page_content')->insert(array(
                            'page_id'    => $id->id,
                            'content'    => $raw_data['page_content'],
                            'user_id'    => $this->user->id,
                            'created_at' => date('Y-m-d H:i:s'),
                            'current'    => 1
                        ));
                    }
                    else
                    {
                        $count = DB::table('page_content')->where('page_id', '=', $id->id)->count();

                        if ($count >= $max_versions)
                        {
                            $count -= $max_versions;
                            $count++;

                            // Laravel doesn't support LIMIT and ORDER BY in DELETE operations
                            $ids = array();

                            foreach (DB::table('page_content')->take($count)->where('page_id', '=', $id->id)->order_by('current', 'asc')->order_by('created_at', 'asc')->get('id') as $i)
                            {
                                $ids[] = $i->id;
                            }

                            if (!empty($ids))
                                DB::table('page_content')->where_in('id', $ids)->delete();
                        }

                        DB::table('page_content')->where('page_id', '=', $id->id)->where('current', '=', 1)->update(array('current' => 0));
                        DB::table('page_content')->insert(array(
                            'page_id'    => $id->id,
                            'content'    => $raw_data['page_content'],
                            'user_id'    => $this->user->id,
                            'created_at' => date('Y-m-d H:i:s'),
                            'current'    => 1
                        ));
                    }
                }

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmienił podstronę: %s', $prepared_data['title']));
                return Redirect::to('admin/pages/index');
            }
        }

        $this->page->set_title('Edycja podstrony');

        $this->page->breadcrumb_append('Podstrony', 'admin/pages/index');
        $this->page->breadcrumb_append('Edycja podstrony', 'admin/pages/edit/'.$id->id);

        $this->view = View::make('admin.pages.edit');

        $old_data = array('title'            => '', 'slug'             => '', 'page_content'     => '', 'meta_title'       => '', 'meta_keys'        => '', 'meta_description' => '', 'layout' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);
        $this->view->with('page_content', $content);
        $this->view->with('layouts', $layouts);

        Ionic\Editor::init();
    }

    /**
     * Set filter
     *
     * @param  string   $id
     * @param  mixed    $value
     * @return Response
     */
    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_page'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    /**
     * Listing
     *
     * @param  string  $id
     * @return Response
     */
    public function action_index($id = null)
    {
        if (!Auth::can('admin_page'))
            return Response::error(403);

        $this->page->set_title('Podstrony');
        $this->page->breadcrumb_append('Podstrony', 'admin/pages/index');

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

    /**
     * Do multiaction
     *
     * @param  string   $name
     * @return Response
     */
    public function action_multiaction($name)
    {
        if (!Auth::can('admin_page_multi'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    /**
     * Preview content
     *
     * @param  string   $id
     * @return Response
     */
    public function action_preview($id)
    {
        // Permissions
        if (!Auth::can('admin_page_versions') or !ctype_digit($id))
            return Response::error(403);

        // Get page
        $id = DB::table('page_content')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        // Make response
        return Response::json(array('content' => $id->content));
    }

    /**
     * Preview diff
     *
     * @param  string   $id
     * @return Response
     */
    public function action_diff($id)
    {
        // Permissions
        if (!Auth::can('admin_page_versions') or !ctype_digit($id))
            return Response::error(403);

        // Get page
        $id = DB::table('page_content')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        // Get current
        $cur = DB::table('page_content')->where('page_id', '=', $id->page_id)->where('current', '=', 1)->first('*');
        if (!$cur)
            return Response::error(500);

        // Diff
        require_once path('app').'vendor'.DS.'Diff.php';

        $diff = new Diff(explode("\n", ionic_normalize_lines($cur->content)), explode("\n", ionic_normalize_lines($id->content)), array());

        require_once path('app').'vendor'.DS.'Diff/Renderer/Html/Inline.php';

        $renderer = new Diff_Renderer_Html_Inline;

        // Make response
        return Response::json(array('content' => $diff->render($renderer)));
    }

    /**
     * Sorting
     *
     * @param  string   $item
     * @return Response
     */
    public function action_sort($item)
    {
        if (!Auth::can('admin_page'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    /**
     * Get page versions
     *
     * @param  string $id
     * @return Response
     */
    public function action_versions($id)
    {
        if (!Auth::can('admin_page_versions') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('pages')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        $this->page->set_title('Wersje podstrony');
        $this->page->breadcrumb_append('Podstrony', 'admin/pages/index');
        $this->page->breadcrumb_append('Wersje podstrony', 'admin/pages/versions/'.$id->id);

        $versions = DB::table('page_content')->left_join('users', 'users.id', '=', 'page_content.user_id')->where('page_id', '=', $id->id)->order_by('created_at', 'desc')->get(array('users.display_name', 'page_content.created_at', 'page_content.id', 'page_content.current'));

        $this->view = View::make('admin.pages.versions', array('versions' => $versions));
    }

    /**
     * Revert version
     *
     * @param  string   $id
     * @return Response
     */
    public function action_version_revert($id)
    {
        if (!Auth::can('admin_page_versions') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('page_content')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if ($id->current == 1)
            return Redirect::to('admin/pages/versions/'.$id->page_id);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/pages/versions/'.$id->page_id);
        }

        DB::table('page_content')->where('page_id', '=', $id->page_id)->where('current', '=', 1)->update(array('current' => 0));
        DB::table('page_content')->where('id', '=', $id->id)->update(array('current' => 1));

        $this->log('Zmienił używaną wersje podstrony');
        $this->notice('Akcja wykonana pomyślnie');

        return Redirect::to('admin/pages/versions/'.$id->page_id);
    }

    /**
     * Make grid
     *
     * @return Ionic\Grid
     */
    protected function make_grid()
    {
        $grid = new Ionic\Grid('pages', 'Podstrony', 'admin/pages', array('pages.slug'));

        $grid->add_column('id', 'ID', 'id', null, 'pages.id');
        $grid->add_column('title', 'Tytuł', function($obj) {
                    return '<a href="'.URL::to('page/show/'.$obj->slug).'">'.$obj->title.'</a>';
                }, 'pages.title', 'pages.title');
        $grid->add_column('display_name', 'Dodający', 'display_name', 'users.display_name', 'users.display_name');
        $grid->add_column('created_at', 'Utworzono', 'created_at', 'pages.created_at', 'pages.created_at');
        $grid->add_column('updated_at', 'Zmieniono', function($obj) {
                    if ($obj->updated_at == '0000-00-00 00:00:00')
                    {
                        return 'nigdy';
                    }

                    return ionic_date($obj->updated_at, 'standard', true);
                }, 'pages.updated_at', 'pages.updated_at');

        if (Auth::can('admin_page_add'))
            $grid->add_button('Dodaj podstronę', 'admin/pages/add', 'add-button');
        if (Auth::can('admin_page_edit'))
            $grid->add_action('Edytuj', 'admin/pages/edit/%d', 'edit-button');
        if (Auth::can('admin_page_versions'))
            $grid->add_action('Historia', 'admin/pages/versions/%d', 'time-button');
        if (Auth::can('admin_page_delete'))
            $grid->add_action('Usuń', 'admin/pages/delete/%d', 'delete-button');

        if (Auth::can('admin_page_delete') and Auth::can('admin_page_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                        $affected = DB::table('pages')->where_in('id', $ids)->delete();
                        DB::table('page_content')->where_in('page_id', $ids)->delete();

                        if ($affected)
                            \Model\Log::add('Masowo usunął podstrony ('.$affected.')', $id);
                    });
        }

        $grid->add_related('users', 'users.id', '=', 'pages.user_id');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('title', 'Tytuł');

        $grid->add_filter_autocomplete('display_name', 'Oryginalny autor', function($str) {
                    $us = DB::table('users')->take(20)->where('display_name', 'like', str_replace('%', '', $str).'%')->get('display_name');

                    $result = array();

                    foreach ($us as $u)
                    {
                        $result[] = $u->display_name;
                    }

                    return $result;
                }, 'users.display_name');

        $grid->add_filter_date('created_at', 'Data utworzenia');
        $grid->add_filter_date('updated_at', 'Data modyfikacji');

        return $grid;
    }

}