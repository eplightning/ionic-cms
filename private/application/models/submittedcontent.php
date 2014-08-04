<?php
namespace Model;

use \DB;

/**
 * Logs model
 *
 * @author  Wrexdot <wrexdot@gmail.com>
 * @package Model
 */
class SubmittedContent {

    /**
     * Retrieve logs
     *
     * @param array $data
     * @param int $limit
     */
    public static function retrieve($data = array(), $limit = 10)
    {
        if (empty($data))
            $data = array('submitted_content.*', 'users.display_name');

        return DB::table('submitted_content')->order_by('submitted_content.id', 'desc')->join('users', 'users.id', '=', 'submitted_content.user_id')->take($limit)->get($data);
    }

}