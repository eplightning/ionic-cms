<?php
namespace Model;

use \DB;

class Warning {

    public static function refresh_count($id)
    {
        $c = (int) DB::table('warnings')->where('user_id', '=', (int) $id)->count();

        DB::table('profiles')->where('user_id', '=', (int) $id)->update(array('warnings_count' => $c));
    }

}