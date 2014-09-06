<?php
namespace Ionic\Forum;

use ArrayAccess;
use Auth;
use Config;
use DB;
use Session;

/**
 * Forum markers manager
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    ionic
 * @subpackage forum
 */
class MarkerManager implements ArrayAccess {

    /**
     * @var int
     */
    protected $expiration = null;

    /**
     * @var array
     */
    public $markers = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->expiration = Config::get('forum.marker_expire', 7) * 86400;

        if (rand(1, Config::get('forum.marker_gc', 10)) == 1)
            $this->garbage_collector();

        // TODO: Cookie markers for guests?
        if (Auth::is_guest())
            return;

        foreach (DB::table('forum_markers')->where('time', '>', time() - $this->expiration)
                                           ->where('user_id', '=', Auth::get_user()->id)
                                           ->get(array('thread_id')) as $marker) {
            $this->markers[] = (int) $marker->thread_id;
        }
    }

    /**
     * Comma seperated marked threads IDs
     *
     * @return  string
     */
    public function comma_seperated()
    {
        return implode(',', $this->markers);
    }

    /**
     * Garbage collection
     */
    protected function garbage_collector()
    {
        DB::table('forum_markers')->where('time', '<=', time() - $this->expiration)->delete();
    }

    /**
     * Makes sure that thread is not already marked
     *
     * @param   int $thread
     * @param   int $age
     */
    public function mark($thread, $age)
    {
        $thread = (int) $thread;

        // TODO: Cookie markers for guests?
        if (Auth::is_guest())
            return;

        // Too old or marked
        if ($age <= time() - $this->expiration or $this->marked($thread))
            return;

        DB::table('forum_markers')->insert(array(
            'thread_id' => $thread,
            'user_id'   => Auth::get_user()->id,
            'time'      => $age
        ));

        $this->markers[] = $thread;
    }

    /**
     * Is marked?
     *
     * @param   int     $thread
     * @return  bool
     */
    public function marked($thread)
    {
        return in_array($thread, $this->markers);
    }

    /**
     * Offset exists
     *
     * @param   mixed   $offset
     * @return  bool
     */
    public function offsetExists($offset)
    {
        return true;
    }

    /**
     * Check if thread is marked
     *
     * @param   mixed   $offset
     * @return  bool
     */
    public function offsetGet($offset)
    {
        return in_array($offset, $this->markers);
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

    /**
     * Remove mark
     *
     * @param   int $thread
     */
    public function unmark($thread)
    {
        DB::delete('forum_markers')->where('thread_id', '=', $thread)->delete();
    }
}
