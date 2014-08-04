<?php
namespace Ionic;

use DB;

class Notifications {

    protected $list = null;

    public function __construct()
    {
        \Event::listen('ionic.notification_add', function ($type, $current_user, $parameters) {
            if ($type == 'friend_request')
            {
                return array(
                    'link'       => 'friends',
                    'message'    => 'Użytkownik '.$current_user->display_name.' zaprosił Ciebie do listy znajomych.',
                    'type'       => 'friend_request',
                    'user_id'    => $parameters['user'],
                    'content_id' => $current_user->id
                );
            }
            elseif ($type == 'conversation_new')
            {
                if ($parameters['setting_email'] and \Config::get('advanced.notifications_email', true))
                {

                    ionic_mail(4, $parameters['email'], array(
                        ':name'    => $parameters['display_name'],
                        ':sender'  => $current_user->display_name,
                        ':title'   => $parameters['title'],
                        ':website' => \URL::to('conversations/show/'.$parameters['id'])
                    ));
                }

                return array(
                    'link'       => 'conversations/show/'.$parameters['id'],
                    'message'    => 'Otrzymałeś zaproszenie do prywatnej dyskusji założonej przez użytkownika '.$current_user->display_name.'.',
                    'type'       => 'conversation_new',
                    'user_id'    => $parameters['user_id'],
                    'content_id' => $parameters['id']
                );
            }
            elseif ($type == 'conversation_reply')
            {
                if ($parameters['setting_email'] and \Config::get('advanced.notifications_email', true))
                {
                    ionic_mail(4, $parameters['email'], array(
                        ':name'    => $parameters['display_name'],
                        ':sender'  => $current_user->display_name,
                        ':title'   => $parameters['title'],
                        ':website' => \URL::to('conversations/show/'.$parameters['id'])
                    ));
                }

                return array(
                    'link'       => 'conversations/show/'.$parameters['id'],
                    'message'    => 'Nowy post w dyskusji: '.$parameters['title'],
                    'type'       => 'conversation_reply',
                    'user_id'    => $parameters['user_id'],
                    'content_id' => $parameters['id']
                );
            }
        });
    }

    public function add($type, array $parameters = array())
    {
        $data = \Event::until('ionic.notification_add', array($type, Auth::get_user(), $parameters));

        if (!$data or !is_array($data))
            return false;

        DB::table('notifications')->insert($data);

        return true;
    }

    public function delete($id)
    {
        if (Auth::is_guest())
            return false;

        $id = DB::table('notifications')->where('id', '=', (int) $id)->first(array('user_id', 'id'));

        if (!$id)
            return false;

        if (Auth::get_user()->id != $id->user_id)
            return false;

        DB::table('notifications')->where('id', '=', $id->id)->delete();

        return true;
    }

    public function get_list()
    {
        if (!is_null($this->list)) return $this->list;

        $this->list = array();

        if (Auth::is_logged() and \Config::get('advanced.notifications', true))
        {
            foreach (DB::table('notifications')->where('user_id', '=', Auth::get_user()->id)->take(10)->get(array('id', 'type', 'message', 'link')) as $not)
            {
                $this->list[$not->id] = $not;
            }
        }

        return $this->list;
    }
}