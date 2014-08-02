<?php
namespace Ionic;

use \DB;
use \Config;

/**
 * Page
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class Page {

    public $breadcrumb = array();
    public $http_equiv = array();
    public $footer_js = array();
    public $is_mobile = false;
    public $meta = array();
    public $properties = array();
    public $section_title = '';
    public $title = '';

    /**
     * Add JS to the footer
     *
     * @param string $js
     */
    public function add_footer_js($js)
    {
        $this->footer_js[] = $js;
    }

    /**
     * Mobile autodetection
     */
    public function autodetect_mobile()
    {
        $detection = new \Mobile_Detect;

        $this->is_mobile = $detection->isMobile();
    }

    /**
     * Append to breadcrumb
     *
     * @param string $title
     * @param string $link
     */
    public function breadcrumb_append($title, $link = null)
    {
        $this->breadcrumb[] = array('title' => $title, 'link'  => $link ? \URL::to($link) : null);
    }

    /**
     * Prepend to breadcrumb
     *
     * @param string $title
     * @param string $link
     */
    public function breadcrumb_prepend($title, $link = null)
    {
        array_unshift($this->breadcrumb, array('title' => $title, 'link'  => $link ? \URL::to($link) : null));
    }

    /**
     * Comments
     *
     * @param int    $content_id
     * @param string $content_type
     */
    public function make_comments($content_id, $content_type)
    {
        $per_page = (int) Config::get('limits.comments', 20);
        
        if (\Auth::can('mod_comments') or \Auth::can('admin_comments'))
        {
            $moderation = true;

            $count = DB::table('comments')->where('content_id', '=', $content_id)->where('content_type', '=', $content_type)->count();

            $comments = DB::table('comments')->where('content_id', '=', $content_id)
                    ->where('content_type', '=', $content_type)
                    ->left_join('users', 'users.id', '=', 'comments.user_id')
                    ->left_join('profiles', 'profiles.user_id', '=', 'users.id')
                    ->take($per_page)->order_by('comments.id', \Config::get('advanced.comment_sort'))
                    ->get(array(
                'comments.id', 'comments.user_id', 'comments.comment', 'comments.created_at', 'comments.ip', 'comments.karma', 'comments.guest_name', 'comments.is_hidden', 'comments.is_reported',
                'users.display_name', 'profiles.comments_count', 'profiles.news_count', 'profiles.avatar', 'users.slug', 'users.email'
                    ));
        }
        else
        {
            $moderation = false;

            $count = DB::table('comments')->where('content_id', '=', $content_id)->where('content_type', '=', $content_type)->where('is_hidden', '=', 0)->count();

            $comments = DB::table('comments')->where('content_id', '=', $content_id)
                    ->where('content_type', '=', $content_type)
                    ->where('is_hidden', '=', 0)
                    ->left_join('users', 'users.id', '=', 'comments.user_id')
                    ->left_join('profiles', 'profiles.user_id', '=', 'users.id')
                    ->take($per_page)->order_by('comments.id', \Config::get('advanced.comment_sort'))
                    ->get(array(
                'comments.id', 'comments.user_id', 'comments.comment', 'comments.created_at', 'comments.ip', 'comments.karma', 'comments.guest_name', 'comments.is_hidden', 'comments.is_reported',
                'users.display_name', 'profiles.comments_count', 'profiles.news_count', 'profiles.avatar', 'users.slug', 'users.email'
                    ));
        }

        $used_karma = array();
        $last_id = null;

        if (Auth::is_guest())
        {
            if (!Config::get('guests.karma', false))
            {
                foreach ($comments as $c)
                {
                    $used_karma[] = $c->id;
                    $last_id = $c->id;
                }
            }
            else
            {
                $ids = array();

                foreach ($comments as $c)
                {
                    $ids[] = $c->id;
                    $last_id = $c->id;
                }

                if (!empty($ids))
                {
                    foreach (DB::table('karma_comments')->where_in('comment_id', $ids)->where('ip', '=', \Request::ip())->take($per_page)->get('comment_id') as $id)
                    {
                        $used_karma[] = $id->comment_id;
                    }
                }
            }
        }
        else
        {
            $ids = array();

            $user_id = Auth::get_user()->id;

            foreach ($comments as $c)
            {
                if ($c->user_id == $user_id)
                {
                    $used_karma[] = $c->id;
                }
                else
                {
                    $ids[] = $c->id;
                }

                $last_id = $c->id;
            }

            if (!empty($ids))
            {
                foreach (DB::table('karma_comments')->where_in('comment_id', $ids)->where('ip', '=', \Request::ip())
                        ->or_where('user_id', '=', $user_id)->where_in('comment_id', $ids)->take($per_page)->get('comment_id') as $id)
                {
                    $used_karma[] = $id->comment_id;
                }
            }
        }

        require_once path('app').'vendor'.DS.'recaptchalib.php';

        $view = View::make('comments.display');

        return $view->with('comments', $comments)
                        ->with('count', $count)
                        ->with('used_karma', $used_karma)
                        ->with('content_id', $content_id)
                        ->with('content_type', $content_type)
                        ->with('last_id', $last_id)
                        ->with('per_page', $per_page)
                        ->with('moderation', $moderation)
                        ->with('action', \URL::to('comments/add/'.$content_type.'/'.$content_id))
                        ->with('can_post', ((\Auth::is_logged() and !\Auth::banned()) or (\Auth::is_guest() and \Config::get('guests.comments', false))))
                        ->with('recaptcha', recaptcha_get_html(Config::get('advanced.recaptcha_public', '')));
    }

    /**
     * Set page HTTP-Equiv
     *
     * @param string $name
     * @param string $value
     */
    public function set_http_equiv($name, $value)
    {
        $this->http_equiv[$name] = $value;
    }

    /**
     * Set page META
     *
     * @param string $name
     * @param string $value
     */
    public function set_meta($name, $value)
    {
        $this->meta[$name] = $value;
    }

    /**
     * Set META property
     *
     * @param string $name
     * @param string $value
     */
    public function set_property($name, $value)
    {
        $this->properties[$name] = $value;
    }

    /**
     * Set page title
     *
     * @param string $section
     * @param bool   $ignore_global
     */
    public function set_title($section, $ignore_global = false)
    {
        if (is_null($section))
        {
            $section = trim(sprintf(\Config::get('meta.title'), ''), ' -');
            $ignore_global = true;
        }

        $this->section_title = $section;
        $this->title = $ignore_global ? $section : sprintf(\Config::get('meta.title'), $section);

        if (\Config::get('advanced.og_fulltitle', false))
        {
            $this->properties['og:title'] = $this->title;
        }
        else
        {
            $this->properties['og:title'] = $section;
        }
    }

}