<?php
namespace Model;

use ArrayAccess;
use DB;

/**
 * News model
 *
 * @author  Wrexdot <wrexdot@gmail.com>
 * @package Model
 */
class News {

    /**
     * Get news with specified tag
     *
     * @param   int     $tag
     * @param   int     $limit
     * @param   string  $order_by
     * @param   string  $order_type [asc, desc]
     * @return  mixed
     */
    public static function get_with_tag($tag, $limit, $order_by = 'news.created_at', $order_type = 'desc')
    {
        return DB::table('news_tags')->left_join('news', 'news.id', '=', 'news_tags.news_id')
                                     ->left_join('users', 'users.id', '=', 'news.user_id')
                                     ->order_by($order_by, $order_type)
                                     ->take($limit)
                                     ->where(function($q) {
                                         $q->where('news.is_published', '=', 1);
                                         $q->or_where('news.publish_at', '<=', date('Y-m-d H:i:s'));
                                         $q->where('news.publish_at', '<>', '0000-00-00 00:00:00');
                                     })
                                     ->where('news_tags.tag_id', '=', $tag)
                                     ->get(array('news.*', 'users.display_name', 'users.slug as user_slug'));
    }

    /**
     * Get news
     *
     * @param   int     $limit
     * @param   array   $exclude
     * @param   string  $order_by
     * @param   string  $order_type [asc, desc]
     * @return  mixed
     */
    public static function get($limit, array $exclude = array(), $order_by = 'news.created_at', $order_type = 'desc')
    {
        $query = DB::table('news')->left_join('users', 'users.id', '=', 'news.user_id')
                                  ->order_by($order_by, $order_type)
                                  ->take($limit)
                                  ->where(function($q) {
                                      $q->where('news.is_published', '=', 1);
                                      $q->or_where('news.publish_at', '<=', date('Y-m-d H:i:s'));
                                      $q->where('news.publish_at', '<>', '0000-00-00 00:00:00');
                                  });

        if (!empty($exclude))
            $query->where_not_in('news.id', $exclude);

        return $query->get(array('news.*', 'users.display_name', 'users.slug as user_slug'));
    }

}

/**
 * Dynamic tag access
 *
 * @author  Wrexdot <wrexdot@gmail.com>
 * @package Model
 */
class NewsTagAccess implements ArrayAccess {

    /**
     * @var array
     */
    public $news_ids = array();

    /**
     * @var array
     */
    protected $results = null;

    /**
     * Serialize support
     *
     * @return  array
     */
    public function __sleep()
    {
        return array('news_ids');
    }

    /**
     * Unserialize support
     */
    public function __wakeup()
    {
        $this->results = null;
    }

    /**
     * Offset exists
     *
     * @param   mixed   $offset
     * @return  bool
     */
    public function offsetExists($offset)
    {
        return $this->results !== null and isset($this->results[(int) $offset]);
    }

    /**
     * Get tags for specified news
     *
     * @param   mixed   $offset
     * @return  bool
     */
    public function offsetGet($offset)
    {
        // lazy loading
        if ($this->results === null and !empty($this->news_ids)) {
            $this->results = array();

            foreach (DB::table('news_tags')->join('tags', 'tags.id', '=', 'news_tags.tag_id')
                                           ->where_in('news_id', $this->news_ids)
                                           ->get(array('tags.title', 'tags.id', 'tags.slug', 'news_tags.news_id')) as $t)
            {
                $this->results[(int) $t->news_id][] = $t;
            }
        }

        // force int
        $offset = (int) $offset;

        return isset($this->results[$offset]) ? $this->results[$offset] : null;
    }

    /**
     * Unimplemented
     *
     * @param   mixed   $offset
     * @param   mixed   $value
     */
    public function offsetSet($offset, $value)
    {
        return;
    }

    /**
     * Unimplemented
     *
     * @param   mixed   $offset
     */
    public function offsetUnset($offset)
    {
        return;
    }

}
