<?php

/**
 * Users
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Users_Controller extends Base_Controller {

    /**
     * Warn user
     *
     * @param  string   $user
     * @return Response
     */
    public function action_add_warning($user)
    {
        if ($this->require_auth(array('admin_warnings', 'mod_warnings')))
            return Redirect::to('index');

        if (Request::method() != 'POST' or Request::forged() or !ctype_digit($user))
        {
            return Response::error(500);
        }

        $user = DB::table('users')->where('users.id', '=', $user)->first(array('users.id', 'users.group_id', 'users.slug', 'users.display_name'));

        if (!$user)
        {
            return Response::error(404);
        }

        if (!Input::has('reason'))
        {
            $this->notice('Powód jest wymagany');
            return Redirect::to('users/profile/'.$user->slug);
        }

        if (DB::table('permissions')->join('roles', 'roles.id', '=', 'permissions.role_id')->where('group_id', '=', $user->group_id)->where('roles.name', '=', 'admin_root')->first('role_id'))
        {
            return Response::error(403);
        }

        DB::table('warnings')->insert(array(
            'mod_id'     => $this->user->id,
            'user_id'    => $user->id,
            'reason'     => HTML::specialchars(Input::get('reason')),
            'created_at' => date('Y-m-d H:i:s')
        ));

        Model\Warning::refresh_count($user->id);

        $this->notice('Ostrzeżenie zostało dodane pomyślnie');
        Model\Log::add('Dodano ostrzeżenie użytkownikowi: '.$user->display_name, $this->user->id);

        return Redirect::to('users/profile/'.$user->slug);
    }

    /**
     * Delete notification
     *
     * @param  string   $id
     * @return Response
     */
    public function action_delete_notification($id)
    {
        if (!ctype_digit($id) or !Request::ajax() or Request::method() != 'POST' or Request::forged())
            return Response::json(array('status' => false));

        // It won't work if caller is a guest so dw
        $status = $this->notifications->delete($id);

        return Response::json(array('status' => $status));
    }

    /**
     * Email user
     *
     * @param  string   $slug
     * @return Response
     */
    public function action_email($slug)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        $user = DB::table('users')->join('profiles', 'profiles.user_id', '=', 'users.id')->where('users.slug', '=', $slug)->first(array('users.display_name', 'users.email', 'users.slug', 'profiles.setting_email'));

        if (!$user)
        {
            return Response::error(404);
        }

        if (!$user->setting_email)
        {
            $this->notice('Ten użytkownik nie zezwolił na wysyłanie wiadomości bezpośrednio na jego e-mail');
            return Redirect::to('users/list');
        }

        if (Request::method() == 'POST' and !Request::forged() and Input::has('message'))
        {
            $mailer = IoC::resolve('mailer');

            $message = \Swift_Message::newInstance();
            $message->setFrom(array(Config::get('email.from') => Config::get('email.from_name')));
            $message->setTo($user->email);
            $message->setReplyTo($this->user->email);

            $message->setSubject('Wiadomość wysłana poprzez serwis');

            $message->setBody('Witaj, poniższa wiadomość została wysłana przez użytkownika '.$this->user->display_name.'. Odpowiadając na ten e-mail wiadomość powinna dojść na jego adres e-mail.

'.HTML::specialchars(Input::get('message')));

            $mailer->send($message);

            $this->notice('Wiadomość e-mail została wysłana pomyślnie');
            return Redirect::to('users/profile/'.$user->slug);
        }

        $this->page->set_title('Wysyłanie wiadomości');
        $this->online('Wysyłanie wiadomości', 'users/list');
        $this->page->breadcrumb_append('Wysyłanie wiadomości', 'users/list');

        $this->view = View::make('users.email', array('user' => $user));
    }

    /**
     * User list
     *
     * @param  string   $letter
     * @return Response
     */
    public function action_list($letter = null)
    {
        if (!Config::get('guests.users', false) and $this->require_auth())
            return Redirect::to('index');

        if ($letter and !preg_match('!^[a-z0-9]$!', $letter))
        {
            $letter = null;
        }

        if (!empty($_GET['sort']) and strstr($_GET['sort'], '-') !== FALSE)
        {
            $sort = explode('-', $_GET['sort'], 2);

            if (count($sort) != 2 or !in_array($sort[0], array('id', 'display_name', 'points', 'comments_count', 'news_count')) or !in_array($sort[1], array('asc', 'desc')))
            {
                $sort = array('id', 'desc');
            }
        }
        else
        {
            $sort = array('id', 'desc');
        }

        $query = DB::table('users')->join('profiles', 'profiles.user_id', '=', 'users.id')->order_by($sort[0], $sort[1]);

        if ($letter)
        {
            if ($letter == '9')
            {
                // it sucks but it's faster
                $query->where('display_name', 'like', '0%')
                        ->or_where('display_name', 'like', '1%')
                        ->or_where('display_name', 'like', '2%')
                        ->or_where('display_name', 'like', '3%')
                        ->or_where('display_name', 'like', '4%')
                        ->or_where('display_name', 'like', '5%')
                        ->or_where('display_name', 'like', '6%')
                        ->or_where('display_name', 'like', '7%')
                        ->or_where('display_name', 'like', '8%')
                        ->or_where('display_name', 'like', '9%');
            }
            else
            {
                if ($letter == 'a')
                {
                    $query->where('display_name', 'like', 'a%');
                    $query->or_where('display_name', 'like', 'ą%');
                }
                elseif ($letter == 'e')
                {
                    $query->where('display_name', 'like', 'e%');
                    $query->or_where('display_name', 'like', 'ę%');
                }
                elseif ($letter == 'o')
                {
                    $query->where('display_name', 'like', 'o%');
                    $query->or_where('display_name', 'like', 'ó%');
                }
                elseif ($letter == 's')
                {
                    $query->where('display_name', 'like', 's%');
                    $query->or_where('display_name', 'like', 'ś%');
                }
                elseif ($letter == 'l')
                {
                    $query->where('display_name', 'like', 'l%');
                    $query->or_where('display_name', 'like', 'ł%');
                }
                elseif ($letter == 'z')
                {
                    $query->where('display_name', 'like', 'z%');
                    $query->or_where('display_name', 'like', 'ź%');
                    $query->or_where('display_name', 'like', 'ż%');
                }
                elseif ($letter == 'c')
                {
                    $query->where('display_name', 'like', 'c%');
                    $query->or_where('display_name', 'like', 'ć%');
                }
                elseif ($letter == 'n')
                {
                    $query->where('display_name', 'like', 'n%');
                    $query->or_where('display_name', 'like', 'ń%');
                }
                else
                {
                    $query->where('display_name', 'like', $letter.'%');
                }
            }
        }

        $query = $query->paginate(20, array(
                    'users.id', 'users.display_name', 'users.slug', 'profiles.points', 'profiles.comments_count', 'profiles.news_count', 'profiles.created_at'
                ))->appends(array('sort' => $sort[0].'-'.$sort[1]));

        $this->page->set_title('Lista użytkowników');
        $this->online('Lista użytkowników', 'users/list');
        $this->page->breadcrumb_append('Lista użytkowników', 'users/list');

        $this->view = View::make('users.list', array(
                    'users'    => $query,
                    'base_url' => $letter ? URL::to('users/list/'.$letter) : URL::to('users/list'),
                    'letter'   => $letter,
                    'sort'     => $sort
                ));
    }

    /**
     * Upvote/downvote
     *
     * @param  string   $t
     * @return Response
     */
    public function action_karma($t)
    {
        if ($t != 'up' and $t != 'down')
            return Response::error(500);

        if (!Request::ajax() or Request::method() != 'POST' or Request::forged() or !Input::has('id') or !Input::has('type') or !ctype_digit(Input::get('id')))
            return Response::error(500);

        $id = (int) Input::get('id');
        $type = Input::get('type');

        if (!in_array($type, array('blog', 'news')) and !\Event::until('ionic.karma_valid', array($type)))
        {
            return Response::error(500);
        }

        if (!Model\Karma::can_karma($id, $type))
            return Response::error(403);

        $karma = 0;

        switch ($type)
        {
            case 'news':
                $news = DB::table('news')->where('id', '=', $id)->first(array('karma', 'id'));

                if (!$news)
                    return Response::error(500);

                $karma = $news->karma;
                $karma += ($t == 'up' ? 1 : -1);

                DB::table('news')->where('id', '=', $id)->update(array(
                    'karma' => $karma,
                ));
                break;

            case 'blog':
                $blog = DB::table('blogs')->where('id', '=', $id)->first(array('karma', 'id'));

                if (!$blog)
                    return Response::error(500);

                $karma = $blog->karma;
                $karma += ($t == 'up' ? 1 : -1);

                DB::table('blogs')->where('id', '=', $id)->update(array(
                    'karma' => $karma,
                ));
                break;

            default:
                $karma = \Event::until('ionic.karma_add', array($id, $t));

                if (is_null($karma) or is_bool($karma))
                {
                    return Response::error(500);
                }

                $karma = (int) $karma;
        }

        DB::table('karma')->insert(array(
            'user_id'      => Auth::is_logged() ? $this->user->id : null,
            'ip'           => Request::ip(),
            'content_id'   => $id,
            'content_type' => $type
        ));

        if ($karma >= 0)
        {
            return Response::json(array('status' => true, 'points' => '+'.$karma, 'color'  => 'green'));
        }

        return Response::json(array('status' => true, 'points' => $karma, 'color'  => 'red'));
    }

    /**
     * Online list
     *
     * @return Response
     */
    public function action_online()
    {
        if (!Config::get('guests.users', false) and $this->require_auth())
            return Redirect::to('index');

        $this->page->set_title('Online');
        $this->online('Online', 'users/online');
        $this->page->breadcrumb_append('Online', 'users/online');

        $this->view = View::make('users.online');
    }

    /**
     * Control panel
     *
     * @return Response
     */
    public function action_panel()
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (Request::method() == 'POST' and !Request::forged())
        {
            if (Input::has('change_password'))
            {
                if (!Input::has('old_password') or !Input::has('new_password') or !Input::has('confirm_password'))
                {
                    $this->notice('Wszystkie pola zmiany hasła są wymagane');
                    return Redirect::to('users/panel');
                }

                $old_password = DB::table('users')->where('id', '=', $this->user->id)->only('password');
                $new_password = Input::get('new_password');

                if (!$old_password)
                {
                    return Response::error(500);
                }

                if (!Hash::check(Input::get('old_password'), $old_password))
                {
                    $this->notice('Stare hasło nie pasuje');
                    return Redirect::to('users/panel');
                }

                if ($new_password != Input::get('confirm_password'))
                {
                    $this->notice('Nowe hasło nie pasuje');
                    return Redirect::to('users/panel');
                }

                if (Str::length($new_password) < 6)
                {
                    $this->notice('Nowe hasło musi mieć conajmniej 6 znaków');
                    return Redirect::to('users/panel');
                }

                DB::table('users')->where('id', '=', $this->user->id)->update(array(
                    'password' => Hash::make($new_password)
                ));

                $this->notice('Hasło zostało zmienione pomyślnie');
            }
            elseif (Input::has('change_avatar'))
            {
                if (Input::has_file('avatar'))
                {
                    $file = Input::file('avatar');

                    if (!is_array($file) or empty($file['size']) or empty($file['tmp_name']) or empty($file['name']) or $file['error'] != UPLOAD_ERR_OK or !File::is(array('gif', 'jpg', 'png'), $file['tmp_name'], $file['name']))
                    {
                        $this->notice('Nieprawidłowy format obrazka');
                        return Redirect::to('users/panel');
                    }

                    if (($file['size'] / 1024) > Config::get('advanced.avatar_size', 50))
                    {
                        $this->notice('Wrzucony obrazek jest za duży');
                        return Redirect::to('users/panel');
                    }

                    if ($this->user->avatar)
                    {
                        @unlink(path('public').'upload'.DS.'avatars'.DS.$this->user->avatar);
                    }

                    $extension = File::extension($file['name']);

                    if ($extension != 'gif' and $extension != 'jpg' and $extension != 'png' and $extension != 'jpeg')
                    {
                        $extension = 'png';
                    }

                    try {
                        $image = WideImage::loadFromFile($file['tmp_name']);

                        if (Config::get('advanced.avatar_type', 'exactly') == 'exactly')
                        {
                            $image = $image->resize(Config::get('advanced.avatar_width', 50), Config::get('advanced.avatar_height', 50), 'fill');
                        }
                        else
                        {
                            $image = $image->resizeDown(Config::get('advanced.avatar_width', 50), Config::get('advanced.avatar_height', 50));
                        }

                        $image->saveToFile(path('public').'upload'.DS.'avatars'.DS.$this->user->id.'.'.$extension);
                    } catch (Exception $e) {
                        DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array(
                            'avatar' => ''
                        ));

                        $this->notice('Wystąpił błąd podczas przetwarzania obrazka. Prawdopodobnie jest on uszkodzony');
                        return Redirect::to('users/panel');
                    }

                    DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array(
                        'avatar' => $this->user->id.'.'.$extension
                    ));

                    $this->notice('Avatar został pomyślnie zaaktualizowany');
                }
                elseif (Input::get('delete_avatar') == '1' and $this->user->avatar)
                {
                    @unlink(path('public').'upload'.DS.'avatars'.DS.$this->user->avatar);

                    DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array(
                        'avatar' => ''
                    ));

                    $this->notice('Avatar został pomyślnie usunięty');
                }
            }
            elseif (Input::has('submit'))
            {
                $profile_data = array(
                    'setting_email'     => Input::get('setting_email', '0') == '1' ? 1 : 0,
                    'setting_showemail' => Input::get('setting_showemail', '0') == '1' ? 1 : 0,
                );

                if (Input::has('real_name'))
                {
                    $real_name = Input::get('real_name');

                    if (Str::length($real_name) > 127)
                    {
                        $this->notice('Maksymalna długość imienia i nazwiska to 127 znaków');
                        return Redirect::to('users/panel');
                    }
                    elseif (!preg_match('!^[\pL\pN\s]+$!u', $real_name))
                    {
                        $this->notice('Nieprawidłowy format imienia i nazwiska. Dozwolne są wyłącznie litery, liczby oraz spacja.');
                        return Redirect::to('users/panel');
                    }

                    $profile_data['real_name'] = HTML::specialchars($real_name);
                }

                DB::table('profiles')->where('user_id', '=', $this->user->id)->update($profile_data);

                \Model\Field::update_fields($this->user->id);

                $this->notice('Profil został zaaktualizowany pomyślnie');
            }

            return Redirect::to('users/panel');
        }

        $this->page->set_title('Panel kontrolny');
        $this->online('Panel kontrolny', 'users/panel');
        $this->page->breadcrumb_append('Panel kontrolny', 'users/panel');

        $this->view = View::make('users.panel', array(
                    'custom_fields'     => Model\Field::get_fields($this->user->id),
                    'avatar_type'       => Config::get('advanced.avatar_type', 'exactly'),
                    'avatar_dimensions' => Config::get('advanced.avatar_width', 50).'x'.Config::get('advanced.avatar_height', 50),
                    'avatar_size'       => Config::get('advanced.avatar_size', 50)
                ));
    }

    /**
     * User profile
     *
     * @param  string   $slug
     * @return Response
     */
    public function action_profile($slug)
    {
        if (!Config::get('guests.users', false) and $this->require_auth())
            return Redirect::to('index');

        $user = DB::table('users')->join('groups', 'users.group_id', '=', 'groups.id')->join('profiles', 'profiles.user_id', '=', 'users.id')
                        ->where('users.slug', '=', $slug)->first(array('users.*', 'profiles.*', 'groups.name as group_name'));

        if (!$user)
        {
            return Response::error(404);
        }

        $roles = array();

        foreach (DB::table('permissions')->join('roles', 'roles.id', '=', 'permissions.role_id')->where('group_id', '=', $user->group_id)->get('roles.name') as $r)
        {
            $roles[] = $r->name;
        }

        $friend = false;

        if (Auth::is_logged())
        {
            $id = $this->user->id;
            if (DB::table('friends')->where(function($q) use($id, $user) {
                                        $q->where('user_id', '=', $id);
                                        $q->where('requested_id', '=', $user->id);
                                    })
                            ->or_where(function($q) use($id, $user) {
                                        $q->where('user_id', '=', $user->id);
                                        $q->where('requested_id', '=', $id);
                                    })->first('user_id'))
            {
                $friend = true;
            }
        }

        $this->page->set_title('Profil użytkownika '.$user->display_name);
        $this->online('Profil użytkownika '.$user->display_name, 'users/profile/'.$user->slug);
        $this->page->breadcrumb_append('Profil użytkownika '.$user->display_name, 'users/profile/'.$user->slug);

        $this->view = View::make('users.profile');

        $this->view->with('user', $user);
        $this->view->with('can_warn', ((Auth::can('admin_warnings') or Auth::can('mod_warnings')) and !in_array('admin_root', $roles)));
        $this->view->with('can_unwarn', (Auth::can('admin_warnings') or Auth::can('mod_warnings')));

        $this->view->with('max_warnings', Config::get('bans.warnings', 5));
        $this->view->with('fields', Model\Field::get_field_values($user->id));
        $this->view->with('warnings', DB::table('warnings')->join('users', 'users.id', '=', 'warnings.mod_id')->where('user_id', '=', $user->id)->get(array('warnings.*', 'users.display_name', 'users.slug')));
        $this->view->with('news', DB::table('news')->order_by('id', 'desc')->take(10)->where('is_published', '=', 1)->where('user_id', '=', $user->id)->get(array('news.title', 'news.slug', 'news.created_at', 'news.external_url')));
        $this->view->with('comments', DB::table('comments')->order_by('id', 'desc')->take(10)->where('is_hidden', '=', 0)->where('user_id', '=', $user->id)->get('*'));
        $this->view->with('blogs', DB::table('blogs')->where('user_id', '=', $user->id)->count());
        $this->view->with('is_friend', $friend);
    }

    /**
     * Remove warning
     *
     * @param  string   $id
     * @return Response
     */
    public function action_remove_warning($id)
    {
        if (($redirect = $this->require_auth(array('admin_warnings', 'mod_warnings'))))
            return $redirect;

        if (!ctype_digit($id))
        {
            return Response::error(500);
        }

        $id = DB::table('warnings')->where('warnings.id', '=', (int) $id)->join('users', 'users.id', '=', 'warnings.user_id')->first(array('warnings.id', 'warnings.user_id', 'users.slug', 'users.display_name'));

        if (!$id)
        {
            return Response::error(404);
        }

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('users/profile/'.$id->slug);
        }

        DB::table('warnings')->where('id', '=', $id->id)->delete();

        Model\Warning::refresh_count($id->user_id);

        $this->notice('Ostrzeżenie usunięte');
        Model\Log::add('Dodano ostrzeżenie użytkownika: '.$id->display_name, $this->user->id);

        return Redirect::to('users/profile/'.$id->slug);
    }

}