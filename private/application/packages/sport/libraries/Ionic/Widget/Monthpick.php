<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;
use \stdClass;

class Monthpick extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('template' => 'widgets.monthpick', 'competition' => 0), $this->options);

        return View::make('admin.widgets.widget_monthpick', array(
                    'action' => \URI::current(),
                    'options' => $options,
                    'competitions' => DB::table('competitions')->get(array(
                        'id',
                        'name'))
                ));
    }

    /**
     * Prepare options field
     *
     * @return string
     */
    public function prepare_options()
    {
        if (\Request::forged() or \Request::method() != 'POST')
        {
            return false;
        }

        $options = array_merge(array('template' => 'widgets.monthpick', 'competition' => 0), $this->options);

        // competiton
        $c = Input::get('competition', 0);

        if ($c and $c != '0')
        {
            $c = DB::table('competitions')->where('id', '=', (int) $c)->first('id');

            if (!$c)
            {
                $options['competition'] = 0;
            }
            else
            {
                $options['competition'] = $c->id;
            }
        }
        else
        {
            $options['competition'] = 0;
        }

        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.monthpick';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('template' => 'widgets.monthpick', 'competition' => 0), $this->options);

        if (\Cache::has('monthpick'))
        {
            $monthpick = \Cache::get('monthpick');
        }
        else
        {
            $monthpick = array('player' => null, 'active' => new \Model\Monthpick,
                'player_votes' => 0, 'player_percent' => 0, 'player_stats' => null);

            $monthpick['player'] = DB::table('monthpicks')->where('best_player_id', '<>', 0)->join('players', 'players.id', '=', 'monthpicks.best_player_id')
                    ->where('is_active', '=', 0)->order_by('monthpicks.created_at', 'desc')
                    ->first(array('monthpicks.title', 'players.*', 'monthpicks.options',
                'monthpicks.votes'));

            $monthpick['player_stats'] = new stdClass;
            $monthpick['player_stats']->matches = 0;
            $monthpick['player_stats']->goals = 0;
            $monthpick['player_stats']->assists = 0;
            $monthpick['player_stats']->red_cards = 0;
            $monthpick['player_stats']->yellow_cards = 0;
            $monthpick['player_stats']->minutes = 0;

            if ($monthpick['player'])
            {
                if ($options['competition'])
                {
                    $stats = DB::table('player_stats')->where('competition_id', '=', $options['competition'])
                                ->where('season_id', \IoC::resolve('current_season')->id)
                                ->where('player_id', '=', $monthpick['player']->id)
                                ->first(array('matches', 'assists', 'goals', 'red_cards', 'yellow_cards', 'minutes'));

                    if ($stats) $monthpick['player_stats'] = $stats;
                }

                $opt = $monthpick['player']->options ? unserialize($monthpick['player']->options) : array(
                        );

                if (isset($opt[(int) $monthpick['player']->id]))
                {
                    $monthpick['player_votes'] = $opt[(int) $monthpick['player']->id]['votes'];
                }

                if ($monthpick['player']->votes > 0)
                {
                    $monthpick['player_percent'] = round($monthpick['player_votes'] / $monthpick['player']->votes * 100, 2);
                }

                unset($opt);
            }

            \Cache::put('monthpick', $monthpick);
        }

        return View::make($options['template'], array('monthpick' => $monthpick));
    }

}