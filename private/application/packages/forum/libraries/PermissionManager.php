<?php
namespace Ionic\Forum;

use ArrayAccess;
use Cache;
use DB;

/**
 * Forum permission manager
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    ionic
 * @subpackage forum
 */
class PermissionManager implements ArrayAccess {

    // Basic permissions (12 bits) 5 free
    const PERM_VIEW      = 0x1;
    const PERM_READ      = 0x2;
    const PERM_POST      = 0x4;
    const PERM_NEW_TOPIC = 0x8;
    const PERM_EDIT_POST = 0x10;
    const PERM_DEL_POST  = 0x20;
    const PERM_DEL_TOPIC = 0x40;

    // Moderation (12 bits) 4 free
    const PERM_MOD           = 0x1000;
    const PERM_MOD_VIEW_IP   = 0x2000;
    const PERM_MOD_EDIT      = 0x4000;
    const PERM_MOD_DEL_POST  = 0x8000;
    const PERM_MOD_DEL_TOPIC = 0x10000;
    const PERM_MOD_MOVE      = 0x20000;
    const PERM_MOD_MERGE     = 0x40000;
    const PERM_MOD_CLOSE     = 0x80000;

    // Free bits (8 bits) 0x1000000 to 0x80000000

    /**
     * @var array
     */
    protected $permissions = array();

    /**
     * @var int
     */
    protected $selected_board = null;

    /**
     * Constructor
     *
     * @param   int $group_id
     */
    public function __construct($group_id = 0)
    {
        $permission_data = Cache::get('forum-permissions');

        if ($permission_data === null) {
            $permission_data = $this->rebuild_cache();

            Cache::put('forum-permissions', $permission_data);
        }

        if (!empty($permission_data[1][$group_id])) {
            $this->permissions = $permission_data[1][$group_id];
        } else {
            $this->permissions = $permission_data[0];
        }
    }

    /**
     * Check permission for board
     *
     * @param   int     $board
     * @param   int     $permission
     * @return  bool
     */
    public function can($board, $permission)
    {
        // All under assumption that provided board_id is valid and our cache is not broken
        return $this->permissions[$board] & $permission;
    }

    /**
     * Offset exists
     *
     * @param   mixed   $offset
     * @return  bool
     */
    public function offsetExists($offset)
    {
        return is_int($offset);
    }

    /**
     * Check permission for board
     *
     * @param   mixed   $offset
     * @return  bool
     */
    public function offsetGet($offset)
    {
        if ($this->selected_board === null)
            return false;

        return $this->permissions[$this->selected_board] & $offset;
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
     * Rebuild permission cache
     *
     * @return array
     */
    protected function rebuild_cache()
    {
        // Group 0 = guests
        $default = array();
        $groups = array(0 => array());

        // Default permissions
        foreach (DB::table('forum_boards')->get(array('id', 'default_permissions', 'guest_permissions')) as $board) {
            $board_id = (int) $board->id;
            $default[$board_id] = (int) $board->default_permissions;
            $groups[0][$board_id] = (int) $board->guest_permissions;
        }

        // Overriden permissions
        foreach (DB::table('forum_permissions')->get(array('board_id', 'group_id', 'permissions')) as $perm) {
            $perm->group_id = (int) $perm->group_id;

            if (!isset($groups[$perm->group_id])) {
                // Copy default permissions
                $groups[$perm->group_id] = $default;
            }

            // Override
            $groups[$perm->group_id][(int) $perm->board_id] = (int) $perm->permissions;
        }

        return array($default, $groups);
    }

    /**
     * Select board
     *
     * @param   int $board
     */
    public function select_board($board)
    {
        $this->selected_board = (int) $board;
    }
}
