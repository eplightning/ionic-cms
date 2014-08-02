<?php

/**
 * Friends management
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Friends_Controller extends Base_Controller {

    /**
     * Accept friend request
     *
     * @param  string   $id
     * @return Response
     */
    public function action_accept($id)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (!ctype_digit($id))
            return Redirect::to('friends');

        $id = DB::table('friends')->where('user_id', '=', $this->user->id)->where('requested_id', '=', $id)->where('is_accepted', '=', 0)->first('requested_id');

        if (!$id)
        {
            $this->notice('Nieprawidłowy użytkownik');
            return Redirect::to('friends');
        }

        DB::table('friends')->where('user_id', '=', $this->user->id)->where('requested_id', '=', $id->requested_id)->update(array('is_accepted' => 1));
        DB::table('notifications')->where('user_id', '=', $this->user->id)->where('content_id', '=', $id->requested_id)->where('type', '=', 'friend_request')->delete();

        $this->notice('Zaproszenie zostało zaakceptowane pomyślnie');
        return Redirect::to('friends');
    }

    /**
     * Username autocompletion
     *
     * @return Response
     */
    public function action_autocomplete()
    {
        if (!Request::ajax() or !Input::has('term') or Auth::is_guest())
            return Response::error(500);

        $str = str_replace('%', '', Input::get('term'));

        $us = DB::table('users')->take(20)->where('display_name', 'like', $str.'%')->get('display_name');

        $result = array();

        foreach ($us as $u)
        {
            $result[] = $u->display_name;
        }

        return Response::json($result);
    }

    /**
     * Decline friend request
     *
     * @param  string   $id
     * @return Response
     */
    public function action_decline($id)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (!ctype_digit($id))
            return Redirect::to('friends');

        $id = DB::table('friends')->where('user_id', '=', $this->user->id)->where('requested_id', '=', $id)->where('is_accepted', '=', 0)->first('requested_id');

        if (!$id)
        {
            $this->notice('Nieprawidłowy użytkownik');
            return Redirect::to('friends');
        }

        DB::table('friends')->where('user_id', '=', $this->user->id)->where('requested_id', '=', $id->requested_id)->delete();
        DB::table('notifications')->where('user_id', '=', $this->user->id)->where('content_id', '=', $id->requested_id)->where('type', '=', 'friend_request')->delete();

        $this->notice('Zaproszenie zostało odrzucone pomyślnie');
        return Redirect::to('friends');
    }

    /**
     * Delete friend
     *
     * @param  string   $id
     * @return Response
     */
    public function action_delete($id)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (!ctype_digit($id))
            return Redirect::to('friends');

        $id = (int) $id;
        $user = $this->user->id;

        if (!DB::table('friends')->where(function($q) use ($id, $user) {
                                    $q->where('user_id', '=', $id);
                                    $q->where('requested_id', '=', $user);
                                })
                        ->or_where(function($q) use ($id, $user) {
                                    $q->where('user_id', '=', $user);
                                    $q->where('requested_id', '=', $id);
                                })->first('user_id'))
        {
            $this->notice('Taki użytkownik nie występuje na twojej liście znajomych');
            return Redirect::to('friends');
        }

        DB::table('friends')->where('user_id', '=', $this->user->id)->where('requested_id', '=', $id)->delete();
        DB::table('friends')->where('user_id', '=', $id)->where('requested_id', '=', $this->user->id)->delete();

        $this->notice('Użytkownik został pomyślnie usunięty z listy znajomych');
        return Redirect::to('friends');
    }

    /**
     * Friends index
     *
     * @return Response
     */
    public function action_index()
    {
        if ($this->require_auth())
            return Redirect::to('index');

        $requests = array('sent'    => array(), 'invites' => array());
        $friends = array();

        foreach (DB::table('friends')->join('users', 'users.id', '=', 'friends.requested_id')->where('user_id', '=', $this->user->id)->get(array('users.id', 'users.slug', 'users.display_name', 'friends.is_accepted')) as $f)
        {
            if ($f->is_accepted == 0)
            {
                $requests['invites'][] = $f;
            }
            else
            {
                $friends[] = $f;
            }
        }

        foreach (DB::table('friends')->join('users', 'users.id', '=', 'friends.user_id')->where('requested_id', '=', $this->user->id)->get(array('users.id', 'users.slug', 'users.display_name', 'friends.is_accepted')) as $f)
        {
            if ($f->is_accepted == 0)
            {
                $requests['sent'][] = $f;
            }
            else
            {
                $friends[] = $f;
            }
        }

        $this->page->set_title('Lista znajomych');
        $this->online('Lista znajomych', 'friends');
        $this->page->breadcrumb_append('Lista znajomych', 'friends');

        Asset::add('jquery-ui', 'public/css/flick/jquery-ui.custom.css');
        Asset::add('jquery-ui', 'public/js/jquery-ui.site.min.js', 'jquery');

        $this->view = View::make('friends.index', array(
                    'requests' => $requests,
                    'friends'  => $friends
                ));
    }

    /**
     * Invite new user
     *
     * @param  string   $id
     * @return Response
     */
    public function action_invite($id = null)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (!$id or !ctype_digit($id))
        {
            if (Request::forged() or !Input::has('user') or Request::method() != 'POST')
                return Redirect::to('friends');

            $user = DB::table('users')->where('display_name', '=', Input::get('user'))->first('id');
        }
        else
        {
            $user = DB::table('users')->where('id', '=', (int) $id)->first('id');
        }

        if (!$user)
        {
            $this->notice('Użytkownik nie został znaleziony');
            return Redirect::to('friends');
        }

        if ($user->id == $this->user->id)
        {
            $this->notice('Nie możesz dodać samego siebie');
            return Redirect::to('friends');
        }

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
            $this->notice('Ten użytkownik już znajduje się na twojej liście znajomych');
            return Redirect::to('friends');
        }

        DB::table('friends')->insert(array(
            'user_id'      => $user->id,
            'requested_id' => $this->user->id,
            'is_accepted'  => 0
        ));

        $this->notifications->add('friend_request', array('user' => $user->id));

        $this->notice('Zaproszenie zostało wysłane. Musi zostać zatwierdzone zanim użytkownik pojawi się na twojej liście znajomych.');
        return Redirect::to('friends');
    }

}