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
        return '1.3';
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

        // nieoficjalny build
        if ($version == '1.1.1')
            $version = '1.2';

        // 1.2 -> 1.2.1
        if ($version == '1.2')
        {
            $api->add_config(12, 'Czas ważności miniaturek', 'Czas ważności miniaturek prezentowany w nagłówku HTTP Expires (nie wpływa na działanie generatora) w sekundach.', 'Optymalizacje', '86400', 'text', 'numeric', 'int', 'thumbnail_expires');
            $api->add_config(12, 'Polityka cookie', 'Czy generować komunikat o polityce cookie?', 'Optymalizacje', '1', 'yesno', '', 'bool', 'cookie_policy');

            $version = '1.2.1';
        }

        // 1.2.1 -> 1.3
        if ($version == '1.2.1')
        {
            $api->add_config(2, 'E-mail kontaktowy', 'Na ten adres e-mail będą wysyłane wiadomości napisane przez formularz kontaktowy.', 'Ogólne', 'example@gmail.com', 'text', '', 'string', 'contact_email');
            $api->add_config(12, 'Dynamiczne akcje w panelu', 'Wyłącz, aby przywrócić okienko potwierdzenia operacji z poprzednich wersji.', 'Panel administracyjny', '1', 'yesno', '', 'bool', 'admin_prefer_ajax');

            $api->add_admin_menu('Kalendarz', 'Treść', 'calendar', 'admin_calendar', 27);

            $api->add_role('admin_calendar', 'Typer', 'Może zarządzać kalendarzem');
            $api->add_role('admin_calendar_add', 'Typer', 'Może dodawać do kalendarza');
            $api->add_role('admin_calendar_edit', 'Typer', 'Może edytować kalendarz');
            $api->add_role('admin_calendar_delete', 'Typer', 'Może usuwać z kalendarza');

            $api->execute_queries(array(
                 "ALTER TABLE ".DB::prefix()."news
                  ADD INDEX created_at (created_at)",

                  "CREATE TABLE IF NOT EXISTS `".DB::prefix()."calendar` (
                   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                   `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
                   `date_start` date NOT NULL DEFAULT '0000-00-00',
                   `date_end` date NOT NULL DEFAULT '0000-00-00',
                   `handler` varchar(127) NOT NULL DEFAULT 'event',
                   `type` varchar(127) NOT NULL DEFAULT '',
                   `options` mediumtext NOT NULL,
                   PRIMARY KEY (`id`)
                   ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;",
            ), true);

            $version = '1.3';
        }

        $api->update_package('core', $this->get_version());
        return true;
    }
}
