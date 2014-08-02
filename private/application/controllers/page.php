<?php

/**
 * Page controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Page_Controller extends Base_Controller {

    /**
     * Site map
     */
    public function action_map()
    {
        $this->page->set_title('Mapa strony');
        $this->page->breadcrumb_append('Mapa strony', 'page/map');
        $this->online('Mapa strony', 'page/map');

        $this->view = View::make('page.map', array(
                    'pages' => DB::table('pages')->take(100)->get(array('title', 'slug'))
                ));
    }

    /**
     * Newsletter sub/unsub
     *
     * @return Response
     */
    public function action_newsletter()
    {
        if (Request::method() != 'POST' or !Input::has('email'))
            return Redirect::to('index');

        $email = HTML::specialchars(Input::get('email'));

        if (strlen($email) > 70 or filter_var($email, FILTER_VALIDATE_EMAIL) === false)
        {
            $this->notice('Podany adres e-mail jest nieprawidłowy');
            return Redirect::to('index');
        }

        $record = DB::table('mailing_list')->where('email', '=', $email)->first('id');

        if ($record)
        {
            DB::table('mailing_list')->where('id', '=', $record->id)->delete();

            $this->notice('Pomyślnie wypisano z newslettera');
            return Redirect::to('index');
        }

        DB::table('mailing_list')->insert(array('email' => $email));

        $this->notice('Pomyślnie zapisano do newslettera');
        return Redirect::to('index');
    }

    /**
     * Search function
     *
     * @return Response
     */
    public function action_search()
    {
        if (Request::method() != 'POST' or !Input::has('query'))
            return Redirect::to('index');

        $query = str_replace('%', '', Input::get('query'));

        if (Str::length($query) < 4)
        {
            $this->notice('Wyszukiwana fraza musi mieć conajmniej 4 znaki');
            return Redirect::to('index');
        }

        $prefix = DB::prefix();

        $news = DB::query('SELECT title, slug, external_url
			               FROM   '.$prefix.'news
						   WHERE  is_published = 1
						   AND    MATCH(title, content) AGAINST (? IN BOOLEAN MODE)
						   LIMIT  50', array($query));

        $pages = DB::query('SELECT page.title, page.slug
			                FROM   '.$prefix.'page_content pc
							JOIN   '.$prefix.'pages page ON (page.id = pc.page_id)
							WHERE  pc.current = 1
							AND    (MATCH(pc.content) AGAINST (? IN BOOLEAN MODE) OR page.title LIKE ?)
							LIMIT  50', array($query, '%'.$query.'%'));

        $this->page->set_title('Wyszukiwarka');
        $this->page->breadcrumb_append('Wyszukiwarka', 'page/search');
        $this->online('Wyszukiwarka', 'page/search');

        $this->view = View::make('page.search', array(
                    'news'  => $news,
                    'pages' => $pages
                ));
    }

    /**
     * Display page
     *
     * @param  string   $page
     * @return Response
     */
    public function action_show($page)
    {
        $page = DB::table('pages')->join('users', 'users.id', '=', 'pages.user_id')->where('pages.slug', '=', $page)->first(array('pages.*', 'users.display_name', 'users.slug as user_slug'));

        if (!$page)
            return Response::error(404);

        $content = DB::table('page_content')->where('page_id', '=', $page->id)->where('current', '=', 1)->first('content');

        if (!$content)
            return Response::error(404);

        $this->page->breadcrumb_append($page->title, 'page/show/'.$page->slug);
        $this->online('Podstrona', 'page/show/'.$page->slug);

        if ($page->meta_title)
        {
            $this->page->set_title($page->meta_title, true);
        }
        else
        {
            $this->page->set_title($page->title);
        }

        if ($page->meta_keys)
            $this->page->set_meta('keywords', $page->meta_keys);
        if ($page->meta_description)
            $this->page->set_meta('description', $page->meta_description);

        $this->page->set_property('og:type', 'article');

        if ($page->layout != 'main')
        {
            $layout = basename($page->layout);

            if (is_file(path('app').'views'.DS.'layouts'.DS.$layout.'.twig'))
            {
                $this->layout = View::make('layouts.'.$layout);
            }
        }

        $this->view = View::make('page.show', array(
                    'sub'          => $page,
                    'page_content' => $content->content
                ));
    }

    /**
     * Switch between mobile and standard version
     */
    public function action_switch_version()
    {
        $this->page->is_mobile = !$this->page->is_mobile;
        Session::put('use_mobile_version', $this->page->is_mobile);

        return Redirect::to('index');
    }

}