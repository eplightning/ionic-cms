<?php

/**
 * Controller for private conversations
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Conversations_Controller extends Base_Controller {

    /**
     * Autocomplete usernames
     *
     * @return Response
     */
    public function action_autocomplete()
    {
        if (!Request::ajax() or !Input::has('term') or Auth::is_guest())
            return Response::error(500);

        $str = str_replace('%', '', Input::get('term'));

        $us = DB::table('users')->where('id', '<>', $this->user->id)->take(20)->where('display_name', 'like', $str.'%')->get('display_name');

        $result = array();

        foreach ($us as $u)
        {
            $result[] = $u->display_name;
        }

        return Response::json($result);
    }

    /**
     * Delete conversation
     *
     * @param  string   $conv
     * @return Response
     */
    public function action_delete($conv)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (!ctype_digit($conv))
            return Response::error(500);

        $conv = DB::table('conversations')->where('id', '=', (int) $conv)->first(array('id', 'poster_id'));

        if (!$conv)
            return Response::error(404);

        if ($conv->poster_id != $this->user->id)
        {
            return Response::error(403);
        }

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('conversations/show/'.$conv->id);
        }

        DB::table('notifications')->where_in('type', array('conversation_reply', 'conversation_new', 'conversation_newsletter'))->where('content_id', '=', $conv->id)->delete();
        DB::table('conversations')->where('id', '=', $conv->id)->delete();

        $this->notice('Prywatna dyskusja i wszystkie zawarte w niej posty zostały pomyślnie usunięte.');
        return Redirect::to('conversations/index');
    }

    /**
     * Invite user
     *
     * @param  string   $conv
     * @return Response
     */
    public function action_edit_add($conv)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (!ctype_digit($conv))
            return Response::error(500);

        $conv = DB::table('conversations')->where('id', '=', (int) $conv)->first(array('id', 'poster_id', 'title'));

        if (!$conv)
            return Response::error(404);

        if ($conv->poster_id != $this->user->id)
        {
            return Response::error(403);
        }

        if (Request::forged() or Request::method() != 'POST')
            return Response::error(500);

        if (DB::table('conversation_users')->where('conversation_id', '=', $conv->id)->count() >= Config::get('limits.conversation_users', 20))
        {
            $this->notice('Przekroczyłeś limit zaproszonych użytkowników');
            return Redirect::to('conversations/show/'.$conv->id);
        }

        if (!Input::has('display_name'))
        {
            $this->notice('Nazwa użytkownika jest wymagana');
            return Redirect::to('conversations/show/'.$conv->id);
        }

        $user = DB::table('users')->join('profiles', 'profiles.user_id', '=', 'users.id')->where('display_name', '=', Input::get('display_name'))
                ->first(array('users.id', 'users.email', 'users.display_name', 'profiles.setting_email'));

        if (!$user)
        {
            $this->notice('Taki użytkownik nie został znaleziony');
            return Redirect::to('conversations/show/'.$conv->id);
        }

        if ($user->id == $conv->poster_id or DB::table('conversation_users')->where('conversation_id', '=', $conv->id)->where('user_id', '=', $user->id)->first('user_id'))
        {
            $this->notice('Taki użytkownik już został zaproszony');
            return Redirect::to('conversations/show/'.$conv->id);
        }

        DB::table('conversation_users')->insert(array(
            'conversation_id' => $conv->id,
            'user_id'         => $user->id,
            'notifications'   => 0
        ));

        $this->notifications->add('conversation_new', array(
            'setting_email' => (bool) $user->setting_email,
            'email'         => $user->email,
            'display_name'  => $user->display_name,
            'user_id'       => $user->id,
            'id'            => $conv->id,
            'title'         => $conv->title
        ));

        $this->notice('Użytkownik został zaproszony pomyślnie');

        return Redirect::to('conversations/show/'.$conv->id);
    }

    /**
     * Remove user from discussion
     *
     * @param  string   $conv
     * @return Response
     */
    public function action_edit_delete($conv)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (!ctype_digit($conv))
            return Response::error(500);

        $conv = DB::table('conversations')->where('id', '=', (int) $conv)->first(array('id', 'poster_id'));

        if (!$conv)
            return Response::error(404);

        if ($conv->poster_id != $this->user->id)
        {
            return Response::error(403);
        }

        if (Request::forged() or Request::method() != 'POST' or !Request::ajax() or !Input::has('user_id') or !ctype_digit(Input::get('user_id')))
            return Response::error(500);

        $user = (int) Input::get('user_id');

        if ($user == $conv->poster_id)
            return Redirect::to('conversations/show/'.$conv->id);

        if (!DB::table('conversation_users')->where('conversation_id', '=', $conv->id)->where('user_id', '=', $user)->first('user_id'))
        {
            return Response::error(404);
        }

        DB::table('conversation_users')->where('conversation_id', '=', $conv->id)->where('user_id', '=', $user)->delete();
        DB::table('notifications')->where_in('type', array('conversation_reply', 'conversation_new', 'conversation_newsletter'))->where('content_id', '=', $conv->id)->where('user_id', '=', $user)->delete();

        return Response::json(array('status' => true));
    }

    /**
     * Index page
     */
    public function action_index()
    {
        if ($this->require_auth())
            return Redirect::to('index');

        $this->page->set_title('Prywatne dyskusje');
        $this->online('Prywatne dyskusje', 'conversations');
        $this->page->breadcrumb_append('Prywatne dyskusje', 'conversations');

        $this->view = View::make('conversations.index', array(
                    'yours'     => DB::table('conversations')->where('is_newsletter', '=', 0)->where('poster_id', '=', $this->user->id)->order_by('conversations.last_post_date', 'desc')->take(Config::get('limits.conversations', 20))->get('*'),
                    'invited'   => DB::table('conversation_users')->where('conversation_users.user_id', '=', $this->user->id)
                            ->join('conversations', 'conversations.id', '=', 'conversation_users.conversation_id')
                            ->join('users', 'users.id', '=', 'conversations.poster_id')
                            ->order_by('conversations.last_post_date', 'desc')
                            ->paginate(20, array(
                                'conversations.id', 'conversations.created_at', 'conversations.title', 'conversations.is_closed', 'conversations.messages_count', 'conversations.last_post_date', 'conversations.last_post_user',
                                'users.display_name', 'users.slug'
                            )),
                    'banned'    => Auth::banned(),
                    'count'     => DB::table('conversations')->where('is_newsletter', '=', 0)->where('poster_id', '=', $this->user->id)->count(),
                    'limit'     => Config::get('limits.conversations', 20),
                    'unlimited' => Auth::can('admin_access')
                ));
    }

    /**
     * Create new conversation
     *
     * @param  string   $user
     * @return Response
     */
    public function action_new($user = null)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (!Auth::can('admin_access') and DB::table('conversations')->where('is_newsletter', '=', 0)->where('poster_id', '=', $this->user->id)->count() >= Config::get('limits.conversations', 20))
        {
            $this->notice('Przekroczyłeś limit prywatnych dyskusji. Usuń stare jeśli chcesz utworzyć kolejną');
            return Redirect::to('conversations');
        }

        if (Auth::banned())
        {
            $this->notice('Jesteś zbanowany');
            return Redirect::to('conversations');
        }

        if (!Request::forged() and Request::method() == 'POST')
        {
            $data = array_merge(array('title'        => '', 'display_name' => '', 'message'      => ''), Input::only(array('title', 'display_name', 'message')));

            $validator = Validator::make($data, array(
                        'title'        => 'required|max:127',
                        'message'      => 'required',
                        'display_name' => 'required|exists:users,display_name|not_in:"'.addslashes($this->user->display_name).'"'
                    ));

            if ($validator->fails())
            {
                return Redirect::to('conversations/new')->with_errors($validator)
                                ->with_input('only', array('title', 'display_name', 'message'));
            }
            else
            {
                $recipent = DB::table('users')->join('profiles', 'profiles.user_id', '=', 'users.id')->where('display_name', '=', $data['display_name'])
                        ->first(array('users.id', 'users.email', 'users.display_name', 'profiles.setting_email'));

                // there's no way this can happen, but still - i'm not sure if checking display name is really reliable
                if ($recipent->id == $this->user->id)
                {
                    return Redirect::to('conversations/new')->with_input('only', array('title', 'display_name', 'message'));
                }

                $data['title'] = HTML::specialchars($data['title']);

                $conversation_id = DB::table('conversations')->insert_get_id(array(
                    'poster_id'      => $this->user->id,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'title'          => $data['title'],
                    'is_closed'      => 0,
                    'messages_count' => 1,
                    'last_post_date' => date('Y-m-d H:i:s'),
                    'last_post_user' => $this->user->display_name,
                    'is_newsletter'  => 0
                        ));

                DB::table('messages')->insert(array(
                    'conversation_id' => $conversation_id,
                    'user_id'         => $this->user->id,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'message'         => Model\Message::prepare_content($data['message']),
                    'is_reported'     => 0
                ));

                DB::table('conversation_users')->insert(array(
                    'conversation_id' => $conversation_id,
                    'user_id'         => $recipent->id,
                    'notifications'   => 0
                ));

                $this->notifications->add('conversation_new', array(
                    'setting_email' => (bool) $recipent->setting_email,
                    'email'         => $recipent->email,
                    'display_name'  => $recipent->display_name,
                    'user_id'       => $recipent->id,
                    'id'            => $conversation_id,
                    'title'         => $data['title']
                ));

                $this->notice('Prywatna dyskusja została utworzona pomyślnie');
                return Redirect::to('conversations/show/'.$conversation_id);
            }
        }

        $this->page->set_title('Nowa prywatna dyskusja');
        $this->online('Nowa prywatna dyskusja', 'conversations/new');
        $this->page->breadcrumb_append('Prywatne dyskusje', 'conversations');
        $this->page->breadcrumb_append('Nowa prywatna dyskusja', 'conversations/new');

        Asset::add('markitup', 'public/js/jquery.markitup.js', 'jquery');
        Asset::add('markitup', 'public/js/skins/simple/style.css');
        Asset::add('jquery-ui', 'public/css/flick/jquery-ui.custom.css');
        Asset::add('jquery-ui', 'public/js/jquery-ui.site.min.js', 'jquery');

        if ($user and ctype_digit($user))
        {
            $user = DB::table('users')->where('id', '=', $user)->first('display_name');

            if ($user)
            {
                $user = $user->display_name;
            }
            else
            {
                $user = '';
            }
        }
        else
        {
            $user = '';
        }

        $this->view = View::make('conversations.new', array(
                    'old'  => Input::old(),
                    'user' => $user
                ));
    }

    /**
     * Add post in conversation
     *
     * @param  string   $conv
     * @return Response
     */
    public function action_reply($conv)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (!ctype_digit($conv) or Request::forged() or Request::method() != 'POST')
            return Response::error(500);

        $conv = DB::table('conversations')->where('id', '=', (int) $conv)->first(array('*'));

        if (!$conv or $conv->is_closed)
            return Response::error(404);

        if ($conv->poster_id != $this->user->id)
        {
            $sub = DB::table('conversation_users')->where('conversation_id', '=', $conv->id)->where('user_id', '=', $this->user->id)->first('notifications');

            if (!$sub)
            {
                return Response::error(403);
            }
        }

        if (!Input::has('message') or Auth::banned())
            return Redirect::to('conversations/show/'.$conv->id);

        DB::table('messages')->insert(array(
            'conversation_id' => $conv->id,
            'user_id'         => $this->user->id,
            'created_at'      => date('Y-m-d H:i:s'),
            'message'         => Model\Message::prepare_content(Input::get('message')),
            'is_reported'     => 0
        ));

        DB::table('conversations')->where('id', '=', $conv->id)->update(array(
            'messages_count' => ($conv->messages_count + 1),
            'last_post_date' => date('Y-m-d H:i:s'),
            'last_post_user' => $this->user->display_name,
        ));

        foreach (DB::table('conversation_users')->where('conversation_users.conversation_id', '=', $conv->id)
                ->where('conversation_users.user_id', '<>', $this->user->id)
                ->where('conversation_users.notifications', '=', 1)
                ->join('users', 'users.id', '=', 'conversation_users.user_id')
                ->join('profiles', 'profiles.user_id', '=', 'users.id')
                ->get(array('users.id', 'users.email', 'users.display_name', 'profiles.setting_email')) as $u)
        {
            $this->notifications->add('conversation_reply', array(
                'setting_email' => (bool) $u->setting_email,
                'email'         => $u->email,
                'display_name'  => $u->display_name,
                'user_id'       => $u->id,
                'id'            => $conv->id,
                'title'         => $conv->title
            ));
        }

        if ($this->user->id != $conv->poster_id)
        {
            $u = DB::table('users')->where('id', '=', $conv->poster_id)->join('profiles', 'profiles.user_id', '=', 'users.id')->first(array('users.id', 'users.email', 'users.display_name', 'profiles.setting_email'));

            if ($u)
            {
                $this->notifications->add('conversation_reply', array(
                    'setting_email' => (bool) $u->setting_email,
                    'email'         => $u->email,
                    'display_name'  => $u->display_name,
                    'user_id'       => $u->id,
                    'id'            => $conv->id,
                    'title'         => $conv->title
                ));
            }
        }

        $this->notice('Post został dodany pomyślnie');
        return Redirect::to('conversations/show/'.$conv->id);
    }

    /**
     * Report naughty message!
     *
     * @param  string   $msg
     * @return Response
     */
    public function action_report($msg)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (!ctype_digit($msg))
            return Response::error(500);

        $msg = DB::table('messages')->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
                        ->where('messages.id', '=', (int) $msg)->first(array('messages.id', 'messages.message', 'conversations.title', 'conversations.id as conv_id', 'conversations.poster_id', 'messages.is_reported', 'messages.user_id'));

        if (!$msg)
            return Response::error(404);

        if ($msg->is_reported)
        {
            $this->notice('Wiadomość była już zgłoszona');
            return Redirect::to('conversations/show/'.$msg->conv_id);
        }

        if ($msg->user_id == $this->user->id)
        {
            $this->notice('Nie możesz zgłosić swojej własnej wiadomości');
            return Redirect::to('conversations/show/'.$msg->conv_id);
        }

        if ($msg->poster_id != $this->user->id)
        {
            if (!DB::table('conversation_users')->where('conversation_id', '=', $msg->conv_id)->where('user_id', '=', $this->user->id)->first('notifications'))
            {
                return Response::error(403);
            }
        }

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('conversations/show/'.$msg->conv_id);
        }

        DB::table('messages')->where('id', '=', $msg->id)->update(array(
            'is_reported' => 1
        ));

        DB::table('reports')->insert(array(
            'user_id'       => $this->user->id,
            'title'         => $msg->title,
            'saved_content' => $msg->message,
            'created_at'    => date('Y-m-d H:i:s'),
            'item_type'     => 'message',
            'item_id'       => $msg->id
        ));

        $this->notice('Prywatna wiadomość została zgłoszona pomyślnie');
        return Redirect::to('conversations/show/'.$msg->conv_id);
    }

    /**
     * Conversation view
     *
     * @param  string   $conv
     * @return Response
     */
    public function action_show($conv)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (!ctype_digit($conv))
            return Response::error(500);

        $conv = DB::table('conversations')->where('id', '=', (int) $conv)->first(array('*'));

        if (!$conv)
            return Response::error(404);

        if ($conv->poster_id != $this->user->id)
        {
            $sub = DB::table('conversation_users')->where('conversation_id', '=', $conv->id)->where('user_id', '=', $this->user->id)->first('notifications');

            if (!$sub)
            {
                return Response::error(403);
            }
        }

        DB::table('notifications')->where_in('type', array('conversation_reply', 'conversation_new', 'conversation_newsletter'))->where('user_id', '=', $this->user->id)->where('content_id', '=', $conv->id)->delete();

        Asset::add('markitup', 'public/js/jquery.markitup.js', 'jquery');
        Asset::add('markitup', 'public/js/skins/simple/style.css');
        Asset::add('jquery-ui', 'public/css/flick/jquery-ui.custom.css');
        Asset::add('jquery-ui', 'public/js/jquery-ui.site.min.js', 'jquery');

        $this->page->set_title('Prywatna dyskusja');
        $this->online('Prywatna dyskusja', 'conversations/show/'.$conv->id);
        $this->page->breadcrumb_append('Prywatne dyskusje', 'conversations');
        $this->page->breadcrumb_append('Prywatna dyskusja', 'conversations/show/'.$conv->id);

        if ($conv->poster_id != $this->user->id)
        {
            $this->view = View::make('conversations.show', array(
                        'conversation' => $conv,
                        'posts'        => DB::table('messages')->where('conversation_id', '=', $conv->id)
                                ->join('users', 'users.id', '=', 'messages.user_id')
                                ->join('profiles', 'users.id', '=', 'profiles.user_id')
                                ->order_by('messages.id', 'desc')
                                ->paginate(20, array('messages.id', 'messages.message', 'messages.created_at', 'messages.is_reported',
                                    'messages.user_id', 'users.display_name', 'profiles.comments_count', 'profiles.news_count', 'profiles.avatar', 'users.slug')),
                        'banned'       => Auth::banned(),
                        'sub'          => (bool) $sub->notifications
                    ));
        }
        else
        {
            $this->view = View::make('conversations.show_owner', array(
                        'conversation' => $conv,
                        'posts'        => DB::table('messages')->where('conversation_id', '=', $conv->id)
                                ->join('users', 'users.id', '=', 'messages.user_id')
                                ->join('profiles', 'users.id', '=', 'profiles.user_id')
                                ->order_by('messages.id', 'desc')
                                ->paginate(20, array('messages.id', 'messages.message', 'messages.created_at', 'messages.is_reported',
                                    'messages.user_id', 'users.display_name', 'profiles.comments_count', 'profiles.news_count', 'profiles.avatar', 'users.slug')),
                        'banned'       => Auth::banned(),
                        'users'        => DB::table('conversation_users')->where('conversation_id', '=', $conv->id)->join('users', 'users.id', '=', 'conversation_users.user_id')
                                ->order_by('users.display_name', 'asc')
                                ->take(Config::get('limits.conversation_users', 20))
                                ->get(array('users.display_name', 'users.slug', 'users.id'))
                    ));
        }
    }

    /**
     * Subscribe to conversation
     *
     * @param  string   $conv
     * @return Response
     */
    public function action_sub($conv)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (!ctype_digit($conv))
            return Response::error(500);

        $conv = DB::table('conversations')->where('id', '=', (int) $conv)->first(array('id', 'poster_id'));

        if (!$conv)
            return Response::error(404);

        if ($conv->poster_id == $this->user->id)
        {
            return Response::error(403);
        }

        $sub = DB::table('conversation_users')->where('conversation_id', '=', $conv->id)->where('user_id', '=', $this->user->id)->first('notifications');

        if (!$sub)
        {
            return Response::error(403);
        }

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('conversations/show/'.$conv->id);
        }

        DB::table('conversation_users')->where('conversation_id', '=', $conv->id)->where('user_id', '=', $this->user->id)->update(array(
            'notifications' => $sub->notifications ? 0 : 1
        ));

        $this->notice('Pomyślnie zmieniono status subskrypcji tej dyskusji');
        return Redirect::to('conversations/show/'.$conv->id);
    }

    /**
     * Open/close conversation
     *
     * @param  string   $conv
     * @return Response
     */
    public function action_status($conv)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (!ctype_digit($conv))
            return Response::error(500);

        $conv = DB::table('conversations')->where('id', '=', (int) $conv)->first(array('id', 'poster_id', 'is_closed'));

        if (!$conv)
            return Response::error(404);

        if ($conv->poster_id != $this->user->id)
        {
            return Response::error(403);
        }

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('conversations/show/'.$conv->id);
        }

        DB::table('conversations')->where('id', '=', $conv->id)->update(array(
            'is_closed' => $conv->is_closed ? 0 : 1
        ));

        $this->notice('Prywatna dyskusja została otwarta/zamknięta pomyślnie');
        return Redirect::to('conversations/show/'.$conv->id);
    }

}