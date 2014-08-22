<?php

/**
 * IonicCMS installation framework module: fresh install
 *
 * @package    ionic
 * @subpackage install
 * @copyright  2009-2013 (c) Wrex
 */
class InstallerModuleInstall extends InstallerModule {

    /**
     * Fourth step: Add administrator
     */
    protected function addAdministrator()
    {
        // Try connecting
        $db = new PDO('mysql:host='.$_SESSION['host'].';dbname='.$_SESSION['database'], $_SESSION['user'], $_SESSION['password']);

        // Set
        $this->installer->setDb($db);

        // Message
        $message = '';

        // Submitted?
        if (!empty($_POST['submit']) and !empty($_POST['username']) and !empty($_POST['password']) and !empty($_POST['password2']) and !empty($_POST['email']))
        {
            // Match?
            if ($_POST['password'] == $_POST['password2'])
            {
                if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
                {
                    $this->installer->addUser($_POST['username'], $_POST['password'], $_POST['email']);
                    $this->installer->nextStep();
                }
                else
                {
                    $message = 'Adres e-mail jest podany w nieprawidłowym formacie';
                }
            }
            else
            {
                $message = 'Hasła nie pasują do siebie';
            }
        }

        // View
        $this->installer->parseView('admin', array('message' => $message));
    }

    /**
     * Second step: Basic configuration (database)
     */
    protected function basicConfig()
    {
        // Message
        $message = '';

        // Submitted?
        if (!empty($_POST['submit']) and !empty($_POST['host']) and !empty($_POST['user']) and !empty($_POST['database']))
        {
            if (empty($_POST['prefix']))
            {
                $dbPref = 'ionic_';
            }
            else
            {
                $dbPref = htmlspecialchars($_POST['prefix'], ENT_QUOTES, 'UTF-8');
            }

            $error = false;

            try {
                $db = new PDO('mysql:host='.$_POST['host'].';dbname='.$_POST['database'], $_POST['user'], isset($_POST['password']) ? $_POST['password'] : '');
            } catch (PDOException $e) {
                $error = $e->getMessage();
            }

            if ($error)
            {
                $message = 'Błąd podczas łączenia z bazą danych: '.$error;
            }
            else
            {
                $options = array();

                $emulated = false;

                if (version_compare($db->getAttribute(PDO::ATTR_SERVER_VERSION), '5.1.21', '<'))
                {
                    $options[PDO::ATTR_EMULATE_PREPARES] = true;
                }
                else
                {
                    $options[PDO::ATTR_EMULATE_PREPARES] = false;
                }

                if (!empty($_POST['persistent']) and $_POST['persistent'] == '1')
                {
                    $options[PDO::ATTR_PERSISTENT] = true;
                }

                // Continue
                try {
                    $this->installer->modifyConfig('database', array(
                        'profile'     => false,
                        'fetch'       => PDO::FETCH_CLASS,
                        'default'     => 'mysql',
                        'connections' => array(
                            'mysql' => array(
                                'driver'   => 'mysql',
                                'host'     => $_POST['host'],
                                'database' => $_POST['database'],
                                'username' => $_POST['user'],
                                'password' => isset($_POST['password']) ? $_POST['password'] : '',
                                'charset'  => 'utf8',
                                'prefix'   => $dbPref,
                                'options'  => $options
                            )
                        )
                    ));
                } catch (Exception $e) {
                    exit('Błąd krytyczny: '.$e->getMessage());
                }

                // Set session data
                $_SESSION['host'] = $_POST['host'];
                $_SESSION['user'] = $_POST['user'];
                $_SESSION['password'] = isset($_POST['password']) ? $_POST['password'] : '';
                $_SESSION['database'] = $_POST['database'];
                $_SESSION['prefix'] = $dbPref;

                // Next step
                $this->installer->nextStep();
            }
        }

        // Parse view
        $this->installer->parseView('config', array('message' => $message));
    }

    /**
     * Third step: Database setup
     */
    protected function databaseSetup()
    {
        // Try connecting
        $db = new PDO('mysql:host='.$_SESSION['host'].';dbname='.$_SESSION['database'], $_SESSION['user'], $_SESSION['password']);

        // Charset
        $db->exec('ALTER DATABASE `'.$_SESSION['database'].'` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci');

        // Set database
        $this->installer->setDb($db);

        // Execute
        $this->installer->executeScheme('ionic.sql', $_SESSION['prefix']);

        // Next step
        $this->installer->nextStep(false);

        // Parse view
        $this->installer->parseView('database');
    }

    /**
     * Finalization
     */
    protected function finalStep()
    {
        // Lock installer
        $this->installer->lockInstaller();

        // View
        $this->installer->parseView('finish');
    }

    /**
     * Get step list
     *
     * @return array
     */
    public function getStepList()
    {
        return array(
            1 => 'Sprawdzenie',
            2 => 'Konfiguracja',
            3 => 'Baza danych',
            4 => 'Dodanie administratora',
            5 => 'Koniec'
        );
    }

    /**
     * Handle current step
     */
    public function handle()
    {
        switch ($this->currentStep)
        {
            case 1:
                $this->preinstallCheck();
                break;

            case 2:
                $this->basicConfig();
                break;

            case 3:
                $this->databaseSetup();
                break;

            case 4:
                $this->addAdministrator();
                break;

            case 5:
                $this->finalStep();
                break;
        }
    }

    /**
     * First step: preinstallation check(CHMOD and requirements)
     */
    protected function preinstallCheck()
    {
        $success = true;

        // Server config
        $server = array();

        // PHP version
        if (version_compare(PHP_VERSION, '5.3.0', '>='))
        {
            $server['php'] = '<span style="color: green">'.PHP_VERSION.'</span>';
        }
        else
        {
            $success = false;
            $server['php'] = '<span style="color: red">'.PHP_VERSION.'</span>';
        }

        // UTF-8
        if (!preg_match('/^.$/u', 'ñ'))
        {
            $success = false;
            $server['pcre'] = '<span style="color: red">Nie</span>';
        }
        else
        {
            $server['pcre'] = '<span style="color: green">Tak</span>';
        }

        // mbstring
        if (!extension_loaded('mbstring'))
        {
            $success = false;
            $server['mbstring'] = '<span style="color: red">Nie</span>';
        }
        else
        {
            $server['mbstring'] = '<span style="color: green">Tak</span>';
        }

        // Fileinfo
        if (!function_exists('finfo_open'))
        {
            $server['fileinfo'] = '<span style="color: red">Nie - potencjalne zagrożenie bezpieczeństwa</span>';
        }
        else
        {
            $server['fileinfo'] = '<span style="color: green">Tak</span>';
        }

        // PDO
        if (!class_exists('PDO'))
        {
            $success = false;
            $server['mysql'] = '<span style="color: red">Nie</span>';
        }
        else
        {
            $server['mysql'] = '<span style="color: green">Tak</span>';
        }

        // Register globals
        if (ini_get('register_globals'))
        {
            $server['globals'] = 'Włączone';
        }
        else
        {
            $server['globals'] = '<span style="color: green">Wyłączone</span>';
        }

        // CHMOD check
        $chmod = $this->installer->checkWrite(array(
            'private/application/config/database.php',
            'private/storage/cache',
            'private/storage/files',
            'private/storage/logs',
            'private/storage/min',
            'private/storage/twig',
            'public/upload/avatars',
            'public/upload/calendar',
            'public/upload/calendar/thumbnail',
            'public/upload/files',
            'public/upload/files/thumbnail',
            'public/upload/images',
            'public/upload/photos',
            'public/upload/photos/thumbnail',
            'public/upload/players',
            'public/upload/players/thumbnail',
            'public/upload/teams',
            'public/upload/teams/thumbnail',
            'public/upload'
        ));

        // Success?
        if (!$chmod['success'] and substr(PHP_OS, 0, 3) != 'WIN')
        {
            // My local Windows server setup acts weird when it comes to permissions
            $success = false;
        }

        // Next step?
        if (isset($_GET['nextstep']) and $_GET['nextstep'] == '1' and $success)
        {
            $this->installer->nextStep();
        }

        // Render view
        $this->installer->parseView('check', array(
            'success' => $success,
            'server'  => $server,
            'files'   => $chmod['files']
        ));
    }

}
