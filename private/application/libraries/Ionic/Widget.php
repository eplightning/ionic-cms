<?php
namespace Ionic;

use \Cache;

/**
 * Widget
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class Widget {

    /**
     * Is cache clear
     *
     * @var bool
     */
    protected static $cache_cleared = false;

    /**
     * Cached widgets
     *
     * @var array
     */
    protected static $cached = null;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        return '';
    }

    /**
     * Factory
     *
     * @param string $name
     * @param string $type
     * @param array  $options
     */
    public static function factory($name, $type, array $options = array())
    {
        $fresh = false;

        // No loaded cache?
        if (static::$cached === null)
        {
            // Retrieve cache
            static::$cached = Cache::get('widgets');

            if (static::$cached === null)
            {
                static::$cached = array();
                $fresh = true;

                $cache = \DB::table('widgets')->get(array('title', 'options'));

                foreach ($cache as $r)
                {
                    static::$cached[$r->title] = ($r->options ? unserialize($r->options) : array());
                }

                Cache::put('widgets', static::$cached);
            }
        }

        // Get options from cache or use default
        if (isset(static::$cached[$name]))
        {
            $options = static::$cached[$name];
        }
        elseif ($fresh)
        {
            \DB::table('widgets')->insert(array('title'   => $name, 'type'    => $type,
                'options' => serialize($options)));

            if (!static::$cache_cleared)
            {
                Cache::forget('widgets');
                static::$cache_cleared = true;
            }
        }
        else
        {
            $widget = \DB::table('widgets')->where('title', '=', $name)->first('options');

            if ($widget)
            {
                $options = ($widget->options ? unserialize($widget->options) : array(
                                ));
            }
            else
            {
                \DB::table('widgets')->insert(array('title'   => $name, 'type'    => $type,
                    'options' => serialize($options)));
            }

            if (!static::$cache_cleared)
            {
                Cache::forget('widgets');
                static::$cache_cleared = true;
            }
        }

        $type = ucfirst($type);
        $type = '\\Ionic\\Widget\\'.$type;

        return new $type($options);
    }

    /**
     * Factory (without setting data)
     *
     * @param string $name
     * @param string $type
     * @param array  $options
     */
    public static function factory_simple($name, $type, array $options = array())
    {
        $type = ucfirst($type);
        $type = 'Ionic\\Widget\\'.$type;

        return new $type($options);
    }

    /**
     * Factory
     *
     * @param string $name
     * @param string $type
     * @param array  $options
     */
    public static function factory_show($name, $type, array $options = array())
    {
        if ($type == 'html' and static::$cached !== null and isset(static::$cached[$name]) and isset(static::$cached[$name]['content']))
            return static::$cached[$name]['content'];

        $widget = static::factory($name, $type, $options);

        return $widget->show();
    }

    /**
     * Get human readable name
     *
     * @param string $t
     */
    public static function name($t)
    {
        static $list = array();

        if (empty($list))
        {
            $list = array(
                'photos'    => 'Zdjęcia',
                'html'      => 'Statyczny HTML',
                'files'     => 'Pliki',
                'videos'    => 'Video',
                'buttons'   => 'Buttony',
                'comments'  => 'Komentarze',
                'loginbox'  => 'Panel użytkownika',
                'news'      => 'Newsy',
                'pagestats' => 'Statystyki strony',
                'poll'      => 'Sonda',
                'rotation'  => 'Rotacja',
                'shoutbox'  => 'Shoutbox',
                'users'     => 'Użytkownicy',
                'calendar'  => 'Kalendarz'
            );

            foreach (\Event::fire('ionic.widget_name') as $r)
            {
                if (is_array($r))
                {
                    $list = array_merge($list, $r);
                }
            }
        }

        return isset($list[$t]) ? $list[$t] : $t;
    }

    /**
     * Prepare options field
     *
     * @return string
     */
    public function prepare_options()
    {
        return '';
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        return '';
    }

}
