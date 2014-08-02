<?php
namespace Ionic;

/**
 * Online list
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class Online {

    public $anonymous = 0;
    public $guests = 0;
    public $users = array();

    /**
     * Load online list
     */
    public function __construct()
    {
        // With group style
        /*
        $data = \DB::table('sessions')
                ->left_join('users', 'users.id', '=', 'sessions.user_id')
                ->left_join('groups', 'groups.id', '=', 'users.group_id')
                ->where('sessions.last_activity', '>', (time() - 600))
                ->get(array('sessions.location_name', 'sessions.last_activity', 'sessions.location_url', 'sessions.type', 'sessions.user_id', 'users.display_name', 'users.slug', 'groups.style'));
        */
        // Without
        $data = \DB::table('sessions')
                ->left_join('users', 'users.id', '=', 'sessions.user_id')
                ->where('sessions.last_activity', '>', (time() - 600))
                ->get(array('sessions.location_name', 'sessions.last_activity', 'sessions.location_url', 'sessions.type', 'sessions.user_id', 'users.display_name', 'users.slug'));

        // Iterate
        foreach ($data as $v)
        {
            if ($v->type == 3)
            {
                $this->anonymous++;
            }
            elseif ($v->type == 2 && $v->user_id)
            {
                $this->users[(int) $v->user_id] = array('display_name'  => $v->display_name, 'slug'          => $v->slug, 'location_name' => $v->location_name, 'location_url'  => $v->location_url, 'last_activity' => $v->last_activity);
            }
            else
            {
                $this->guests++;
            }
        }
    }

    /**
     * Is specified user online
     *
     * @param  int  $id
     * @return bool
     */
    public function is_online($id)
    {
        if (!$id)
            return false;

        return isset($this->users[(int) $id]) ? true : false;
    }

}