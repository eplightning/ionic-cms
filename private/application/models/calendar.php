<?php
namespace Model;

use DB;
use Event;

/**
 * Calendar API
 *
 * @package Model
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class Calendar {

    /**
     * Retrieve events for a day
     *
     * @param   string  $date
     * @param   int     $image_width
     * @param   int     $image_height
     * @return  array
     */
    public static function day_view($date, $image_width = 64, $image_height = 64)
    {
        $handlers = array();

        foreach (Event::fire('ionic.calendar_handler') as $r)
        {
            if (is_array($r))
            {
                $handlers = array_merge($handlers, $r);
            }
        }

        $events = array();

        foreach (self::get_events($date, $date) as $ev)
        {
            if ($ev->handler == 'event')
            {
                $image = null;

                if (!empty($ev->options))
                {
                    $options = unserialize($ev->options);
                    $image = !empty($options['image']) ? ionic_thumb('calendar', $options['image'], $image_width.'x'.$image_height) : null;
                    unset($options);
                }

                $events[] = array(
                    'title' => $ev->title,
                    'details' => null,
                    'url' => 'calendar/event/'.$ev->id,
                    'image' => $image
                );
            }
            elseif (!empty($handlers[$ev->handler]))
            {
                foreach ($handlers[$ev->handler]->collect_events($ev, $date, $date, $image_width, $image_height) as $col)
                {
                    $events[] = $col;
                }
            }
        }

        return $events;
    }

    /**
     * Get events from database
     *
     * @param   string  $date_start
     * @param   string  $date_end
     * @return  mixed
     */
    public static function get_events($date_start, $date_end)
    {
        return DB::table('calendar')->where('date_start', '<=', $date_end)
                                    ->where('date_end', '>=', $date_start)
                                    ->order_by('id', 'desc')
                                    ->take(100) // sanity check
                                    ->get('*');
    }

    /**
     * Retrieve events for month
     *
     * @param   int     $month
     * @param   int     $year
     * @param   int     $image_width
     * @param   int     $image_height
     * @return  array
     */
    public static function month_view($month, $year, $image_width = 64, $image_height = 64)
    {
        $first_day = mktime(8, 0, 0, $month, 1, $year);
        $first_day_of_the_month = (int) date('N', $first_day);
        $number_of_days = (int) date('t', $first_day);

        $first_day_date = date('Y-m-d', $first_day);
        $last_day_date = date('Y-m-d', mktime(8, 0, 0, $month, $number_of_days, $year));

        $events = array();

        for ($i = 1; $i <= $number_of_days; $i++)
        {
            $events[$i] = array();
        }

        $handlers = array();

        foreach (Event::fire('ionic.calendar_handler') as $r)
        {
            if (is_array($r))
            {
                $handlers = array_merge($handlers, $r);
            }
        }

        foreach (self::get_events($first_day_date, $last_day_date) as $ev)
        {
            if ($ev->handler == 'event')
            {
                $image = null;
                $url = 'calendar/event/'.$ev->id;

                if (!empty($ev->options))
                {
                    $options = unserialize($ev->options);
                    $image = !empty($options['image']) ? ionic_thumb('calendar', $options['image'], $image_width.'x'.$image_height) : null;

                    if (!empty($options['url']))
                        $url = $options['url'];

                    unset($options);
                }

                $day = (int) substr($ev->date_start, 8);

                $events[$day][] = array(
                    'day' => $day,
                    'title' => $ev->title,
                    'details' => null,
                    'url' => $url,
                    'image' => $image
                );
            }
            elseif (!empty($handlers[$ev->handler]))
            {
                foreach ($handlers[$ev->handler]->collect_events($ev, $first_day_date, $last_day_date, $image_width, $image_height) as $col)
                {
                    $events[$col['day']][] = $col;
                }
            }
        }

        $padding_start = $first_day_of_the_month - 1;
        $padding_end = ceil(($padding_start + $number_of_days) / 7);
        $padding_end = (7 * $padding_end) - ($padding_start + $number_of_days);

        return array(
            'number_of_days' => $number_of_days,
            'padding_start'  => $padding_start,
            'padding_end'    => $padding_end,
            'events'         => $events
        );
    }
}
