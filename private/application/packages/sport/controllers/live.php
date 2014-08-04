<?php

/**
 * Live controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Live_Controller extends Base_Controller {

    /**
     * Listing
     */
    public function action_index()
    {
        $this->page->set_title('Relacje live');
        $this->page->breadcrumb_append('Relacje live', 'live/index');

        $this->view = View::make('live.index', array(
                    'relations' => DB::table('relations')->order_by('relations.id', 'desc')
                            ->join('matches', 'matches.id', '=', 'relations.match_id')
                            ->join('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id')
                            ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                            ->paginate(20, array(
                                'relations.id', 'relations.is_finished', 'relations.current_score', 'home.name as home_name', 'away.name as away_name', 'matches.date'
                            ))
                ));
    }

    /**
     * Refresh relation
     *
     * @param string $relation
     */
    public function action_refresh($relation)
    {
        if (!ctype_digit($relation) or !Request::ajax())
            return Response::error(500);

        $relation = DB::table('relations')->where('relations.id', '=', (int) $relation)
                ->first(array(
            'relations.id'
                ));

        if (!$relation)
            return Response::error(404);

        return Response::make(View::make('live.refresh', array(
                            'messages' => DB::table('relation_messages')->where('relation_id', '=', $relation->id)->order_by('minute', 'desc')->order_by('id', 'desc')->get('*'),
                            'types'    => array(
                                'standard'          => 'Zwykła',
                                'yellow_card'       => 'Żółta kartka',
                                'red_card'          => 'Czerwona kartka',
                                'doubleyellow_card' => 'Druga żółta kartka',
                                'penalty_kick'      => 'Rzut karny',
                                'free_kick'         => 'Rzut wolny',
                                'corner_kick'       => 'Rzut rożny',
                                'goal'              => 'Bramka',
                                'injury'            => 'Kontuzja',
                                'change'            => 'Zmiana'
                            )
                        )));
    }

    /**
     * Show relation
     *
     * @param string $relation
     */
    public function action_show($relation)
    {
        if (!ctype_digit($relation))
            return Response::error(500);

        $relation = DB::table('relations')->where('relations.id', '=', (int) $relation)
                ->join('matches', 'matches.id', '=', 'relations.match_id')
                ->join('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id')
                ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                ->first(array(
            'relations.id', 'relations.current_score', 'relations.is_finished',
            'home.name as home_name', 'home.image as home_image',
            'away.name as away_name', 'away.image as away_image'
                ));

        if (!$relation)
            return Response::error(404);

        $players = array();
        $cards = array(0 => array(), 1 => array());
        $goals = array(0 => array(), 1 => array());
        $changes = array(0 => array(), 1 => array());
        $team_players = array(0 => array(), 1 => array(), 2 => array(), 3 => array());

        foreach (DB::table('relation_players')->where('relation_id', '=', $relation->id)->order_by('sorting', 'asc')->get('*') as $p)
        {
            $players[$p->id] = $p;
            $team_players[($p->team == 0 ? ($p->squad == 0 ? 0 : 1) : ($p->squad == 0 ? 2 : 3))][$p->id] = array(
                'name' => str_replace(',', '', $p->name),
                'number' => $p->number,
                'goals' => 0,
                'red_cards' => 0,
                'yellow_cards' => 0,
                'change_in' => false,
                'change_out' => false
            );
        }

        foreach (DB::table('relation_events')->where('relation_id', '=', $relation->id)->order_by('minute', 'asc')->get('*') as $e)
        {
            // errors
            if (!isset($players[$e->player_id]))
                continue;
            if (empty($e->data))
                continue;
            $data = unserialize($e->data);
            if (empty($data))
                continue;

            if ($players[$e->player_id]->team == 0)
            {
                $tid = ($players[$e->player_id]->squad == 0) ? 0 : 1;
            }
            else
            {
                $tid = ($players[$e->player_id]->squad == 0) ? 2 : 3;
            }

            if ($e->type == 0) // goal
            {
                $team_players[$tid][$e->player_id]['goals']++;

                $goals[$players[$e->player_id]->team][$e->id] = array(
                    'name'   => $players[$e->player_id]->name,
                    'type'   => $data['type'],
                    'minute' => $e->minute
                );
            }
            elseif ($e->type == 1) // card
            {
                $team_players[$tid][$e->player_id][$data['type'] == 'red' ? 'red_cards' : 'yellow_cards']++;

                $cards[$players[$e->player_id]->team][$e->id] = array(
                    'name'   => $players[$e->player_id]->name,
                    'type'   => ($data['type'] == 'red' ? 'red' : 'yellow'),
                    'minute' => $e->minute
                );
            }
            else // change
            {
                // data integrity error
                if (!isset($players[$data['new_player']]))
                    continue;

                $team_players[$tid][$e->player_id]['change_out'] = true;

                if ($players[$data['new_player']]->team == 0)
                {
                    $team_players[$players[$data['new_player']]->squad == 0 ? 0 : 1][$data['new_player']]['change_in'] = true;
                }
                else
                {
                    $team_players[$players[$data['new_player']]->squad == 0 ? 2 : 3][$data['new_player']]['change_in'] = true;
                }

                $changes[$players[$e->player_id]->team][$e->id] = array(
                    'name'       => $players[$e->player_id]->name,
                    'new_player' => $players[$data['new_player']]->name,
                    'minute'     => $e->minute
                );
            }
        }

        $this->page->set_title('Relacja live');
        $this->page->breadcrumb_append('Relacje live', 'live/index');
        $this->page->breadcrumb_append('Relacja live', 'live/show/'.$relation->id);
        $this->online('Relacja live', 'live/show/'.$relation->id);

        $this->view = View::make('live.show', array(
                    'cards'    => $cards,
                    'goals'    => $goals,
                    'changes'  => $changes,
                    'players'  => $team_players,
                    'relation' => $relation,
                    'messages' => DB::table('relation_messages')->where('relation_id', '=', $relation->id)->order_by('minute', 'desc')->order_by('id', 'desc')->get('*'),
                    'refresh'  => (int) Config::get('advanced.relation_refresh', 0),
                    'types'    => array(
                        'standard'          => 'Zwykła',
                        'yellow_card'       => 'Żółta kartka',
                        'red_card'          => 'Czerwona kartka',
                        'doubleyellow_card' => 'Druga żółta kartka',
                        'penalty_kick'      => 'Rzut karny',
                        'free_kick'         => 'Rzut wolny',
                        'corner_kick'       => 'Rzut rożny',
                        'goal'              => 'Bramka',
                        'injury'            => 'Kontuzja',
                        'change'            => 'Zmiana'
                    )
                ));
    }

}