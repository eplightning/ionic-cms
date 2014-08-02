<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;
use \IoC;

class Stats extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('season'      => null, 'competition' => null, 'limit'       => 0, 'sort'        => 'goals', 'template'    => 'widgets.stats'), $this->options);

        $seasons = array(null => 'Obecny');

        foreach (DB::table('seasons')->get(array('id', 'year')) as $s)
        {
            $seasons[$s->id] = $s->year.' / '.($s->year + 1);
        }

        return View::make('admin.widgets.widget_stats', array(
                    'options'      => $options,
                    'action'       => \URI::current(),
                    'seasons'      => $seasons,
                    'competitions' => DB::table('competitions')->get(array('id', 'name'))
                ));
    }

    /**
     * Prepare options field
     *
     * @return string
     */
    public function prepare_options()
    {
        if (\Request::forged() or \Request::method() != 'POST' or !Input::has('competition') or !ctype_digit(Input::get('competition')))
        {
            return false;
        }

        $options = array_merge(array('season'      => null, 'competition' => null, 'limit'       => 0, 'sort'        => 'goals', 'template'    => 'widgets.stats'), $this->options);

        // season
        if (Input::has('season') and ctype_digit(Input::get('season')))
        {
            $s = DB::table('seasons')->where('id', '=', (int) Input::get('season'))->first('id');

            if ($s)
            {
                $options['season'] = $s->id;
            }
            else
            {
                $options['season'] = null;
            }
        }
        else
        {
            $options['season'] = null;
        }

        // competiton
        $c = Input::get('competition');
        $c = DB::table('competitions')->where('id', '=', (int) $c)->first('id');

        if (!$c)
        {
            return false;
        }

        $options['competition'] = $c->id;

        // limit
        $options['limit'] = (int) Input::get('limit', 0);

        // sorting
        if (!Input::has('sort') or !in_array(Input::get('sort'), array('goals', 'red_cards', 'yellow_cards', 'assists', 'matches', 'minutes')))
        {
            $options['sort'] = 'goals';
        }
        else
        {
            $options['sort'] = Input::get('sort');
        }

        // template
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.stats';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('season'      => null, 'competition' => null, 'limit'       => 0, 'sort'        => 'goals', 'template'    => 'widgets.stats'), $this->options);

        if (!$options['competition'])
        {
            return;
        }

        if (!$options['season'])
        {
            $options['season'] = IoC::resolve('current_season')->id;
        }

        if ($options['limit'] < 0)
        {
            return;
        }

        $stats = 'stats-'.$options['sort'].'-'.$options['limit'].'-'.$options['competition'].'-'.$options['season'];

        if (\Cache::has($stats))
        {
            $stats = \Cache::get($stats);
        }
        else
        {
            $stats = DB::table('player_stats')->where('competition_id', '=', $options['competition'])
                    ->where('season_id', '=', $options['season'])
                    ->take($options['limit'])
                    ->join('players', 'players.id', '=', 'player_stats.player_id')
                    ->join('teams', 'teams.id', '=', 'players.team_id')
                    ->order_by('player_stats.'.$options['sort'], 'desc')
                    ->get(array('player_stats.*', 'players.name', 'players.number', 'players.slug', 'teams.name as team_name', 'teams.is_distinct', 'teams.slug as team_slug', 'teams.image as team_image'));

            $stats = (string) View::make($options['template'], array('stats' => $stats));

            \Cache::put('stats-'.$options['sort'].'-'.$options['limit'].'-'.$options['competition'].'-'.$options['season'], $stats);
        }

        return $stats;
    }

}