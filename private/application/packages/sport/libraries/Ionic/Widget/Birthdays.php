<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;
use \Cache;

class Birthdays extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array(
            'limit'    => 5,
            'distinct' => true,
            'template' => 'widgets.birthdays'), $this->options);

        return View::make('admin.widgets.widget_birthdays', array(
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

        $options = array_merge(array(
            'limit'    => 5,
            'distinct' => true,
            'template' => 'widgets.birthdays'), $this->options);

        $options['distinct'] = Input::get('distinct', '0') == '1' ? true : false;
        $options['limit'] = (int) Input::get('limit', 0);
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.birthdays';

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
            'limit'    => 5,
            'distinct' => true,
            'template' => 'widgets.birthdays'), $this->options);

        $birthdays = 'birthdays-'.$options['limit'].'-'.(int) $options['distinct'];

        if ($options['limit'] <= 0)
        {
            return;
        }

        if (($birthdays = Cache::get($birthdays)) === null)
        {
            $birthdays = DB::query("SELECT     players.*, teams.name AS team_name, teams.slug AS team_slug, teams.image AS team_image,
			                                   players.date + INTERVAL(YEAR(CURRENT_TIMESTAMP) - YEAR(players.date)) + 0 YEAR AS currbirthday,
                                               players.date + INTERVAL(YEAR(CURRENT_TIMESTAMP) - YEAR(players.date)) + 1 YEAR AS nextbirthday
			                        FROM       ".DB::prefix()."players AS players
			                        INNER JOIN ".DB::prefix()."teams AS teams ON (teams.id = players.team_id)
			                        WHERE      players.date <> '0000-00-00'
                                    ".($options['distinct'] ? "AND teams.is_distinct = 1" : "")."
                                    ORDER BY   CASE WHEN currbirthday < CURRENT_TIMESTAMP
                                    THEN       nextbirthday
                                    ELSE       currbirthday
                                    END
			                        LIMIT      ".(int) $options['limit']);

            $birthdays = View::make($options['template'], array('birthdays' => $birthdays))->render();

            Cache::put('birthdays-'.$options['limit'].'-'.(int) $options['distinct'], $birthdays);
        }

        return $birthdays;
    }

}
