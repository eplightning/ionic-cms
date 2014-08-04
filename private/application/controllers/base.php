<?php

/**
 * Base controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
abstract class Base_Controller extends Controller {

    /**
     * @var string
     */
    public $layout = 'layouts.main';

    /**
     * @var array
     */
    public $main_news = null;

    /**
     * @var Ionic\Notifications
     */
    public $notifications = null;

    /**
     * @var Ionic\Page
     */
    public $page = null;

    /**
     * @var stdClass
     */
    public $user = null;

    /**
     * @var Ionic\View
     */
    public $view = null;

    /**
     * This function is called after the action is executed.
     *
     * @param  Response  $response
     * @return void
     */
    public function after($response)
    {
        // Nothing to do here gentlemen
        if (!$this->layout or !$this->view)
        {
            return;
        }

        // Force mobile layout
        if ($this->page->is_mobile)
        {
            $response->content = View::make('layout');
        }

        // Breadcrumbs
        $this->page->breadcrumb_prepend('Strona główna', 'index');

        // JS and CSS
        $response->content->with('styles', Asset::styles());
        $response->content->with('scripts', Asset::scripts());

        // Menu
        if (Cache::has('menu'))
        {
            $menu = Cache::get('menu');
        }
        else
        {
            $menu = Ionic\Tree::build_tree('menu', 0, Config::get('limits.menu', 1));

            Cache::put('menu', $menu);
        }

        // Main news
        if (Config::get('homepage.main_news', false))
        {
            if ($this->main_news === null)
            {
                $this->main_news = DB::table('news_tags')->left_join('news', 'news.id', '=', 'news_tags.news_id')
                        ->left_join('users', 'users.id', '=', 'news.user_id')
                        ->order_by('news.created_at', 'desc')
                        ->take(Config::get('limits.main_news', 0))
                        ->where(function($q) {
                                    $q->where('news.is_published', '=', 1);
                                    $q->or_where('news.publish_at', '<=', date('Y-m-d H:i:s'));
                                    $q->where('news.publish_at', '<>', '0000-00-00 00:00:00');
                                })
                        ->where('news_tags.tag_id', '=', 1)
                        ->get(array('news.*',
                    'users.display_name', 'users.slug as user_slug'
                        ));
            }

            $response->content->with('main_news', $this->main_news);
        }

        // Cookie policy
        if (Config::get('advanced.cookie_policy', true))
        {
            if (Cookie::get('cookie_accept', null) == 'cookie_accept')
            {
                $response->content->with('cookie_policy', false);
            }
            else
            {
                $response->content->with('cookie_policy', true);
            }
        }
        else
        {
            $response->content->with('cookie_policy', false);
        }

        // Add menu stuff to layout
        $response->content->with('menu', $menu);

        // Notice
        $response->content->with('notice', Session::get('notice', ''));

        // Notifications
        $response->content->with('notifications', $this->notifications->get_list());

        // Content
        $response->content->with('content', $this->view instanceof View ? $this->view->render() : (string) $this->view);
    }

    /**
     * This function is called before the action is executed.
     *
     * @return void
     */
    public function before()
    {
        // Session
        if (!Request::cli())
            Session::load();

        // Init user system
        Auth::init();
        $this->user = Auth::get_user();

        // Page container setup
        $this->page = IoC::resolve('page');
        $this->notifications = IoC::resolve('notifications');

        View::share('page', $this->page);
        View::share('current_user', $this->user);
        View::share('online', IoC::resolve('online'));

        // Mobile
        if (Session::has('use_mobile_version'))
        {
            $this->page->is_mobile = (bool) Session::get('use_mobile_version', false);
        }
        else
        {
            $this->page->is_mobile = Config::get('advanced.mobile_version', false) ? $this->page->autodetect_mobile() : false;
            Session::put('use_mobile_version', $this->page->is_mobile);
        }

        // Set META stuff
        $this->page->set_title(null);
        $this->page->set_meta('description', Config::get('meta.description'));
        $this->page->set_meta('keywords', Config::get('meta.keywords'));
        $this->page->set_meta('csrf-token', Session::token());

        // Open graph
        $this->page->set_property('og:title', 'Strona');
        $this->page->set_property('og:type', 'website');
        $this->page->set_property('og:image', URL::base().'/'.Config::get('advanced.og_defaultimage', 'opengraph.png'));
        $this->page->set_property('og:url', URL::current());
        $this->page->set_property('og:site_name', $this->page->title);
        $this->page->set_property('og:description', Config::get('meta.description'));
        $this->page->set_property('og:locale', 'pl_PL');

        // Javascript
        Asset::add('jquery', 'public/js/jquery.min.js');

        if (!$this->page->is_mobile)
        {
            Asset::add('ionic', 'public/js/ionic.js', 'jquery');
        }
        else
        {
            Asset::add('mobile', 'public/js/mobile.js', 'jquery');
        }

        if (Config::get('advanced.cookie_policy', true))
        {
            Asset::add('jquery.cookie', 'public/js/jquery.cookie.js', 'jquery');
        }
    }

    /**
     * Confirmation window
     *
     * @return int
     */
    protected function confirm()
    {
        // If form was submitted
        if (Input::has('yes') and !Request::forged())
        {
            return 1;
        }
        elseif (Input::has('no') and !Request::forged())
        {
            return 2;
        }

        // Or not
        $this->view = View::make('confirm', array('current' => URI::current()));
        return 0;
    }

    /**
     * Set notice
     *
     * @param string $notice
     */
    protected function notice($notice)
    {
        Session::flash('notice', $notice);
    }

    /**
     * Set online presence
     *
     * @param string $name
     * @param string $location
     */
    protected function online($name, $location = null)
    {
        Session::location($name, $location);
    }

    /**
     * Permission check
     *
     * @param  string $role
     * @param  bool   $set_notice
     * @return bool
     */
    protected function require_auth($role = '', $set_notice = true)
    {
        if (Auth::is_guest())
        {
            if ($set_notice)
                $this->notice('Dostęp do tej strony mają wyłącznie zalogowani użytkownicy.');
            return true;
        }

        if ($role)
        {
            $access = false;

            foreach ((array) $role as $r)
            {
                if (Auth::can($r))
                {
                    $access = true;
                    break;
                }
            }

            if (!$access)
            {
                if ($set_notice)
                    $this->notice('Nie masz wystarczających uprawnień, aby wyświetlić tę stronę.');
                return true;
            }
        }

        return false;
    }

}