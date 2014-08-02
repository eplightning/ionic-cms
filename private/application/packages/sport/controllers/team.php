<?php

/**
 * Team controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Team_Controller extends Base_Controller {

    /**
     * Player
     *
     * @param string $team
     * @param string $player
     */
    public function action_player($team, $player)
    {
        $team = DB::table('teams')->where('slug', '=', $team)->first('*');
        if (!$team)
            return Response::error(404);

        $player = DB::table('players')->where('slug', '=', $player)->where('team_id', '=', $team->id)->first('*');
        if (!$player)
            return Response::error(404);

        $this->page->set_title('Zawodnik - '.$player->name);
        $this->page->breadcrumb_append('Klub - '.$team->name, 'team/show/'.$team->slug);
        $this->page->breadcrumb_append('Zawodnik - '.$player->name, 'team/player/'.$team->slug.'/'.$player->slug);
        $this->online('Zawodnik - '.$player->name, 'team/player/'.$team->slug.'/'.$player->slug);

        $countries = ionic_country_list();

        if ($player->country and isset($countries[$player->country]))
        {
            $country = $countries[$player->country];
        }
        else
        {
            $country = 'b/d';
        }

        $this->view = View::make('team.player', array(
                    'team'    => $team,
                    'player'  => $player,
                    'country' => $country,
                    'stats'   => DB::table('player_stats')->join('competitions', 'competitions.id', '=', 'player_stats.competition_id')
                            ->join('seasons', 'seasons.id', '=', 'player_stats.season_id')
                            ->order_by('seasons.year', 'desc')
                            ->where('player_id', '=', $player->id)
                            ->get(array('competitions.name as competition_name',
                                'seasons.year as season_year',
                                'matches', 'assists', 'goals', 'red_cards', 'yellow_cards', 'minutes'))
                ));
    }

    /**
     * Show team
     *
     * @param string $team
     */
    public function action_players($team)
    {
        $team = DB::table('teams')->where('slug', '=', $team)->first('*');

        if (!$team)
            return Response::error(404);

        $grouped = array();

        foreach (DB::table('players')->where('team_id', '=', $team->id)->order_by('number', 'asc')->get('*') as $p)
        {
            if (!isset($grouped[$p->position]))
                $grouped[$p->position] = array();

            $grouped[$p->position][] = $p;
        }

        uasort($grouped, function($a, $b)
                {
                    $posa = $a[0]->position;
                    $posb = $b[0]->position;

                    if ($posa == $posb)
                        return 0;

                    // Soccer positions
                    if ($posa == 'Bramkarz')
                        return -1;
                    if ($posb == 'Bramkarz')
                        return 1;
                    if ($posa == 'Napastnik')
                        return 1;
                    if ($posb == 'Napastnik')
                        return -1;
                    if ($posa == 'Obrońca')
                        return -1;
                    if ($posb == 'Obrońca')
                        return 1;

                    // Fallback to numbers
                    if ($a[0]->number == $b[0]->number)
                        return 0;

                    return ($a[0]->number > $b[0]->number ? 1 : -1);
                });

        $this->page->set_title('Kadra - '.$team->name);
        $this->page->breadcrumb_append('Kadra - '.$team->name, 'team/players/'.$team->slug);
        $this->online('Kadra - '.$team->name, 'team/players/'.$team->slug);

        $this->view = View::make('team.players', array(
                    'team'    => $team,
                    'players' => $grouped
                ));
    }

    /**
     * Show team
     *
     * @param string $team
     */
    public function action_show($team)
    {
        $team = DB::table('teams')->where('slug', '=', $team)->first('*');

        if (!$team)
            return Response::error(404);

        $countries = ionic_country_list();

        if ($team->country and isset($countries[$team->country]))
        {
            $country = $countries[$team->country];
        }
        else
        {
            $country = 'b/d';
        }

        $grouped = array();

        foreach (DB::table('players')->where('team_id', '=', $team->id)->order_by('number', 'asc')->get('*') as $p)
        {
            if (!isset($grouped[$p->position]))
                $grouped[$p->position] = array();

            $grouped[$p->position][] = $p;
        }

        uasort($grouped, function($a, $b)
                {
                    $posa = $a[0]->position;
                    $posb = $b[0]->position;

                    if ($posa == $posb)
                        return 0;

                    // Soccer positions
                    if ($posa == 'Bramkarz')
                        return -1;
                    if ($posb == 'Bramkarz')
                        return 1;
                    if ($posa == 'Napastnik')
                        return 1;
                    if ($posb == 'Napastnik')
                        return -1;
                    if ($posa == 'Obrońca')
                        return -1;
                    if ($posb == 'Obrońca')
                        return 1;

                    // Fallback to numbers
                    if ($a[0]->number == $b[0]->number)
                        return 0;

                    return ($a[0]->number > $b[0]->number ? 1 : -1);
                });

        $this->page->set_title('Klub - '.$team->name);
        $this->page->breadcrumb_append('Klub - '.$team->name, 'team/show/'.$team->slug);
        $this->online('Klub - '.$team->name, 'team/show/'.$team->slug);

        $this->view = View::make('team.show', array(
                    'team'    => $team,
                    'players' => $grouped,
                    'country' => $country
                ));
    }

}