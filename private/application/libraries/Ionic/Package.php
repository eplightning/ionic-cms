<?php
namespace Ionic;

/**
 * Ionic package
 *
 * Events:
 * string      ionic.controller_path(string controller_name)
 * array       ionic.widget_name(void)
 * array       ionic.submitted_content_list(void)
 * string|bool ionic.submitted_content_publish(Submitted_Content obj)
 * array       ionic.template_directories(void)
 * array       ionic.comment_add(string content_type, int content_id)
 * array       ionic.comment_list(void)
 * bool        ionic.comment_delete(string content_type, int content_id)
 * bool        ionic.thumbnail_allowed(string type)
 * array       ionic.reports_list(void)
 * int|bool    ionic.karma_add(int content_id, string type) <- type == 'up' or type == 'down'
 * array       ionic.karma_valid(string type)
 * array       ionic.notification_add(string type, User user, array parameters)
 * array       ionic.pagemap_links(string format)
 * Ionic\Cal.. ionic.calendar_handler(void)
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
abstract class Package {

    protected static $packages = array();

    /**
     * Check if the package can be installed
     *
     * @return bool
     */
    public function can_install()
    {
        return true;
    }

    /**
     * Check if the package can be uninstalled
     *
     * @return bool
     */
    public function can_uninstall()
    {
        return true;
    }

    /**
     * Check if the package can be upgraded
     *
     * @param  string $version
     * @return bool
     */
    public function can_upgrade($version)
    {
        return version_compare($version, $this->get_version(), '<');
    }

    /**
     * Called when package state is changed (enabled/disabled) by user
     *
     * @param bool $state
     * @param  \Ionic\Package\API $api
     */
    public function change_state($state, \Ionic\Package\API $api)
    {

    }

    /**
     * Get package author
     *
     * @return string
     */
    abstract public function get_author();

    /**
     * Get package description
     *
     * @return string
     */
    abstract public function get_description();

    /**
     * Get package name
     *
     * @return string
     */
    abstract public function get_name();

    /**
     * Get package version
     *
     * @return string
     */
    abstract public function get_version();

    /**
     * (optional) Init package
     */
    public function init_package()
    {

    }

    /**
     * Install package
     *
     * @param  \Ionic\Package\API $api
     * @return bool
     */
    public function install_package(\Ionic\Package\API $api)
    {
        return true;
    }

    /**
     * Load packages
     */
    public static function load()
    {
        if (\Cache::has('packages'))
        {
            $packages = \Cache::get('packages');
        }
        else
        {
            $packages = array();

            foreach (\DB::table('packages')->where('is_disabled', '=', 0)->where('id', '<>', 'core')->get('id') as $pkg)
            {
                $packages[] = $pkg->id;
            }

            \Cache::put('packages', $packages);
        }

        foreach ($packages as $pkg)
        {
            if (!is_file(path('app').'packages/'.$pkg.'.php'))
                continue;

            require_once path('app').'packages/'.$pkg.'.php';

            $pkg_class = '\\'.\Str::classify($pkg).'_Package';

            if (!class_exists($pkg_class))
                continue;

            self::$packages[$pkg] = new $pkg_class;

            if (self::$packages[$pkg] instanceof Package)
                self::$packages[$pkg]->init_package();
        }
    }

    /**
     * Uninstall package
     *
     * @param  \Ionic\Package\API $api
     * @return bool
     */
    public function uninstall_package(\Ionic\Package\API $api)
    {
        return true;
    }

    /**
     * Upgrade package
     *
     * @param  \Ionic\Package\API $api
     * @param  string             $version
     * @return bool
     */
    public function upgrade_package(\Ionic\Package\API $api, $version)
    {
        return true;
    }

}
