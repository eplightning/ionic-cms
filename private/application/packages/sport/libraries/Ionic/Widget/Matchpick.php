<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;
use Cache;

class Matchpick extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('template' => 'widgets.matchpick'), $this->options);

        return View::make('admin.widgets.widget_matchpick', array(
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

        $options = array_merge(array('template' => 'widgets.matchpick'), $this->options);

        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.matchpick';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('template' => 'widgets.matchpick'), $this->options);

        if (!($matchpick = Cache::get('matchpick')))
        {
            $matchpick = array('player'        => null, 'active'        => new \Model\Matchpick, 'player_rating' => 0);

            $matchpick['player'] = DB::table('matchpicks')->where('best_player_id', '<>', 0)->join('players', 'players.id', '=', 'matchpicks.best_player_id')
                    ->where('is_active', '=', 0)->order_by('matchpicks.created_at', 'desc')
                    ->first(array('matchpicks.title', 'players.*', 'matchpicks.options'));

            if ($matchpick['player'])
            {
                $opt = $matchpick['player']->options ? unserialize($matchpick['player']->options) : array();

                if (isset($opt[(int) $matchpick['player']->id]))
                {
                    $matchpick['player_rating'] = $opt[(int) $matchpick['player']->id]['rating'];
                }

                unset($opt);
            }

            \Cache::put('matchpick', $matchpick);
        }

        return View::make($options['template'], array('matchpick' => $matchpick))->render();
    }

}
