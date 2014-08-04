<?php

/**
 * Template management
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Templates_Controller extends Admin_Controller {

    protected function _list()
    {
        $directories = array(
            'layouts' => 'Layout',
            'news' => 'Newsy',
            'blog' => 'Blog',
            'comments' => 'Komentarze',
            'conversations' => 'Prywatne dyskusje',
            'files' => 'Pliki',
            'friends' => 'Znajomi',
            'gallery' => 'Galeria',
            'login' => 'Logowanie',
            'page' => 'Strony',
            'poll' => 'Sondy',
            'register' => 'Rejestracja',
            'shoutbox' => 'Shoutbox',
            'submit' => 'Zaproponuj materiał',
            'users' => 'Użytkownicy',
            'video' => 'Video',
            'widgets' => 'Widok widżetu'
        );

        foreach (\Event::fire('ionic.template_directories') as $r)
        {
            if (is_array($r))
            {
                $directories = array_merge($directories, $r);
            }
        }

		$list = array(
			1 => array(
				'id' => 1,
				'name' => 'Zablokowany adres IP <small>(banned_ip.twig)</small>',
				'writable' => false,
				'filename' => 'banned_ip.twig'
			),
			2 => array(
				'id' => 2,
				'name' => 'Splash <small>(splash.twig)</small>',
				'writable' => false,
				'filename' => 'splash.twig'
			),
			3 => array(
				'id' => 3,
				'name' => 'Potwierdzenie akcji <small>(confirm.twig)</small>',
				'writable' => false,
				'filename' => 'confirm.twig'
			),
		);

        $i = 4;

        foreach ($directories as $dir => $name)
        {
            foreach (new \FilesystemIterator(path('app').'views/'.$dir, \FilesystemIterator::SKIP_DOTS) as $f)
            {
                if ($f->isFile())
                {
                    if (pathinfo($f->getPathname(), PATHINFO_EXTENSION) == 'twig')
                    {
                        $list[$i] = array(
                            'id'       => $i,
                            'name'     => $name.' <small>('.$dir.'/'.$f->getFilename().')</small>',
                            'writable' => false,
                            'filename' => $dir.'/'.$f->getFilename()
                        );

                        $i++;
                    }
                }
            }
        }

        return $list;
    }

    /**
     * Edit template
     *
     * @param string $id
     */
    public function action_edit($id)
    {
        // Check permissions
        if (!Auth::can('admin_templates'))
            return Response::error(403);

        // Check request
        if (!ctype_digit($id))
            return Response::error(500);
        $id = (int) $id;

        // List of editable templates
        $list = $this->_list();

        // Check if exists
        if (!isset($list[$id]))
            return Response::error(404);

        // Submit form
        if (!Request::forged() and Request::method() == 'POST')
        {
            if (!is_writable(path('app').'views'.DS.$list[$id]['filename']))
            {
                return Redirect::to('admin/templates/edit/'.$id);
            }

            file_put_contents(path('app').'views'.DS.$list[$id]['filename'], Input::get('code', ''));

            $this->notice('Zmiany pomyślnie zapisane');
            $this->log('Edytował szablon: '.$list[$id]['filename']);

            return Redirect::to('admin/templates/edit/'.$id);
        }

        // Breadcrumb and title
        $this->page->breadcrumb_append('Szablony', 'admin/templates/index');
        $this->page->breadcrumb_append('Edycja szablonu', 'admin/templates/edit'.$id);
        $this->page->set_title('Edycja szablonu');

        // Assets
        Asset::add('codemirror', 'public/css/codemirror.css');
        Asset::add('codemirror', 'public/js/codemirror.js');
        Asset::add('codemirror_twig', 'public/js/codemirror_twig.js', 'codemirror');

        $this->view = View::make('admin.templates.edit', array(
                    'writable' => is_writable(path('app').'views'.DS.$list[$id]['filename']),
                    'item'     => $list[$id],
                    'content'  => file_get_contents(path('app').'views'.DS.$list[$id]['filename'])
                ));
    }

    /**
     * Index action
     *
     * @return Response
     */
    public function action_index()
    {
        // Check permissions
        if (!Auth::can('admin_templates'))
            return Response::error(403);

        // Breadcrumb and title
        $this->page->breadcrumb_append('Szablony', 'admin/templates/index');
        $this->page->set_title('Szablony');

        // List of editable templates
        $list = $this->_list();

        // Check writable
        foreach ($list as $l)
        {
            if (is_writable(path('app').'views'.DS.$l['filename']))
            {
                $list[$l['id']]['writable'] = true;
            }
        }

        $this->view = View::make('admin.templates.index', array('list' => $list));
    }

}