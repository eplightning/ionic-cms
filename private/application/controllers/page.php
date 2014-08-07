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
     * Contact
     *
     * @return Response
     */
    public function action_contact()
    {
        $target_email = Config::get('email.contact_email', '');

        if (!$target_email)
        {
            $this->notice('Formularz kontaktowy jest wyłączony');
            return Redirect::to('index');
        }

        $this->page->set_title('Formularz kontaktowy');
        $this->page->breadcrumb_append('Formularz kontaktowy', 'page/contact');
        $this->online('Formularz kontaktowy', 'page/contact');

        if (Request::method() == 'POST' and !Request::forged() and Input::has('message') and Input::has('email') and Input::has('subject'))
        {
            $message = Input::get('message');
            $subject = strip_tags(Input::get('subject'));
            $email = HTML::specialchars(Input::get('email'));

            if (strlen($email) > 70 or filter_var($email, FILTER_VALIDATE_EMAIL) === false)
            {
                $this->notice('Podany adres e-mail jest nieprawidłowy');
                return Redirect::to('page/contact');
            }

            $mailer = IoC::resolve('mailer');

            $msg = \Swift_Message::newInstance();
            $msg->setFrom(array(Config::get('email.from') => Config::get('email.from_name')));
            $msg->setTo($target_email);
            $msg->setReplyTo($email);
            $msg->setSubject($subject);
            $msg->setBody($message, 'text/plain');

            $mailer->send($msg);

            $this->notice('Wiadomość e-mail została wysłana pomyślnie');
            return Redirect::to('page/contact');
        }

        $this->view = View::make('page.contact');
    }

    /**
     * Site map
     *
     * @return Response
     */
    public function action_map($format = 'html')
    {
        if (Cache::has('pagemap-links'))
        {
            $links = Cache::get('pagemap-links');
        }
        else
        {
            $links = array('Główne' => array(), 'Dla zalogowanych' => array(), 'Podstrony' => array(), 'Newsy' => array());

            $links['Główne']['Strona główna'] = 'news';
            $links['Główne']['Archiwum newsów'] = 'news/archive';
            $links['Główne']['RSS'] = 'news/rss';
            $links['Główne']['Blogi użytkowników'] = 'blog';
            $links['Dla zalogowanych']['Prywatne dyskusje'] = 'conversations';
            $links['Główne']['Repozytorium plików'] = 'files';
            $links['Dla zalogowanych']['Lista znajomych'] = 'friends';
            $links['Główne']['Galeria'] = 'gallery';
            $links['Główne']['Logowanie'] = 'login';
            $links['Główne']['Formularz przywracania zapomnianego hasła'] = 'login/password';
            $links['Główne']['Archiwum sond'] = 'poll';
            $links['Dla zalogowanych']['Zaproponuj news'] = 'submit/news';
            $links['Główne']['Archiwum shoutbox\'a'] = 'shoutbox';
            $links['Główne']['Lista użytkowników'] = 'users/list';
            $links['Główne']['Użytkownicy online'] = 'users/online';
            $links['Główne']['Biblioteka video'] = 'video';
            $links['Główne']['Formularz kontaktowy'] = 'page/contact';

            foreach (DB::table('news')->order_by('id', 'desc')->take(100)->get(array('title', 'slug')) as $p)
            {
                $links['Newsy'][$p->title] = 'news/show/'.$p->slug;
            }

            foreach (DB::table('pages')->order_by('id', 'desc')->take(100)->get(array('title', 'slug')) as $p)
            {
                $links['Podstrony'][$p->title] = 'page/show/'.$p->slug;
            }

            foreach (\Event::fire('ionic.pagemap_links', array($format)) as $r)
            {
                if (is_array($r))
                {
                    $links = array_merge($links, $r);
                }
            }

            Cache::put('pagemap-links', $links);
        }

        if ($format == 'sitemap.xml' || $format == 'xml')
        {
            $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">
</urlset>");

            $base = URL::base();

            foreach ($links as $links2)
            {
                foreach ($links2 as $title => $url)
                {
                    $elem = $xml->addChild('url');

                    $elem->addChild('loc', $base.'/'.$url);
                }
            }

            return Response::make($xml->asXML(), 200, array(
                'Content-type' => 'text/xml; charset=UTF-8'
            ));
        }
        else
        {
            $this->page->set_title('Mapa strony');
            $this->page->breadcrumb_append('Mapa strony', 'page/map');
            $this->online('Mapa strony', 'page/map');

            $this->view = View::make('page.map', array(
                'base_url' => URL::base(),
                'links' => $links
            ));
        }
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