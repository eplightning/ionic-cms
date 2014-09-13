<?php
namespace Model\Forum;

use Config;
use DB;

/**
 * Board model
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    ionic
 * @subpackage forum
 */
class Board {

    /**
     * Build jumpbox
     *
     * @return  array
     */
    public static function build_jumpbox()
    {
        $path = array();

        $select = array();
        $prev_depth = 0;

        foreach (DB::table('forum_boards')->order_by('left', 'asc')->get(array('id', 'title', 'slug', 'depth')) as $elem) {
            for (; $prev_depth > $elem->depth; $prev_depth--) {
                if (isset($path[$prev_depth]))
                    unset($path[$prev_depth]);
            }

            $path[$elem->depth] = $elem->title;

            if ($elem->depth > 0)
                $select[(int) $elem->id] = array('board/show/'.$elem->slug, implode(' / ', $path));

            $prev_depth = $elem->depth;
        }

        return $select;
    }

    /**
     * Get three (or one/two) levels of boards
     *
     * @param   int     $left
     * @param   int     $right
     * @param   int     $start_depth
     * @param   int     $end_depth
     * @return  array
     */
    public static function get_3level($left = null, $right = null, $start_depth = 0, $end_depth = 2)
    {
        $query = DB::table('forum_boards')->left_join('users', 'users.id', '=', 'forum_boards.last_user_id')
                                          ->where('forum_boards.depth', '>=', $start_depth)
                                          ->where('forum_boards.depth', '<=', $end_depth)
                                          ->order_by('forum_boards.left', 'asc');

        if ($left !== null and $right !== null) {
            $query->where('forum_boards.left', '>', $left)
                  ->where('forum_boards.right', '<', $right);
        }

        $boards = array();

        $last_root = 0;
        $last_sub = 0;
        $start_depth_p1 = $start_depth + 1;

        foreach ($query->get(array('forum_boards.*', 'users.slug as user_slug', 'users.display_name')) as $b) {
            switch ((int) $b->depth) {
            case $start_depth:
                $boards[$b->id] = array($b, array());
                $last_root = $b->id;
                break;
            case $start_depth_p1:
                $boards[$last_root][1][$b->id] = array($b, array());
                $last_sub = $b->id;
                break;
            default:
                $boards[$last_root][1][$last_sub][1][$b->id] = $b;
            }
        }

        return $boards;
    }

    /**
     * Tell us which of these boards can be considered unread!
     *
     * @param   array   $board_ids
     * @param   array   $marker_ids
     * @param   array   $unread
     * @return  array
     */
    public static function get_unread_boards(array $board_ids, array $marker_ids, array &$unread)
    {
        $count = count($board_ids);

        if (!$count)
            return;

        $boards = array();

        $query = DB::table('forum_threads')->group_by('board_id')
                                           ->take($count)
                                           ->order_by('last_date', 'desc')
                                           ->where_in('board_id', $board_ids)
                                           ->where('last_date', '>', time() - Config::get('forum.marker_expire', 7) * 86400);

        if (!empty($marker_ids))
            $query->where_not_in('id', $marker_ids);

        foreach ($query->get(array('board_id')) as $b) {
            $unread[(int) $b->board_id] = true;
        }

        foreach ($board_ids as $id) {
            if (!isset($unread[$id]))
                $unread[$id] = false;
        }

        return $boards;
    }

    /**
     * Rebuild last post for specified board and its parents
     *
     * Please note this relies on thread cache being actually up to date, so if needed rebuild thread's last post before calling this function
     *
     * @param   int $board_left
     * @param   int $board_right
     * @param   int $thread_id
     */
    public static function rebuild_last_post($board_left, $board_right, $thread_id = null)
    {
        $query = DB::table('forum_boards')->where('left', '<=', $board_left)
                                          ->where('right', '>=', $board_right)
                                          ->where('depth', '>', 0);

        if ($thread_id)
            $query->where('last_id', '=', $thread_id);

        // All ancestors + child
        foreach ($query->get(array('id', 'left', 'right')) as $b) {
            $last_thread = DB::table('forum_threads')->join('forum_boards', 'forum_boards.id', '=', 'forum_threads.board_id')
                                                     ->where('forum_boards.left', '>=', $b->left)
                                                     ->where('forum_boards.right', '<=', $b->right)
                                                     ->order_by('last_date', 'desc')
                                                     ->first(array('forum_threads.id', 'forum_threads.title', 'forum_threads.slug', 'forum_threads.last_date',
                                                                 'forum_threads.last_user_id'));

            if (!$last_thread) {
                DB::table('forum_boards')->where('id', '=', $b->id)->update(array(
                    'last_id'      => 0,
                    'last_title'   => '',
                    'last_date'    => '0000-00-00 00:00:00',
                    'last_slug'    => '',
                    'last_user_id' => 0
                ));
            } else {
                DB::table('forum_boards')->where('id', '=', $b->id)->update(array(
                    'last_id'      => $last_thread->id,
                    'last_title'   => $last_thread->title,
                    'last_date'    => $last_thread->last_date,
                    'last_slug'    => $last_thread->slug,
                    'last_user_id' => $last_thread->last_user_id
                ));
            }
        }
    }

    /**
     * Update board and its parents counters
     *
     * @param   int $left
     * @param   int $right
     * @param   int $posts_change
     * @param   int $threads_change
     */
    public static function update_board_counters($left, $right, $posts_change = 0, $threads_change = 0)
    {
        $update = array();

        if ($posts_change > 0) {
            $update['posts_count'] = DB::raw('`posts_count` + '.$posts_change);
        } elseif ($posts_change < 0) {
            $update['posts_count'] = DB::raw('`posts_count` - '.abs($posts_change));
        }

        if ($threads_change > 0) {
            $update['threads_count'] = DB::raw('`threads_count` + '.$threads_change);
        } elseif ($threads_change < 0) {
            $update['threads_count'] = DB::raw('`threads_count` - '.abs($threads_change));
        }

        if (!empty($update)) {
            $query = DB::table('forum_boards')->where('left', '<=', $left)->where('right', '>=', $right);

            // to avoid error if something is very wrong
            if ($posts_change < 0)
                $query->where('posts_count', '>=', abs($posts_change));

            if ($threads_change < 0)
                $query->where('threads_count', '>=', abs($threads_change));

            $query->update($update);
        }
    }

    /**
     * Update last post after merge
     *
     * @param   array   $old_threads
     * @param   string  $new_thread_id
     * @param   string  $new_thread_title
     * @param   string  $new_thread_slug
     */
    public static function update_last_post_info(array $old_threads, $new_thread_id, $new_thread_title, $new_thread_slug)
    {
        DB::table('forum_boards')->where_in('last_id', $old_threads)->update(array(
            'last_id'    => $new_thread_id,
            'last_title' => $new_thread_title,
            'last_slug'  => $new_thread_slug
        ));
    }
}
