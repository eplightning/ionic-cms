<?php

/**
 * Pruning
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Prune_Controller extends Admin_Controller {

    /**
     * Account cleanup
     *
     * @return Response
     */
    public function action_accounts()
    {
        if (!Auth::can('admin_prune'))
            return Response::error(403);

        if (Request::forged() or Request::method() != 'POST' or !Input::has('older_than'))
        {
            return Response::error(404);
        }

        $date = strtotime(Input::get('older_than'));

        if (!$date)
        {
            $this->notice('Nieprawidłowa data');
            return Redirect::to('admin/prune/index');
        }

        $date = date('Y-m-d H:i:s', $date);

        DB::table('validating_users')->where('created_at', '<', $date)->delete();

        $this->log('Usunięto stare nieaktywowane konta');
        $this->notice('Operacja wykonana pomyślnie');

        return Redirect::to('admin/prune/index');
    }

    /**
     * Recounting
     *
     * @return Response
     */
    public function action_counters()
    {
        if (!Auth::can('admin_prune'))
            return Response::error(403);

        $this->page->breadcrumb_append('Czyszczenie', 'admin/prune/index');
        $this->page->set_title('Czyszczenie');

        if (!Session::has('prune_counters'))
        {
            $current = 0;
        }
        else
        {
            $current = Session::get('prune_counters');
        }

        $i = 0;

        foreach (DB::table('profiles')->skip($current)->take(30)->get('user_id') as $id)
        {
            $i++;

            DB::table('profiles')->where('user_id', '=', $id->user_id)->update(array(
                'news_count'     => DB::table('news')->where('user_id', '=', $id->user_id)->count(),
                'comments_count' => DB::table('comments')->where('user_id', '=', $id->user_id)->where('is_hidden', '=', 0)->count(),
            ));
        }

        if ($i == 30)
        {
            Session::put('prune_counters', ($current + 30));

            $this->page->set_http_equiv('refresh', 2);
            $this->view = View::make('admin.prune.progress', array('current' => $current));

            return;
        }

        Session::forget('prune_counters');

        $this->log('Przeliczono liczniki komentarzy i newsów');
        $this->notice('Operacja wykonana pomyślnie');

        return Redirect::to('admin/prune/index');
    }

    /**
     * Index
     *
     * @return Response
     */
    public function action_index()
    {
        if (!Auth::can('admin_prune'))
            return Response::error(403);

        $this->page->breadcrumb_append('Czyszczenie', 'admin/prune/index');
        $this->page->set_title('Czyszczenie');

        $sessions = DB::table('sessions')->where('last_activity', '<', (time() - 3600))->count();

        $this->view = View::make('admin.prune.index', array('old_sessions' => $sessions));
    }

    /**
     * Old messages
     *
     * @return Response
     */
    public function action_messages()
    {
        if (!Auth::can('admin_prune'))
            return Response::error(403);

        if (Request::forged() or Request::method() != 'POST' or !Input::has('older_than'))
        {
            return Response::error(404);
        }

        $date = strtotime(Input::get('older_than'));

        if (!$date)
        {
            $this->notice('Nieprawidłowa data');
            return Redirect::to('admin/prune/index');
        }

        $date = date('Y-m-d H:i:s', $date);

        DB::table('conversations')->where('created_at', '<', $date)->delete();

        $this->log('Usunięto stare wiadomości');
        $this->notice('Operacja wykonana pomyślnie');

        return Redirect::to('admin/prune/index');
    }

    /**
     * Recounting
     *
     * @return Response
     */
    public function action_news_counters()
    {
        if (!Auth::can('admin_prune'))
            return Response::error(403);

        $this->page->breadcrumb_append('Czyszczenie', 'admin/prune/index');
        $this->page->set_title('Czyszczenie');

        if (!Session::has('prune_news_counters'))
        {
            if (!Request::forged() and Request::method() == 'POST' and ctype_digit(Input::get('news_count', '100')))
            {
                $current = (int) Input::get('news_count', 100);
            }
            else
            {
                return Redirect::to('admin/prune/index');
            }
        }
        else
        {
            $current = Session::get('prune_news_counters');
        }

        if ($current < 0)
            $current = 0;

        foreach (DB::table('news')->order_by('id', 'desc')->skip($current)->take(30)->get('id') as $id)
        {
            DB::table('news')->where('id', '=', $id->id)->update(array(
                'comments_count' => DB::table('comments')->where('content_type', '=', 'news')->where('content_id', '=', $id->id)->where('is_hidden', '=', 0)->count(),
            ));
        }

        if ($current != 0)
        {
            Session::put('prune_news_counters', ($current - 30));

            $this->page->set_http_equiv('refresh', 2);
            $this->view = View::make('admin.prune.progress', array('current' => $current.' (pozostało)'));

            return;
        }

        Session::forget('prune_news_counters');

        $this->log('Przeliczono liczniki komentarzy w newsach');
        $this->notice('Operacja wykonana pomyślnie');

        return Redirect::to('admin/prune/index');
    }

    /**
     * Old sessions
     *
     * @return Response
     */
    public function action_sessions()
    {
        if (!Auth::can('admin_prune'))
            return Response::error(403);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/prune/index');
        }

        DB::table('sessions')->where('last_activity', '<', (time() - 3600))->delete();

        $this->log('Usunięto stare sesje');
        $this->notice('Operacja wykonana pomyślnie');

        return Redirect::to('admin/prune/index');
    }

}