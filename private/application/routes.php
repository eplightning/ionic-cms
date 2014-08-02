<?php
// Index
Route::get(array('/', 'index'), function() {
    if (Config::get('homepage.use_splash', false))
    {
        return View::make('splash');
    }

    $homepage = explode('@', Config::get('homepage.uri', 'news@index'));
    $count = count($homepage);

    if ($count < 2)
    {
        return Controller::call($homepage[0].'@index');
    }
    elseif ($count == 2)
    {
        return Controller::call($homepage[0].'@'.$homepage[1]);
    }
    else
    {
        $parameters = array();

        for ($i = 2; $i < $count; $i++)
        {
            $parameters[] = $homepage[$i];
        }

        return Controller::call($homepage[0].'@'.$homepage[1], $parameters);
    }
});

// admin/Controller/Action/Param1/Param2/Param3
Router::register(array('GET', 'POST'), 'admin(?:/([a-zA-Z0-9_]+)(?:/([a-zA-Z0-9_]+)/(:any?)/(:any?)/(:any?))?)?', function ($controller = null, $action = null, $param1 = null, $param2 = null, $param3 = null) {
    if (!$controller or $controller == 'index')
    {
        return Controller::call('admin@index');
    }
    elseif ($controller == 'logout')
    {
        return Controller::call('admin@logout');
    }
    else
    {
        $controller = 'admin.'.$controller;

        if (!$action)
            $action = 'index';

        $params = array();

        if ($param1 !== null)
            $params[] = $param1;
        if ($param2 !== null)
            $params[] = $param2;
        if ($param3 !== null)
            $params[] = $param3;

        return Controller::call($controller.'@'.$action, $params);
    }
});

// Controller/Action/Param1/Param2/Param3
Router::register(array('GET', 'POST'), '([a-zA-Z0-9_]+)(?:/([a-zA-Z0-9_]+)/(:any?)/(:any?)/(:any?))?', function ($controller = null, $action = null, $param1 = null, $param2 = null, $param3 = null) {
    if (!$action)
        $action = 'index';

    $params = array();

    if ($param1 !== null)
        $params[] = $param1;
    if ($param2 !== null)
        $params[] = $param2;
    if ($param3 !== null)
        $params[] = $param3;

    return Controller::call($controller.'@'.$action, $params);
});

Event::listen('403', function() {
    return Response::error(403);
});

Event::listen('404', function() {
    return Response::error(404);
});

Event::listen('500', function() {
    return Response::error(500);
});

// IP bans
Route::filter('before', function() {
    // IP bans
    $bans = ionic_normalize_lines(Config::get('bans.banned_ips', ''));

    if (!empty($bans))
    {
        $bans = explode("\n", $bans);

        if (in_array(Request::ip(), $bans))
        {
            return Response::make(View::make('banned_ip', array('message' => Config::get('bans.ip_ban_message', ''))));
        }
    }
});

// Set charset and optionally enable GZIP compression
Route::filter('after', function($response) {
    $response->foundation->setCharset('UTF-8');

    if (Config::get('application.gzip') and !ini_get('zlib.output_compression') and function_exists('gzdeflate'))
    {
        ob_start('ob_gzhandler');
    }
});