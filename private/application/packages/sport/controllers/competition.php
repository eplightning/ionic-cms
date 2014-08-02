<?php

/**
 * Competition controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Competition_Controller extends Base_Controller {

    /**
     * Crosstab
     *
     * @param string $competition
     * @param string $season
     */
    public function action_crosstab($competition, $season)
    {
        if (!ctype_digit($season))
            return Response::error(500);

        $competition = DB::table('competitions')->where('slug', '=', $competition)->first('*');
        $season = DB::table('seasons')->where('year', '=', (int) $season)->first('*');

        if (!$competition or !$season)
            return Response::error(404);

        $teams = DB::table('competition_teams')->where('competition_id', '=', $competition->id)
                ->join('teams', 'teams.id', '=', 'team_id')
                ->get(array('id', 'name', 'slug', 'image'));

        $score_info = array();

        foreach ($teams as $t)
        {
            $score_info[$t->id] = array();

            foreach ($teams as $t2)
            {
                $score_info[$t->id][$t2->id] = '-:-';
            }
        }

        foreach (DB::table('matches')->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                ->where('competition_id', '=', $competition->id)
                ->where('season_id', '=', $season->id)
                ->where('score', '<>', '')
                ->get(array('home_id', 'away_id', 'score')) as $m)
        {
            $score_info[$m->home_id][$m->away_id] = $m->score;
        }

        $this->page->set_title('Tabela krzyżowa');
        $this->page->breadcrumb_append('Rozgrywki', 'competition/index');
        $this->page->breadcrumb_append('Tabela krzyżowa', 'competition/crosstab/'.$competition->slug.'/'.$season->year);
        $this->online('Tabela krzyżowa', 'competition/crosstab/'.$competition->slug.'/'.$season->year);

        $this->view = View::make('competition.crosstab', array(
                    'competition' => $competition,
                    'season'      => $season,
                    'score_info'  => $score_info,
                    'teams'       => $teams
                ));
    }

    /**
     * List all competitions and stuff
     */
    public function action_index()
    {
        $this->page->set_title('Lista rozgrywek');
        $this->page->breadcrumb_append('Rozgrywki', 'competition/index');
        $this->online('Rozgrywki', 'competition/index');

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

        $links = array();

        // Timetable/crosstab
        foreach (DB::table('matches')->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                                     ->order_by('competition_id', 'desc')->order_by('season_id', 'desc')
                                     ->distinct()->get(array('fixtures.competition_id', 'fixtures.season_id')) as $row)
        {
            if (!isset($links[$row->competition_id]))
                $links[$row->competition_id] = array();

            $links[$row->competition_id][$row->season_id] = array(
                'name' => $competitions[$row->competition_id][0].' ('.$seasons[$row->season_id][0].' / '.$seasons[$row->season_id][1].')',
                'links' => array(
                    'Terminarz' => 'competition/timetable/'.$competitions[$row->competition_id][1].'/'.$seasons[$row->season_id][0],
                    'Tabela krzyżowa' => 'competition/crosstab/'.$competitions[$row->competition_id][1].'/'.$seasons[$row->season_id][0]
                )
            );
        }

        // Stats
        foreach (DB::table('player_stats')->order_by('competition_id', 'desc')->order_by('season_id', 'desc')
                                     ->distinct()->get(array('competition_id', 'season_id')) as $row)
        {
            if (!isset($links[$row->competition_id]))
            {
                $links[$row->competition_id] = array();
            }

            if (!isset($links[$row->competition_id][$row->season_id]))
            {
                $links[$row->competition_id][$row->season_id] = array(
                    'name' => $competitions[$row->competition_id][0].' ('.$seasons[$row->season_id][0].' / '.$seasons[$row->season_id][1].')',
                    'links' => array()
                );
            }

            $links[$row->competition_id][$row->season_id]['links']['Statystyki zawodników'] = 'competition/stats/'.$competitions[$row->competition_id][1].'/'.$seasons[$row->season_id][0];
        }

        // Tables
        foreach (DB::table('tables')->get(array('competition_id', 'season_id', 'slug', 'title')) as $row)
        {
            if (!isset($links[$row->competition_id]))
            {
                $links[$row->competition_id] = array();
            }

            if (!isset($links[$row->competition_id][$row->season_id]))
            {
                $links[$row->competition_id][$row->season_id] = array(
                    'name' => $competitions[$row->competition_id][0].' ('.$seasons[$row->season_id][0].' / '.$seasons[$row->season_id][1].')',
                    'links' => array()
                );
            }

            $links[$row->competition_id][$row->season_id]['links']['Tabela: '.$row->title] = 'competition/table/'.$row->slug;
        }

        $this->view = View::make('competition.index', array(
                    'list' => $links
                ));
    }

    /**
     * Injuries
     */
    public function action_injuries()
    {
        $this->page->set_title('Kontuzje');
        $this->page->breadcrumb_append('Rozgrywki', 'competition/index');
        $this->page->breadcrumb_append('Kontuzje', 'competition/injuries');
        $this->online('Kontuzje', 'competition/injuries');

        $this->view = View::make('competition.injuries', array(
                    'injuries' => DB::table('player_injuries')->join('players', 'players.id', '=', 'player_injuries.player_id')
                            ->join('teams', 'teams.id', '=', 'players.team_id')
                            ->order_by('player_injuries.recovery_date', 'asc')
                            ->where(function($q) {
                                        $q->where('player_injuries.recovery_date', '>=', date('Y-m-d H:i:s'));
                                        $q->or_where('player_injuries.recovery_date', '=', '0000-00-00 00:00:00');
                                    })
                            ->paginate(20, array(
                                'player_injuries.id', 'player_injuries.injury', 'player_injuries.recovery_date',
                                'players.name', 'players.image', 'players.slug', 'players.number',
                                'teams.name as team_name', 'teams.image as team_image', 'teams.is_distinct as team_is_distinct', 'teams.slug as team_slug'
                            ))
                ));
    }

    /**
     * Season stats
     *
     * @param string $competition
     * @param string $season
     * @param string $sort_by
     */
    public function action_stats($competition, $season, $sort_by = 'goals')
    {
        if (!ctype_digit($season))
            return Response::error(500);

        $competition = DB::table('competitions')->where('slug', '=', $competition)->first('*');
        $season = DB::table('seasons')->where('year', '=', (int) $season)->first('*');

        if (!$competition or !$season)
            return Response::error(404);

        if (!in_array($sort_by, array('goals', 'matches', 'assists', 'yellow_cards', 'red_cards', 'minutes')))
            $sort_by = 'goals';

        $this->page->set_title('Statystyki');
        $this->page->breadcrumb_append('Rozgrywki', 'competition/index');
        $this->page->breadcrumb_append('Statystyki', 'competition/stats/'.$competition->slug.'/'.$season->year);
        $this->online('Statystyki', 'competition/stats/'.$competition->slug.'/'.$season->year);

        $this->view = View::make('competition.stats', array(
                    'competition' => $competition,
                    'season'      => $season,
                    'sort_by'     => $sort_by,
                    'players'     => DB::table('player_stats')->where('competition_id', '=', $competition->id)->where('season_id', '=', $season->id)
                            ->join('players', 'players.id', '=', 'player_stats.player_id')
                            ->join('teams', 'teams.id', '=', 'players.team_id')
                            ->order_by($sort_by, 'desc')
                            ->get(array('goals', 'matches', 'assists', 'yellow_cards', 'red_cards', 'minutes',
                                'players.slug as player_slug', 'players.name as player_name',
                                'teams.name as team_name', 'teams.slug as team_slug', 'teams.is_distinct as team_is_distinct', 'teams.image as team_image'))
                ));
    }

    /**
     * Table
     *
     * @param string $table
     * @param string $sort_by
     */
    public function action_table($table, $sort_by = 'position')
    {
        $table = DB::table('tables')->where('slug', '=', $table)->first(array('title', 'id', 'slug'));

        if (!$table)
            return Response::error(404);

        $this->page->set_title($table->title);
        $this->page->breadcrumb_append('Rozgrywki', 'competition/index');
        $this->page->breadcrumb_append($table->title, 'competition/table/'.$table->slug);
        $this->online($table->title, 'competition/table/'.$table->slug);

        if (!in_array($sort_by, array('position', 'points', 'matches', 'wins', 'losses', 'draws', 'goals_shot')))
        {
            $sort_by = 'position';
        }

        $positions = DB::table('table_positions')->where('table_id', '=', $table->id)
                ->join('teams', 'teams.id', '=', 'table_positions.team_id')
                ->order_by($sort_by == 'goals_shot' ? DB::raw('goals_shot - goals_lost') : $sort_by, $sort_by == 'position' ? 'asc' : 'desc')
                ->get(array('table_positions.*', 'teams.name', 'teams.slug', 'teams.image', 'teams.is_distinct'));


        $this->view = View::make('competition.table', array(
                    'sort_by'   => $sort_by,
                    'table'     => $table,
                    'positions' => $positions
                ));
    }

    /**
     * Display timetable
     *
     * @param string $competition
     * @param string $season
     */
    public function action_timetable($competition, $season)
    {
        if (!ctype_digit($season))
            return Response::error(500);

        $competition = DB::table('competitions')->where('slug', '=', $competition)->first('*');
        $season = DB::table('seasons')->where('year', '=', (int) $season)->first('*');

        if (!$competition or !$season)
            return Response::error(404);

        if (Session::has('timetable_filter'))
        {
            $filters = Session::get('timetable_filter');

            if (!is_array($filters))
            {
                $filters = array('fixture' => 0, 'team'    => 0);
            }
        }
        else
        {
            $filters = array('fixture' => 0, 'team'    => 0);
        }

        if (Request::method() == 'POST')
        {
            if (Input::has('fixture') and Input::get('fixture') != '0')
            {
                $fixture = Input::get('fixture');

                if (!ctype_digit($fixture))
                    return Response::error(500);

                $fixture = DB::table('fixtures')->where('competition_id', '=', $competition->id)->where('season_id', '=', $season->id)
                                ->where('id', '=', (int) $fixture)->first('id');

                if (!$fixture)
                {
                    $filters['fixture'] = 0;
                }
                else
                {
                    $filters['fixture'] = (int) $fixture->id;
                }
            }
            else
            {
                $filters['fixture'] = 0;
            }

            if (Input::has('team') and Input::get('team') != '0')
            {
                $team = Input::get('team');

                if (!ctype_digit($team))
                    return Response::error(500);

                $team = DB::table('teams')->where('id', '=', (int) $team)->first('id');

                if (!$team)
                {
                    $filters['team'] = 0;
                }
                else
                {
                    $filters['team'] = (int) $team->id;
                }
            }
            else
            {
                $filters['team'] = 0;
            }

            Session::put('timetable_filter', $filters);
            return Redirect::to('competition/timetable/'.$competition->slug.'/'.$season->year);
        }

        $teams = array();
        $fixtures = array();

        foreach (DB::table('fixtures')->where('competition_id', '=', $competition->id)->where('season_id', '=', $season->id)
                ->order_by('number', 'asc')->get(array('id', 'name')) as $f)
        {
            $fixtures[$f->id] = $f->name;
        }

        $matches = DB::table('matches')->where('competition_id', '=', $competition->id)
                ->where('season_id', '=', $season->id)
                ->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                ->join('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id')
                ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                ->order_by('matches.date', 'asc');

        $grouped_matches = array();

        if (!empty($filters['fixture']))
        {
            $matches->where('matches.fixture_id', '=', $filters['fixture']);
        }

        if (!empty($filters['team']))
        {
            $matches->where(function($q) use ($filters) {
                        $q->where('matches.home_id', '=', $filters['team']);
                        $q->or_where('matches.away_id', '=', $filters['team']);
                    });
        }

        foreach ($matches->get(array(
            'matches.*', 'fixtures.name as fixture_name', 'fixtures.number as fixture_number',
            'home.name as home_name', 'home.is_distinct as home_is_distinct', 'home.slug as home_slug', 'home.image as home_image',
            'away.name as away_name', 'away.is_distinct as away_is_distinct', 'away.slug as away_slug', 'away.image as away_image')) as $m)
        {
            if (!isset($teams[$m->home_id]))
                $teams[$m->home_id] = $m->home_name;
            if (!isset($teams[$m->away_id]))
                $teams[$m->away_id] = $m->away_name;
            if (!isset($grouped_matches[$m->fixture_id]))
                $grouped_matches[$m->fixture_id] = array('matches' => array(), 'name'    => $m->fixture_name, 'number'  => (int) $m->fixture_number);

            $grouped_matches[$m->fixture_id]['matches'][] = $m;
        }

        uasort($grouped_matches, function($a, $b) {
                    if ($a['number'] == $b['number'])
                        return 0;

                    return ($a['number'] < $b['number']) ? 1 : -1;
                });

        $this->page->set_title('Terminarz');
        $this->page->breadcrumb_append('Rozgrywki', 'competition/index');
        $this->page->breadcrumb_append('Terminarz', 'competition/timetable/'.$competition->slug.'/'.$season->year);
        $this->online('Terminarz', 'competition/timetable/'.$competition->slug.'/'.$season->year);

        $this->view = View::make('competition.timetable', array(
                    'filters'     => $filters,
                    'teams'       => $teams,
                    'fixtures'    => $fixtures,
                    'matches'     => $grouped_matches,
                    'competition' => $competition,
                    'season'      => $season
                ));
    }

    /**
     * Transfers
     */
    public function action_transfers($type = 'from', $team = null)
    {
        if (Session::has('transfers_filter'))
        {
            $filters = Session::get('transfers_filter');

            if (!is_array($filters))
            {
                $filters = array('team' => 0, 'type' => 'all', 'date_start' => null, 'date_end' => null);
            }
        }
        else
        {
            $filters = array('team' => 0, 'type' => 'all', 'date_start' => null, 'date_end' => null);
        }

        if (Request::method() == 'POST')
        {
            if (in_array(Input::get('type'), array('from', 'to', 'loan', 'loan_back', 'all')))
            {
                $filters['type'] = Input::get('type');
            }
            else
            {
                $filters['type'] = 'all';
            }

            if (Input::has('team') and Input::get('team') != '0')
            {
                $team = Input::get('team');

                if (!ctype_digit($team))
                    return Response::error(500);

                $team = DB::table('teams')->where('id', '=', (int) $team)->first('id');

                if (!$team)
                {
                    $filters['team'] = 0;
                }
                else
                {
                    $filters['team'] = (int) $team->id;
                }
            }
            else
            {
                $filters['team'] = 0;
            }

            if (Input::has('date_start') and preg_match('!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!', Input::get('date_start')))
            {
                $filters['date_start'] = Input::get('date_start');
            }
            else
            {
                $filters['date_start'] = null;
            }

            if (Input::has('date_end') and preg_match('!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!', Input::get('date_end')))
            {
                $filters['date_end'] = Input::get('date_end');

                if ($filters['date_start'] and strcmp($filters['date_start'], $filters['date_end']) > 0)
                {
                    $filters['date_end'] = null;
                }
            }
            else
            {
                $filters['date_end'] = null;
            }

            Session::put('transfers_filter', $filters);
            return Redirect::to('competition/transfers');
        }
        elseif ($team)
        {
            $team = DB::table('teams')->where('slug', '=', $team)->first(array('id'));

            if (!$team)
            {
                return Redirect::to('competition/transfers');
            }

            $filters['team'] = $team->id;

            if (in_array($type, array('from', 'to', 'loan', 'loan_back', 'all')))
            {
                $filters['type'] = $type;
            }
        }

        $query = DB::table('player_transfers')->join('players', 'players.id', '=', 'player_transfers.player_id')
                            ->join('teams as '.DB::prefix().'from', 'from.id', '=', 'player_transfers.from_team')
                            ->join('teams as '.DB::prefix().'to', 'to.id', '=', 'player_transfers.team_id')
                            ->order_by('player_transfers.date', 'desc');

        if ($filters['date_start'])
        {
            $query->where('player_transfers.date', '>=', $filters['date_start']);
        }

        if ($filters['date_end'])
        {
            $query->where('player_transfers.date', '<=', $filters['date_end']);
        }

        if ($filters['team'])
        {
            switch ($filters['type'])
            {
                case 'loan_back':
                    $query->where('player_transfers.type', '=', 2);
                    $query->where(function($q) use ($filters) {
                        $q->where('player_transfers.from_team', '=', $filters['team']);
                        $q->or_where('player_transfers.team_id', '=', $filters['team']);
                    });
                    break;

                case 'loan':
                    $query->where('player_transfers.type', '=', 1);
                    $query->where(function($q) use ($filters) {
                        $q->where('player_transfers.from_team', '=', $filters['team']);
                        $q->or_where('player_transfers.team_id', '=', $filters['team']);
                    });
                    break;

                case 'from':
                    $query->where('player_transfers.type', '=', 0);
                    $query->where('player_transfers.from_team', '=', $filters['team']);
                    break;

                case 'to':
                    $query->where('player_transfers.type', '=', 0);
                    $query->where('player_transfers.team_id', '=', $filters['team']);
                    break;

                case 'all':
                    $query->where(function($q) use ($filters) {
                        $q->where('player_transfers.from_team', '=', $filters['team']);
                        $q->or_where('player_transfers.team_id', '=', $filters['team']);
                    });
            }
        }
        else
        {
            switch ($filters['type'])
            {
                case 'loan_back':
                    $query->where('player_transfers.type', '=', 2);
                    break;

                case 'loan':
                    $query->where('player_transfers.type', '=', 1);
                    break;

                case 'from':
                case 'to':
                    $query->where('player_transfers.type', '=', 0);
            }
        }

        $this->page->set_title('Transfery');
        $this->page->breadcrumb_append('Rozgrywki', 'competition/index');
        $this->page->breadcrumb_append('Transfery', 'competition/transfers');
        $this->online('Transfery', 'competition/transfers');

        Asset::add('jquery-ui', 'public/css/flick/jquery-ui.custom.css');
        Asset::add('jquery-ui', 'public/js/jquery-ui.site.min.js', 'jquery');

        $this->view = View::make('competition.transfers', array(
                    'filters' => $filters,
                    'teams' => DB::table('teams')->order_by('name', 'asc')->get(array('id', 'name')),
                    'transfers' => $query->paginate(20, array(
                                'player_transfers.id', 'player_transfers.type', 'player_transfers.cost', 'player_transfers.description', 'player_transfers.date',
                                'players.name', 'players.image', 'players.slug', 'players.number',
                                'from.name as from_name', 'from.image as from_image', 'from.is_distinct as from_is_distinct', 'from.slug as from_slug',
                                'to.name as to_name', 'to.image as to_image', 'to.is_distinct as to_is_distinct', 'to.slug as to_slug'
                            ))
                ));
    }

}