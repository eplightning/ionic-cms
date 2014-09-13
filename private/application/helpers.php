<?php

/**
 * Apply censorship
 *
 * @param  string $message
 * @return string
 */
function ionic_censor($message)
{
    $censorship = ionic_normalize_lines(Config::get('bans.censorship', ''));

    if (empty($censorship))
        return $message;

    foreach (explode("\n", $censorship) as $word)
    {
        $message = str_replace($word, str_repeat('*', Str::length($word)), $message);
    }

    return $message;
}

/**
 * Find and remove specified cache files
 *
 * @param string $file
 */
function ionic_clear_cache($file)
{
    $results = glob(path('storage').'cache'.DS.$file, GLOB_NOSORT);

    if (!$results)
    {
        return;
    }

    foreach ($results as $f)
    {
        @unlink($f);
    }
}

/**
 * Find and remove generated thumbnails
 *
 * @param string $type
 * @param string $file
 */
function ionic_clear_thumbnails($type, $file)
{
    $results = glob(path('public').'upload'.DS.$type.DS.'thumbnail'.DS.$file.'_*.png', GLOB_NOSORT);

    if (!$results)
    {
        return;
    }

    foreach ($results as $f)
    {
        @unlink($f);
    }
}

/**
 * List of all countries (for use with flags)
 *
 * @return array
 */
function ionic_country_list()
{
    $countries = array(
        'ad'       => 'Andora',
        'ae'       => 'Zjednoczone Emiraty Arabskie',
        'af'       => 'Afganistan',
        'ag'       => 'Antigua i Barbuda',
        'ai'       => 'Anguilla',
        'al'       => 'Albania',
        'am'       => 'Armenia',
        'an'       => 'Antyle Holenderskie',
        'ao'       => 'Angola',
        'ar'       => 'Argentyna',
        'as'       => 'Samoa Amerykańskie',
        'at'       => 'Austria',
        'au'       => 'Australia',
        'aw'       => 'Aruba',
        'ax'       => 'Wyspy Alandzkie',
        'az'       => 'Azerbejdżan',
        'ba'       => 'Bośnia i Hercegowina',
        'bb'       => 'Barbados',
        'bd'       => 'Bangladesz',
        'be'       => 'Belgia',
        'bf'       => 'Burkina Faso',
        'bg'       => 'Bułgaria',
        'bh'       => 'Bahrajn',
        'bi'       => 'Burundi',
        'bj'       => 'Benin',
        'bm'       => 'Bermuda',
        'bn'       => 'Brunei Darussalam',
        'bo'       => 'Boliwia',
        'br'       => 'Brazylia',
        'bs'       => 'Bahamy',
        'bt'       => 'Bhutan',
        'bv'       => 'Wyspa Bouveta',
        'bw'       => 'Botswana',
        'by'       => 'Białoruś',
        'bz'       => 'Belize',
        'ca'       => 'Kanada',
        'cc'       => 'Wyspy Kokosowe',
        'cd'       => 'Kongo',
        'cf'       => 'Republika Środkowoafrykańska',
        'ch'       => 'Szwajcaria',
        'ci'       => 'Wybrzeże Kości Słoniowej',
        'ck'       => 'Wyspy Cooka',
        'cl'       => 'Chile',
        'cm'       => 'Kamerun',
        'cn'       => 'Chiny',
        'co'       => 'Kolumbia',
        'cr'       => 'Kostaryka',
        'cu'       => 'Kuba',
        'cv'       => 'Republika Zielonego Przylądka',
        'cx'       => 'Wyspa Wielkanocna',
        'cy'       => 'Cypr',
        'cz'       => 'Czechy',
        'de'       => 'Niemcy',
        'dj'       => 'Dżibuti',
        'dk'       => 'Dania',
        'dm'       => 'Dominika',
        'do'       => 'Dominikana',
        'dz'       => 'Algeria',
        'ec'       => 'Ekwador',
        'ee'       => 'Estonia',
        'eg'       => 'Egipt',
        'eh'       => 'Sahara Zachodnia',
        'england'  => 'Anglia',
        'er'       => 'Erytrea',
        'es'       => 'Hiszpania',
        'et'       => 'Etiopia',
        'fi'       => 'Finlandia',
        'fj'       => 'Fidżi',
        'fk'       => 'Falklandy',
        'fo'       => 'Wyspy Owcze',
        'fm'       => 'Mikronezja',
        'fr'       => 'Francja',
        'ga'       => 'Gabon',
        'gb'       => 'Wielka Brytania',
        'gd'       => 'Grenada',
        'ge'       => 'Gruzja',
        'gf'       => 'Gujana Francuska',
        'gh'       => 'Ghana',
        'gi'       => 'Gibraltar',
        'gl'       => 'Grenlandia',
        'gm'       => 'Gambia',
        'gn'       => 'Gwinea',
        'gq'       => 'Gwinea Równikowa',
        'gr'       => 'Grecja',
        'gs'       => 'Georgia Południowa i Sandwich Południowy',
        'gt'       => 'Gwatemala',
        'gu'       => 'Guam',
        'gw'       => 'Gwinea Bissau',
        'gy'       => 'Gujana',
        'hk'       => 'Hong Kong',
        'hn'       => 'Honduras',
        'hr'       => 'Chorowacja',
        'ht'       => 'Haiti',
        'hu'       => 'Węgry',
        'id'       => 'Indonezja',
        'ie'       => 'Irlandia',
        'il'       => 'Izrael',
        'in'       => 'Indie',
        'iq'       => 'Irak',
        'ir'       => 'Iran',
        'is'       => 'Islandia',
        'it'       => 'Włochy',
        'jm'       => 'Jamajka',
        'jo'       => 'Jordania',
        'jp'       => 'Japonia',
        'ke'       => 'Kenia',
        'kg'       => 'Kirgistan',
        'kh'       => 'Kambodża',
        'ki'       => 'Kiribati',
        'km'       => 'Komory',
        'kn'       => 'Saint Kitts i Nevis',
        'kp'       => 'Korea Północna',
        'kr'       => 'Korea Południowa',
        'kw'       => 'Kuwejt',
        'ky'       => 'Kajmany',
        'kz'       => 'Kazachstan',
        'la'       => 'Laos',
        'lb'       => 'Liban',
        'lc'       => 'Saint Lucia',
        'li'       => 'Lichtensztajn',
        'lk'       => 'Sri Lanka',
        'lr'       => 'Liberia',
        'ls'       => 'Lesotho',
        'lt'       => 'Litwa',
        'lu'       => 'Luxemburg',
        'lv'       => 'Łotwa',
        'ly'       => 'Libia',
        'ma'       => 'Maroko',
        'mc'       => 'Monako',
        'md'       => 'Mołdawia',
        'me'       => 'Czarnogóra',
        'mg'       => 'Madagaskar',
        'mh'       => 'Wyspy Marshalla',
        'mk'       => 'Macedonia',
        'mm'       => 'Myanmar (Birma)',
        'ml'       => 'Mali',
        'mn'       => 'Mongolia',
        'mo'       => 'Makau',
        'mp'       => 'Mariany Północne',
        'mq'       => 'Martynika',
        'mr'       => 'Mauretania',
        'ms'       => 'Montserrat',
        'mt'       => 'Malta',
        'mu'       => 'Mauritius',
        'mv'       => 'Malediwy',
        'mw'       => 'Malawi',
        'mx'       => 'Meksyk',
        'my'       => 'Malezja',
        'mz'       => 'Mozambik',
        'na'       => 'Namibia',
        'nc'       => 'Nowa Kaledonia',
        'nf'       => 'Norfolk',
        'ng'       => 'Nigeria',
        'ni'       => 'Nikaragua',
        'nl'       => 'Holandia',
        'no'       => 'Norwegia',
        'np'       => 'Nepal',
        'nr'       => 'Nauru',
        'nu'       => 'Niue',
        'nz'       => 'Nowa Zelandia',
        'om'       => 'Oman',
        'pa'       => 'Panama',
        'pe'       => 'Peru',
        'pf'       => 'Polinezja Francuska',
        'pg'       => 'Papua-Nowa Gwinea',
        'ph'       => 'Filipiny',
        'pk'       => 'Pakistan',
        'pl'       => 'Polska',
        'pm'       => 'Saint Pierre i Miquelon',
        'pn'       => 'Pitcairn',
        'pr'       => 'Portoryko',
        'ps'       => 'Palestyna',
        'pt'       => 'Portugalia',
        'pw'       => 'Palau',
        'py'       => 'Paragwaj',
        'qa'       => 'Katar',
        're'       => 'Reunion',
        'ro'       => 'Rumunia',
        'rs'       => 'Serbia',
        'ru'       => 'Rosja',
        'rw'       => 'Rwanda',
        'sa'       => 'Arabia Saudyjska',
        'sb'       => 'Wyspy Salomona',
        'sc'       => 'Seszele',
        'scotland' => 'Szkocja',
        'sd'       => 'Sudan',
        'se'       => 'Szwecja',
        'sg'       => 'Singapur',
        'sh'       => 'Święta Helena',
        'si'       => 'Słowenia',
        'sj'       => 'Svalbard i Jan Mayen',
        'sk'       => 'Słowacja',
        'sl'       => 'Sierra Leone',
        'sm'       => 'San Marino',
        'sn'       => 'Senegal',
        'so'       => 'Somalia',
        'sr'       => 'Surinam',
        'st'       => 'Wyspy Świętego Tomasza i Książęca',
        'sv'       => 'Salwador',
        'sy'       => 'Syria',
        'sz'       => 'Suazi',
        'tc'       => 'Turks i Caicos',
        'td'       => 'Czad',
        'tf'       => 'Francuskie Terytoria Południowe',
        'tg'       => 'Togo',
        'th'       => 'Tajlandia',
        'tj'       => 'Tadżykistan',
        'tk'       => 'Tokelau',
        'tl'       => 'Timor Wschodni',
        'tm'       => 'Turkmenistan',
        'tn'       => 'Tunezja',
        'to'       => 'Tonga',
        'tr'       => 'Turcja',
        'tt'       => 'Trynidad i Tobago',
        'tv'       => 'Tuvalu',
        'tw'       => 'Tajwan',
        'tz'       => 'Tanzania',
        'ua'       => 'Ukraina',
        'ug'       => 'Uganda',
        'us'       => 'USA',
        'uy'       => 'Urugwaj',
        'uz'       => 'Uzbekistan',
        'va'       => 'Watykan',
        'vc'       => 'Saint Vincent i Grenadyny',
        've'       => 'Wenezuela',
        'vg'       => 'Wyspy Dziewicze (UK)',
        'vi'       => 'Wyspy Dziewicze (US)',
        'vn'       => 'Wietnam',
        'vu'       => 'Vanuatu',
        'wales'    => 'Walia',
        'wf'       => 'Wallis i Futuna',
        'ws'       => 'Samoa',
        'ye'       => 'Yemen',
        'yt'       => 'Majotta',
        'za'       => 'RPA',
        'zm'       => 'Zambia',
        'zw'       => 'Zimbabwe',
    );

    asort($countries);

    return $countries;
}

/**
 * Format date
 *
 * @param  string|int $time
 * @param  string     $format
 * @param  bool       $relative
 * @return string
 */
function ionic_date($time = null, $format = 'standard', $relative = false)
{
    // DateTime
    if ($time === null)
    {
        $time = new DateTime('now');
    }
    elseif (is_string($time) and !ctype_digit($time))
    {
        $time = new DateTime($time);
    }
    else
    {
        $time = DateTime::createFromFormat('U', (int) $time);
    }

    // Relative
    if ($relative)
    {
        // Interval
        $now = new DateTime('now');
        $interval = $time->diff($now, true);

        if ($interval->y > 0)
        {
            if ($interval->y == 1)
            {
                return '1 rok temu';
            }
            elseif ($interval->y < 5)
            {
                return sprintf('%d lata temu', $interval->y);
            }
            else
            {
                return sprintf('%d lat temu', $interval->y);
            }
        }
        elseif ($interval->m > 0)
        {
            if ($interval->m == 1)
            {
                return '1 miesiąc temu';
            }
            elseif ($interval->m < 5)
            {
                return sprintf('%d miesiące temu', $interval->m);
            }
            else
            {
                return sprintf('%d miesięcy temu', $interval->m);
            }
        }
        elseif ($interval->d > 0)
        {
            $now->setTime(0, 0);
            $interval = $time->diff($now, true);

            if ($interval->d < 0)
            {
                return 'wczoraj o '.$time->format(Config::get('application.date_time'));
            }

            return ($interval->d + 1).' dni temu';
        }
        elseif ($interval->h > 0)
        {
            if ($interval->h == 1)
            {
                return '1 godzinę temu';
            }
            elseif ($interval->h < 5)
            {
                return sprintf('%d godziny temu', $interval->h);
            }
            else
            {
                return sprintf('%d godzin temu', $interval->h);
            }
        }
        elseif ($interval->i > 0)
        {
            if ($interval->i == 1)
            {
                return '1 minutę temu';
            }
            elseif ($interval->i < 5)
            {
                return sprintf('%d minuty temu', $interval->i);
            }
            else
            {
                return sprintf('%d minut temu', $interval->i);
            }
        }
        else
        {
            return 'mniej niż minute temu';
        }
    }

    // Load format
    switch ($format)
    {
        case 'standard':
            $format = Config::get('application.date_standard');
            break;

        case 'time':
            $format = Config::get('application.date_time');
            break;

        case 'short':
            $format = Config::get('application.date_short');
            break;

        case 'month':
            switch ((int) $time->format('n'))
            {
                case 1:
                    return $time->format('j').' stycznia';

                case 2:
                    return $time->format('j').' lutego';

                case 3:
                    return $time->format('j').' marca';

                case 4:
                    return $time->format('j').' kwietnia';

                case 5:
                    return $time->format('j').' maja';

                case 6:
                    return $time->format('j').' czerwca';

                case 7:
                    return $time->format('j').' lipca';

                case 8:
                    return $time->format('j').' sierpnia';

                case 9:
                    return $time->format('j').' września';

                case 10:
                    return $time->format('j').' października';

                case 11:
                    return $time->format('j').' listopada';

                default:
                    return $time->format('j').' grudnia';
            }
    }

    // Return formatted
    return $time->format($format);
}

/**
 * Format date as relative (shortcut for Twig)
 *
 * @param  string|int $time
 * @return string
 */
function ionic_date_rel($time = null)
{
    return ionic_date($time, 'standard', true);
}

/**
 * Format special date string, allowed formats:
 *
 * Y-m-d        : Standard date
 * Y-m-00       : Standard date without day
 * Y-00-00      : Standard date without day and month
 * 0000-00-00   : Unknown
 *
 * @param   string  $time
 * @return  string
 */
function ionic_date_special($time = null)
{
    if (!is_string($time))
        return ionic_date();

    if (strlen($time) != 10 or $time == '0000-00-00')
        return 'Nieznana';

    if (substr($time, 5, 2) == '00')
        return date('Y', strtotime($time) + 86400);

    if (substr($time, 8) == '00')
    {
        $time = strtotime($time) + 86400;

        switch ((int) date('n', $time))
        {
            case 1:
                return 'styczeń '.date('Y', $time);

            case 2:
                return 'luty '.date('Y', $time);

            case 3:
                return 'marzec '.date('Y', $time);

            case 4:
                return 'kwiecień '.date('Y', $time);

            case 5:
                return 'maj '.date('Y', $time);

            case 6:
                return 'czerwiec '.date('Y', $time);

            case 7:
                return 'lipiec '.date('Y', $time);

            case 8:
                return 'sierpień '.date('Y', $time);

            case 9:
                return 'wrzesień '.date('Y', $time);

            case 10:
                return 'październik '.date('Y', $time);

            case 11:
                return 'listopad '.date('Y', $time);

            default:
                return 'grudzień '.date('Y', $time);
        }
    }

    return ionic_date($time);
}

/**
 * Find unique slug
 *
 * @param string $title
 * @param int    $id
 * @param string $table
 * @param int    $max
 */
function ionic_find_slug($title, $id, $table, $max = 127)
{
    $title = Str::slug($title);
    $i = 0;

    do
    {
        switch ($i)
        {
            case 0:
                $slug = $title;
                break;

            case 1:
                $slug = $title.'-'.$id;
                break;

            case 2:
                $slug = $title.'-'.$id.'-'.rand(0, 9);
                break;

            default:
                $slug = Str::random($max > 20 ? 20 : $max);
        }

        if (strlen($slug) > $max)
            $slug = substr($slug, 0, $max);

        $i++;
    }
    while (DB::table($table)->where('id', '<>', $id)->where('slug', '=', $slug)->first('id'));

    return $slug;
}

/**
 * Send email registered in database
 *
 * @param int|string $id
 * @param string     $to
 * @param array      $replacement
 */
function ionic_mail($id, $to, array $replacement = array())
{
    // Get mailer
    $mailer = IoC::resolve('mailer');

    // Retrieve email data
    if (is_int($id))
    {
        $data = DB::table('emails')->where('id', '=', $id)->first(array('subject', 'message'));
    }
    elseif (is_string($id))
    {
        $data = DB::table('emails')->where('title', '=', $id)->first(array('subject', 'message'));
    }
    else
    {
        $data = $id;
    }

    if (!$data)
        return;

    // Create new email
    $message = Swift_Message::newInstance();
    $message->setFrom(array(Config::get('email.from') => Config::get('email.from_name')));
    $message->setTo($to);

    $message->setSubject(strtr($data->subject, $replacement));
    $message->setBody(strtr($data->message, $replacement), 'text/html');

    // Send it
    $mailer->send($message);
}

/**
 * Link generator
 *
 * @param string $type
 */
function ionic_make_link($type)
{
    $args = func_get_args();

    switch ($type)
    {
        case 'user':
            return 'users/profile/'.$args[1];

        case 'news':
            return !empty($args[2]) ? $args[2] : 'news/show/'.$args[1];
    }
}

/**
 * Normalize new lines
 *
 * @param  string $text
 * @return string
 */
function ionic_normalize_lines($text)
{
    return str_replace(array("\r\n", "\r"), "\n", $text);
}

/**
 * Parse match score
 *
 * @param  string     $score
 * @return array|null
 */
function ionic_parse_score($score)
{
    if (!$score)
        return null;

    $score = explode(':', str_replace('-', ':', $score), 2);

    if (!ctype_digit($score[0]) or !ctype_digit($score[1]))
        return null;

    $score[0] = (int) $score[0];
    $score[1] = (int) $score[1];

    return $score;
}

/**
 * Temporary slug finder
 *
 * @param string $table
 */
function ionic_tmp_slug($table)
{
    do
    {
        $slug = $table.'-'.Str::random(15);
    }
    while (DB::table($table)->where('slug', '=', $slug)->first('id'));

    return $slug;
}

/**
 * Returns link to thumbnail
 *
 * @param string $type
 * @param string $filename
 * @param string $$size
 */
function ionic_thumb($type, $filename, $size)
{
    static $smart = null;

    if ($smart === null)
        $smart = Config::get('advanced.thumbnail_smart', true);

    if (!$filename)
        return '';

    if ($smart and is_file(path('public').'upload'.DS.$type.DS.'thumbnail'.DS.$filename.'_'.$size.'.png'))
    {
        return URL::base().'/public/upload/'.$type.'/thumbnail/'.$filename.'_'.$size.'.png';
    }

    return URL::base().'/thumbnail/index/'.$type.'/'.$filename.'/'.$size;
}
