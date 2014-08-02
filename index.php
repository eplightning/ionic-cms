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
// Tick... Tock... Tick... Tock...
// --------------------------------------------------------------
define('LARAVEL_START', microtime(true));

// --------------------------------------------------------------
// Set the core Laravel path constants.
// --------------------------------------------------------------
require dirname(__FILE__).DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'paths.php';

// --------------------------------------------------------------
// Serwer ze zle skonfigurowanym FastCGI ,ktory powoduje ze musimy podawac argumenty do routera przez query string ,a nie przez path info
// Co z kolei powoduje ,że psujemy $_GET
// Ten hack próbuje odtworzyć $_GET ręcznie korzystając z $_SERVER['REQUEST_URI']
// --------------------------------------------------------------
// define('HACK_BROKENFCGI', true);

// --------------------------------------------------------------
// Launch Laravel.
// --------------------------------------------------------------
require path('sys').'laravel.php';