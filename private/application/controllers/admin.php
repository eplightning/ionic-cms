<?php

/**
 * Administration base class
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Controller extends Controller {

    /**
     * @var string
     */
    public $layout = 'admin.layout';

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
     * Logout
     */
    public function action_logout()
    {
        // Logout from admin and redirect to login page
        Auth::logout(true);

        return Redirect::to('admin');
    }

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

        // Breadcrumbs
        $this->page->breadcrumb_prepend('Panel administracyjny', 'admin/dashboard/index');
        $this->page->breadcrumb_prepend('Strona główna', 'index');

        // Select skin
        if (Cookie::has('ionic-admin-skin'))
        {
            if (Cookie::get('ionic-admin-skin') == 'admin_flat.css')
            {
                $response->content->with('admin_skin', 'admin_flat.css');
            }
            else
            {
                $response->content->with('admin_skin', 'admin.css');
            }
        }

        // JS and CSS
        $response->content->with('styles', Asset::styles());
        $response->content->with('scripts', Asset::scripts());

        // Menu
        if (!Cache::has('admin-menu'))
        {
            $menu = Model\AdminMenu::retrieve();

            Cache::put('admin-menu', $menu);
        }
        else
        {
            $menu = Cache::get('admin-menu');
        }

        // Determine active admin module and remove unaccessible ones
        $active_index = null;
        $active_module = null;

        $url = explode('/', URI::current(), 3);
        if (!isset($url[1]))
            $url[1] = 'dashboard';

        foreach ($menu as $k => $v)
        {
            foreach ($v as $k2 => $v2)
            {
                // Permission check
                if ($v2['role'] and !Auth::can($v2['role']))
                {
                    unset($menu[$k][$k2]);
                }

                // Are we here?
                if (!$active_module and $url[1] == $v2['module'])
                {
                    $active_index = $k;
                    $active_module = $v2['module'];
                }
            }
        }

        // Add menu stuff to layout
        $response->content->with('active_module', $active_module);
        $response->content->with('active_index', $active_index);
        $response->content->with('menu', $menu);

        // Notice
        $response->content->with('notice', Session::get('notice', ''));

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
        // Page container setup
        $this->page = IoC::resolve('page');
        Config::set('meta.title', '%s - Panel administracyjny');
        View::share('page', $this->page);
        View::share('current_user', Auth::get_user());

        // Default title
        $this->page->set_title('Panel administracyjny', true);

        // CSRF token
        $this->page->set_meta('csrf-token', Session::token());

        // Javascript
        Asset::add('jquery', 'public/js/jquery.min.js');
        Asset::add('jquery-ui', 'public/js/jquery-ui.custom.min.js', 'jquery');
        Asset::add('admin', 'public/js/admin.js', 'jquery');
        Asset::add('cookie', 'public/js/jquery.cookie.js', 'jquery');

        // Styles
        Asset::add('jquery-ui', 'public/css/flick/jquery-ui.custom.css');

        // User
        $this->user = Auth::get_user();
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
        $this->view = View::make('admin.confirm', array('current' => URI::current()));
        return 0;
    }

    /**
     * Execute a controller method with the given parameters.
     *
     * @param  string    $method
     * @param  array     $parameters
     * @return Response
     */
    public function execute($method, $parameters = array())
    {
        $filters = $this->filters('before', $method);

        // Again, as was the case with route closures, if the controller "before"
        // filters return a response, it will be considered the response to the
        // request and the controller method will not be used.
        $response = Filter::run($filters, array(), true);

        // Session
        if (!Request::cli())
            Session::load();

        // User system
        Auth::init_admin();

        // Force login form?
        if (Auth::is_guest() and is_null($response))
        {
            if (Input::except(array('alusername', 'alpassword', Session::csrf_token, 'alsubmit')))
            {
                Input::flash('except', array('alusername', 'alpassword', Session::csrf_token, 'alsubmit'));
            }
            elseif (Session::get(Input::old_input, array()))
            {
                Session::keep(Input::old_input);
            }

            if (Input::has('alusername') and Input::has('alpassword') and Request::method() == 'POST')
            {
                if (Request::forged())
                {
                    return Response::error('500');
                }

                $status = Auth::login_admin(Input::get('alusername'), Input::get('alpassword'));

                switch ($status)
                {
                    case Auth::LOGIN_SUCCESS:
                        if (URI::current() == 'admin')
                        {
                            return Redirect::to('admin/dashboard/index');
                        }
                        else
                        {
                            return Redirect::to(URI::current());
                        }
                        break;

                    case Auth::LOGIN_BRUTEFORCE:
                        $response = View::make('admin.login', array('message' => 'Liczba nieudanych prób logowania przekroczyła limit. Musisz odczekać 15 minut.', 'url'     => URI::current()));
                        break;

                    case Auth::LOGIN_NOT_ADMIN:
                        $response = View::make('admin.login', array('message' => 'To konto nie posiada wystarczających uprawnień do zarządzania stroną.', 'url'     => URI::current()));
                        break;

                    default:
                        $response = View::make('admin.login', array('message' => 'Nieprawidłowa nazwa użytkownika lub hasło.', 'url'     => URI::current()));
                }
            }
            else
            {
                $response = View::make('admin.login', array('message' => '', 'url'     => URI::current()));
            }
        }
        elseif (is_null($response))
        {
            if (URI::current() == 'admin' or URI::current() == 'admin/index')
            {
                return Redirect::to('admin/dashboard/index');
            }

            $this->before();

            $response = $this->response($method, $parameters);
        }

        $response = Response::prepare($response);

        // The "after" function on the controller is simply a convenient hook
        // so the developer can work on the response before it's returned to
        // the browser. This is useful for templating, etc.
        $this->after($response);

        Filter::run($this->filters('after', $method), array($response));

        return $response;
    }

    /**
     * Log admin action
     *
     * @param string $title
     */
    protected function log($title)
    {
        Model\Log::add($title, $this->user->id);
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

}