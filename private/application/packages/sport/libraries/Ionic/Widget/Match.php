<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;

class Match extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array(
            'competition' => 0,
            'distinct'    => true,
            'template'    => 'widgets.match',
            'type'        => 'next'), $this->options);

        return View::make('admin.widgets.widget_match', array(
                    'options'      => $options,
                    'action'       => \URI::current(),
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

        $options = array_merge(array(
            'competition' => 0,
            'distinct'    => true,
            'template'    => 'widgets.match',
            'type'        => 'next'), $this->options);

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

        $options['distinct'] = Input::get('distinct', '0') == '1' ? true : false;
        $options['type'] = Input::get('type', 'next') == 'next' ? 'next' : 'prev';

        // template
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.match';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array(
            'competition' => 0,
            'distinct'    => true,
            'template'    => 'widgets.match',
            'type'        => 'next'), $this->options);

        $match = 'match-'.$options['type'].'-'.$options['competition'].'-'.(int) $options['distinct'];

        if (\Cache::has($match))
        {
            return \Cache::get($match);
        }
        else
        {
            $match = DB::table('matches')->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                    ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
                    ->join('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id')
                    ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id');

            if ($options['competition'])
            {
                $match->where('fixtures.competition_id', '=', $options['competition']);
            }

            if ($options['distinct'])
            {
                $match->where(function($q)
                        {
                            $q->where('home.is_distinct', '=', 1);
                            $q->or_where('away.is_distinct', '=', 1);
                        });
            }

            if ($options['type'] == 'prev')
            {
                $match->where('matches.score', '<>', '')->order_by('matches.date', 'desc')->where('matches.date', '<=', date('Y-m-d H:i:s'));
            }
            else
            {
                $match->where('matches.score', '=', '')->order_by('matches.date', 'asc')->where('matches.date', '>=', date('Y-m-d H:i:s', (time() - 7200)));
            }

            $match = $match->first(array(
                'matches.*',
                'fixtures.name as fixture_name',
                'fixtures.number as fixture_number',
                'competitions.name as competition_name',
                'home.name as home_name',
                'home.is_distinct as home_is_distinct',
                'home.slug as home_slug',
                'home.image as home_image',
                'away.name as away_name',
                'away.is_distinct as away_is_distinct',
                'away.slug as away_slug',
                'away.image as away_image'
                    ));

            if (!$match)
            {
                $match = false;
            }
            else
            {
                if ($match->home_is_distinct)
                {
                    $match->opponent_name = $match->away_name;
                    $match->opponent_image = $match->away_image;
                    $match->opponent_slug = $match->away_slug;
                }
                else
                {
                    $match->opponent_name = $match->home_name;
                    $match->opponent_image = $match->home_image;
                    $match->opponent_slug = $match->home_slug;
                }

                if ($match->score)
                {
                    $score = ionic_parse_score($match->score);

                    if (is_null($score))
                    {
                        $match->score_home = '';
                        $match->score_away = '';
                    }
                    else
                    {
                        $match->score_home = $score[0];
                        $match->score_away = $score[1];
                    }

                    unset($score);
                }
                else
                {
                    $match->score_home = '';
                    $match->score_away = '';
                }
            }

            $match = (string) View::make($options['template'], array(
                        'match' => $match));

            \Cache::put('match-'.$options['type'].'-'.$options['competition'].'-'.(int) $options['distinct'], $match);

            return $match;
        }
    }

}