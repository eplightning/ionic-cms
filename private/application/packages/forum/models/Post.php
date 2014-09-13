<?php
namespace Model\Forum;

use Auth;
use DB;
use Request;
use URL;

/**
 * Post model
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    ionic
 * @subpackage forum
 */
class Post {

    /**
     * @var string
     */
    protected $content = '';

    /**
     * @var string
     */
    protected $content_raw = '';

    /**
     * @var string
     */
    protected $content_clean = '';

    /**
     * @var string
     */
    public $created_at = null;

    /**
     * @var int
     */
    public $id = null;

    /**
     * @var string
     */
    public $ip = null;

    /**
     * @var bool
     */
    public $is_op = false;

    /**
     * @var bool
     */
    public $is_reported = false;

    /**
     * @var int
     */
    public $thread_id = 0;

    /**
     * @var string
     */
    public $updated_at = '0000-00-00 00:00:00';

    /**
     * @var string
     */
    public $updated_by = '';

    /**
     * @var int
     */
    public $user_id = null;

    /**
     * Constructor
     */
    public function __construct($post = null)
    {
        $this->id = $post;

        if ($this->id) {
            $post = DB::table('forum_posts')->where('id', '=', $post)->first(array('*'));

            if (!$post) {
                $this->id = null;
                return;
            }

            $this->content = $post->content;
            $this->content_raw = $post->content_raw;
            $this->created_at = $post->created_at;
            $this->id = (int) $post->id;
            $this->ip = $post->ip;
            $this->is_op = (bool) $post->is_op;
            $this->is_reported = (bool) $post->is_reported;
            $this->thread_id = (int) $post->thread_id;
            $this->updated_at = $post->updated_at;
            $this->updated_by = $post->updated_by;
            $this->user_id = (int) $post->user_id;
        }
    }

    /**
     * Create post
     */
    public function create()
    {
        if ($this->user_id === null)
            $this->user_id = Auth::is_guest() ? null : Auth::get_user()->id;

        if (!$this->ip)
            $this->ip = Request::ip();

        if (!$this->created_at)
            $this->created_at = date('Y-m-d H:i:s');

        $post_id = DB::table('forum_posts')->insert_get_id(array(
            'thread_id'   => $this->thread_id,
            'user_id'     => $this->user_id,
            'is_op'       => (int) $this->is_op,
            'is_reported' => (int) $this->is_reported,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            'updated_by'  => $this->updated_by,
            'content'     => $this->content,
            'content_raw' => $this->content_raw,
            'ip'          => $this->ip
        ));

        DB::table('forum_posts_index')->insert(array(
            'post_id'       => $post_id,
            'content_plain' => $this->content_clean
        ));

        $this->id = (int) $post_id;
    }

    /**
     * Delete this post
     */
    public function delete()
    {
        if (!$this->id)
            return;

        DB::table('forum_posts')->where('id', '=', $this->id)->delete();
        DB::table('forum_posts_index')->where('post_id', '=', $this->id)->delete();
    }

    /**
     * Get content
     *
     * @param   bool    $raw
     * @return  string
     */
    public function get_content($raw = true)
    {
        return $raw ? $this->content_raw : $this->content;
    }

    /**
     * Set content
     *
     * @param   string  $input
     */
    public function set_content($input)
    {
        $this->content_raw = $input;

        require_once path('app').'vendor'.DS.'nbbc.php';

        $code = new \BBCode;

        $code->SetDetectURLs(true);
        $code->SetSmileyURL(URL::base().'/public/img/smileys');
        $code->RemoveRule('wiki');
        $code->RemoveRule('columns');
        $code->RemoveRule('nextcol');

        $this->content = $code->Parse(ionic_censor($input));
        $this->content_clean = strip_tags($this->content);
    }

    /**
     * Update post
     */
    public function update($auto_updated = true)
    {
        if (!$this->id)
            return;

        if ($auto_updated) {
            $this->updated_by = Auth::is_guest() ? 'Gość' : Auth::get_user()->display_name;
            $this->updated_at = date('Y-m-d H:i:s');
        }

        DB::table('forum_posts')->where('id', '=', $this->id)->update(array(
            'updated_at'  => $this->updated_at,
            'updated_by'  => $this->updated_by,
            'content'     => $this->content,
            'content_raw' => $this->content_raw
        ));

        DB::table('forum_posts_index')->where('post_id', '=', $this->id)->update(array(
            'content_plain' => $this->content_clean
        ));
    }

    /**
     * Get posts
     *
     * @param   int     $thread_id
     * @param   int     $offset
     * @param   int     $limit
     * @param   string  $order_by
     * @param   string  $order_type
     */
    public static function get_posts($thread_id, $offset = 0, $limit = 20, $order_by = 'forum_posts.id', $order_type = 'asc')
    {
        $query = DB::table('forum_posts')->left_join('users', 'forum_posts.user_id', '=', 'users.id')
                                         ->left_join('profiles', 'profiles.user_id', '=', 'users.id')
                                         ->left_join('groups', 'users.group_id', '=', 'groups.id')
                                         ->where('forum_posts.thread_id', '=', $thread_id)
                                         ->skip($offset)
                                         ->take($limit)
                                         ->order_by($order_by, $order_type);

        return $query->get(array('forum_posts.*',
                                 'users.slug as slug', 'users.display_name as display_name', 'users.email',
                                 'groups.name as group_name', 'groups.style as group_style',
                                 'profiles.posts_count', 'profiles.threads_count', 'profiles.avatar'));
    }
}
