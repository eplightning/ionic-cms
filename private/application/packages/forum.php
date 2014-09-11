<?php
use Ionic\Package;
use Ionic\Package\API;

/**
 * Ionic forum module
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class Forum_Package extends Package {

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
        return false;
    }

    /**
     * Called when package state is changed (enabled/disabled) by user
     *
     * @param bool $state
     * @param  \Ionic\Package\API $api
     */
    public function change_state($state, API $api)
    {

    }

    /**
     * Get package author
     *
     * @return string
     */
    public function get_author()
    {
        return 'Wrexdot';
    }

    /**
     * Get package description
     *
     * @return string
     */
    public function get_description()
    {
        return 'Prosty moduł forum w pełni zintegrowany z systemem.';
    }

    /**
     * Get package name
     *
     * @return string
     */
    public function get_name()
    {
        return 'Forum';
    }

    /**
     * Get package version
     *
     * @return string
     */
    public function get_version()
    {
        return '1.3';
    }

    /**
     * Init package
     */
    public function init_package()
    {
        // Widget names
        Event::listen('ionic.widget_name', function() {
            return array(
                'posts' => 'Ostatnie posty'
            );
        });

        // Template directories
        Event::listen('ionic.template_directories', function() {
            return array(
                'forum' => 'Forum'
            );
        });

        // Controller path
        Event::listen('ionic.controller_path', function($controller) {
            if (is_file($path = path('app').'packages/forum/controllers/'.$controller.EXT)) {
                return $path;
            }
        });

        // Pagemap
        Event::listen('ionic.pagemap_links', function($format) {
            $links = array('Forum' => array());

            $links['Forum']['Strona główna forum'] = 'forum';

            return $links;
        });

        $root = path('app').'packages'.DS.'forum'.DS;

        // Class mappings
        Autoloader::map(array(
            'Model\\Forum\\Board'             => $root.'models'.DS.'Board.php',
            'Model\\Forum\\Post'              => $root.'models'.DS.'Post.php',
            'Model\\Forum\\Thread'            => $root.'models'.DS.'Thread.php',
            'Ionic\\Forum\\MarkerManager'     => $root.'libraries'.DS.'MarkerManager.php',
            'Ionic\\Forum\\PermissionManager' => $root.'libraries'.DS.'PermissionManager.php',
            'Ionic\\Widget\\Posts'            => $root.'libraries'.DS.'Ionic'.DS.'Widget'.DS.'Posts.php',
        ));
    }

    /**
     * Install package
     *
     * @param  \Ionic\Package\API $api
     * @return bool
     */
    public function install_package(API $api)
    {
        $prefix = DB::prefix();

        $api->execute_queries(array(
            // Boards
            "CREATE TABLE IF NOT EXISTS `".$prefix."forum_boards` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `left` int(10) unsigned NOT NULL DEFAULT '0',
              `right` int(10) unsigned NOT NULL DEFAULT '0',
              `depth` int(10) unsigned NOT NULL DEFAULT '0',
              `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
              `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
              `description` text COLLATE utf8_unicode_ci NOT NULL,
              `external_link` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
              `default_permissions` int(10) unsigned NOT NULL DEFAULT '0',
              `guest_permissions` int(10) unsigned NOT NULL DEFAULT '0',
              `posts_count` int(10) unsigned NOT NULL DEFAULT '0',
              `threads_count` int(10) unsigned NOT NULL DEFAULT '0',
              `last_id` int(10) unsigned NOT NULL DEFAULT '0',
              `last_title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
              `last_slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
              `last_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
              `last_user_id` int(10) unsigned NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`),
              UNIQUE KEY `slug` (`slug`),
              KEY `last_user_id` (`last_user_id`),
              KEY `left` (`left`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1",

            // Threads
            "CREATE TABLE IF NOT EXISTS `".$prefix."forum_threads` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `board_id` int(10) unsigned NOT NULL,
              `user_id` int(10) unsigned DEFAULT NULL,
              `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
              `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
              `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
              `is_closed` tinyint(3) NOT NULL DEFAULT '0',
              `is_sticky` tinyint(3) NOT NULL DEFAULT '0',
              `posts_count` int(10) unsigned NOT NULL DEFAULT '0',
              `views` int(10) unsigned NOT NULL DEFAULT '0',
              `last_id` int(10) unsigned NOT NULL DEFAULT '0',
              `last_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
              `last_user_id` int(10) unsigned NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`),
              UNIQUE KEY `slug` (`slug`),
              KEY `board_id` (`board_id`),
              KEY `user_id` (`user_id`),
              KEY `last_date` (`last_date`),
              KEY `last_user_id` (`last_user_id`),
              KEY `is_sticky` (`is_sticky`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1",
            "ALTER TABLE `".$prefix."forum_threads`
             ADD FOREIGN KEY (`board_id`) REFERENCES `".$prefix."forum_boards`(`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            "ALTER TABLE `".$prefix."forum_threads`
             ADD FOREIGN KEY (`user_id`) REFERENCES `".$prefix."users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE",

            // Posts
            "CREATE TABLE IF NOT EXISTS `".$prefix."forum_posts` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `thread_id` int(10) unsigned NOT NULL,
              `user_id` int(10) unsigned DEFAULT NULL,
              `is_op` tinyint(3) NOT NULL DEFAULT '0',
              `is_reported` tinyint(3) NOT NULL DEFAULT '0',
              `content` mediumtext COLLATE utf8_unicode_ci NOT NULL,
              `content_raw` mediumtext COLLATE utf8_unicode_ci NOT NULL,
              `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
              `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
              `updated_by` varchar(20) NOT NULL DEFAULT '',
              `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
              PRIMARY KEY (`id`),
              KEY `thread_id` (`thread_id`),
              KEY `user_id` (`user_id`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1",
            "ALTER TABLE `".$prefix."forum_posts`
             ADD FOREIGN KEY (`thread_id`) REFERENCES `".$prefix."forum_threads`(`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            "ALTER TABLE `".$prefix."forum_posts`
             ADD FOREIGN KEY (`user_id`) REFERENCES `".$prefix."users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE",

            // Posts Search Index
            "CREATE TABLE IF NOT EXISTS `".$prefix."forum_posts_index` (
              `post_id` int(10) unsigned NOT NULL,
              `content_plain` mediumtext COLLATE utf8_unicode_ci NOT NULL,
              PRIMARY KEY (`post_id`),
              FULLTEXT KEY `content_plain` (`content_plain`)
             ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",

            // Markers
            "CREATE TABLE IF NOT EXISTS `".$prefix."forum_markers` (
              `thread_id` int(10) unsigned NOT NULL,
              `user_id` int(10) unsigned NOT NULL,
              `time` int(10) unsigned NOT NULL DEFAULT '0',
              PRIMARY KEY (`thread_id`, `user_id`),
              KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",
            "ALTER TABLE `".$prefix."forum_markers`
             ADD FOREIGN KEY (`thread_id`) REFERENCES `".$prefix."forum_threads`(`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            "ALTER TABLE `".$prefix."forum_markers`
             ADD FOREIGN KEY (`user_id`) REFERENCES `".$prefix."users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE",

            // Permissions
            "CREATE TABLE IF NOT EXISTS `".$prefix."forum_permissions` (
               `board_id` int(10) unsigned NOT NULL,
               `group_id` int(10) unsigned NOT NULL,
               `permissions` int(10) unsigned NOT NULL DEFAULT '0',
               PRIMARY KEY (`board_id`,`group_id`),
               KEY `group_id` (`group_id`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",
            "ALTER TABLE `".$prefix."forum_permissions`
             ADD FOREIGN KEY (`board_id`) REFERENCES `".$prefix."forum_boards`(`id`) ON DELETE CASCADE ON UPDATE CASCADE",
            "ALTER TABLE `".$prefix."forum_permissions`
             ADD FOREIGN KEY (`group_id`) REFERENCES `".$prefix."groups`(`id`) ON DELETE CASCADE ON UPDATE CASCADE",

            // Profile fields
            "ALTER TABLE `".$prefix."profiles`
             ADD `posts_count` int(10) unsigned NOT NULL DEFAULT '0'",
            "ALTER TABLE `".$prefix."profiles`
             ADD `threads_count` int(10) unsigned NOT NULL DEFAULT '0'",
        ), true);

        // TODO: Config
        // TODO: Admin menu
        // TODO: Roles

        return true;
    }

    /**
     * Uninstall package
     *
     * @param  \Ionic\Package\API $api
     * @return bool
     */
    public function uninstall_package(API $api)
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
    public function upgrade_package(API $api, $version)
    {
        $api->update_package('forum', $this->get_version());

        return true;
    }

}
