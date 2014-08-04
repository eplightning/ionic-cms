<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;

class Relation extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('template' => 'widgets.relation'), $this->options);

        return View::make('admin.widgets.widget_relation', array(
                    'action'  => \URI::current(),
                    'options' => $options
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

        $options = array_merge(array('template' => 'widgets.relation'), $this->options);

        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.relation';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('template' => 'widgets.relation'), $this->options);

        if (\Cache::has('last-relation'))
        {
            $relation = \Cache::get('last-relation');
        }
        else
        {
            $relation = DB::table('relations')->join('matches', 'matches.id', '=', 'relations.match_id')
                    ->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                    ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
                    ->join('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id')
                    ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                    ->where('relations.is_finished', '=', 0)
                    ->where('matches.score', '=', '')
                    ->order_by('matches.date', 'asc')
                    ->first(array(
                'matches.*', 'relations.current_score',
                'fixtures.name as fixture_name', 'fixtures.number as fixture_number',
                'competitions.name as competition_name',
                'home.name as home_name', 'home.is_distinct as home_is_distinct', 'home.slug as home_slug', 'home.image as home_image',
                'away.name as away_name', 'away.is_distinct as away_is_distinct', 'away.slug as away_slug', 'away.image as away_image'
                    ));

            if (!$relation)
                $relation = false;

            $relation = (string) View::make($options['template'], array('relation' => $relation));

            \Cache::put('last-relation', $relation);
        }

        return $relation;
    }

}