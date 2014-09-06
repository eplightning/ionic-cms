<?php
namespace Model\Forum;

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

        foreach (DB::table('forum_boards')->order_by('left', 'asc')->get(array('id', 'title', 'slug', 'depth')) as $elem)
        {
            if ($prev_depth > $elem->depth)
            {
                for (; $prev_depth > $elem->depth; $prev_depth--)
                {
                    if (isset($path[$prev_depth]))
                        unset($path[$prev_depth]);
                }
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
}
