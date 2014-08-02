<?php
namespace Model;

use \DB;

/**
 * User model
 *
 * @author  Wrexdot <wrexdot@gmail.com>
 * @package Model
 */
class User {

    /**
     * Increase user attempts
     *
     * @param string $ip
     */
    public static function add_attempt($ip)
    {
        $record = DB::table('login_attempts')->where('ip', '=', $ip)->first(array('count'));

        if (!$record)
        {
            DB::table('login_attempts')->insert(array('ip'      => $ip, 'count'   => 1, 'expires' => (time() + 900)));
            return;
        }

        $record->count = (int) $record->count;
        $record->count++;

        DB::table('login_attempts')->where('ip', '=', $ip)->update(array('count'   => $record->count, 'expires' => (time() + 900)));
    }

    /**
     * Add user
     *
     * @param string $username
     * @param string $password
     * @param stirng $email
     * @param string $display_name
     * @param int $group_id
     * @return boolean|int
     */
    public static function add_user($username, $password, $email, $display_name = null, $group_id = 2, $ip = null, $date = null)
    {
        if ($display_name == null)
            $display_name = $username;
        if ($ip == null)
            $ip = \Request::ip();
        if ($date == null)
            $date = date('Y-m-d H:i:s');

        // Make sure he's unique
        $user = DB::table('users')->where('username', '=', $username)->or_where('email', '=', $email)->or_where('display_name', '=', $display_name)->first(array('id'));

        if ($user)
        {
            return false;
        }

        // First users table
        $id = DB::table('users')->insert_get_id(array(
            'username'     => $username,
            'password'     => \Hash::make($password),
            'email'        => $email,
            'display_name' => $display_name,
            'group_id'     => (int) $group_id,
            'slug'         => ionic_tmp_slug('users')
                ));

        DB::table('users')->where('id', '=', $id)->update(array('slug' => ionic_find_slug($display_name, $id, 'users', 30)));

        // Profile
        DB::table('profiles')->insert(array(
            'user_id'    => $id,
            'ip'         => $ip,
            'created_at' => $date
        ));

        return $id;
    }

    /**
     * Find users by groups
     *
     * @param array $groups
     * @param array $fields
     */
    public static function find_by_groups($groups = array(), $fields = array())
    {
        if (empty($fields))
            $fields = array('users.*', 'groups.name as group_name', 'profiles.*');

        return DB::table('users')->where_in('group_id', $groups)
                        ->left_join('profiles', 'profiles.user_id', '=', 'users.id')
                        ->join('groups', 'groups.id', '=', 'users.group_id')
                        ->get($fields);
    }

    /**
     * Get user attempts
     *
     * @param  string $ip
     * @return int
     */
    public static function get_attempts($ip)
    {
        $record = DB::table('login_attempts')->where('ip', '=', $ip)->first(array('count', 'expires'));

        if (!$record)
        {
            return 0;
        }

        if ((int) $record->expires <= time())
        {
            DB::table('login_attempts')->where('ip', '=', $ip)->delete();

            return 0;
        }

        return (int) $record->count;
    }

    /**
     * Reset attempts
     *
     * @param string $ip
     */
    public static function reset_attempts($ip)
    {
        DB::table('login_attempts')->where('ip', '=', $ip)->delete();
    }

    /**
     * Get user data with profile
     *
     * @param  int $id
     * @return mixed
     */
    public static function retrieve($id)
    {
        return DB::table('users')->where('users.id', '=', (int) $id)
                        ->left_join('profiles', 'profiles.user_id', '=', 'users.id')
                        ->join('groups', 'groups.id', '=', 'users.group_id')
                        ->first(array('users.*', 'groups.name as group_name', 'groups.style as group_style', 'profiles.*'));
    }

    /**
     * Get user data using username
     *
     * @param  string $name
     * @return mixed
     */
    public static function retrieve_by_username($name)
    {
        return DB::table('users')->where('users.username', '=', $name)
                        ->left_join('profiles', 'profiles.user_id', '=', 'users.id')
                        ->join('groups', 'groups.id', '=', 'users.group_id')
                        ->first(array('users.*', 'groups.name as group_name', 'groups.style as group_style', 'profiles.*'));
    }

    /**
     * Get user using slug
     *
     * @param string $slug
     */
    public static function retrieve_by_slug($slug)
    {
        return DB::table('users')->where('users.slug', '=', $slug)
                        ->left_join('profiles', 'profiles.user_id', '=', 'users.id')
                        ->join('groups', 'groups.id', '=', 'users.group_id')
                        ->first(array('users.*', 'groups.name as group_name', 'groups.style as group_style', 'profiles.*'));
    }

}