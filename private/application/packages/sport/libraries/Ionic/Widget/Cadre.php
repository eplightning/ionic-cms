<?php
namespace Ionic\Widget;

use DateTime;
use View;
use Ionic\Widget;
use DB;
use Input;
use IoC;
use Cache;
use URI;
use Request;

class Cadre extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('team' => null, 'limit' => 0, 'sort' => 'number', 'template' => 'widgets.cadre'), $this->options);

        foreach (DB::table('teams')->get(array('id', 'name')) as $s)
        {
            $teams[$s->id] = $s->name;
        }

        return View::make('admin.widgets.widget_cadre', array(
            'options'      => $options,
            'action'       => URI::current(),
            'teams'        => $teams
        ));
    }

    /**
     * Prepare options field
     *
     * @return string
     */
    public function prepare_options()
    {
        if (Request::forged() or Request::method() != 'POST' or !Input::has('team') or !ctype_digit(Input::get('team')))
            return false;

        $options = array_merge(array('team' => null, 'limit' => 0, 'sort' => 'number', 'template' => 'widgets.cadre'), $this->options);

        // team
        $c = Input::get('team');
        $c = DB::table('teams')->where('id', '=', (int) $c)->first('id');

        if (!$c)
            return false;

        $options['team'] = $c->id;

        // limit
        $options['limit'] = (int) Input::get('limit', 0);

        // sorting
        if (!Input::has('sort') or !in_array(Input::get('sort'), array('id', 'number', 'name')))
        {
            $options['sort'] = 'number';
        }
        else
        {
            $options['sort'] = Input::get('sort');
        }

        // template
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.cadre';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('team' => null, 'limit' => 0, 'sort' => 'number', 'template' => 'widgets.cadre'), $this->options);

        if (!$options['team'])
        {
            return;
        }

        if ($options['limit'] < 0)
        {
            return;
        }

        $cadre = 'cadre-'.$options['sort'].'-'.$options['limit'].'-'.$options['team'];

        if (($cadre = Cache::get($cadre)) === null)
        {
            $players = DB::table('players')->where('team_id', '=', $options['team'])->take($options['limit'])->order_by($options['sort'], 'asc')->get('*');
            $grouped = array();

            $now = new DateTime('now');

            foreach ($players as $p)
            {
                if (!isset($grouped[$p->position]))
                    $grouped[$p->position] = array();

                $grouped[$p->position][] = $p;

                if ($p->date != '0000-00-00') {
                    $date = new DateTime($p->date);

                    $interval = $date->diff($now, true);

                    $p->age = $interval->y;
                } else {
                    $p->age = 0;
                }
            }

            $field = $options['sort'];

            uasort($grouped, function($a, $b) use ($field) {
                $posa = $a[0]->position;
                $posb = $b[0]->position;

                if ($posa == $posb)
                    return 0;

                // Soccer positions
                if ($posa == 'Bramkarz')
                    return -1;
                if ($posb == 'Bramkarz')
                    return 1;
                if ($posa == 'Napastnik')
                    return 1;
                if ($posb == 'Napastnik')
                    return -1;
                if ($posa == 'Obrońca')
                    return -1;
                if ($posb == 'Obrońca')
                    return 1;

                // Fallback to numbers
                if ($a[0]->$field == $b[0]->$field)
                    return 0;

                return ($a[0]->$field > $b[0]->$field ? 1 : -1);
            });

            $cadre = View::make($options['template'], array('players' => $players, 'grouped' => $grouped))->render();

            Cache::put('cadre-'.$options['sort'].'-'.$options['limit'].'-'.$options['team'], $cadre);
        }

        return $cadre;
    }
}
