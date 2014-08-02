<?php

/**
 * Ionic core module
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class Core_Package extends \Ionic\Package {

    /**
     * Check if the package can be installed
     *
     * @return bool
     */
    public function can_install()
    {
        return false;
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
        return 'Podstawowy moduł systemu.';
    }

    /**
     * Get package name
     *
     * @return string
     */
    public function get_name()
    {
        return 'IonicCMS';
    }

    /**
     * Get package version
     *
     * @return string
     */
    public function get_version()
    {
        return '1.2';
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
        // 1.1 -> 1.2
        if ($version == '1.1')
        {
            $api->execute_queries(array(
                "UPDATE ".DB::prefix()."config
                 SET    description = 'Lista słów ,których użycie w komentarzach lub prywatnych wiadomościach spowoduje zamiane na gwiazdki. Jedno na linię.'
                 WHERE  php_key = 'censorship'
                 AND    name = 'Zablokowane słowa'",

                "ALTER TABLE ".DB::prefix()."admin_menu
                 ADD is_hidden tinyint(3) unsigned NOT NULL DEFAULT '0'",

                "ALTER TABLE ".DB::prefix()."groups
                 ADD style varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''",

                "ALTER TABLE ".DB::prefix()."pages
                 ADD layout varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'main'",
            ), true);

            $api->add_config(12, 'Licznik odsłon newsów', 'Włączyć licznik odsłon newsów (dodatkowe zapytanie SQL)?', 'Optymalizacje', '0', 'yesno', '', 'bool', 'news_counter');
            $api->add_config(12, 'Użyj dużego obrazka', 'Używać dużego obrazka w tagach Open Graph? Dotyczy newsów.', 'Open Graph', '0', 'yesno', '', 'bool', 'og_bigimage');
            $api->add_config(12, 'Dodaj tytuł strony', 'Czy dodawać tytuł strony w tagach Open Graph?', 'Open Graph', '0', 'yesno', '', 'bool', 'og_fulltitle');
            $api->add_config(12, 'Domyślny obrazek', 'Adres relatywny do głównego katalogu strony.', 'Open Graph', 'opengraph.png', 'text', '', 'string', 'og_defaultimage');

            $version = '1.2';
        }

        $api->update_package('core', $version);
        return true;
    }
}