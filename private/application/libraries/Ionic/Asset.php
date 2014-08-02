<?php
namespace Ionic;

use \HTML;
use \Config;

class Asset {

    protected static $scripts = array();
    protected static $scripts2 = array();
    protected static $styles = array();

    public static function add($name, $source, $dependencies = array(), $attributes = array())
    {
        if (pathinfo($source, PATHINFO_EXTENSION) == 'css')
        {
            self::$styles[$name] = $source;
        }
        else
        {
            self::$scripts[$name] = $source;
        }
    }

    public static function add_nominify($name, $source, $dependencies = array(), $attributes = array())
    {
        if (pathinfo($source, PATHINFO_EXTENSION) == 'css')
        {
            self::$styles[$name] = $source;
        }
        else
        {
            self::$scripts2[$name] = $source;
        }
    }

    public static function scripts()
    {
        $html = '';

        if (Config::get('meta.minify'))
        {
            $minified = array();

            foreach (self::$scripts as $js)
            {
                if (starts_with($js, 'public/js/'))
                {
                    $minified[] = substr($js, 7);
                }
                else
                {
                    $html .= HTML::script($js);
                }
            }

            foreach (self::$scripts2 as $js)
            {
                $html .= HTML::script($js);
            }

            $html = HTML::script('public/min/?f='.implode($minified, ',')).$html;
        }
        else
        {
            foreach (self::$scripts as $js)
            {
                $html .= HTML::script($js);
            }

            foreach (self::$scripts2 as $js)
            {
                $html .= HTML::script($js);
            }
        }

        return $html;
    }

    public static function styles()
    {
        $html = '';

        foreach (self::$styles as $style)
        {
            $html .= HTML::style($style, array('media' => 'all'));
        }

        return $html;
    }
}