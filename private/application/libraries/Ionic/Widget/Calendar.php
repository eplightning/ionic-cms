<?php
namespace Ionic\Widget;

use View;
use Ionic\Widget;
use DB;
use Input;
use URI;
use Request;
use Cache;

class Calendar extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('template' => 'widgets.calendar', 'width' => 64, 'height' => 64), $this->options);

        return View::make('admin.widgets.widget_calendar', array(
            'options' => $options,
            'action'  => URI::current()
        ));
    }

    /**
     * Prepare options field
     *
     * @return string
     */
    public function prepare_options()
    {
        if (Request::forged() or Request::method() != 'POST')
        {
            return false;
        }

        $options = array_merge(array('template' => 'widgets.calendar', 'width' => 64, 'height' => 64), $this->options);

        $options['width'] = (int) Input::get('width', 64);
        $options['height'] = (int) Input::get('height', 64);
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.calendar';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('template' => 'widgets.calendar', 'width' => 64, 'height' => 64), $this->options);

        if (Cache::has('calendar'))
        {
            return Cache::get('calendar');
        }
        else
        {
            $month = date('m');
            $year = date('Y');

            $calendar_data = \Model\Calendar::month_view((int) $month, (int) $year, $options['width'], $options['height']);

            $calendar = (string) View::make($options['template'], array(
                'number_of_days' => $calendar_data['number_of_days'],
                'padding_start' => $calendar_data['padding_start'],
                'padding_end' => $calendar_data['padding_end'],
                'events' => $calendar_data['events'],
                'day' => (int) date('j'),
                'month' => $month,
                'year' => $year
            ));

            Cache::put('calendar', $calendar);

            return $calendar;
        }
    }

}
