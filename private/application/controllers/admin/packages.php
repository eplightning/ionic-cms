<?php

/**
 * Package management
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Packages_Controller extends Admin_Controller {

    /**
     * Toggle state
     *
     * @param string $id
     */
    public function action_enable($id)
    {
        // Check permissions
        if (!Auth::can('admin_root'))
            return Response::error(403);

        // Retrieve package info
        $pkg = DB::table('packages')->where('id', '=', $id)->first(array('is_disabled', 'id'));

        if (!$pkg)
        {
            return Response::error(404);
        }

        // Package object
        $pkg_obj = $this->get_package($id);

        if (!$pkg_obj)
        {
            return Response::error(404);
        }

        // Confirmation
        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/packages/index');
        }

        // Call
        $pkg_obj->change_state($pkg->is_disabled ? true : false, new \Ionic\Package\API);

        // Update
        DB::table('packages')->where('id', '=', $pkg->id)->update(array(
            'is_disabled' => $pkg->is_disabled ? 0 : 1
        ));

        Cache::forget('packages');
        Cache::forget('admin-menu');

        $this->notice('Zmieniono status paczki: '.$pkg->id);
        $this->log(sprintf('Zmieniono status paczki: %s', $pkg->id));
        return Redirect::to('admin/packages/index');
    }

    /**
     * Install
     *
     * @param string $id
     */
    public function action_install($id)
    {
        // Check permissions
        if (!Auth::can('admin_root'))
            return Response::error(403);

        // Retrieve package info
        $pkg = DB::table('packages')->where('id', '=', $id)->first(array('version', 'id'));

        if (!$pkg)
        {
            return Response::error(404);
        }

        // Package object
        $pkg_obj = $this->get_package($id);

        if (!$pkg_obj)
        {
            return Response::error(404);
        }

        // Can we
        if (!$pkg_obj->can_install())
        {
            return Response::error(404);
        }

        // Confirmation
        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/packages/index');
        }

        // Call
        $state = $pkg_obj->install_package(new \Ionic\Package\API);

        if (!$state)
        {
            $this->notice('Wystąpił błąd podczas instalacji paczki');
            return Redirect::to('admin/packages/index');
        }

        Cache::forget('packages');
        Cache::forget('admin-menu');

        $this->notice('Zainstalowano paczkę: '.$pkg->id);
        $this->log(sprintf('Zainstalowano paczkę: %s', $pkg->id));
        return Redirect::to('admin/packages/index');
    }

    /**
     * Uninstall
     *
     * @param string $id
     */
    public function action_uninstall($id)
    {
        // Check permissions
        if (!Auth::can('admin_root'))
            return Response::error(403);

        // Retrieve package info
        $pkg = DB::table('packages')->where('id', '=', $id)->first(array('version', 'id'));

        if (!$pkg)
        {
            return Response::error(404);
        }

        // Package object
        $pkg_obj = $this->get_package($id);

        if (!$pkg_obj)
        {
            return Response::error(404);
        }

        // Can we
        if (!$pkg_obj->can_uninstall())
        {
            return Response::error(404);
        }

        // Confirmation
        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/packages/index');
        }

        // Call
        $state = $pkg_obj->uninstall_package(new \Ionic\Package\API);

        if (!$state)
        {
            $this->notice('Wystąpił błąd podczas deinstalacji paczki');
            return Redirect::to('admin/packages/index');
        }

        Cache::forget('packages');
        Cache::forget('admin-menu');

        $this->notice('Odinstalowano paczkę: '.$pkg->id);
        $this->log(sprintf('Odinstalowano paczkę: %s', $pkg->id));
        return Redirect::to('admin/packages/index');
    }

    /**
     * Upgrade
     *
     * @param string $id
     */
    public function action_upgrade($id)
    {
        // Check permissions
        if (!Auth::can('admin_root'))
            return Response::error(403);

        // Retrieve package info
        $pkg = DB::table('packages')->where('id', '=', $id)->first(array('version', 'id'));

        if (!$pkg)
        {
            return Response::error(404);
        }

        // Package object
        $pkg_obj = $this->get_package($id);

        if (!$pkg_obj)
        {
            return Response::error(404);
        }

        // Upgradeable?
        if (!$pkg_obj->can_upgrade($pkg->version))
        {
            return Response::error(404);
        }

        // Confirmation
        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/packages/index');
        }

        // Call
        $state = $pkg_obj->upgrade_package(new \Ionic\Package\API, $pkg->version);

        if (!$state)
        {
            $this->notice('Wystąpił błąd podczas aktualizacji paczki');
            return Redirect::to('admin/packages/index');
        }

        Cache::forget('packages');
        Cache::forget('admin-menu');

        $this->notice('Zaaktualizowano paczkę: '.$pkg->id);
        $this->log(sprintf('Zaaktualizowano paczkę: %s', $pkg->id));
        return Redirect::to('admin/packages/index');
    }

    /**
     * Listing
     */
    public function action_index()
    {
        // Check permissions
        if (!Auth::can('admin_root'))
            return Response::error(403);

        // Installed packages
        $installed_packages = array();

        foreach (DB::table('packages')->order_by('id', 'asc')->get('*') as $pkg)
        {
            $installed_packages[$pkg->id] = $pkg;
        }

        // Compile list
        $list = array();

        foreach (glob(path('app').'packages'.DS.'*.php') as $f)
        {
            require_once $f;

            $f = strtolower(basename($f, '.php'));

            $pkg_class = '\\'.Str::classify($f).'_Package';

            if (!class_exists($pkg_class))
                continue;

            $pkg_class = new $pkg_class;

            $list[$f] = array(
                'name' => $pkg_class->get_name(),
                'description' => $pkg_class->get_description(),
                'author' => $pkg_class->get_author(),
                'is_installed' => isset($installed_packages[$f]),
                'is_enabled' => isset($installed_packages[$f]) and $installed_packages[$f]->is_disabled == 0,
                'installed_version' => isset($installed_packages[$f]) ? $installed_packages[$f]->version : 'n/a',
                'version' => $pkg_class->get_version(),
                'actions' => array()
            );

            if ($f == 'core')
            {
                if ($pkg_class->can_upgrade($installed_packages[$f]->version))
                {
                    $list[$f]['actions']['Aktualizuj'] = 'admin/packages/upgrade/'.$f;
                }

                continue;
            }

            // Installed
            if (isset($installed_packages[$f]))
            {
                if ($pkg_class->can_upgrade($installed_packages[$f]->version))
                {
                    $list[$f]['actions']['Aktualizuj'] = 'admin/packages/upgrade/'.$f;
                }

                if ($pkg_class->can_uninstall())
                {
                    $list[$f]['actions']['Odinstaluj'] = 'admin/packages/uninstall/'.$f;
                }

                if ($installed_packages[$f]->is_disabled)
                {
                    $list[$f]['actions']['Włącz'] = 'admin/packages/enable/'.$f;
                }
                else
                {
                    $list[$f]['actions']['Wyłącz'] = 'admin/packages/enable/'.$f;
                }
            }
            else
            {
                if ($pkg_class->can_install())
                {
                    $list[$f]['actions']['Instaluj'] = 'admin/packages/install/'.$f;
                }
            }
        }

        $this->page->breadcrumb_append('Moduły systemu', 'admin/packages/index');
        $this->page->set_title('Moduły systemu');

        $this->view = View::make('admin.packages.index', array('list' => $list));
    }

    /**
     * Get package object
     *
     * @param  string         $id
     * @return \Ionic\Package
     */
    protected function get_package($id)
    {
        if (!is_file(path('app').'packages'.DS.$id.'.php'))
        {
            return false;
        }

        require_once path('app').'packages'.DS.$id.'.php';

        $pkg_class = '\\'.Str::classify($id).'_Package';

        if (!class_exists($pkg_class))
            return false;

        return new $pkg_class;
    }
}