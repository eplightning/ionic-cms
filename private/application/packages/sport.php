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
        return '1.3';
    }

    /**
     * Init package
     */
    public function init_package()
    {
        // Widget names
        \Event::listen('ionic.widget_name', function() {
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
        \Event::listen('ionic.template_directories', function() {
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
        \Event::listen('ionic.thumbnail_allowed', function($type) {
            if ($type == 'teams' or $type == 'players')
                return true;
        });

        // Controller path
        \Event::listen('ionic.controller_path', function($controller) {
            if (is_file($path = path('app').'packages/sport/controllers/'.$controller.EXT))
            {
                return $path;
            }
        });

        // Current season
        IoC::singleton('current_season', function() {
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

        // Pagemap
        \Event::listen('ionic.pagemap_links', function($format) {
            $links = array('Rozgrywki' => array(), 'Tabele' => array(), 'Terminarze' => array(), 'Tabele krzyżowe' => array(), 'Statystyki zawodników' => array(),
                           'Kluby' => array());

            $links['Rozgrywki']['Lista rozgrywek'] = 'competition';
            $links['Rozgrywki']['Kontuzje'] = 'competition/injuries';
            $links['Rozgrywki']['Transfery'] = 'competition/transfers';
            $links['Rozgrywki']['Typer'] = 'bet';
            $links['Rozgrywki']['Relacje live'] = 'live';
            $links['Rozgrywki']['Piłkarz meczu'] = 'matchpick';
            $links['Rozgrywki']['Piłkarz miesiąca'] = 'monthpick';

            // Teams
            foreach (DB::table('teams')->order_by('is_distinct', 'desc')->order_by('id', 'desc')->take(10)->get(array('name', 'slug')) as $c)
            {
                $links['Kluby'][$c->name] = 'team/show/'.$c->slug;
            }

            // Competition
            $competitions = array();
            $seasons = array();

            foreach (DB::table('competitions')->order_by('id', 'desc')->get(array('id', 'name', 'slug')) as $c)
            {
                $competitions[$c->id] = array($c->name, $c->slug);
            }

            foreach (DB::table('seasons')->order_by('year', 'desc')->get(array('id', 'year')) as $c)
            {
                $seasons[$c->id] = array($c->year, $c->year + 1);
            }

            // Timetable/crosstab
            foreach (DB::table('matches')->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                                        ->order_by('competition_id', 'desc')->order_by('season_id', 'desc')
                                        ->distinct()->get(array('fixtures.competition_id', 'fixtures.season_id')) as $row)
            {
                $links['Terminarze']['Terminarz '.$competitions[$row->competition_id][0].' ('.$seasons[$row->season_id][0].' / '.$seasons[$row->season_id][1].')'] = 'competition/timetable/'.$competitions[$row->competition_id][1].'/'.$seasons[$row->season_id][0];
                $links['Tabele krzyżowe']['Tabela krzyżowa '.$competitions[$row->competition_id][0].' ('.$seasons[$row->season_id][0].' / '.$seasons[$row->season_id][1].')'] = 'competition/crosstab/'.$competitions[$row->competition_id][1].'/'.$seasons[$row->season_id][0];
            }

            // Stats
            foreach (DB::table('player_stats')->order_by('competition_id', 'desc')->order_by('season_id', 'desc')
                                        ->distinct()->get(array('competition_id', 'season_id')) as $row)
            {
                $links['Statystyki zawodników']['Statystyki '.$competitions[$row->competition_id][0].' ('.$seasons[$row->season_id][0].' / '.$seasons[$row->season_id][1].')'] = 'competition/stats/'.$competitions[$row->competition_id][1].'/'.$seasons[$row->season_id][0];            }

            // Tables
            foreach (DB::table('tables')->get(array('slug', 'title')) as $row)
            {
                $links['Tabele']['Tabela '.$row->title] = 'competition/table/'.$row->slug;
            }

            return $links;
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

        // 1.2.1 -> 1.3
        if ($version == '1.2.1')
        {
            // competition_teams
            $api->execute_queries(array(
                "ALTER TABLE ".DB::prefix()."competition_teams
                ADD season_id int(10) unsigned NOT NULL DEFAULT '0'",

                "UPDATE ".DB::prefix()."competition_teams
                 SET season_id = ".IoC::resolve('current_season')->id,

                "ALTER TABLE ".DB::prefix()."competition_teams
                 ADD INDEX season_id (season_id)",

                "ALTER TABLE ".DB::prefix()."competition_teams
                 DROP PRIMARY KEY, ADD PRIMARY KEY (competition_id, team_id, season_id)",

                "ALTER TABLE ".DB::prefix()."competition_teams ADD CONSTRAINT ".DB::prefix()."competition_teams_ibfk_3
                 FOREIGN KEY (season_id) REFERENCES ".DB::prefix()."seasons (id) ON DELETE CASCADE ON UPDATE CASCADE"
            ), true);

            $version = '1.3';
        }

        $api->update_package('sport', $this->get_version());

        return true;
    }

}