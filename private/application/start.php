<?php
Laravel\Autoloader::$aliases = Laravel\Config::get('application.aliases');

Autoloader::map(array(
    'TCPDF'            => path('app').'vendor'.DS.'tcpdf'.DS.'tcpdf.php',
    'Swift'            => path('app').'vendor'.DS.'SwiftMailer'.DS.'classes'.DS.'Swift.php',
    'WideImage'        => path('app').'vendor'.DS.'WideImage'.DS.'WideImage.php',
    'Admin_Controller' => path('app').'controllers'.DS.'admin.php',
    'Base_Controller'  => path('app').'controllers'.DS.'base.php',
    'Mobile_Detect'    => path('app').'libraries'.DS.'Mobile_Detect.php',
));

Autoloader::directories(array(
    path('app').'libraries'
));

Autoloader::namespaces(array(
    'Model' => path('app').'models'
));

Autoloader::underscored(array(
    'Twig'  => path('app').'vendor'.DS.'Twig',
    'Swift' => path('app').'vendor'.DS.'SwiftMailer'.DS.'classes'.DS.'Swift'
));

Event::listen(Lang::loader, function($bundle, $language, $file) {
    return Lang::file($bundle, $language, $file);
});

if (Config::get('application.profiler'))
{
    Profiler::attach();
}

setlocale(LC_ALL, Config::get('application.locale'));

require path('app').'helpers.php';

// SwiftMailer - need to be called before creating messages of any kind...
IoC::singleton('mailer', function() {
    // Load and init SwiftMailer
    require path('app').'vendor'.DS.'SwiftMailer'.DS.'swift_init.php';

    Swift::init(function (){});

    // Create mailer with correct transport
    if (Config::get('email.type') == 'mail')
    {
        return Swift_Mailer::newInstance(Swift_MailTransport::newInstance());
    }
    elseif (Config::get('email.type') == 'sendmail')
    {
        return Swift_Mailer::newInstance(Swift_SendmailTransport::newInstance(Config::get('email.sendmail')));
    }
    elseif (Config::get('email.type') == 'none')
    {
        return Swift_Mailer::newInstance(Swift_NullTransport::newInstance());
    }
    else
    {
        return Swift_Mailer::newInstance(Swift_SmtpTransport::newInstance(Config::get('email.host'), Config::get('email.port'), Config::get('email.encryption'))
                                                            ->setUsername(Config::get('email.username'))
                                                            ->setPassword(Config::get('email.password')));
    }
});

// Twig
IoC::singleton('twig', function() {
    // Create environment
    if (IoC::resolve('page')->is_mobile)
    {
        $twig = new Twig_Environment(new Twig_Loader_Filesystem(array(path('app').'views_mobile', path('app').'views')), array(
            'cache'       => path('storage').'twig',
            'autoescape'  => false,
            'auto_reload' => true
        ));
    }
    else
    {
        $twig = new Twig_Environment(new Twig_Loader_Filesystem(path('app').'views'), array(
            'cache'       => path('storage').'twig',
            'autoescape'  => false,
            'auto_reload' => true
        ));
    }

    // Register extension
    $twig->addExtension(new Ionic\TwigExtension);

    // Register CSRF key as global variable
    $twig->addGlobal('csrf_key', Session::csrf_token);

    // Return instance
    return $twig;
});

IoC::singleton('page', function() {
    return new Ionic\Page;
});

IoC::singleton('notifications', function() {
    return new Ionic\Notifications;
});

IoC::singleton('online', function() {
    return new Ionic\Online;
});

// We need config before we do any package initialization
$config = Cache::get('db-config');

if ($config)
{
    Config::set_from_array($config);
}
else
{
    $config = Model\Config::retrieve();

    Config::set_from_array($config);

    Cache::put('db-config', $config);
}

unset($config);

// Update timezone
date_default_timezone_set(Config::get('application.timezone'));

// Load packages
Ionic\Package::load();
