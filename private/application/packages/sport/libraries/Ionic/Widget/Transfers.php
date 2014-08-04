<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;

class Transfers extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('limit' => 5, 'distinct' => true, 'type' => 'all', 'template' => 'widgets.transfers'), $this->options);

        return View::make('admin.widgets.widget_transfers', array(
                    'options' => $options,
                    'action'  => \URI::current()
                ));
    }

    /**
     * Prepare options field
     *
     * @return string
     */
    public function prepare_options($opt = '')
    {
        if (\Request::forged() or \Request::method() != 'POST')
        {
            return false;
        }

        $options = array_merge(array('limit' => 5, 'distinct' => true, 'type' => 'all', 'template' => 'widgets.transfers'), $this->options);

        $options['distinct'] = Input::get('distinct', '0') == '1' ? true : false;
        $options['limit'] = (int) Input::get('limit', 0);

        if (!in_array(Input::get('type'), array('all', 'from', 'to')))
        {
            $options['type'] = 'all';
        }
        else
        {
            $options['type'] = Input::get('type');
        }

        // template
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.transfers';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('limit' => 5, 'distinct' => true, 'type' => 'all', 'template' => 'widgets.transfers'), $this->options);

        $transfers = 'transfers-'.$options['limit'].'-'.$options['type'].'-'.(int) $options['distinct'];

        if ($options['limit'] <= 0)
        {
            return;
        }

        if (\Cache::has($transfers))
        {
            $transfers = \Cache::get($transfers);
        }
        else
        {
            $transfers = DB::table('player_transfers')->take($options['limit'])
                    ->join('players', 'players.id', '=', 'player_transfers.player_id')
                    ->join('teams as '.DB::prefix().'from', 'from.id', '=', 'player_transfers.from_team')
                    ->join('teams as '.DB::prefix().'to', 'to.id', '=', 'player_transfers.team_id')
                    ->order_by('player_transfers.date', 'desc');

            if ($options['distinct'])
            {
                switch ($options['type'])
                {
                    case 'from':
                        $transfers->where('from.is_distinct', '=', 1);
                        break;

                    case 'to':
                        $transfers->where('to.is_distinct', '=', 1);

                    default:
                        $transfers->where(function($q) {
                                    $q->where('from.is_distinct', '=', 1);
                                    $q->or_where('to.is_distinct', '=', 1);
                                });
                }
            }

            $transfers = $transfers->get(array(
                'player_transfers.id', 'player_transfers.type', 'player_transfers.cost', 'player_transfers.description', 'player_transfers.date',
                'players.name', 'players.image', 'players.slug', 'players.number',
                'from.name as from_name', 'from.image as from_image', 'from.is_distinct as from_is_distinct', 'from.slug as from_slug',
                'to.name as to_name', 'to.image as to_image', 'to.is_distinct as to_is_distinct', 'to.slug as to_slug'
                    ));

            $transfers = (string) View::make($options['template'], array('transfers' => $transfers));

            \Cache::put('transfers-'.$options['limit'].'-'.$options['type'].'-'.(int) $options['distinct'], $transfers);
        }

        return $transfers;
    }

}