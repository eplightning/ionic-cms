<?php
/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @version  3.2.5
 * @author   Taylor Otwell <taylorotwell@gmail.com>
 * @link     http://laravel.com
 */

// --------------------------------------------------------------
// Change to the current working directory.
// --------------------------------------------------------------
chdir(__DIR__);

// --------------------------------------------------------------
// Define the directory separator for the environment.
// --------------------------------------------------------------
if ( ! defined('DS'))
{
    define('DS', DIRECTORY_SEPARATOR);
}

// --------------------------------------------------------------
// Define the path to the base directory.
// --------------------------------------------------------------
$GLOBALS['laravel_paths'] = array(
    'base' => __DIR__.DS,
    'app' => __DIR__.DS.'application'.DS,
    'sys' => __DIR__.DS.'laravel'.DS,
    'bundle' => __DIR__.DS.'bundles'.DS,
    'storage' => __DIR__.DS.'storage'.DS,
    'public' => realpath('./../public').DS
);

/**
 * A global path helper function.
 *
 * <code>
 *     $storage = path('storage');
 * </code>
 *
 * @param  string  $path
 * @return string
 */
function path($path)
{
    return $GLOBALS['laravel_paths'][$path];
}

/**
 * A global path setter function.
 *
 * @param  string  $path
 * @param  string  $value
 * @return void
 */
function set_path($path, $value)
{
    $GLOBALS['laravel_paths'][$path] = $value;
}
