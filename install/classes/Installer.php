<?php

/**
 * IonicCMS installation framework core
 *
 * @package    ionic
 * @subpackage install
 * @copyright  2009-2013 (c) Wrex
 */
class Installer {

    /**
     * @var PDO
     */
    protected $db = NULL;

    /**
     * @var bool
     */
    protected $next_step = false;

    /**
     * @var string
     */
    protected $view = '';

    /**
     * Add user
     *
     * @param  string  $login
     * @param  string  $password
     * @param  string  $email
     * @param  boolean $admin [optional]
     */
    public function addUser($login, $password, $email, $admin = true)
    {
        // DB initialised
        if (!($this->db instanceof PDO))
        {
            throw new Exception('Database connection problem');
        }

        // Laravel Hash and Str classes
        require_once IONIC_PATH.'private/laravel/str.php';
        require_once IONIC_PATH.'private/laravel/hash.php';

        // Determine prefix
        $prefix = isset($_SESSION['prefix']) ? $_SESSION['prefix'] : 'ionic_';

        // We don't want HTML here
        $login = htmlspecialchars($login, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

        // Creating slug is quite a bitch
        $ascii = array(
            '/æ|ǽ/'                               => 'ae',
            '/œ/'                                 => 'oe',
            '/À|Á|Â|Ã|Ä|Å|Ǻ|Ā|Ă|Ą|Ǎ|А/'           => 'A',
            '/à|á|â|ã|ä|å|ǻ|ā|ă|ą|ǎ|ª|а/'         => 'a',
            '/Б/'                                 => 'B',
            '/б/'                                 => 'b',
            '/Ç|Ć|Ĉ|Ċ|Č|Ц/'                       => 'C',
            '/ç|ć|ĉ|ċ|č|ц/'                       => 'c',
            '/Ð|Ď|Đ|Д/'                           => 'Dj',
            '/ð|ď|đ|д/'                           => 'dj',
            '/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě|Е|Ё|Э/'           => 'E',
            '/è|é|ê|ë|ē|ĕ|ė|ę|ě|е|ё|э/'           => 'e',
            '/Ф/'                                 => 'F',
            '/ƒ|ф/'                               => 'f',
            '/Ĝ|Ğ|Ġ|Ģ|Г/'                         => 'G',
            '/ĝ|ğ|ġ|ģ|г/'                         => 'g',
            '/Ĥ|Ħ|Х/'                             => 'H',
            '/ĥ|ħ|х/'                             => 'h',
            '/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ|И/'             => 'I',
            '/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı|и/'             => 'i',
            '/Ĵ|Й/'                               => 'J',
            '/ĵ|й/'                               => 'j',
            '/Ķ|К/'                               => 'K',
            '/ķ|к/'                               => 'k',
            '/Ĺ|Ļ|Ľ|Ŀ|Ł|Л/'                       => 'L',
            '/ĺ|ļ|ľ|ŀ|ł|л/'                       => 'l',
            '/М/'                                 => 'M',
            '/м/'                                 => 'm',
            '/Ñ|Ń|Ņ|Ň|Н/'                         => 'N',
            '/ñ|ń|ņ|ň|ŉ|н/'                       => 'n',
            '/Ö|Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ|О/'         => 'O',
            '/ö|ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º|о/'       => 'o',
            '/П/'                                 => 'P',
            '/п/'                                 => 'p',
            '/Ŕ|Ŗ|Ř|Р/'                           => 'R',
            '/ŕ|ŗ|ř|р/'                           => 'r',
            '/Ś|Ŝ|Ş|Ș|Š|С/'                       => 'S',
            '/ś|ŝ|ş|ș|š|ſ|с/'                     => 's',
            '/Ţ|Ț|Ť|Ŧ|Т/'                         => 'T',
            '/ţ|ț|ť|ŧ|т/'                         => 't',
            '/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ü|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ|У/' => 'U',
            '/ù|ú|û|ũ|ū|ŭ|ů|ü|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ|у/' => 'u',
            '/В/'                                 => 'V',
            '/в/'                                 => 'v',
            '/Ý|Ÿ|Ŷ|Ы/'                           => 'Y',
            '/ý|ÿ|ŷ|ы/'                           => 'y',
            '/Ŵ/'                                 => 'W',
            '/ŵ/'                                 => 'w',
            '/Ź|Ż|Ž|З/'                           => 'Z',
            '/ź|ż|ž|з/'                           => 'z',
            '/Æ|Ǽ/'                               => 'AE',
            '/ß/'                                 => 'ss',
            '/Ĳ/'                                 => 'IJ',
            '/ĳ/'                                 => 'ij',
            '/Œ/'                                 => 'OE',
            '/Ч/'                                 => 'Ch',
            '/ч/'                                 => 'ch',
            '/Ю/'                                 => 'Ju',
            '/ю/'                                 => 'ju',
            '/Я/'                                 => 'Ja',
            '/я/'                                 => 'ja',
            '/Ш/'                                 => 'Sh',
            '/ш/'                                 => 'sh',
            '/Щ/'                                 => 'Shch',
            '/щ/'                                 => 'shch',
            '/Ж/'                                 => 'Zh',
            '/ж/'                                 => 'zh',
        );

        $slug = preg_replace(array_keys($ascii), array_values($ascii), $login);
        $slug = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $slug);
        $slug = mb_strtolower($slug);
        $slug = preg_replace('![^\-\pL\pN\s]+!u', '', $slug);
        $slug = preg_replace('![\-\s]+!u', '-', $slug);

        // Basic user data
        $stmt = $this->db->prepare("INSERT INTO `".$prefix."users`
                                    (`username`, `email`, `password`, `display_name`, `group_id`, `slug`)
                                    VALUES (?, ?, ?, ?, ?, ?)");

        $stmt->execute(array(
            $login,
            $email,
            Laravel\Hash::make($password),
            $login,
            ($admin ? 1 : 2),
            $slug
        ));

        // Profile
        $stmt = $this->db->prepare("INSERT INTO `".$prefix."profiles`
                                    (`user_id`, `ip`, `created_at`)
                                    VALUES (?, ?, ?)");

        $stmt->execute(array(
            $this->db->lastInsertId(),
            $_SERVER['REMOTE_ADDR'],
            date('Y-m-d H:i:s')
        ));
    }

    /**
     * CHMOD check
     *
     * @param  array $list
     * @return array
     */
    public function checkWrite(array $list)
    {
        // Success?
        $success = true;

        // New list
        $new = array(
        );

        // Iterate
        foreach ($list as $f)
        {
            if (!is_writable(IONIC_PATH.$f))
                $success = false;

            $new[] = array(
                'file'      => $f,
                'writeable' => is_writable(IONIC_PATH.$f));
        }

        // Return
        return array(
            'success' => $success,
            'files'   => $new);
    }

    /**
     * Execute MySQL scheme
     *
     * @param object $file
     * @return
     */
    public function executeScheme($file, $prefix = 'ionic_')
    {
        // Exists?
        if (!is_readable(INSTALLER_PATH.$file))
        {
            throw new Exception('Scheme not found: '.$file);
        }

        // DB initialised
        if (!($this->db instanceof PDO))
        {
            throw new Exception('Database connection problem');
        }

        // Some vars
        $inString = false;
        $stringChar = '';
        $query = '';

        // Retrieve schema
        $file = file_get_contents(INSTALLER_PATH.$file);
        $count = strlen($file) - 1;

        // Prefix = {dbp}
        // Iterate
        for ($i = 0; $i <= $count; $i++)
        {
            // Prefix
            if ($file[$i] == '{' and $file[$i + 1] == 'd' and $file[$i + 2] == 'b' and $file[$i + 3] == 'p' and $file[$i + 4] == '}')
            {
                // Add prefix
                $query .= $prefix;

                // Move cursor
                $i = ($i + 4);

                // Next iteration
                continue;
            }

            // String
            if ($file[$i] == '"' OR $file[$i] == "'")
            {
                if (!$inString)
                {
                    $inString = true;
                    $stringChar = $file[$i];
                }
                elseif ($file[$i] == $stringChar)
                {
                    $inString = false;
                }
            }

            // Seperator
            if ($file[$i] == ';' and $query and !$inString)
            {
                // Execute this query and move on to parsing next one
                $this->db->exec($query);
                $query = '';
            }
            else
            {
                $query .= $file[$i];
            }
        }

        // One more
        if (!empty($query))
        {
            $this->db->exec($query);
        }
    }

    /**
     * Installation handler
     *
     * @param InstallerModule $mod
     */
    public function handleInstallation(InstallerModule $mod)
    {
        // Blocked?
        if (file_exists(IONIC_PATH.'public/upload/install.lock'))
        {
            echo $this->parseView('layout', array(
                'content'     => $this->parseView('blocked'),
                'steps'       => array(
                    1 => 'Informacja'),
                'currentStep' => 1));

            return;
        }

        if (empty($_SESSION['module']))
        {
            $_SESSION['module'] = MODULE;
            $_SESSION['step'] = 1;
        }
        else
        {
            if ($_SESSION['module'] != MODULE)
            {
                $_SESSION['module'] = MODULE;
                $_SESSION['step'] = 1;
            }
        }

        // Installer
        $mod->installer($this);

        // Fresh install
        if (empty($_SESSION['step']))
        {
            $_SESSION['step'] = 1;

            $mod->currentStep(1);
        }
        else
        {
            $mod->currentStep($_SESSION['step']);
        }

        // Handle module
        $mod->handle();

        // Output
        $content = $this->parseView('layout', array(
            'content'     => $this->view,
            'steps'       => $mod->getStepList(),
            'currentStep' => $_SESSION['step']));

        // Next step?
        if ($this->next_step)
        {
            $_SESSION['step']++;
        }

        echo $content;
    }

    /**
     * Locks installer, preventing another instance execution
     *
     * @param bool $destroySession
     */
    public function lockInstaller($destroySession = true)
    {
        @touch(IONIC_PATH.'public/upload/install.lock');

        if ($destroySession)
        {
            session_destroy();
        }
    }

    /**
     * Modify WxSport config
     *
     * @param string $file
     * @param array  $values
     */
    public function modifyConfig($file, array $values)
    {
        // Exists?
        if (!is_writable(IONIC_PATH.'private/application/config/'.$file.'.php') and substr(PHP_OS, 0, 3) != 'WIN')
        {
            throw new Exception('Config not found/writeable: '.$file);
        }

        // Save
        file_put_contents(IONIC_PATH.'private/application/config/'.$file.'.php', '<?php'."\n\n".'return '.var_export($values, true).';');
    }

    /**
     * Proceed
     */
    public function nextStep($file = 'index.php')
    {
        // Soft
        if (!$file)
        {
            $this->next_step = true;
            return;
        }

        // Hard
        $_SESSION['step'] += 1;

        header('Location: '.$file);
        exit;
    }

    /**
     * Parse view
     *
     * @param  string $name
     * @param  array  $params [optional]
     * @return string
     */
    public function parseView($viewName, array $params = array())
    {
        // Exists?
        if (!is_readable(INSTALLER_PATH.'views/'.$viewName.'.php'))
        {
            throw new Exception('View not found: '.$viewName);
        }

        // Extract
        extract($params);

        // Handler
        ob_start();

        // Output
        include INSTALLER_PATH.'views/'.$viewName.'.php';

        // End
        $this->view = ob_get_clean();

        // Return
        return $this->view;
    }

    /**
     * Set current PDO object
     *
     * @param PDO $db
     */
    public function setDb(PDO $db)
    {
        $this->db = $db;

        $this->db->exec('SET NAMES utf8');
    }

}
