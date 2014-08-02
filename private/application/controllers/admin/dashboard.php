<?php

/**
 * Dashboard
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Dashboard_Controller extends Admin_Controller {

    /**
     * Add admin note
     *
     * @return Response
     */
    public function action_add()
    {
        // Basic check
        if (!Auth::can('admin_dashboard_add') or Request::forged() or !Input::has('title') or !Input::has('message'))
        {
            return Redirect::to('admin/dashboard/index');
        }

        // Get content and title
        $title = HTML::specialchars(Input::get('title'));
        $content = Input::get('message');

        // Don't filter roots
        if (!Auth::can('admin_root'))
        {
            require_once path('app').'vendor'.DS.'htmLawed.php';

            $content = htmLawed($content, array(
                'safe'           => 1,
                'deny_attribute' => 'style'));
        }

        // Add note
        Model\AdminNote::add($title, $content, $this->user->id);

        // Notice, log and redirect
        $this->notice('Informacja została dodana pomyślnie');
        $this->log('Dodał nową informację: '.$title);

        return Redirect::to('admin/dashboard/index');
    }

    /**
     * Delete
     *
     * @param  string   $id
     * @return Response
     */
    public function action_delete($id)
    {
        // General checks
        if (!Auth::can('admin_dashboard_delete') or !ctype_digit($id))
        {
            return Response::error(500);
        }

        // Find note to delete
        $id = Model\AdminNote::find($id, array(
                    'id',
                    'title'));

        if (!$id)
        {
            return Response::error(404);
        }

        // Confirmation
        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/dashboard/index');
        }

        // Delete
        Model\AdminNote::delete($id->id);

        // Finish
        $this->notice('Informacja została usunięta pomyślnie');
        $this->log('Usunął informację: '.$id->title);

        return Redirect::to('admin/dashboard/index');
    }

    /**
     * Edit content
     *
     * @return Response
     */
    public function action_edit_content()
    {
        // Check permissions
        if (!Auth::can('admin_dashboard_edit'))
        {
            return Response::error(403);
        }

        // Validate request
        if (!Request::ajax() or !Input::has('id') or !Input::has('value') or Request::forged() or !starts_with(Input::get('id'), 'note-'))
        {
            return Response::error(500);
        }

        // Get rid of -note
        $id = substr(Input::get('id'), 5);

        // If it's still not number, error
        if (!ctype_digit($id))
        {
            return Response::error(500);
        }

        // Get new content
        $content = Input::get('value');

        // Don't filter roots
        if (!Auth::can('admin_xss'))
        {
            require_once path('app').'vendor'.DS.'htmLawed.php';

            $content = htmLawed($content, array(
                'safe'           => 1,
                'deny_attribute' => 'style'));
        }

        // Edit
        $status = Model\AdminNote::edit($id, null, $content);

        // Error?
        if (!$status)
        {
            return Response::error(404);
        }

        // Log
        $this->log('Zmienił treść informacji: '.$status);

        // Return content to display
        return Response::make(nl2br($content));
    }

    /**
     * Edit title
     *
     * @return Response
     */
    public function action_edit_title()
    {
        // Permissions
        if (!Auth::can('admin_dashboard_edit'))
        {
            return Response::error(403);
        }

        // Check if request is valid
        if (!Request::ajax() or !Input::has('id') or !Input::has('value') or Request::forged() or !starts_with(Input::get('id'), 'note-title-'))
        {
            return Response::error(500);
        }

        $id = substr(Input::get('id'), 11);

        if (!ctype_digit($id))
        {
            return Response::error(500);
        }

        // Get new title
        $title = HTML::specialchars(Input::get('value'));

        // Edit
        Model\AdminNote::edit($id, $title);

        // Log
        $this->log('Zmienił tytuł informacji: '.$title);

        // Return title to display
        return Response::make($title);
    }

    /**
     * Image list for TinyMCE
     *
     * @return Response
     */
    public function action_imagelist()
    {
        $images = array();

        $basedir = path('public').'upload'.DS.'images';
        $basedir_length = strlen($basedir);

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basedir, \RecursiveDirectoryIterator::SKIP_DOTS)) as $f)
        {
            if ($f->isFile())
            {
                $extension = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));

                if ($extension == 'gif' or $extension == 'jpg' or $extension == 'jpeg' or $extension == 'png')
                {
                    $basename = strtr(substr($f->getPathname(), ($basedir_length + 1)), array(
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

                    $images[] = array(
                        'value' => 'public/upload/images/'.$basename,
                        'title' => $basename
                    );

                }
            }
        }

        usort($images, function($a, $b) {
            return strnatcasecmp($a['title'], $b['title']);
        });

        return Response::json($images);
    }

    /**
     * Index
     *
     * @return Response
     */
    public function action_index()
    {
        // Assets
        Asset::add('jeditable', 'public/js/jquery.jeditable.min.js', 'jquery');

        // Online subsystem (needed for one block)
        $online = IoC::resolve('online');

        // Create view
        $this->view = View::make('admin.dashboard.index');

        // Stats
        $stats = array(
            'users'    => array(
                'title' => 'Użytkowników',
                'your'  => '---',
                'total' => DB::table('users')->count()),
            'news'     => array(
                'title' => 'Newsów',
                'your'  => $this->user->news_count,
                'total' => DB::table('news')->count()),
            'pages'    => array(
                'title' => 'Stron',
                'your'  => DB::table('pages')->where('user_id', '=', $this->user->id)->count(),
                'total' => DB::table('pages')->count()),
            'comments' => array(
                'title' => 'Komentarzy',
                'your'  => $this->user->comments_count,
                'total' => DB::table('comments')->count())
        );

        $this->view->with('stats', $stats);

        // Online administrators
        $groups = Model\Group::with_role('admin_access');

        $this->view->with('online', $this->online);

        if (!empty($groups))
        {
            $this->view->with('admins', Model\User::find_by_groups($groups, array(
                        'users.id',
                        'users.display_name',
                        'profiles.news_count',
                        'groups.name as group_name')));
        }
        else
        {
            $this->view->with('admins', array());
        }

        // Notes
        $this->view->with('notes', Model\AdminNote::retrieve());

        // Logs
        if (Auth::can('admin_logs'))
        {
            $this->view->with('logs', Model\Log::retrieve(array(
                        'logs.created_at',
                        'logs.title',
                        'users.display_name')));
        }

        // Submitted content
        if (Auth::can('admin_submitted_content'))
        {
            $this->view->with('submitted', Model\SubmittedContent::retrieve(array(
                        'users.display_name',
                        'submitted_content.title',
                        'submitted_content.created_at',
                        'submitted_content.type')));
        }

        // Shoutbox
        $this->view->with('list', View::make('shoutbox.list2', array(
                    'moderation' => (\Auth::can('mod_shoutbox') or \Auth::can('admin_shoutbox')),
                    'posts'      => DB::table('shoutbox')->where('type', '=', 'admin')->left_join('users', 'users.id', '=', 'shoutbox.user_id')->take(\Config::get('limits.shoutbox', 10))
                            ->order_by('id', 'desc')
                            ->get(array(
                                'shoutbox.*',
                                'users.display_name',
                                'users.slug'))
                )));
    }

    /**
     * Load contents for edit
     *
     * @return Response
     */
    public function action_load_content()
    {
        // Permissions
        if (!Auth::can('admin_dashboard_edit'))
        {
            return Response::error(403);
        }

        // Validate request
        if (!Request::ajax() or !Input::has('id') or !starts_with(Input::get('id'), 'note-'))
        {
            return Response::error(500);
        }

        $id = substr(Input::get('id'), 5);

        if (!ctype_digit($id))
        {
            return Response::error(500);
        }

        // Find note
        $id = Model\AdminNote::find($id, 'note');

        // Not found?
        if (!$id)
        {
            return Response::make('');
        }

        // Return raw data
        return Response::make($id->note);
    }

    /**
     * Load title for edit
     *
     * @return Response
     */
    public function action_load_title()
    {
        // Permissions
        if (!Auth::can('admin_dashboard_edit'))
        {
            return Response::error(403);
        }

        // Validate request
        if (!Request::ajax() or !Input::has('id') or !starts_with(Input::get('id'), 'note-title-'))
        {
            return Response::error(500);
        }

        $id = substr(Input::get('id'), 11);

        if (!ctype_digit($id))
        {
            return Response::error(500);
        }

        // Find note
        $id = Model\AdminNote::find($id, 'title');

        // Not found?
        if (!$id)
        {
            return Response::make('');
        }

        // Return raw data
        return Response::make(htmlspecialchars_decode($id->title, ENT_QUOTES));
    }

}