<?php
namespace Model;

use \DB;
use \Auth;
use \Request;
use \Config as Configuration;

class Karma {

    public static function can_karma($content_id, $content_type)
    {
        $user = Auth::get_user();

        if (!$user)
        {
            if (!Configuration::get('guests.karma', false))
            {
                return false;
            }

            if (DB::table('karma')->where('content_id', '=', $content_id)->where('content_type', '=', $content_type)->where('ip', '=', Request::ip())->first('ip'))
            {
                return false;
            }

            return true;
        }

        if (DB::table('karma')->where('content_id', '=', $content_id)
                        ->where('content_type', '=', $content_type)
                        ->where(function($q) use ($user) {
                                    $q->where('ip', '=', Request::ip());
                                    $q->or_where('user_id', '=', $user->id);
                                })
                        ->first('ip'))
        {
            return false;
        }

        return true;
    }

}