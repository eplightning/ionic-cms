<?php
namespace Model;

use \DB;

/**
 * Config model
 *
 * @author  Wrexdot <wrexdot@gmail.com>
 * @package Model
 */
class Config {

    /**
     * Retrieve config
     *
     * @return array
     */
    public static function retrieve()
    {
        $config = array();

        foreach (DB::table('config')->join('config_categories', 'config_categories.id', '=', 'config.category_id')
                ->get(array('config_categories.key', 'config.value', 'config.php_type', 'config.php_key')) as $row)
        {
            if ($row->php_type == 'int')
            {
                $config[$row->key][$row->php_key] = (int) $row->value;
            }
            elseif ($row->php_type == 'float')
            {
                $config[$row->key][$row->php_key] = (float) $row->value;
            }
            elseif ($row->php_type == 'bool')
            {
                $config[$row->key][$row->php_key] = ($row->value == '1' ? true : false);
            }
            else
            {
                $config[$row->key][$row->php_key] = $row->value;
            }
        }

        return $config;
    }

    /**
     * Get categories
     *
     * @return array
     */
    public static function retrieve_categories()
    {
        return DB::table('config_categories')
                        ->left_join('config', 'config.category_id', '=', 'config_categories.id')
                        ->group_by('config.category_id')
                        ->having('aggr', '>', 0)
                        ->get(array('config_categories.*', DB::raw('COUNT('.DB::prefix().'config.id) AS aggr')));
    }

    /**
     * Retrieve items
     */
    public static function retrieve_items($id)
    {
        $items = array();

        foreach (DB::table('config')->where('category_id', '=', (int) $id)->get('*') as $i)
        {
            if (!isset($items[$i->section]))
                $items[$i->section] = array();

            $items[$i->section][] = array('item' => $i, 'html' => '');
        }

        return $items;
    }

    /**
     * Update using list
     *
     * @param array $list
     */
    public static function update($list)
    {
        foreach ($list as $id => $value)
        {
            DB::table('config')->where('id', '=', (int) $id)->update(array('value' => (string) $value));
        }
    }

}