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
        
        Cache::forget_multiple('*');
        
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
        if (Config::get('cache.driver') == 'file')
            $id = basename($id);
        
        if (!Cache::has($id))
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
        
        $list = Cache::list_all();
        
        foreach ($list as $k => $v)
        {
            switch ($v['cache_name'])
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
                    $name = $v['cache_name'];
            }

            $list[$k]['name'] = $name;
        }
        
        $this->view = View::make('admin.cache.index', array(
            'list' => $list
        ));
    }
    
}