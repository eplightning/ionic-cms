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
            return;

        // Force mobile layout
        if ($this->page->is_mobile)
            $response->content = View::make('layout');

        // Breadcrumbs
        $this->page->breadcrumb_prepend('Strona główna', 'index');

        // Menu
        $menu = Cache::get('menu');

        if ($menu === null)
        {
            $menu = Ionic\Tree::build_tree('menu', 0, Config::get('limits.menu', 1));

            Cache::put('menu', $menu);
        }

        // Load main news if needed
        if (Config::get('homepage.main_news', false) and $this->main_news === null)
            $this->main_news = Model\News::get_with_tag(1, Config::get('limits.main_news', 0));

        // Variables
        $response->content->with(array(
            // CSS, JS
            'styles'        => Asset::styles(),
            'scripts'       => Asset::scripts(),
            // Additional stuff
            'menu'          => $menu,
            'main_news'     => $this->main_news,
            'notice'        => Session::get('notice', ''),
            'notifications' => $this->notifications->get_list(),
            'cookie_policy' => Config::get('advanced.cookie_policy', true) and Cookie::get('cookie_accept', null) != 'cookie_accept',
            // Content
            'content'       => $this->view instanceof View ? $this->view->render() : (string) $this->view
        ));
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

        if (Session::has('use_mobile_version'))
        {
            $this->page->is_mobile = (bool) Session::get('use_mobile_version', false);
        }
        else
        {
            $this->page->is_mobile = Config::get('advanced.mobile_version', false) ? $this->page->autodetect_mobile() : false;
            Session::put('use_mobile_version', $this->page->is_mobile);
        }

        $this->page->set_title(null);
        $this->page->set_meta('description', Config::get('meta.description'));
        $this->page->set_meta('keywords', Config::get('meta.keywords'));
        $this->page->set_meta('csrf-token', Session::token());

        $this->page->set_property('og:title', 'Strona');
        $this->page->set_property('og:type', 'website');
        $this->page->set_property('og:image', URL::base().'/'.Config::get('advanced.og_defaultimage', 'opengraph.png'));
        $this->page->set_property('og:url', URL::current());
        $this->page->set_property('og:site_name', $this->page->title);
        $this->page->set_property('og:description', Config::get('meta.description'));
        $this->page->set_property('og:locale', 'pl_PL');

        // Notifications subsystem
        $this->notifications = IoC::resolve('notifications');

        // Global view variables
        View::share('page', $this->page);
        View::share('current_user', $this->user);
        View::share('online', IoC::resolve('online'));

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
            Asset::add('jquery.cookie', 'public/js/jquery.cookie.js', 'jquery');
    }

    /**
     * Confirmation window
     *
     * @return int
     */
    protected function confirm()
    {
        // Confirmation received
        if (!Request::forged())
        {
            if (Input::has('yes'))
            {
                return 1;
            }
            elseif (Input::has('no'))
            {
                return 2;
            }
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

        if ($role and !Auth::can('admin_root'))
        {
            foreach ((array) $role as $r)
            {
                if (Auth::can($r))
                    return false;
            }

            if ($set_notice)
                $this->notice('Nie masz wystarczających uprawnień, aby wyświetlić tę stronę.');

            return true;
        }

        return false;
    }

}
