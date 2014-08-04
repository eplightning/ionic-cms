<?php

/**
 * Ionic sport module
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class Sport_Package extends \Ionic\Package {

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
     * Called when package state is changed (enabled/disabled) by user
     *
     * @param bool $state
     * @param  \Ionic\Package\API $api
     */
    public function change_state($state, \Ionic\Package\API $api)
    {
        $api->disable_menu(array(
            'competitions', 'teams', 'seasons', 'fixtures', 'matches', 'players', 'tables', 'player_stats', 'player_injuries', 'player_transfers', 'bet_matches',
            'monthpicks', 'matchpicks', 'relations'
        ), $state);
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
        return 'Sportowy moduł dla systemu IonicCMS. Zawiera relacje live, mecze, kolejki, zawodników, piłkarza meczu i wiele więcej.';
    }

    /**
     * Get package name
     *
     * @return string
     */
    public function get_name()
    {
        return 'Moduł sportowy';
    }

    /**
     * Get package version
     *
     * @return string
     */
    public function get_version()
    {
        return '1.2.1';
    }

    /**
     * Init package
     */
    public function init_package()
    {
        // Widget names
        \Event::listen('ionic.widget_name', function()
                {
                    return array(
                        'relation' => 'Relacja live',
                        'matchpick' => 'Piłkarz meczu',
                        'monthpick' => 'Piłkarz miesiąca',
                        'injuries' => 'Kontuzje',
                        'table' => 'Tabela',
                        'timetable' => 'Terminarz',
                        'transfers' => 'Transfery',
                        'birthdays' => 'Urodziny zaw.',
                        'match' => 'Mecz',
                        'stats' => 'Statystyki',
                    );
                });

        // Template directories
        \Event::listen('ionic.template_directories', function()
                {
                    return array(
                        'bet' => 'Typer',
                        'competition' => 'Rozgrywki',
                        'live' => 'Relacje live',
                        'match' => 'Mecz',
                        'matchpick' => 'Piłkarz meczu',
                        'monthpick' => 'Piłkarz miesiąca',
                        'team' => 'Klub',
                    );
                });

        // Allowed thumbnail
        \Event::listen('ionic.thumbnail_allowed', function($type)
                {
                    if ($type == 'teams' or $type == 'players')
                        return true;
                });

        // Controller path
        \Event::listen('ionic.controller_path', function($controller)
                {
                    if (is_file($path = path('app').'packages/sport/controllers/'.$controller.EXT))
                    {
                        return $path;
                    }
                });

        // Current season
        IoC::singleton('current_season', function()
                {
                    if (\Cache::has('current-season'))
                    {
                        return \Cache::get('current-season');
                    }
                    else
                    {
                        $season = \DB::table('seasons')->where('is_active', '=', 1)->first('*');

                        if (!$season)
                        {
                            // Fallback
                            $season = new \stdClass;
                            $season->id = 0;
                            $season->year = date('Y');
                            $season->is_active = 1;

                            return $season;
                        }

                        \Cache::put('current-season', $season);

                        return $season;
                    }
                });

        $root = path('app').'packages'.DS.'sport'.DS;

        // Class mappings
        Autoloader::map(array(
            'Model\\Matchpick' => $root.'models'.DS.'matchpick.php',
            'Model\\Monthpick' => $root.'models'.DS.'monthpick.php',
            'Ionic\\BetHandler' => $root.'libraries'.DS.'Ionic'.DS.'BetHandler.php',
            'Ionic\\TableManager' => $root.'libraries'.DS.'Ionic'.DS.'TableManager.php',
            'Ionic\\Widget\\Birthdays' => $root.'libraries'.DS.'Ionic'.DS.'Widget'.DS.'Birthdays.php',
            'Ionic\\Widget\\Injuries' => $root.'libraries'.DS.'Ionic'.DS.'Widget'.DS.'Injuries.php',
            'Ionic\\Widget\\Match' => $root.'libraries'.DS.'Ionic'.DS.'Widget'.DS.'Match.php',
            'Ionic\\Widget\\Matchpick' => $root.'libraries'.DS.'Ionic'.DS.'Widget'.DS.'Matchpick.php',
            'Ionic\\Widget\\Monthpick' => $root.'libraries'.DS.'Ionic'.DS.'Widget'.DS.'Monthpick.php',
            'Ionic\\Widget\\Relation' => $root.'libraries'.DS.'Ionic'.DS.'Widget'.DS.'Relation.php',
            'Ionic\\Widget\\Stats' => $root.'libraries'.DS.'Ionic'.DS.'Widget'.DS.'Stats.php',
            'Ionic\\Widget\\Table' => $root.'libraries'.DS.'Ionic'.DS.'Widget'.DS.'Table.php',
            'Ionic\\Widget\\Timetable' => $root.'libraries'.DS.'Ionic'.DS.'Widget'.DS.'Timetable.php',
            'Ionic\\Widget\\Transfers' => $root.'libraries'.DS.'Ionic'.DS.'Widget'.DS.'Transfers.php',
        ));
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
        // 1.1, 1.1.1 -> 1.2
        if ($version == '1.1' or $version == '1.1.1')
            $version = '1.2';

        // 1.2 -> 1.2.1
        if ($version == '1.2')
        {
            $api->execute_queries(array(
                "ALTER TABLE ".DB::prefix()."table_positions
                 CHANGE `goals_lost` `goals_lost` SMALLINT(5) NOT NULL DEFAULT '0'",
                "ALTER TABLE ".DB::prefix()."table_positions
                 CHANGE `goals_shot` `goals_shot` SMALLINT(5) NOT NULL DEFAULT '0'"
            ), true);

            $version = '1.2.1';
        }

        $api->update_package('sport', $this->get_version());

        return true;
    }

}