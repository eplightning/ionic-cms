<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;
use \IoC;

class Timetable extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array(
            'season'      => null,
            'competition' => 0,
            'distinct'    => false,
            'limit'       => 10,
            'type'        => 'next',
            'type2'       => 's',
            'template'    => 'widgets.timetable'), $this->options);

        $seasons = array(
            null => 'Obecny');

        foreach (DB::table('seasons')->get(array(
            'id',
            'year')) as $s)
        {
            $seasons[$s->id] = $s->year.' / '.($s->year + 1);
        }

        return View::make('admin.widgets.widget_timetable', array(
                    'options'      => $options,
                    'action'       => \URI::current(),
                    'seasons'      => $seasons,
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
        if (\Request::forged() or \Request::method() != 'POST' or !Input::has('competition') or !ctype_digit(Input::get('competition')))
        {
            return false;
        }

        $options = array_merge(array(
            'season'      => null,
            'competition' => 0,
            'distinct'    => false,
            'limit'       => 10,
            'type'        => 'next',
            'type2'       => 's',
            'template'    => 'widgets.timetable'), $this->options);

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

        // limit
        $options['limit'] = (int) Input::get('limit', 0);

        $options['distinct'] = Input::get('distinct', '0') == '1' ? true : false;
        $options['type'] = Input::get('type', 'next') == 'next' ? 'next' : 'prev';
        $options['type2'] = Input::get('type2', 'standard') == 'standard' ? 's' : 'f';

        // template
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.timetable';

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
            'season'      => null,
            'competition' => 0,
            'distinct'    => false,
            'limit'       => 10,
            'type'        => 'next',
            'type2'       => 's',
            'template'    => 'widgets.timetable'), $this->options);

        if (!$options['season'])
        {
            $options['season'] = IoC::resolve('current_season')->id;
        }

        if ($options['limit'] < 0)
        {
            return;
        }

        $timetable = 'timetable-'.$options['type'].$options['type2'].'-'.(int) $options['distinct'].'-'.$options['limit'].'-'.$options['competition'].'-'.$options['season'];

        if (\Cache::has($timetable))
        {
            $timetable = \Cache::get($timetable);
        }
        else
        {
            if ($options['type2'] == 's')
            {
                $timetable = DB::table('matches')->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                        ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
                        ->join('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id')
                        ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id');

                if ($options['competition'])
                    $timetable->where('fixtures.competition_id', '=', $options['competition']);

                $timetable->where('fixtures.season_id', '=', $options['season']);

                if ($options['type'] == 'prev')
                {
                    $timetable->where('matches.score', '<>', '')->order_by('matches.date', 'desc')->where('matches.date', '<=', date('Y-m-d H:i:s'));
                }
                else
                {
                    $timetable->where('matches.score', '=', '')->order_by('matches.date', 'asc')->where('matches.date', '>=', date('Y-m-d H:i:s'));
                }
            }
            else
            {
                $fixture = DB::table('matches')->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                                               ->where('fixtures.season_id', '=', $options['season']);

                $fixture_prev = DB::table('matches')
                                  ->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                                  ->where('fixtures.season_id', '=', $options['season'])
                                  ->where('matches.score', '<>', '')
                                  ->order_by('matches.date', 'desc')
                                  ->where('matches.date', '<=', date('Y-m-d H:i:s'));

                if ($options['competition'])
                {
                    $fixture->where('fixtures.competition_id', '=', $options['competition']);
                    $fixture_prev->where('fixtures.competition_id', '=', $options['competition']);
                }

                $fixture_prev = $fixture_prev->first(array('fixture_id', 'matches.date', 'fixtures.name'));

                if ($options['type'] == 'prev')
                {
                    if ($fixture_prev)
                    {
                        if (strtotime($fixture_prev->date) >= (time() - 86400 * 2))
                        {
                            $fixture = $fixture_prev;
                        }
                        else
                        {
                            $fixture = $fixture->where('matches.score', '=', '')
                                               ->order_by('matches.date', 'asc')
                                               ->where('matches.date', '>=', date('Y-m-d H:i:s'))
                                               ->where('matches.fixture_id', '<>', $fixture_prev->fixture_id)
                                               ->first(array('fixture_id', 'fixtures.name'));
                        }
                    }
                    else
                    {
                        $fixture = $fixture->where('matches.score', '=', '')
                                           ->order_by('matches.date', 'asc')
                                           ->where('matches.date', '>=', date('Y-m-d H:i:s'))
                                           ->first(array('fixture_id', 'fixtures.name'));
                    }
                }
                else
                {
                    if ($fixture_prev)
                    {
                        $fixture = $fixture->where('matches.score', '=', '')
                                           ->order_by('matches.date', 'asc')
                                           ->where('matches.date', '>=', date('Y-m-d H:i:s'))
                                           ->where('matches.fixture_id', '<>', $fixture_prev->fixture_id)
                                           ->first(array('fixture_id', 'fixtures.name'));
                    }
                    else
                    {
                        $fixture = $fixture->where('matches.score', '=', '')
                                           ->order_by('matches.date', 'asc')
                                           ->where('matches.date', '>=', date('Y-m-d H:i:s'))
                                           ->first(array('fixture_id', 'fixtures.name'));
                    }
                }

                if ($fixture)
                {
                    $current_fixture = $fixture->name;
                    $fixture = $fixture->fixture_id;
                }
                else
                {
                    $fixture = 0;
                }

                // find matches
                $timetable = DB::table('matches')->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                        ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
                        ->join('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id')
                        ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                        ->order_by('matches.date', 'asc')
                        ->where('matches.fixture_id', '=', $fixture);
            }

            if ($options['distinct'])
            {
                $timetable->where(function($q)
                        {
                            $q->where('home.is_distinct', '=', 1);
                            $q->or_where('away.is_distinct', '=', 1);
                        });
            }

            $timetable = $timetable->take($options['limit'])->get(array(
                'matches.*',
                'fixtures.name as fixture_name',
                'fixtures.number as fixture_number',
                'competitions.name as competition_name',
                'competitions.slug as competition_slug',
                'home.name as home_name',
                'home.is_distinct as home_is_distinct',
                'home.slug as home_slug',
                'home.image as home_image',
                'away.name as away_name',
                'away.is_distinct as away_is_distinct',
                'away.slug as away_slug',
                'away.image as away_image'
                    ));

            $timetable = (string) View::make($options['template'], array(
                        'timetable' => $timetable));

            \Cache::put('timetable-'.$options['type'].$options['type2'].'-'.(int) $options['distinct'].'-'.$options['limit'].'-'.$options['competition'].'-'.$options['season'], $timetable);
        }

        return $timetable;
    }

}