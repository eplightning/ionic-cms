<?php

/**
 * Cache management
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Cache_Controller extends Admin_Controller {
    
    /**
     * Clear cache
     */
    public function action_clear()
    {
        if (!Auth::can('admin_cache'))
            return Response::error(403);
        
        if (!($status = $this->confirm()))
        {
            return;
        } //!($status = $this->confirm())
        elseif ($status == 2)
        {
            return Redirect::to('admin/cache/index');
        } //$status == 2
        
        foreach (new FilesystemIterator(path('storage') . 'cache', FilesystemIterator::SKIP_DOTS) as $f)
        {
            // Ignore directories and other non-file things
            if ($f->isFile())
            {
                @unlink($f->getPathname());
            } //$f->isFile()
        } //new FilesystemIterator(path('storage') . 'cache', FilesystemIterator::SKIP_DOTS) as $f
        
        $this->log('Wyczyścił cache');
        $this->notice('Cache usunięte pomyślnie');
        
        return Redirect::to('admin/cache/index');
    }
    
    /**
     * Delete action
     *
     * @param  string   $id
     * @return Response
     */
    public function action_delete($id)
    {
        if (!Auth::can('admin_cache'))
            return Response::error(403);
        
        // Directory traversal fix
        $id = basename($id);
        
        if (!is_file(path('storage') . 'cache' . DS . $id))
            return Response::error(404);
        
        if (!($status = $this->confirm()))
        {
            return;
        } //!($status = $this->confirm())
        elseif ($status == 2)
        {
            return Redirect::to('admin/cache/index');
        } //$status == 2

        Cache::forget($id);

        $this->log('Usunął cache: ' . HTML::specialchars($id));
        $this->notice('Cache usunięte pomyślnie');

        return Redirect::to('admin/cache/index');
    }
    
    /**
     * Index action
     *
     * @return Response
     */
    public function action_index()
    {
        if (!Auth::can('admin_cache'))
            return Response::error(403);
        
        $this->page->breadcrumb_append('Cache', 'admin/cache/index');
        $this->page->set_title('Cache');
        
        $list = array();
        
        foreach (new FilesystemIterator(path('storage') . 'cache', FilesystemIterator::SKIP_DOTS) as $f)
        {
            // Ignore directories and other non-file things
            if ($f->isFile())
            {
                // Get expiration from file
                $expire = (int) substr(file_get_contents($f->getPathname()), 0, 10);
                
                switch ($f->getFilename())
                {
                    case 'admin-menu':
                        $name = 'Menu administracji';
                        break;
                    
                    case 'db-config':
                        $name = 'Konfiguracja';
                        break;
                    
                    case 'widgets':
                        $name = 'Widżety';
                        break;
                    
                    case 'current-season':
                        $name = 'Aktualny sezon';
                        break;
                    
                    default:
                        $name = $f->getFilename();
                } //$f->getFilename()
                
                $list[] = array(
                    'name' => $name,
                    'cache_name' => $f->getFilename(),
                    'expiration' => $expire
                );
            } //$f->isFile()
        } //new FilesystemIterator(path('storage') . 'cache', FilesystemIterator::SKIP_DOTS) as $f
        
        $this->view = View::make('admin.cache.index', array(
            'list' => $list
        ));
    }
    
}