<?php
namespace Model\Forum;

use Auth;
use Config;
use DB;

/**
 * Thread model
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    ionic
 * @subpackage forum
 */
class Thread {

    const UPDATE_USER_NEW_THREAD = 0;
    const UPDATE_USER_NEW_POST   = 1;
    const UPDATE_USER_DEL_POST   = 2;
    const UPDATE_USER_DEL_THREAD = 3;

    /**
     * @var int
     */
    public $board_id = null;

    /**
     * @var string
     */
    public $created_at = null;

    /**
     * @var int
     */
    public $id = null;

    /**
     * @var bool
     */
    public $is_closed = false;

    /**
     * @var bool
     */
    public $is_sticky = false;

    /**
     * @var int
     */
    public $last_id = 0;

    /**
     * @var string
     */
    public $last_date = '0000-00-00 00:00:00';

    /**
     * @var int
     */
    public $last_user_id = 0;

    /**
     * @var int
     */
    public $posts_count = 0;

    /**
     * @var string
     */
    public $slug = null;

    /**
     * @var string
     */
    public $title = '';

    /**
     * @var int
     */
    public $user_id = null;

    /**
     * Constructor
     *
     * @param   int|string  $thread
     */
    public function __construct($thread = null)
    {
        if (is_string($thread)) {
            $thread = DB::table('forum_threads')->where('slug', '=', $thread)->first(array('*'));
        } elseif (is_int($thread)) {
            $thread = DB::table('forum_threads')->where('id', '=', $thread)->first(array('*'));
        }

        if (!$thread)
            return;

        $this->board_id = (int) $thread->board_id;
        $this->created_at = $thread->created_at;
        $this->id = (int) $thread->id;
        $this->is_closed = (bool) $thread->is_closed;
        $this->is_sticky = (bool) $thread->is_sticky;
        $this->last_id = (int) $thread->last_id;
        $this->last_date = $thread->last_date;
        $this->last_user_id = (int) $thread->last_user_id;
        $this->posts_count = (int) $thread->posts_count;
        $this->slug = $thread->slug;
        $this->title = $thread->title;
        $this->user_id = (int) $thread->user_id;
    }

    /**
     * Create thread
     */
    public function create()
    {
        if ($this->user_id === null)
            $this->user_id = Auth::is_guest() ? null : Auth::get_user()->id;

        if (!$this->created_at)
            $this->created_at = date('Y-m-d H:i:s');

        $thread_id = DB::table('forum_threads')->insert_get_id(array(
            'board_id'    => $this->board_id,
            'user_id'     => $this->user_id,
            'is_closed'   => (int) $this->is_closed,
            'is_sticky'   => (int) $this->is_sticky,
            'created_at'  => $this->created_at,
            'title'       => $this->title,
            'slug'        => ionic_tmp_slug('forum_threads'),
            'posts_count' => $this->posts_count
        ));

        $this->slug = ionic_find_slug($this->title, $thread_id, 'forum_threads');

        DB::table('forum_threads')->where('id', '=', $thread_id)->update(array('slug' => $this->slug));

        $this->id = (int) $thread_id;
    }

    /**
     * Delete thread
     */
    public function delete()
    {
        if (!$this->id)
            return;

        DB::table('forum_threads')->where('id', '=', $this->id)->delete();
    }

    /**
     * Update thread's board
     */
    public function update_board()
    {
        if (!$this->id)
            return;

        DB::table('forum_threads')->where('id', '=', $this->id)->update(array(
            'board_id' => $this->board_id
        ));
    }

    /**
     * Update last post info
     */
    public function update_last()
    {
        if (!$this->id)
            return;

        DB::table('forum_threads')->where('id', '=', $this->id)->update(array(
            'last_id'      => $this->last_id,
            'last_date'    => $this->last_date,
            'last_user_id' => $this->last_user_id
        ));
    }

    /**
     * Update thread state
     */
    public function update_state()
    {
        if (!$this->id)
            return;

        DB::table('forum_threads')->where('id', '=', $this->id)->update(array(
            'is_closed' => (int) $this->is_closed,
            'is_sticky' => (int) $this->is_sticky
        ));
    }

    /**
     * Update thread title
     */
    public function update_title()
    {
        if (!$this->id)
            return;

        DB::table('forum_threads')->where('id', '=', $this->id)->update(array(
            'title' => $this->title
        ));
    }

    /**
     * Get unread threads
     *
     * @param   array   $ignore_board_ids
     * @param   int     $offset
     * @param   int     $count
     * @param   array   $marker_ids
     * @return  array
     */
    public static function get_new_threads(array $ignore_board_ids = array(), array $marker_ids = array(), $offset = 0, $count = 20)
    {
        $query = DB::table('forum_threads')->take($count)
                                           ->skip($offset)
                                           ->left_join('users', 'users.id', '=', 'forum_threads.last_user_id')
                                           ->left_join('users as '.DB::prefix().'author', 'author.id', '=', 'forum_threads.user_id')
                                           ->order_by('forum_threads.last_date', 'desc')
                                           ->where('forum_threads.last_date', '>', time() - Config::get('forum.marker_expire', 7) * 86400);

        if (!empty($ignore_board_ids))
            $query->where_not_in('forum_threads.board_id', $ignore_board_ids);

        if (!empty($marker_ids))
            $query->where_not_in('forum_threads.id', $marker_ids);

        return $query->get(array('forum_threads.*',
                                 'users.slug as last_user_slug', 'users.display_name as last_display_name',
                                 'author.slug as user_slug', 'author.display_name as display_name'));
    }

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
                                           ->left_join('users as '.DB::prefix().'author', 'author.id', '=', 'forum_threads.user_id')
                                           ->where('forum_threads.board_id', '=', $board_id)
                                           ->skip($offset)
                                           ->take($limit)
                                           ->order_by('forum_threads.is_sticky', 'desc')
                                           ->order_by($order_by, $order_type);

        return $query->get(array('forum_threads.*',
                                 'users.slug as last_user_slug', 'users.display_name as last_display_name',
                                 'author.slug as user_slug', 'author.display_name as display_name'));
    }

    /**
     * Refresh thread counters
     *
     * @param   int $thread_id
     */
    public static function refresh_thread_counters($thread_id)
    {
        DB::table('forum_threads')->where('id', '=', $thread_id)->update(array(
            'posts_count' => DB::table('forum_posts')->where('thread_id', '=', $thread_id)->count()
        ));
    }

    /**
     * Update last post
     *
     * Call when new post is being created
     *
     * @param   int     $thread_id
     * @param   string  $thread_title
     * @param   string  $thread_slug
     * @param   int     $board_left
     * @param   int     $board_right
     * @param   int     $post_id
     * @param   string  $post_date
     * @param   int     $post_user
     */
    public static function update_last_post($thread_id, $thread_title, $thread_slug, $board_left, $board_right, $post_id, $post_date, $post_user)
    {
        // Thread
        DB::table('forum_threads')->where('id', '=', $thread_id)->update(array(
            'last_id'      => $post_id,
            'last_date'    => $post_date,
            'last_user_id' => $post_user
        ));

        // Boards
        DB::table('forum_boards')->where('left', '<=', $board_left)
                                 ->where('right', '>=', $board_right)
                                 ->where('last_date', '<', $post_date)
                                 ->update(array(
            'last_id'      => $thread_id,
            'last_date'    => $post_date,
            'last_user_id' => $post_user,
            'last_title'   => $thread_title,
            'last_slug'    => $thread_slug
        ));
    }

    /**
     * Update user counters
     *
     * @param   int     $type
     * @param   int     $user_id
     * @param   array   $users
     */
    public static function update_user_counters($type = 0, $user_id = null, array $users = array())
    {
        // Self update
        if ($user_id === null) {
            if (Auth::is_guest())
                return;

            $user_id = Auth::get_user()->id;
        }

        // New topic
        if ($type == self::UPDATE_USER_NEW_THREAD and $user_id) {
            DB::table('profiles')->where('user_id', '=', $user_id)->update(array(
                'posts_count'   => DB::raw('`posts_count` + 1'),
                'threads_count' => DB::raw('`threads_count` + 1')
            ));
        } elseif ($type == self::UPDATE_USER_NEW_POST and $user_id) {
            DB::table('profiles')->where('user_id', '=', $user_id)->update(array(
                'posts_count'   => DB::raw('`posts_count` + 1')
            ));
        } elseif ($type == self::UPDATE_USER_DEL_POST and $user_id) {
            DB::table('profiles')->where('posts_count', '>', 0)->where('user_id', '=', $user_id)->update(array(
                'posts_count'   => DB::raw('`posts_count` - 1')
            ));
        } elseif ($type == self::UPDATE_USER_DEL_THREAD) {
            $prepared = array();

            foreach ($users as $id => $count) {
                if (!isset($prepared[$count]))
                    $prepared[$count] = array();

                $prepared[$count][] = $id;
            }

            foreach ($prepared as $c => $ids)
            {
                DB::table('profiles')->where('posts_count', '>=', $c)
                                     ->where_in('user_id', $ids)
                                     ->update(array('posts_count' => DB::raw('posts_count - '.$c)));
            }

            if ($user_id)
                DB::table('profiles')->where('user_id', '=', $user_id)->where('threads_count', '>', 0)->update(array(
                    'threads_count' => DB::raw('threads_count - 1')
                ));
        }
    }

    /**
     * Update views for thread
     *
     * @param   int $thread_id
     */
    public static function update_views($thread_id)
    {
        DB::table('forum_threads')->where('id', '=', $thread_id)->update(array('views' => DB::raw('`views` + 1')));
    }
}
