<?php
namespace Model;

use \DB;

/**
 * Logs model
 *
 * @author  Wrexdot <wrexdot@gmail.com>
 * @package Model
 */
class Log {

    /**
     * Add log
     *
     * @param string $title
     * @param int    $user_id
     */
    public static function add($title, $user_id)
    {
        DB::table('logs')->insert(array('title'      => $title, 'user_id'    => (int) $user_id, 'created_at' => date('Y-m-d H:i:s'), 'ip'         => \Request::ip()));
    }

    /**
     * Clear logs
     */
    public static function clear()
    {
        DB::table('logs')->where('id', '>', 0)->delete();
    }

    /**
     * Retrieve logs
     *
     * @param array $data
     * @param int $limit
     */
    public static function retrieve($data = array(), $limit = 10)
    {
        if (empty($data))
            $data = array('logs.*', 'users.display_name');

        return DB::table('logs')->order_by('logs.id', 'desc')->join('users', 'users.id', '=', 'logs.user_id')->take($limit)->get($data);
    }

}