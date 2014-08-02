<?php
namespace Model;

use \DB;

/**
 * Group model
 *
 * @author  Wrexdot <wrexdot@gmail.com>
 * @package Model
 */
class Group {

    /**
     * Get group roles
     *
     * @param  int $id
     * @return array
     */
    public static function roles($id)
    {
        $roles = array();

        foreach (DB::table('permissions')->join('roles', 'roles.id', '=', 'permissions.role_id')->where('permissions.group_id', '=', (int) $id)
                ->get(array('roles.name')) as $r)
        {
            $roles[$r->name] = true;
        }

        return $roles;
    }

    /**
     * Return groups with specified role
     *
     * @param string $role
     * @param bool   $or_root
     */
    public static function with_role($role, $or_root = true)
    {
        $groups = array();

        $query = DB::table('permissions')->where('roles.name', '=', $role)->join('roles', 'roles.id', '=', 'permissions.role_id');

        if ($or_root and $role != 'admin_root')
        {
            $query->or_where('roles.name', '=', 'admin_root');
        }

        foreach ($query->distinct()->get('permissions.group_id') as $g)
        {
            $groups[] = $g->group_id;
        }

        return $groups;
    }

}