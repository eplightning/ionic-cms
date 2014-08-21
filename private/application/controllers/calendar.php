<?php

/**
 * Calendar
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Calendar_Controller extends Base_Controller {

    /**
     * Display single day
     *
     * @param   string  $id
     */
    public function action_day($date)
    {
        if (!preg_match('!^(20[0-9]{2}|19[7-9][0-9])-[0-9]{2}-[0-9]{1,2}$!', $date))
            return Response::error(500);

        $date = strtotime($date);

        if (!$date)
            return Response::error(500);

        $date_ymd = date('Y-m-d', $date);
        $date = ionic_date($date_ymd, 'short');

        $this->page->set_title('Kalendarz - '.$date);
        $this->page->breadcrumb_append('Kalendarz', 'calendar');
        $this->page->breadcrumb_append('Kalendarz - '.$date, 'calendar/day/'.$date_ymd);
        $this->online('Kalendarz - '.$date, 'calendar/day/'.$date_ymd);

        $this->view = View::make('calendar.day', array('events' => Model\Calendar::day_view($date_ymd, 64, 64), 'date' => $date));
    }

    /**
     * Display single event
     *
     * @param   string  $id
     */
    public function action_event($id)
    {
        if (!ctype_digit($id))
            return Response::error(500);

        $id = DB::table('calendar')->where('handler', '=', 'event')->where('id', '=', (int) $id)->first(array('*'));

        if (!$id or empty($id->options))
            return Response::error(404);

        $options = unserialize($id->options);

        if (empty($options['content']) or !empty($options['url']))
            return Redirect::to('calendar');

        $this->page->set_title('Wydarzenie - '.$id->title);
        $this->page->breadcrumb_append('Kalendarz', 'calendar');
        $this->page->breadcrumb_append('Wydarzenie - '.$id->title, 'calendar/event/'.$id->id);
        $this->online('Wydarzenie - '.$id->title, 'calendar/event/'.$id->id);

        $this->view = View::make('calendar.event', array('event' => $id, 'options' => $options));
    }

    /**
     * Display calendar
     *
     * @return Response
     */
    public function action_index($month = '', $year = '')
    {
        switch ($month)
        {
            case 'jan': $month = '01'; break;
            case 'feb': $month = '02'; break;
            case 'mar': $month = '03'; break;
            case 'apr': $month = '04'; break;
            case 'may': $month = '05'; break;
            case 'jun': $month = '06'; break;
            case 'jul': $month = '07'; break;
            case 'aug': $month = '08'; break;
            case 'sep': $month = '09'; break;
            case 'oct': $month = '10'; break;
            case 'nov': $month = '11'; break;
            case 'dec': $month = '12'; break;
            default: $month = date('m');
        }

        $year = (int) $year;

        if ($year > 2100 or $year < 1970)
        {
            $year = (int) date('Y');
        }

        $calendar_data = Model\Calendar::month_view((int) $month, $year, 64, 64);

        $this->page->set_title('Kalendarz');
        $this->page->breadcrumb_append('Kalendarz', 'calendar');
        $this->online('Kalendarz', 'calendar');

        $this->view = View::make('calendar.index', array(
            'number_of_days' => $calendar_data['number_of_days'],
            'padding_start' => $calendar_data['padding_start'],
            'padding_end' => $calendar_data['padding_end'],
            'events' => $calendar_data['events'],
            'day' => (date('Y-m') == $year.'-'.$month) ? (int) date('j') : 0,
            'month' => $month,
            'year' => $year
        ));
    }
}
