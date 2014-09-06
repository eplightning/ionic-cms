<?php
namespace Model\Forum;

use DB;

/**
 * Thread model
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    ionic
 * @subpackage forum
 */
class Thread {

    /**
     * Get threads
     *
     * @param   int     $board_id
     * @param   int     $offset
     * @param   int     $limit
     * @param   string  $order_by
     * @param   string  $order_type
     */
    public static function get_threads($board_id, $offset = 0, $limit = 20, $order_by = 'forum_threads.last_date', $order_type = 'desc')
    {
        $query = DB::table('forum_threads')->left_join('users', 'users.id', '=', 'forum_threads.last_user_id')
                                           ->left_join('users as '.DB::prefix().'author', 'users.id', '=', 'forum_threads.user_id')
                                           ->where('forum_threads.board_id', '=', $board_id)
                                           ->skip($offset)
                                           ->take($limit)
                                           ->order_by($order_by, $order_type);

        return $query->get(array('forum_threads.*',
                                 'users.slug as last_user_slug', 'users.display_name as last_display_name',
                                 'author.slug as user_slug', 'author.display_name as display_name'));
    }
}
