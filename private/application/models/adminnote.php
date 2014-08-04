<?php
namespace Model;

use \DB;

/**
 * Admin note model
 *
 * @author  Wrexdot <wrexdot@gmail.com>
 * @package Model
 */
class AdminNote {

    /**
     * Add new note
     *
     * @param string $title
     * @param string $message
     * @param int    $user_id
     */
    public static function add($title, $message, $user_id)
    {
        DB::table('admin_notes')->insert(array('title'      => $title, 'note'       => $message, 'user_id'    => (int) $user_id, 'created_at' => date('Y-m-d H:i:s')));
    }

    /**
     * Delete note
     *
     * @param int $id
     */
    public static function delete($id)
    {
        DB::table('admin_notes')->delete((int) $id);
    }

    /**
     * Edit note
     *
     * @param  int         $id
     * @param  string      $title
     * @param  string      $content
     * @return string|bool
     */
    public static function edit($id, $title = null, $content = null)
    {
        // Find note
        $object = DB::table('admin_notes')->where('id', '=', $id)->first('title');

        if (!$object)
        {
            return false;
        }

        // Compile data to update
        $data = array();

        if ($title)
            $data['title'] = $title;
        if ($content)
            $data['note'] = $content;
        if (empty($data))
            return false;

        // Update
        DB::table('admin_notes')->where('id', '=', $id)->update($data);

        // Return title
        return $object->title;
    }

    /**
     * Find note
     *
     * @param int $id
     */
    public static function find($id, $data = array())
    {
        if (empty($data))
            $data = '*';

        return DB::table('admin_notes')->where('id', '=', (int) $id)->first($data);
    }

    /**
     * Get all
     *
     * @return array
     */
    public static function retrieve()
    {
        return DB::table('admin_notes')->order_by('admin_notes.id', 'desc')->join('users', 'users.id', '=', 'admin_notes.user_id')
                        ->get(array('users.display_name', 'admin_notes.*'));
    }

}