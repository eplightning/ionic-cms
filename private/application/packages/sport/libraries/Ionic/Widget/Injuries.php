<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;

class Injuries extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('limit'    => 5, 'distinct' => true, 'template' => 'widgets.injuries'), $this->options);

        return View::make('admin.widgets.widget_injuries', array(
                    'options' => $options,
                    'action'  => \URI::current()
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

        $options = array_merge(array('limit'    => 5, 'distinct' => true, 'template' => 'widgets.injuries'), $this->options);

        $options['distinct'] = Input::get('distinct', '0') == '1' ? true : false;
        $options['limit'] = (int) Input::get('limit', 0);

        // template
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.injuries';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('limit'    => 5, 'distinct' => true, 'template' => 'widgets.injuries'), $this->options);

        $injuries = 'injuries-'.$options['limit'].'-'.(int) $options['distinct'];

        if ($options['limit'] <= 0)
        {
            return;
        }

        if (\Cache::has($injuries))
        {
            return \Cache::get($injuries);
        }
        else
        {
            $injuries = DB::table('player_injuries')->take($options['limit'])
                    ->join('players', 'players.id', '=', 'player_injuries.player_id')
                    ->join('teams', 'teams.id', '=', 'players.team_id')
                    ->order_by('player_injuries.recovery_date', 'asc')
                    ->where(function($q) {
                        $q->where('player_injuries.recovery_date', '>=', date('Y-m-d'));
                        $q->or_where('player_injuries.recovery_date', '=', '0000-00-00');
                        $q->or_where('player_injuries.recovery_date', '=', date('Y-m-').'00');
                    });

            if ($options['distinct'])
            {
                $injuries->where('teams.is_distinct', '=', 1);
            }

            $injuries = $injuries->get(array(
                'player_injuries.id', 'player_injuries.injury', 'player_injuries.recovery_date',
                'players.name', 'players.image', 'players.slug', 'players.number',
                'teams.name as team_name', 'teams.image as team_image', 'teams.is_distinct as team_is_distinct', 'teams.slug as team_slug'
            ));

            $injuries = (string) View::make($options['template'], array('injuries' => $injuries));

            \Cache::put('injuries-'.$options['limit'].'-'.(int) $options['distinct'], $injuries);

            return $injuries;
        }
    }

}
