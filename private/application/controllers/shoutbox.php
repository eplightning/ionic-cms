<?php

/**
 * Shoutbox
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Shoutbox_Controller extends Base_Controller {

    /**
     * Delete message
     *
     * @param  string   $id
     * @return Response
     */
    public function action_delete($id)
    {
        if (!Request::ajax() or Request::method() != 'POST' or Request::forged() or !ctype_digit($id))
            return Response::error(500);

        // admin session
        if (!Auth::is_logged() and Session::has('admin_id') and Session::has('admin_roles'))
        {
            Auth::init_admin();
            $this->user = Auth::get_user();
        }

        if (!Auth::can('mod_shoutbox') and !Auth::can('admin_shoutbox'))
        {
            return Response::error(403);
        }

        DB::table('shoutbox')->where('id', '=', (int) $id)->delete();

        Model\Log::add('Usunął wpis w shoutboxie', $this->user->id);

        return Response::json(array(
                    'status' => true));
    }

    /**
     * Shoutbox archive
     */
    public function action_index()
    {
        $this->online('Archiwum shoutboxa', 'shoutbox');

        $this->page->set_title('Archiwum shoutboxa');
        $this->page->breadcrumb_append('Archiwum shoutboxa', 'shoutbox');

        $this->view = View::make('shoutbox.archive', array(
                    'posts' => DB::table('shoutbox')->where('type', '=', 'global')
                            ->left_join('users', 'users.id', '=', 'shoutbox.user_id')
                            ->order_by('id', 'desc')
                            ->paginate(20, array(
                                'shoutbox.id',
                                'shoutbox.user_id',
                                'shoutbox.content',
                                'shoutbox.created_at',
                                'shoutbox.user_id',
                                'users.display_name',
                                'users.slug'))
                ));
    }

    /**
     * Refresh
     *
     * @param  string   $type
     * @return Response
     */
    public function action_refresh($type = 'global')
    {
        if (!Request::ajax())
            return Response::error(500);

        if ($type == 'admin')
        {
            // admin session
            if (!Auth::is_logged() and Session::has('admin_id') and Session::has('admin_roles'))
            {
                Auth::init_admin();
                $this->user = Auth::get_user();
            }

            if (!Auth::can('admin_access'))
            {
                return Response::error(403);
            }
        }

        return Response::make(View::make(($type == 'admin' ? 'shoutbox.list2' : 'shoutbox.list'), array(
                            'posts'      => DB::table('shoutbox')->where('type', '=', $type)->left_join('users', 'users.id', '=', 'shoutbox.user_id')->take(\Config::get('limits.shoutbox', 10))
                                    ->order_by('id', 'desc')
                                    ->get(array(
                                        'shoutbox.*',
                                        'users.display_name',
                                        'users.slug')),
                            'moderation' => (Auth::can('mod_shoutbox') or Auth::can('admin_shoutbox'))
                        )));
    }

    /**
     * New shoutbox post
     *
     * @param  string   $type
     * @return Response
     */
    public function action_post($type = 'global')
    {
        if (!Request::ajax() or Request::method() != 'POST' or Request::forged() or !Input::has('post'))
            return Response::error(500);

        // admin session
        if (!Auth::is_logged() and Session::has('admin_id') and Session::has('admin_roles'))
        {
            Auth::init_admin();
            $this->user = Auth::get_user();
        }

        if ((Auth::is_logged() and Auth::banned()) or (Auth::is_guest() and !Config::get('guests.shoutbox', false)))
        {
            return Response::error(403);
        }

        if ($type == 'admin' and !Auth::can('admin_access'))
        {
            return Response::error(404);
        }

        if (Auth::is_logged() and Config::get('advanced.shoutbox_flood', 0))
        {
            $last_post = DB::table('shoutbox')->where('user_id', '=', $this->user->id)->order_by('id', 'desc')->first('created_at');

            if ($last_post and strtotime($last_post->created_at) >= (time() - Config::get('advanced.shoutbox_flood', 0)))
            {
                return Response::json(array(
                            'status' => false));
            }
        }

        DB::table('shoutbox')->insert(array(
            'user_id'    => (Auth::is_logged() ? $this->user->id : null),
            'type'       => $type,
            'created_at' => date('Y-m-d H:i:s'),
            'content'    => ionic_censor(nl2br(HTML::specialchars(Input::get('post')))),
            'ip'         => Request::ip()
        ));

        return Response::json(array(
                    'status' => true));
    }

}