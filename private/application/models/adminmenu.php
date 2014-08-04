<?php
namespace Model;

use \DB;

/**
 * Admin menu model
 *
 * @author  Wrexdot <wrexdot@gmail.com>
 * @package Model
 */
class AdminMenu {

    /**
     * Get admin menu structure
     *
     * @return array
     */
    public static function retrieve()
    {
        $menu = array();

        foreach (DB::table('admin_menu')->where('is_hidden', '=', 0)->order_by('sorting', 'asc')->get(array('title', 'category', 'module', 'role')) as $m)
        {
            if (!isset($menu[$m->category]))
                $menu[$m->category] = array();

            $menu[$m->category][] = array(
                'title'  => $m->title,
                'module' => $m->module,
                'role'   => $m->role,
                'url'    => \URL::base().'/admin/'.$m->module.'/index'
            );
        }

        return $menu;
    }

}