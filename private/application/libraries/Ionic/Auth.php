<?php
namespace Ionic;

use \Model;
use \Session;
use \Cookie;
use \Request;
use \DB;

/**
 * Auth
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class Auth {

    const LOGIN_SUCCESS = 1;
    const LOGIN_NOT_FOUND = 2;
    const LOGIN_INVALID_PASSWORD = 3;
    const LOGIN_BRUTEFORCE = 4;
    const LOGIN_NOT_ADMIN = 5;

    protected static $anonymous = false;
    protected static $roles = array();
    protected static $user = null;

    public static function banned()
    {
        if (!static::$user)
            return false;

        return (static::$user->is_banned == 1 or static::$user->warnings_count >= \Config::get('bans.warnings', 5));
    }

    public static function can($role, $ignore_root = false)
    {
        if (!$ignore_root and isset(static::$roles['admin_root']))
            return true;

        return isset(static::$roles[$role]);
    }

    public static function get_roles()
    {
        return static::$roles;
    }

    public static function get_user()
    {
        return static::$user;
    }

    public static function init()
    {
        // Session
        if (Session::has('user_id') and Session::has('user_roles'))
        {
            static::$user = Model\User::retrieve(Session::get('user_id'));

            if (!static::$user)
            {
                Session::instance()->userdata();
                return;
            }

            static::$roles = Session::get('user_roles');
            static::$anonymous = Session::get('user_anon', false);

            Session::instance()->userdata(static::$user->id, static::$anonymous);
        }
        elseif (Cookie::has('ionic_id') and Cookie::has('ionic_hash') and ctype_digit(Cookie::get('ionic_id')))
        {
            $user = Model\User::retrieve((int) Cookie::get('ionic_id'));

            if ($user and Cookie::get('ionic_hash') == $user->password)
            {
                static::$user = $user;
                static::$roles = Model\Group::roles($user->group_id);
                static::$anonymous = (Cookie::get('ionic_anon', '0') == '1' ? true : false);

                Session::put('user_id', $user->id);
                Session::put('user_roles', static::$roles);
                Session::put('user_anon', static::$anonymous);

                Session::instance()->userdata(static::$user->id, static::$anonymous);
            }
            else
            {
                Session::instance()->userdata();
            }
        }
        else
        {
            Session::instance()->userdata();
        }
    }

    public static function init_admin()
    {
        if (Session::has('admin_id') and Session::has('admin_roles'))
        {
            static::$user = Model\User::retrieve(Session::get('admin_id'));

            if (!static::$user)
            {
                Session::instance()->userdata();
                Session::instance()->location('');
                return;
            }

            static::$roles = Session::get('admin_roles');
            static::$anonymous = Session::get('user_anon', false);

            Session::instance()->userdata(static::$user->id, static::$anonymous);
            Session::instance()->location('Panel administracyjny', 'admin');
        }
        else
        {
            Session::instance()->userdata();
            Session::instance()->location('');
        }
    }

    public static function is_guest()
    {
        return (static::$user == null);
    }

    public static function is_logged()
    {
        return (static::$user != null);
    }

    public static function login($username, $password, $remember = true, $anon = false)
    {
        // Bruteforce
        $attempts = Model\User::get_attempts(Request::ip());

        if ($attempts >= 10)
        {
            return static::LOGIN_BRUTEFORCE;
        }

        // Get user and check password
        $username = Model\User::retrieve_by_username($username);

        if (!$username)
        {
            Model\User::add_attempt(Request::ip());
            return static::LOGIN_NOT_FOUND;
        }

        if (!\Hash::check($password, $username->password))
        {
            Model\User::add_attempt(Request::ip());
            return static::LOGIN_INVALID_PASSWORD;
        }

        // Get roles and check'em
        $roles = Model\Group::roles($username->group_id);

        // Success
        static::$user = $username;
        static::$roles = $roles;
        static::$anonymous = $anon;

        Session::put('user_id', $username->id);
        Session::put('user_roles', $roles);
        Session::put('user_anon', static::$anonymous);

        // Cookies
        if ($remember)
        {
            Cookie::put('ionic_id', $username->id, 10080);
            Cookie::put('ionic_hash', $username->password, 10080);
            Cookie::put('ionic_anon', static::$anonymous ? '1' : '0', 10080);
        }

        Session::instance()->userdata(static::$user->id, static::$anonymous);
        Session::instance()->location('Logowanie', 'login');
        Model\User::reset_attempts(Request::ip());

        return static::LOGIN_SUCCESS;
    }

    public static function login_admin($username, $password)
    {
        // Bruteforce
        $attempts = Model\User::get_attempts(Request::ip());

        if ($attempts >= 10)
        {
            return static::LOGIN_BRUTEFORCE;
        }

        // Get user and check password
        $username = Model\User::retrieve_by_username($username);

        if (!$username)
        {
            Model\User::add_attempt(Request::ip());
            return static::LOGIN_NOT_FOUND;
        }

        if (!\Hash::check($password, $username->password))
        {
            Model\User::add_attempt(Request::ip());
            return static::LOGIN_INVALID_PASSWORD;
        }

        // Get roles and check'em
        $roles = Model\Group::roles($username->group_id);

        if (!isset($roles['admin_access']) and !isset($roles['admin_root']))
        {
            return static::LOGIN_NOT_ADMIN;
        }

        // Success
        static::$user = $username;
        static::$roles = $roles;
        static::$anonymous = Session::get('user_anon', false);

        Session::put('admin_id', $username->id);
        Session::put('admin_roles', $roles);

        Session::instance()->userdata(static::$user->id, static::$anonymous);
        Session::instance()->location('Panel administracyjny', 'admin');
        Model\User::reset_attempts(Request::ip());

        return static::LOGIN_SUCCESS;
    }

    public static function logout($only_admin = false)
    {
        // Session data
        Session::instance()->userdata();
        Session::instance()->location('');

        // User data
        static::$user = null;
        static::$roles = array();
        static::$anonymous = false;

        // Only admin?
        if ($only_admin)
        {
            Session::forget('admin_id');
            Session::forget('admin_roles');
            return;
        }

        Session::flush();

        // Cookies
        Cookie::forget('ionic_id');
        Cookie::forget('ionic_hash');
        Cookie::forget('ionic_anon');
    }

}