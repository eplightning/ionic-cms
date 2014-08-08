CREATE TABLE IF NOT EXISTS `{dbp}admin_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `category` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sorting` int(10) unsigned NOT NULL DEFAULT '1',
  `module` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `role` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_hidden` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=47 ;

INSERT INTO `{dbp}admin_menu` (`id`, `title`, `category`, `sorting`, `module`, `role`) VALUES
(1, 'Dashboard', 'System', 1, 'dashboard', ''),
(2, 'Konfiguracja', 'System', 2, 'config', 'admin_config'),
(3, 'Dziennik akcji', 'System', 3, 'logs', 'admin_logs'),
(4, 'Widżety', 'System', 4, 'widgets', 'admin_widgets'),
(5, 'Zgłoszenia', 'System', 5, 'reports', 'admin_reports'),
(6, 'Cache', 'System', 6, 'cache', 'admin_cache'),
(7, 'Czyszczenie', 'System', 7, 'prune', 'admin_prune'),
(8, 'E-maile', 'System', 8, 'emails', 'admin_emails'),
(10, 'Użytkownicy', 'Użytkownicy', 10, 'users', 'admin_users'),
(11, 'Grupy', 'Użytkownicy', 11, 'groups', 'admin_groups'),
(12, 'Własne pola', 'Użytkownicy', 12, 'fields', 'admin_fields'),
(13, 'Ostrzeżenia', 'Użytkownicy', 13, 'warnings', 'admin_warnings'),
(14, 'Nieaktywowani', 'Użytkownicy', 14, 'validating_users', 'admin_validating_users'),
(15, 'Newsletter', 'Użytkownicy', 15, 'newsletter', 'admin_newsletter'),
(16, 'Lista mailingowa', 'Użytkownicy', 16, 'mailing_list', 'admin_mailing_list'),
(17, 'Menu', 'Treść', 17, 'menu', 'admin_menu'),
(18, 'Podstrony', 'Treść', 18, 'pages', 'admin_page'),
(19, 'Obrazki', 'Treść', 19, 'images', 'admin_images'),
(20, 'Nowości', 'Treść', 20, 'news', 'admin_news'),
(21, 'Podesłane', 'Treść', 21, 'submitted_content', 'admin_submitted_content'),
(22, 'Sondy', 'Treść', 22, 'polls', 'admin_polls'),
(23, 'Komentarze', 'Treść', 23, 'comments', 'admin_comments'),
(27, 'Kat. plików', 'Multimedia', 27, 'file_categories', 'admin_file_categories'),
(24, 'Tagi', 'Treść', 24, 'tags', 'admin_tags'),
(25, 'Blogi', 'Treść', 25, 'blogs', 'admin_blogs'),
(28, 'Kat. zdjęć', 'Multimedia', 28, 'photo_categories', 'admin_photo_categories'),
(29, 'Kat. video', 'Multimedia', 29, 'video_categories', 'admin_video_categories'),
(30, 'Download', 'Multimedia', 30, 'files', 'admin_files'),
(31, 'Zdjęcia', 'Multimedia', 31, 'photos', 'admin_photos'),
(32, 'Video', 'Multimedia', 32, 'videos', 'admin_videos'),
(26, 'Shoutbox', 'Treść', 26, 'shoutbox', 'admin_shoutbox'),
(9, 'Szablony', 'System', 9, 'templates', 'admin_templates'),
(33, 'Rozgrywki', 'Rozgrywki', 33, 'competitions', 'admin_competitions'),
(34, 'Kluby', 'Rozgrywki', 34, 'teams', 'admin_teams'),
(35, 'Sezony', 'Rozgrywki', 35, 'seasons', 'admin_seasons'),
(36, 'Kolejki', 'Rozgrywki', 36, 'fixtures', 'admin_fixtures'),
(37, 'Mecze', 'Rozgrywki', 37, 'matches', 'admin_matches'),
(38, 'Zawodnicy', 'Rozgrywki', 38, 'players', 'admin_players'),
(39, 'Tabele', 'Rozgrywki', 39, 'tables', 'admin_tables'),
(40, 'Statystyki', 'Rozgrywki', 40, 'player_stats', 'admin_player_stats'),
(41, 'Transfery', 'Rozgrywki', 41, 'player_transfers', 'admin_player_transfers'),
(42, 'Kontuzje', 'Rozgrywki', 42, 'player_injuries', 'admin_player_injuries'),
(43, 'Typer', 'Rozgrywki', 43, 'bet_matches', 'admin_bet_matches'),
(44, 'Piłkarz miesiąca', 'Rozgrywki', 44, 'monthpicks', 'admin_monthpicks'),
(45, 'Piłkarz meczu', 'Rozgrywki', 45, 'matchpicks', 'admin_matchpicks'),
(46, 'Relacje live', 'Rozgrywki', 46, 'relations', 'admin_relations'),
(47, 'Moduły systemu', 'System', 10, 'packages', 'admin_root');

CREATE TABLE IF NOT EXISTS `{dbp}admin_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `note` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}bets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `match_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bet` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `used_points` int(10) unsigned NOT NULL DEFAULT '0',
  `acquired_points` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `match_id` (`match_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}bet_matches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `home` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `away` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `date_start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_end` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `score` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ratio_home` decimal(5,2) unsigned NOT NULL DEFAULT '1.10',
  `ratio_draw` decimal(5,2) unsigned NOT NULL DEFAULT '1.10',
  `ratio_away` decimal(5,2) unsigned NOT NULL DEFAULT '1.10',
  `archive` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}blogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `content_raw` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comments_count` int(11) NOT NULL DEFAULT '0',
  `karma` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `comment_raw` text COLLATE utf8_unicode_ci NOT NULL,
  `karma` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `content_link` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `guest_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_hidden` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `is_reported` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `content_id` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}competitions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}competition_teams` (
  `competition_id` int(10) unsigned NOT NULL DEFAULT '0',
  `team_id` int(10) unsigned NOT NULL DEFAULT '0',
  `season_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`competition_id`,`team_id`,`season_id`),
  KEY `team_id` (`team_id`),
  KEY `season_id` (`season_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbp}config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `section` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'text',
  `options` text COLLATE utf8_unicode_ci NOT NULL,
  `php_type` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'string',
  `php_key` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=74 ;

INSERT INTO `{dbp}config` (`id`, `category_id`, `name`, `description`, `section`, `value`, `type`, `options`, `php_type`, `php_key`) VALUES
(1, 1, 'Strefa czasowa', 'Wybierz strefę czasową ,w której się znajdujesz.', 'Data i czas', 'Europe/Warsaw', 'select', 'timezone', 'string', 'timezone'),
(2, 1, 'Standardowa data', 'Więcej informacji: <a href="http://www.php.net/manual/pl/function.date.php">http://www.php.net/manual/pl/function.date.php</a>', 'Data i czas', 'd.m.Y; H:i', 'text', '', 'string', 'date_standard'),
(4, 1, 'Format czasu', 'Więcej informacji: <a href="http://www.php.net/manual/pl/function.date.php">http://www.php.net/manual/pl/function.date.php</a>', 'Data i czas', 'H:i', 'text', '', 'string', 'date_time'),
(5, 1, 'Krótka data', 'Więcej informacji: <a href="http://www.php.net/manual/pl/function.date.php">http://www.php.net/manual/pl/function.date.php</a>', 'Data i czas', 'd.m.Y', 'text', '', 'string', 'date_short'),
(6, 1, 'Domena dla ciasteczek', 'Zwykle w formacie .twojadomena.com .', 'Cookies', '', 'text', '', 'string', 'cookie_domain'),
(7, 1, 'Ścieżka dla ciateczek', 'W formacie /sciezka/do/aplikacji/ .', 'Cookies', '/', 'text', '', 'string', 'cookie_path'),
(8, 2, 'Sposób wysyłki poczty', 'Wybierz jeden z dostępnych sposobów wysyłki poczty. Zalecany jest SMTP.', 'Ogólne', 'mail', 'select', 'smtp=SMTP\r\nmail=PHP mail()\r\nsendmail=sendmail\r\nnone=Wyłącz e-mail', 'string', 'type'),
(9, 2, 'Serwer SMTP', 'W przypadku używania SMTP do wysyłki poczty podaj adres serwera.', 'SMTP', 'smtp.gmail.com', 'text', '', 'string', 'host'),
(10, 2, 'Port SMTP', 'Port używany przez powyższy serwer.', 'SMTP', '465', 'text', 'numeric', 'int', 'port'),
(11, 2, 'Rodzaj szyfrowania', 'Rodzaj szyfrowania używany przez serwer.', 'SMTP', 'ssl', 'select', 'ssl=SSL\r\ntls=TLS\r\ntcp=Brak', 'string', 'encryption'),
(12, 2, 'Nazwa użytkownika', 'Jeśli serwer wymaga logowania podaj login.', 'SMTP', 'example@gmail.com', 'text', '', 'string', 'username'),
(13, 2, 'Hasło użytkownika', 'Jeśli serwer wymaga logowania podaj hasło.', 'SMTP', '', 'text', '', 'string', 'password'),
(14, 2, 'Ścieżka do sendmail-a', 'Wymagane tylko ,gdy używasz sendmail do wysyłki poczty.', 'Sendmail', '/usr/sbin/sendmail -bs', 'text', '', 'string', 'sendmail'),
(15, 2, 'E-mail nadawcy', 'Jeśli używasz SMTP ten e-mail powinien pasować, w przeciwnym razie mogą wystąpić problemy.', 'Ogólne', 'example@gmail.com', 'text', '', 'string', 'from'),
(16, 2, 'Nazwa nadawcy', 'Nazwa widoczna w wysyłanych przez serwis wiadomościach.', 'Ogólne', 'Administrator', 'text', '', 'string', 'from_name'),
(17, 3, 'Zablokowane adresy IP', 'Lista zablokowanych adresów IP. Jeden na linię.', 'IP', '', 'textarea', '', 'string', 'banned_ips'),
(18, 3, 'Wiadomość', 'Wiadomość ,którą zobaczą użytkownicy korzystający z zablokowanego adresu IP.', 'IP', 'Twój adres IP został zablokowany na naszym serwerze. Prawdopodobnie jest to skutkiem złamania regulaminu.', 'html', '', 'string', 'ip_ban_message'),
(19, 1, 'Kompresja GZIP', 'Włącz tylko i wyłącznie wtedy ,gdy twój serwer nie obsługuje kompresji natywnie (Apache2 mod_deflate.c itp.).', 'Serwer', '0', 'yesno', '', 'bool', 'gzip'),
(20, 4, 'Tytuł strony', 'Tytuł strony zawarty w tagu &lt;title&gt;. %s jest zastępowany tytułem danej podstrony.', 'META', '%s - Przykładowa strona', 'text', '', 'string', 'title'),
(21, 4, 'Opis strony', 'Zawartość tagu meta z kluczem "&lt;description&gt;"', 'META', 'Tag description', 'text', '', 'string', 'description'),
(22, 4, 'Słowa kluczowe', 'Zawartość tagu meta z kluczem "&lt;keywords&gt;"', 'META', 'key, key2, key3', 'text', '', 'string', 'keywords'),
(23, 4, 'Minify', 'Czy używać Minify do kompresji skryptów na stronie? Można wyłączyć jeśli sprawia problemy.', 'Minify', '1', 'yesno', '', 'bool', 'minify'),
(24, 3, 'Zablokowane słowa', 'Lista słów ,których użycie w komentarzach lub prywatnych wiadomościach spowoduje zamiane na gwiazdki. Jedno na linię.', 'Cenzura', 'kurwa', 'textarea', '', 'string', 'censorship'),
(25, 3, 'Max. ostrzeżeń', 'Liczba ostrzeżeń powodująca zablokowanie możliwości dodawania komentarzy i innych treści', 'Ostrzeżenia', '5', 'text', 'numeric', 'int', 'warnings'),
(26, 2, 'Listów na sesje', 'Dotyczy newslettera. Wysoka ilość może być niemożliwa na niektórych serwerach.', 'Ogólne', '20', 'text', 'numeric', 'int', 'per_session'),
(27, 5, 'Przechowywanych wersji', 'Ilość przechowywanych wersji jednej podstrony. Większa ilość oznacza dużo większe zużycie pojemności bazy danych.', 'Wersje', '5', 'text', 'numeric', 'int', 'max_versions'),
(28, 6, 'Punktów za news', 'Ilość otrzymywanych punktów za dodanie newsa', 'Ilość', '10', 'text', 'numeric', 'int', 'points_for_news'),
(29, 6, 'Punktów za komentarz', 'Ilość otrzymywanych punktów za dodanie komentarza', 'Ilość', '5', 'text', 'numeric', 'int', 'points_for_comment'),
(30, 7, 'Włączyć znak wodny', 'Czy dodawać znak wodny przy dodawaniu obrazów do galerii?', 'Znak wodny', '1', 'yesno', '', 'bool', 'watermark'),
(31, 7, 'Plik znaku wodnego', 'Musi znajdować się w katalogu private na serwerze oraz być prawidłowym obrazem.', 'Znak wodny', 'watermark.png', 'text', '', 'string', 'watermark_image'),
(32, 7, 'Poziomo', 'Pozycja pozioma znaku wodnego', 'Znak wodny', 'right', 'select', 'right=Prawa\r\ncenter=Środek\r\nleft=Lewa', 'string', 'watermark_horizontal'),
(33, 7, 'Pionowo', 'Pozycja pionowa znaku wodnego', 'Znak wodny', 'bottom', 'select', 'bottom=Dół\r\nmiddle=Środek\r\ntop=Góra', 'string', 'watermark_vertical'),
(34, 8, 'Rodzaj', 'Rodzaj typera', 'Ogólne', 'betting', 'select', 'simple=Prosty\r\nbetting=Obstawianie', 'string', 'type'),
(35, 8, 'Początkowe punkty', 'Punkty ,z którymi użytkownik zaczyna obstawianie', 'Typer: Obstawianie', '50', 'text', 'numeric', 'int', 'bet_starting'),
(36, 8, 'Minimalna stawka', 'Minimalna stawka do obstawienia', 'Typer: Obstawianie', '10', 'text', 'numeric', 'int', 'bet_minimum'),
(37, 8, 'Nagroda za typ', 'Punkty otrzymywane za pomyślny typ', 'Typer: Prosty', '3', 'text', 'numeric', 'int', 'simple_points'),
(39, 9, 'Komentarze', 'Czy goście mogą dodawać komentarze? (Zabezpieczone przez system reCaptcha)', 'Ogólne', '1', 'yesno', '', 'bool', 'comments'),
(40, 9, 'Sondy', 'Czy goście mogą głosować w sondach?', 'Ogólne', '1', 'yesno', '', 'bool', 'polls'),
(41, 9, 'Piłkarz miesiąca', 'Czy goście mogą głosować na piłkarza miesiąca?', 'Ogólne', '1', 'yesno', '', 'bool', 'monthpicks'),
(42, 9, 'Piłkarz meczu', 'Czy goście mogą głosować na piłkarza meczu?', 'Ogólne', '1', 'yesno', '', 'bool', 'matchpicks'),
(43, 9, 'Shoutbox', 'Czy goście mogą pisać posty w shoutboxie? (Brak zabezpieczeń przed spamem)', 'Ogólne', '1', 'yesno', '', 'bool', 'shoutbox'),
(44, 9, 'Karma', 'Czy goście mogą oceniać komentarze/inne?', 'Ogólne', '1', 'yesno', '', 'bool', 'karma'),
(45, 10, 'Strona główna', 'Podaj gdzie ma kierować strona główna w formacie: kontroler@akcja. Dla newsów podaj wartość news@index', 'Ogólne', 'news@index', 'text', '', 'string', 'uri'),
(46, 10, 'Użyj splasha', 'Powoduje wyświetlenia splasha na stronie głównej zamiast powyższej akcji. Wygląd splasha można modyfikować w szablonie splash.twig', 'Ogólne', '0', 'yesno', '', 'bool', 'use_splash'),
(47, 11, 'Menu', 'Podaj maksymalną głębie menu ,którą obsługuje twój layout (głębia liczy się od 0)', 'Layout', '1', 'text', 'numeric', 'int', 'menu'),
(48, 11, 'Shoutbox', 'Wpisów w shoutboxie na stronę', 'Na stronę', '10', 'text', 'numeric', 'int', 'shoutbox'),
(50, 12, 'Nowszy generator miniaturek', 'Włączenie tej opcji spowoduje ,że system będzie sprawdzał czy miniaturka już istnieje na serwerze PRZY generowaniu linku. Generalnie powinno poprawić wydajność na wszystkich serwerach, ale brak testów aby to potwierdzić.', 'Optymalizacje', '1', 'yesno', '', 'bool', 'thumbnail_smart'),
(51, 12, 'Rodzaj ograniczeń', 'Pierwsze ustawienie spowoduje ,że avatar zawsze będzie zmieniony do podanych rozmiarów. Druga opcja zmniejszy obraz tylko w przypadku, gdy jest większy od podanych wymiarów.', 'Avatary', 'max', 'select', 'exactly=Statyczne wymiary\r\nmax=Maksymalne wymiary', 'string', 'avatar_type'),
(52, 12, 'Szerokość', 'Szerokość avatarów', 'Avatary', '160', 'text', 'numeric', 'int', 'avatar_width'),
(53, 12, 'Wysokość', 'Wysokość avatarów', 'Avatary', '90', 'text', 'numeric', 'int', 'avatar_height'),
(54, 13, 'Regulamin', 'Regulamin widoczny podczas rejestracji', 'Regulamin', '<p>\r\n	1. Punkt<br />\r\n	2. Punkt</p>\r\n', 'html', '', 'string', 'rules'),
(55, 13, 'Rodzaj aktywacji', 'Rodzaj aktywacji nowych kont użytkowników', 'Aktywacja', 'email', 'select', 'email=Wiadomość e-mail\r\nadmin=Przez panel administracyjny\r\nauto=Automatycznie (brak aktywacji)', 'string', 'activation'),
(56, 12, 'Publiczny klucz', 'Publiczny klucz systemu reCaptcha. Powinien być wygenerowany ręcznie', 'reCAPTCHA', '6LceRNYSAAAAAK1qmTm_55gqIibE7Yf9GfftW4oj', 'text', '', 'string', 'recaptcha_public'),
(57, 12, 'Prywatny klucz', 'Prywatny klucz systemu reCaptcha. Powinien być wygenerowany ręcznie', 'reCAPTCHA', '6LceRNYSAAAAAAbvh0ZLPBsdL3h8XpHNgj3ZWE9s', 'text', '', 'string', 'recaptcha_private'),
(58, 9, 'Użytkownicy', 'Czy mogą przeglądać liste online, użytkowników oraz profile?', 'Ogólne', '1', 'yesno', '', 'bool', 'users'),
(59, 12, 'Rozmiar', 'Maksymalny rozmiar avatarów w KB', 'Avatary', '128', 'text', 'numeric', 'int', 'avatar_size'),
(60, 12, 'BBCode w komentarzach', 'Włączyć BBCode w komentarzach? (bez tagu img)', 'Komentarze', '1', 'yesno', '', 'bool', 'comment_bbcode'),
(61, 12, 'Sortowanie komentarzy', 'Jak sortować komentarze', 'Komentarze', 'desc', 'select', 'asc=Rosnąco\r\ndesc=Malejąco', 'string', 'comment_sort'),
(62, 12, 'Odstęp w shoutboxie', 'Minimalny odstęp czasu pomiędzy wysyłanymi wiadomościami w shoutboxie przez danego użytkownika. Nie ma efektu na gościach.', 'Flood', '5', 'text', 'numeric', 'int', 'shoutbox_flood'),
(63, 12, 'Odstęp w komentarzach', 'Minimalny odstęp czasu pomiędzy wysyłanymi wiadomościami w komentarzach przez danego użytkownika/gościa.', 'Flood', '30', 'text', 'numeric', 'int', 'comments_flood'),
(64, 12, 'System powiadomień', 'Czy system powiadomień ma być włączony (nie ma większego powodu ,żeby go wyłączać - to jedynie jedno lekkie zapytanie SQL na odsłone)', 'Optymalizacje', '1', 'yesno', '', 'bool', 'notifications'),
(65, 11, 'Prywatne dyskusje', 'Ile prywatnych dyskusji może założyć jeden użytkownik?', 'Prywatne dyskusje', '20', 'text', 'numeric', 'int', 'conversations'),
(66, 11, 'Uczestników', 'Ile osób może łącznie uczestniczyć w prywatnej dyskusji (osoba zakładająca się nie liczy)?', 'Prywatne dyskusje', '20', 'text', 'numeric', 'int', 'conversation_users'),
(67, 12, 'Powiadomienia na e-mail', 'Wyłącz jeśli serwer ma problemy z wysyłaniem powiadomień o nowych wiadomościach do skrzynek mailowych.', 'Optymalizacje', '1', 'yesno', '', 'bool', 'notifications_email'),
(68, 10, 'Globalne główne newsy', 'Czy główne newsy są używane na stronach innych niż lista newsów?', 'Newsy', '0', 'yesno', '', 'bool', 'main_news'),
(69, 11, 'Głównych newsów', 'Limit głównych newsów', 'Strona główna', '1', 'text', 'numeric', 'int', 'main_news'),
(70, 11, 'Zwykłych newsów', 'Ilość zwykłych newsów', 'Strona główna', '5', 'text', 'numeric', 'int', 'news'),
(71, 11, 'Nagłówków', 'Ilość nagłówków', 'Strona główna', '5', 'text', 'numeric', 'int', 'headlines'),
(72, 9, 'Pliki', 'Czy mogą pobierać pliki?', 'Ogólne', '1', 'yesno', '', 'bool', 'files'),
(73, 12, 'Odświeżanie relacji', 'Co ile sekund odświeżać relacje live (0 ,aby wyłączyć)?', 'Optymalizacje', '60', 'text', 'numeric', 'int', 'relation_refresh'),
(74, 12, 'Wersja mobilna', 'Automatycznie wykrywać i włączać wersje mobilną?', 'Optymalizacje', '0', 'yesno', '', 'bool', 'mobile_version'),
(75, 11, 'Komentarzy na stronę', 'Ilość komentarzy na stronę?', 'Na stronę', '20', 'text', 'numeric', 'int', 'comments'),
(76, 12, 'Licznik odsłon newsów', 'Włączyć licznik odsłon newsów (dodatkowe zapytanie SQL)?', 'Optymalizacje', '0', 'yesno', '', 'bool', 'news_counter'),
(77, 12, 'Użyj dużego obrazka', 'Używać dużego obrazka w tagach Open Graph? Dotyczy newsów.', 'Open Graph', '0', 'yesno', '', 'bool', 'og_bigimage'),
(78, 12, 'Dodaj tytuł strony', 'Czy dodawać tytuł strony w tagach Open Graph?', 'Open Graph', '0', 'yesno', '', 'bool', 'og_fulltitle'),
(79, 12, 'Domyślny obrazek', 'Adres relatywny do głównego katalogu strony.', 'Open Graph', 'opengraph.png', 'text', '', 'string', 'og_defaultimage'),
(80, 12, 'Czas ważności miniaturek', 'Czas ważności miniaturek prezentowany w nagłówku HTTP Expires (nie wpływa na działanie generatora) w sekundach.', 'Optymalizacje', '86400', 'text', 'numeric', 'int', 'thumbnail_expires'),
(81, 12, 'Polityka cookie', 'Czy generować komunikat o polityce cookie?', 'Optymalizacje', '1', 'yesno', '', 'bool', 'cookie_policy'),
(82, 2, 'E-mail kontaktowy', 'Na ten adres e-mail będą wysyłane wiadomości napisane przez formularz kontaktowy.', 'Ogólne', 'example@gmail.com', 'text', '', 'string', 'contact_email'),
(83, 12, 'Dynamiczne akcje w panelu', 'Wyłącz, aby przywrócić okienko potwierdzenia operacji z poprzednich wersji.', 'Panel administracyjny', '1', 'yesno', '', 'bool', 'admin_prefer_ajax');

CREATE TABLE IF NOT EXISTS `{dbp}config_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `key` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=14 ;

INSERT INTO `{dbp}config_categories` (`id`, `name`, `description`, `key`) VALUES
(1, 'Ustawienia główne', 'Podstawowe ustawienia aplikacji takie jak strefa czasowa czy formaty czasu.', 'application'),
(2, 'Ustawienia poczty e-mail', 'Konfiguracja sposobu ,w który aplikacja wysyła pocztę e-mail do użytkowników.', 'email'),
(3, 'Banicja', 'W tej kategorii możesz skonfigurować ustawienia banów oraz je dodać lub usunąć. Dodatkowo w tej sekcji możesz zmodyfikować listę zabronionych słów.', 'bans'),
(4, 'META', 'Zawiera opcje dotyczące meta tagów, tytułu strony oraz inne.', 'meta'),
(5, 'Podstrony', 'Ustawienia podstron', 'page'),
(6, 'Punktacja', 'Zawiera ustawienia dotyczące punktacji użytkowników', 'points'),
(7, 'Galeria', 'Ustawienia galerii', 'gallery'),
(8, 'Typer', 'Ustawienia typera', 'bets'),
(9, 'Goście', 'Co goście mogą robić i czego nie mogą.', 'guests'),
(10, 'Strona główna', 'Ustawienia dotyczące strony głównej', 'homepage'),
(11, 'Limity', 'Różnego rodzaju limity', 'limits'),
(12, 'Zaawansowane', 'Zaawansowane ustawienia systemu', 'advanced'),
(13, 'Rejestracja', 'Ustawienia rejestracji', 'register');

CREATE TABLE IF NOT EXISTS `{dbp}conversations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `poster_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_closed` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `messages_count` int(10) unsigned NOT NULL DEFAULT '0',
  `last_post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_post_user` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_newsletter` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `poster_id` (`poster_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}conversation_users` (
  `conversation_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `notifications` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`conversation_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbp}emails` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `vars` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ;

INSERT INTO `{dbp}emails` (`id`, `title`, `subject`, `message`, `vars`) VALUES
(1, 'Aktywacja konta przez admina', 'Twoje konto zostało aktywowane przez administratora', '<div style="padding: 15px; border: 1px solid #ccc; background: #efefef; margin: 10px auto">\r\n	<p>\r\n		Witaj :name,</p>\r\n	<p>\r\n		Twoje konto w serwisie zostało właśnie aktywowane przez administratora - :admin.</p>\r\n	<p>\r\n		Możesz przejść do strony głównej <a href=":website">serwisu</a>, aby zalogować się i zacząć korzystać z Twojego konta.</p>\r\n	<p>\r\n		Pozdrawiamy</p>\r\n</div>\r\n<p>\r\n	&nbsp;</p>\r\n', '<strong>:name</strong> - nazwa użytkownika, <strong>:admin</strong> - nazwa admina ,który aktywował konto, <strong>:website</strong> - link do strony głównej serwisu'),
(2, 'Przywracanie hasła', 'Przywracanie hasła', '<div style="padding: 15px; border: 1px solid #ccc; background: #efefef; margin: 10px auto">\r\n	<p>\r\n		Witaj :name,</p>\r\n	<p>\r\n		Osoba używająca adresu IP - :ip - właśnie wypełniła formularz do przywrócenia zapomnianego hasła.</p>\r\n	<p>\r\n		Jeśli to byłeś ty, możesz użyc poniższego linku ,aby potwierdzić:</p>\r\n	<p>\r\n		<a href=":website">:website</a></p>\r\n	<p>\r\n		Pozdrawiamy</p>\r\n</div>\r\n<p>\r\n	&nbsp;</p>\r\n', '<strong>:name</strong> - nazwa użytkownika, <strong>:ip</strong> - IP ,który użył formularza, <strong>:website</strong> - link do potwierdzenia'),
(3, 'Potwierdzenie rejestracji', 'Potwierdzenie rejestracji', '<div style="padding: 15px; border: 1px solid #ccc; background: #efefef; margin: 10px auto">\r\n	<p>\r\n		Witaj :name,</p>\r\n<p>Aby aktywować założone konto po prostu kliknij w podany poniżej link:</p>\r\n	<p>\r\n		<a href=":website">:website</a></p>\r\n	<p>\r\n		Pozdrawiamy</p>\r\n</div>\r\n<p>\r\n	&nbsp;</p>\r\n', '<strong>:name</strong> - nazwa użytkownika, <strong>:website</strong> - link do potwierdzenia'),
(4, 'Nowa prywatna wiadomość', 'Nowa prywatna wiadomość', '<div style="padding: 15px; border: 1px solid #ccc; background: #efefef; margin: 10px auto">\r\n	<p>\r\n		Witaj :name,</p>\r\n	<p>\r\n		Użytkownik :sender dodał nową wiadomość w prywatnej dyskusji: :title .</p>\r\n	<p>\r\n		<a href=":website">Kliknij, aby przeczytać wiadomość</a></p>\r\n	<p>\r\n		Pozdrawiamy</p>\r\n</div>\r\n<p>\r\n	&nbsp;</p>\r\n', '<strong>:name</strong> - nazwa użytkownika, <strong>:sender</strong> - autor wiadomości, <strong>:title</strong> - tytuł wiadomości, <strong>:website</strong> - link do wiadomości');

CREATE TABLE IF NOT EXISTS `{dbp}fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'text',
  `default` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `options` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}field_values` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field_id` int(10) unsigned NOT NULL DEFAULT '0',
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`user_id`,`field_id`),
  KEY `field_id` (`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbp}files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `filename` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `filelocation` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `comments_count` int(10) unsigned NOT NULL DEFAULT '0',
  `downloads` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `image` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}file_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_file_id` int(10) unsigned NOT NULL DEFAULT '0',
  `left` int(10) unsigned NOT NULL DEFAULT '0',
  `right` int(10) unsigned NOT NULL DEFAULT '0',
  `depth` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}fixtures` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `competition_id` int(10) unsigned NOT NULL DEFAULT '0',
  `season_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `number` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `competition_id` (`competition_id`),
  KEY `season_id` (`season_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}friends` (
  `requested_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_accepted` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`requested_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbp}groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `style` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

INSERT INTO `{dbp}groups` (`id`, `name`, `description`) VALUES
(1, 'Administratorzy', 'Osoby zarządzające stroną'),
(2, 'Użytkownicy', 'Zwykli użytkownicy serwisu');

CREATE TABLE IF NOT EXISTS `{dbp}karma` (
  `user_id` int(10) unsigned DEFAULT NULL,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbp}karma_comments` (
  `comment_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  KEY `comment_id` (`comment_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbp}login_attempts` (
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  `expires` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbp}logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}mailing_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(70) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}matches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `home_id` int(10) unsigned NOT NULL DEFAULT '0',
  `away_id` int(10) unsigned NOT NULL DEFAULT '0',
  `fixture_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `score` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `stadium` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `prematch_slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `report_slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `report_data` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `home_id` (`home_id`),
  KEY `away_id` (`away_id`),
  KEY `fixture_id` (`fixture_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}matchpicks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `match_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `expires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `options` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `best_player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `votes` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `match_id` (`match_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}matchpick_voters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `matchpick_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  PRIMARY KEY (`id`),
  KEY `matchpick_id` (`matchpick_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `link` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `left` int(10) unsigned NOT NULL DEFAULT '0',
  `right` int(10) unsigned NOT NULL DEFAULT '0',
  `depth` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `conversation_id` int(10) unsigned NOT NULL DEFAULT '0',
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_reported` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `conversation_id` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}monthpicks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `options` text COLLATE utf8_unicode_ci NOT NULL,
  `votes` int(10) unsigned NOT NULL DEFAULT '0',
  `best_player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}monthpick_voters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `monthpick_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  PRIMARY KEY (`id`),
  KEY `monthpick_id` (`monthpick_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `content` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `content_intro` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comments_count` int(10) unsigned NOT NULL DEFAULT '0',
  `source` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `image_text` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_published` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `karma` int(11) NOT NULL DEFAULT '0',
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `big_image` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `small_image` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `publish_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `enable_comments` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `external_url` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`),
  FULLTEXT KEY `title` (`content`,`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}newsletter` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'email',
  `emails` text COLLATE utf8_unicode_ci NOT NULL,
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  `in_progress` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}news_tags` (
  `tag_id` int(10) unsigned NOT NULL DEFAULT '0',
  `news_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tag_id`,`news_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbp}notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `message` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}packages` (
  `id` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1.0',
  `is_disabled` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `{dbp}packages` (`id`, `version`, `is_disabled`) VALUES
('core', '1.2', 0),
('sport', '1.2', 0);

CREATE TABLE IF NOT EXISTS `{dbp}pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `meta_title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `meta_keys` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `meta_description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `layout` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'main',
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}page_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `current` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  KEY `user_id` (`user_id`),
  FULLTEXT KEY `content` (`content`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}password_recovery` (
  `id` char(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `expires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbp}permissions` (
  `role_id` int(10) unsigned NOT NULL DEFAULT '0',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`role_id`,`group_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `{dbp}permissions` (`role_id`, `group_id`) VALUES
(1, 1);

CREATE TABLE IF NOT EXISTS `{dbp}photos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `image` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}photo_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_photo_id` int(10) unsigned NOT NULL DEFAULT '0',
  `comments_count` int(10) unsigned NOT NULL DEFAULT '0',
  `left` int(10) unsigned NOT NULL DEFAULT '0',
  `right` int(10) unsigned NOT NULL DEFAULT '0',
  `depth` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}players` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `number` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `image` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `position` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `height` int(10) unsigned NOT NULL DEFAULT '0',
  `weight` int(10) unsigned NOT NULL DEFAULT '0',
  `cost` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `prev_club` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `country` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `birthplace` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_on_loan` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`),
  KEY `team_id` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}player_injuries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `injury` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `recovery_date` date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (`id`),
  KEY `player_id` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}player_stats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `competition_id` int(10) unsigned NOT NULL DEFAULT '0',
  `season_id` int(10) unsigned NOT NULL DEFAULT '0',
  `goals` int(10) unsigned NOT NULL DEFAULT '0',
  `yellow_cards` int(10) unsigned NOT NULL DEFAULT '0',
  `red_cards` int(10) unsigned NOT NULL DEFAULT '0',
  `matches` int(10) unsigned NOT NULL DEFAULT '0',
  `assists` int(10) unsigned NOT NULL DEFAULT '0',
  `minutes` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `player_id` (`player_id`,`competition_id`,`season_id`),
  KEY `competition_id` (`competition_id`),
  KEY `season_id` (`season_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}player_transfers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int(10) unsigned NOT NULL DEFAULT '0',
  `from_team` int(10) unsigned NOT NULL DEFAULT '0',
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `cost` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `date` date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`),
  KEY `player_id` (`player_id`),
  KEY `from_team` (`from_team`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}polls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `votes` int(10) unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}poll_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `poll_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `votes` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `poll_id` (`poll_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}poll_voters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `poll_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  PRIMARY KEY (`id`),
  KEY `poll_id` (`poll_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}profiles` (
  `user_id` int(10) unsigned NOT NULL,
  `points` int(10) unsigned NOT NULL DEFAULT '0',
  `warnings_count` int(10) unsigned NOT NULL DEFAULT '0',
  `comments_count` int(10) unsigned NOT NULL DEFAULT '0',
  `news_count` int(10) unsigned NOT NULL DEFAULT '0',
  `is_banned` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `avatar` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `setting_editor` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ckeditor',
  `setting_email` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `setting_showemail` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `real_name` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `bet_points` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbp}relations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `match_id` int(10) unsigned NOT NULL DEFAULT '0',
  `current_score` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_finished` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `match_id` (`match_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}relation_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `relation_id` int(10) unsigned NOT NULL DEFAULT '0',
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `minute` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `data` tinytext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `relation_id` (`relation_id`),
  KEY `player_id` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}relation_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `relation_id` int(10) unsigned NOT NULL DEFAULT '0',
  `minute` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `minute_display` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'standard',
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `relation_id` (`relation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}relation_players` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `relation_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL,
  `number` int(10) unsigned NOT NULL DEFAULT '0',
  `team` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `squad` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `sorting` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `relation_id` (`relation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `item_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `item_id` int(10) NOT NULL DEFAULT '0',
  `item_link` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `saved_content` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `section` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=164 ;

INSERT INTO `{dbp}roles` (`id`, `name`, `section`, `title`) VALUES
(1, 'admin_root', 'Administracja', 'Root admin (wszystkie uprawnienia)'),
(2, 'admin_access', 'Administracja', 'Dostęp do panelu administracyjnego'),
(3, 'admin_dashboard_add', 'Dashboard', 'Może dodawać informacje'),
(4, 'admin_dashboard_delete', 'Dashboard', 'Może usuwać informacje'),
(5, 'admin_dashboard_edit', 'Dashboard', 'Może edytować informacje'),
(6, 'admin_logs', 'Logi', 'Może przeglądać logi'),
(7, 'admin_logs_delete', 'Logi', 'Może czyścić logi'),
(8, 'admin_submitted_content', 'Administracja', 'Może zarządzać podesłanymi materiałami'),
(9, 'admin_config', 'Administracja', 'Może zarządzać konfiguracją'),
(10, 'admin_reports', 'Zgłoszenia', 'Może zarządzać zgłoszeniami'),
(11, 'admin_reports_delete', 'Zgłoszenia', 'Może usuwać zgłoszenia'),
(12, 'admin_emails', 'Administracja', 'Może zarządzać e-mailami'),
(13, 'admin_prune', 'Administracja', 'Może czyścić stare dane'),
(14, 'admin_users', 'Użytkownicy', 'Może zarządzać użytkownikami'),
(15, 'admin_users_delete', 'Użytkownicy', 'Może usuwać użytkowników'),
(16, 'admin_users_edit', 'Użytkownicy', 'Może edytować użytkowników'),
(17, 'admin_users_multi', 'Użytkownicy', 'Może wykonywać masowe akcje na użytkownikach'),
(18, 'admin_groups', 'Grupy', 'Może zarządzać grupami'),
(19, 'admin_groups_edit', 'Grupy', 'Może edytować grupy'),
(20, 'admin_groups_add', 'Grupy', 'Może dodawać grupy'),
(21, 'admin_groups_delete', 'Grupy', 'Może usuwać grupy'),
(22, 'admin_users_add', 'Użytkownicy', 'Może dodawać użytkowników'),
(23, 'admin_fields', 'Własne pola', 'Może zarządzać polami'),
(24, 'admin_fields_add', 'Własne pola', 'Może dodawać pola'),
(25, 'admin_fields_delete', 'Własne pola', 'Może usuwać pola'),
(26, 'admin_fields_edit', 'Własne pola', 'Może edytować pola'),
(27, 'admin_fields_multi', 'Własne pola', 'Może wykonywać masowe akcje na polach'),
(28, 'admin_warnings', 'Ostrzeżenia', 'Może zarządzać ostrzeżeniami'),
(29, 'admin_warnings_add', 'Ostrzeżenia', 'Może dodawać ostrzeżenia'),
(30, 'admin_warnings_edit', 'Ostrzeżenia', 'Może edytować ostrzeżenia'),
(31, 'admin_warnings_delete', 'Ostrzeżenia', 'Może usuwać ostrzeżenia'),
(32, 'admin_warnings_multi', 'Ostrzeżenia', 'Może wykonywać multiakcje na ostrzeżeniach'),
(33, 'admin_validating_users', 'Nieaktywowani użytkownicy', 'Może zarządzać nieaktywowanymi użytkownikami'),
(34, 'admin_validating_users_multi', 'Nieaktywowani użytkownicy', 'Może wykonywać multioperacje na nich'),
(35, 'admin_validating_users_delete', 'Nieaktywowani użytkownicy', 'Może usuwać nieaktywowanych użytkowników'),
(36, 'admin_menu', 'Menu', 'Może zarządzać menu'),
(37, 'admin_mailing_list', 'Newsletter', 'Może zarządzać listą mailingową'),
(38, 'admin_newsletter', 'Newsletter', 'Może zarządzać newsletterem'),
(39, 'admin_newsletter_send', 'Newsletter', 'Może wysyłać newsletter'),
(40, 'admin_page', 'Podstrony', 'Może zarządzać podstronami'),
(41, 'admin_page_delete', 'Podstrony', 'Może usuwać podstrony'),
(42, 'admin_page_edit', 'Podstrony', 'Może edytować podstrony'),
(43, 'admin_page_add', 'Podstrony', 'Może dodawać podstrony'),
(44, 'admin_page_multi', 'Podstrony', 'Może wykonywać multi-akcje na podstronach'),
(45, 'admin_page_versions', 'Podstrony', 'Może zarządzać wersjami podstron'),
(46, 'admin_images', 'Obrazki', 'Może zarządzać obrazkami'),
(47, 'admin_images_add', 'Obrazki', 'Może dodawać obrazki'),
(48, 'admin_image_delete', 'Obrazki', 'Może usuwać obrazki'),
(49, 'admin_image_edit', 'Obrazki', 'Może edytować obrazki'),
(50, 'admin_tags', 'Tagi', 'Może zarządzać tagami'),
(51, 'admin_tags_delete', 'Tagi', 'Może usuwać tagi'),
(52, 'admin_tags_edit', 'Tagi', 'Może edytować tagi'),
(53, 'admin_tags_add', 'Tagi', 'Może dodawać tagi'),
(54, 'admin_tags_multi', 'Tagi', 'Może wykonywać multi-akcje na tagach'),
(55, 'admin_news', 'Newsy', 'Może zarządzać newsami'),
(56, 'admin_news_all', 'Newsy', 'Może zarządzać wszystkimi newsami'),
(57, 'admin_news_add', 'Newsy', 'Może dodawać newsy'),
(58, 'admin_news_edit', 'Newsy', 'Może edytować newsy'),
(59, 'admin_news_delete', 'Newsy', 'Może usuwać newsy'),
(60, 'admin_news_multi', 'Newsy', 'Może wykonywać multi-operacje na newsach'),
(61, 'admin_submitted_content_multi', 'Administracja', 'Może wykonywać multi-akcje na podesłanych materiałach'),
(62, 'admin_polls', 'Sondy', 'Może zarządzać sondami'),
(63, 'admin_polls_add', 'Sondy', 'Może dodawać sondy'),
(64, 'admin_polls_delete', 'Sondy', 'Może usuwać sondy'),
(65, 'admin_polls_edit', 'Sondy', 'Może edytować sondy'),
(66, 'admin_polls_multi', 'Sondy', 'Może wykonywać multi-akcje na sondach'),
(67, 'admin_comments', 'Komentarze', 'Może zarządzać komentarzami'),
(68, 'admin_comments_delete', 'Komentarze', 'Może usuwać komentarze'),
(69, 'admin_comments_edit', 'Komentarze', 'Może edytować komentarze'),
(70, 'admin_blogs', 'Blogi', 'Może zarządzać blogami'),
(71, 'admin_blogs_delete', 'Blogi', 'Może usuwać wpisy w blogach'),
(72, 'admin_blogs_edit', 'Blogi', 'Może edytować wpisy w blogach'),
(73, 'admin_blogs_multi', 'Blogi', 'Może wykonywać multi-akcje na wpisach blogów'),
(74, 'admin_file_categories', 'Kategorie multimediów', 'Może zarządzać kategoriami plików'),
(75, 'admin_files', 'Pliki', 'Może zarządzać plikami'),
(76, 'admin_files_add', 'Pliki', 'Może dodawać pliki'),
(77, 'admin_files_edit', 'Pliki', 'Może edytować pliki'),
(78, 'admin_files_delete', 'Pliki', 'Może usuwać pliki'),
(79, 'admin_photo_categories', 'Kategorie multimediów', 'Może zarządzać kategoriami galerii'),
(80, 'admin_photos', 'Galeria', 'Może zarządzać zdjęciami'),
(81, 'admin_photos_add', 'Galeria', 'Może dodawać zdjęcia'),
(82, 'admin_photos_edit', 'Galeria', 'Może edytować zdjęcia'),
(83, 'admin_photos_delete', 'Galeria', 'Może usuwać zdjęcia'),
(84, 'admin_video_categories', 'Kategorie multimedów', 'Może zarządzać kategoriami video'),
(85, 'admin_videos', 'Video', 'Może zarządzać video'),
(86, 'admin_videos_add', 'Video', 'Może dodawać video'),
(87, 'admin_videos_edit', 'Video', 'Może edytować video'),
(88, 'admin_videos_delete', 'Video', 'Może usuwać video'),
(89, 'admin_shoutbox', 'Shoutbox', 'Może zarządzać shoutboxem'),
(90, 'admin_shoutbox_multi', 'Shoutbox', 'Może wykonywać multi-akcje na wpisach shoutboxa'),
(91, 'admin_shoutbox_delete', 'Shoutbox', 'Może usuwać wpisy z shoutbox-a'),
(92, 'admin_templates', 'Administracja', 'Może zarządzać szablonami'),
(93, 'admin_competitions', 'Rozgrywki', 'Może zarządzać rozgrywkami'),
(94, 'admin_competitions_add', 'Rozgrywki', 'Może dodawać rozgrywki'),
(95, 'admin_competitions_edit', 'Rozgrywki', 'Może edytować rozgrywki'),
(96, 'admin_competitions_delete', 'Rozgrywki', 'Może usuwać rozgrywki'),
(97, 'admin_teams', 'Kluby', 'Może zarządzać klubami'),
(98, 'admin_teams_add', 'Kluby', 'Może dodawać kluby'),
(99, 'admin_teams_edit', 'Kluby', 'Może edytować kluby'),
(100, 'admin_teams_delete', 'Kluby', 'Może usuwać kluby'),
(101, 'admin_teams_multi', 'Kluby', 'Może wykonywać multi-operacje na klubach'),
(102, 'admin_seasons', 'Sezony', 'Może zarządzać sezonami'),
(103, 'admin_seasons_add', 'Sezony', 'Może dodawać sezony'),
(104, 'admin_seasons_edit', 'Sezony', 'Może edytować sezony'),
(105, 'admin_seasons_delete', 'Sezony', 'Może usuwać sezony'),
(106, 'admin_fixtures', 'Kolejki', 'Może zarządzać kolejkami'),
(107, 'admin_fixtures_add', 'Kolejki', 'Może dodawać kolejki'),
(108, 'admin_fixtures_edit', 'Kolejki', 'Może edytować kolejki'),
(109, 'admin_fixtures_delete', 'Kolejki', 'Może usuwać kolejki'),
(110, 'admin_fixtures_multi', 'Kolejki', 'Może usuwać wiele kolejek naraz'),
(111, 'admin_matches', 'Mecze', 'Może zarządzać meczami'),
(112, 'admin_matches_add', 'Mecze', 'Może dodawać mecze'),
(113, 'admin_matches_edit', 'Mecze', 'Może edytować mecze'),
(114, 'admin_matches_delete', 'Mecze', 'Może usuwać mecze'),
(115, 'admin_matches_multi', 'Mecze', 'Może usuwać wiele meczów naraz'),
(116, 'admin_players', 'Zawodnicy', 'Może zarządzać zawodnikami'),
(117, 'admin_players_add', 'Zawodnicy', 'Może dodawać zawodników'),
(118, 'admin_players_edit', 'Zawodnicy', 'Może edytować zawodników'),
(119, 'admin_players_delete', 'Zawodnicy', 'Może usuwać zawodników'),
(120, 'admin_players_multi', 'Zawodnicy', 'Może usuwać wielu zawodników naraz'),
(121, 'admin_tables', 'Tabele', 'Może zarządzać tabelami'),
(122, 'admin_tables_add', 'Tabele', 'Może dodawać tabele'),
(123, 'admin_tables_edit', 'Tabele', 'Może edytować tabele'),
(124, 'admin_tables_delete', 'Tabele', 'Może usuwać tabele'),
(125, 'admin_tables_multi', 'Tabele', 'Może usuwać wiele tabel naraz'),
(126, 'admin_player_stats', 'Statystyki', 'Może zarządzać statystykami'),
(127, 'admin_player_stats_add', 'Statystyki', 'Może dodawać statystyki'),
(128, 'admin_player_stats_edit', 'Statystyki', 'Może edytować statystyki'),
(129, 'admin_player_stats_delete', 'Statystyki', 'Może usuwać statystyki'),
(130, 'admin_player_stats_multi', 'Statystyki', 'Może usuwać wiele statystyk naraz'),
(131, 'admin_player_transfers', 'Transfery', 'Może zarządzać transferami'),
(132, 'admin_player_transfers_add', 'Transfery', 'Może dodawać transfery'),
(133, 'admin_player_transfers_edit', 'Transfery', 'Może edytować transfery'),
(134, 'admin_player_transfers_delete', 'Transfery', 'Może usuwać transfery'),
(135, 'admin_player_transfers_multi', 'Transfery', 'Może usuwać wiele transferów naraz'),
(136, 'admin_player_injuries', 'Kontuzje', 'Może zarządzać kontuzjami'),
(137, 'admin_player_injuries_add', 'Kontuzje', 'Może dodawać kontuzje'),
(138, 'admin_player_injuries_edit', 'Kontuzje', 'Może edytować kontuzje'),
(139, 'admin_player_injuries_delete', 'Kontuzje', 'Może usuwać kontuzje'),
(140, 'admin_player_injuries_multi', 'Kontuzje', 'Może usuwać wiele kontuzji naraz'),
(141, 'admin_bet_matches', 'Typer', 'Może zarządzać meczami typera'),
(142, 'admin_bet_matches_add', 'Typer', 'Może dodawać mecze typera'),
(143, 'admin_bet_matches_edit', 'Typer', 'Może edytować mecze typera'),
(144, 'admin_bet_matches_delete', 'Typer', 'Może usuwać mecze typera'),
(145, 'admin_bet_matches_multi', 'Typer', 'Może usuwać wiele meczy typera naraz'),
(146, 'admin_monthpicks', 'Piłkarz miesiąca', 'Może zarządzać głosowaniem'),
(147, 'admin_monthpicks_add', 'Piłkarz miesiąca', 'Może dodawać głosowania'),
(148, 'admin_monthpicks_edit', 'Piłkarz miesiąca', 'Może edytować głosowania'),
(149, 'admin_monthpicks_delete', 'Piłkarz miesiąca', 'Może usuwać głosowania'),
(150, 'admin_monthpicks_multi', 'Piłkarz miesiąca', 'Może usuwać wiele głosowań naraz'),
(151, 'admin_matchpicks', 'Piłkarz meczu', 'Może zarządzać głosowaniem'),
(152, 'admin_matchpicks_add', 'Piłkarz meczu', 'Może dodawać głosowania'),
(153, 'admin_matchpicks_edit', 'Piłkarz meczu', 'Może edytować głosowania'),
(154, 'admin_matchpicks_delete', 'Piłkarz meczu', 'Może usuwać głosowania'),
(155, 'admin_matchpicks_multi', 'Piłkarz meczu', 'Może usuwać wiele głosowań naraz'),
(156, 'admin_relations', 'Relacje live', 'Może zarządzać relacjami live'),
(157, 'admin_relations_add', 'Relacje live', 'Moze dodawać relacje live'),
(158, 'admin_relations_edit', 'Relacje live', 'Może prowadzić relacje live'),
(159, 'admin_relations_delete', 'Relacje live', 'Może usuwać relacje live'),
(160, 'admin_relations_multi', 'Relacje live', 'Może usuwać wiele relacji live naraz'),
(161, 'mod_warnings', 'Moderacja', 'Może dodawać/usuwać ostrzeżenia'),
(162, 'mod_shoutbox', 'Moderacja', 'Może moderować shoutbox'),
(163, 'mod_blogs', 'Moderacja', 'Może moderować blogi'),
(164, 'admin_xss', 'Administracja', 'Może używać potencjalnie niebezpiecznego kodu w newsach i podstronach?');

CREATE TABLE IF NOT EXISTS `{dbp}seasons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint(5) unsigned NOT NULL DEFAULT '2012',
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

INSERT INTO `{dbp}seasons` (`id`, `year`, `is_active`) VALUES
(1, 2012, 1);

CREATE TABLE IF NOT EXISTS `{dbp}sessions` (
  `id` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  `location_name` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `location_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbp}shoutbox` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'global',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}submitted_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'news',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}tables` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `competition_id` int(10) unsigned NOT NULL DEFAULT '0',
  `season_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sorting_rules` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'standard',
  `auto_generation` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `competition_id` (`competition_id`),
  KEY `season_id` (`season_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}table_positions` (
  `team_id` int(10) unsigned NOT NULL DEFAULT '0',
  `table_id` int(10) unsigned NOT NULL DEFAULT '0',
  `points` smallint(6) NOT NULL DEFAULT '0',
  `matches` smallint(5) unsigned NOT NULL DEFAULT '0',
  `wins` smallint(5) unsigned NOT NULL DEFAULT '0',
  `losses` smallint(5) unsigned NOT NULL DEFAULT '0',
  `draws` smallint(5) unsigned NOT NULL DEFAULT '0',
  `goals_shot` smallint(5) NOT NULL DEFAULT '0',
  `goals_lost` smallint(5) NOT NULL DEFAULT '0',
  `position` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`team_id`,`table_id`),
  KEY `table_id` (`table_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbp}tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

INSERT INTO `{dbp}tags` (`id`, `title`, `slug`) VALUES
(1, 'Główne newsy', 'glowne-newsy-1');

CREATE TABLE IF NOT EXISTS `{dbp}teams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `image` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `is_distinct` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `year` smallint(5) unsigned NOT NULL DEFAULT '2012',
  `address` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `colors` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `stadium` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `chairman` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `coach` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `star` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `webpage` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `country` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `password` char(60) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(70) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `display_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `display_name` (`display_name`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}validating_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `activation_code` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `password` char(60) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `display_name` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(70) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `activation_code` (`activation_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}videos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `embed` text COLLATE utf8_unicode_ci NOT NULL,
  `link` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `thumbnail` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `comments_count` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}video_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `slug` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_video_id` int(10) unsigned NOT NULL DEFAULT '0',
  `left` int(10) unsigned NOT NULL DEFAULT '0',
  `right` int(10) unsigned NOT NULL DEFAULT '0',
  `depth` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}warnings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `mod_id` int(10) unsigned NOT NULL DEFAULT '0',
  `reason` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `mod_id` (`mod_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbp}widgets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(127) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'html',
  `options` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;


ALTER TABLE `{dbp}admin_notes`
  ADD CONSTRAINT `{dbp}admin_notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}bets`
  ADD CONSTRAINT `{dbp}bets_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}bets_ibfk_3` FOREIGN KEY (`match_id`) REFERENCES `{dbp}bet_matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}blogs`
  ADD CONSTRAINT `{dbp}blogs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}comments`
  ADD CONSTRAINT `{dbp}comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}competition_teams`
  ADD CONSTRAINT `{dbp}competition_teams_ibfk_1` FOREIGN KEY (`competition_id`) REFERENCES `{dbp}competitions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}competition_teams_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `{dbp}teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}competition_teams_ibfk_3` FOREIGN KEY (`season_id`) REFERENCES `{dbp}seasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}config`
  ADD CONSTRAINT `{dbp}config_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `{dbp}config_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}conversations`
  ADD CONSTRAINT `{dbp}conversations_ibfk_1` FOREIGN KEY (`poster_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}conversation_users`
  ADD CONSTRAINT `{dbp}conversation_users_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `{dbp}conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}conversation_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}field_values`
  ADD CONSTRAINT `{dbp}field_values_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}field_values_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `{dbp}fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}files`
  ADD CONSTRAINT `{dbp}files_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}files_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `{dbp}file_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}fixtures`
  ADD CONSTRAINT `{dbp}fixtures_ibfk_1` FOREIGN KEY (`competition_id`) REFERENCES `{dbp}competitions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}fixtures_ibfk_2` FOREIGN KEY (`season_id`) REFERENCES `{dbp}seasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}friends`
  ADD CONSTRAINT `{dbp}friends_ibfk_1` FOREIGN KEY (`requested_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}friends_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}karma`
  ADD CONSTRAINT `{dbp}karma_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}karma_comments`
  ADD CONSTRAINT `{dbp}karma_comments_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `{dbp}comments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}karma_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}logs`
  ADD CONSTRAINT `{dbp}logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}matches`
  ADD CONSTRAINT `{dbp}matches_ibfk_1` FOREIGN KEY (`home_id`) REFERENCES `{dbp}teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}matches_ibfk_2` FOREIGN KEY (`away_id`) REFERENCES `{dbp}teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}matches_ibfk_3` FOREIGN KEY (`fixture_id`) REFERENCES `{dbp}fixtures` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}matchpicks`
  ADD CONSTRAINT `{dbp}matchpicks_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `{dbp}matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}matchpick_voters`
  ADD CONSTRAINT `{dbp}matchpick_voters_ibfk_1` FOREIGN KEY (`matchpick_id`) REFERENCES `{dbp}matchpicks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}matchpick_voters_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}messages`
  ADD CONSTRAINT `{dbp}messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}messages_ibfk_2` FOREIGN KEY (`conversation_id`) REFERENCES `{dbp}conversations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}monthpick_voters`
  ADD CONSTRAINT `{dbp}monthpick_voters_ibfk_1` FOREIGN KEY (`monthpick_id`) REFERENCES `{dbp}monthpicks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}monthpick_voters_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}news_tags`
  ADD CONSTRAINT `{dbp}news_tags_ibfk_1` FOREIGN KEY (`tag_id`) REFERENCES `{dbp}tags` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}notifications`
  ADD CONSTRAINT `{dbp}notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}pages`
  ADD CONSTRAINT `{dbp}pages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}password_recovery`
  ADD CONSTRAINT `{dbp}password_recovery_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}permissions`
  ADD CONSTRAINT `{dbp}permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `{dbp}roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}permissions_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `{dbp}groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}photos`
  ADD CONSTRAINT `{dbp}photos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}photos_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `{dbp}photo_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}players`
  ADD CONSTRAINT `{dbp}players_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `{dbp}teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}player_injuries`
  ADD CONSTRAINT `{dbp}player_injuries_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `{dbp}players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}player_stats`
  ADD CONSTRAINT `{dbp}player_stats_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `{dbp}players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}player_stats_ibfk_2` FOREIGN KEY (`competition_id`) REFERENCES `{dbp}competitions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}player_stats_ibfk_3` FOREIGN KEY (`season_id`) REFERENCES `{dbp}seasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}player_transfers`
  ADD CONSTRAINT `{dbp}player_transfers_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `{dbp}teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}player_transfers_ibfk_3` FOREIGN KEY (`player_id`) REFERENCES `{dbp}players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}player_transfers_ibfk_4` FOREIGN KEY (`from_team`) REFERENCES `{dbp}teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}poll_options`
  ADD CONSTRAINT `{dbp}poll_options_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `{dbp}polls` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}poll_voters`
  ADD CONSTRAINT `{dbp}poll_voters_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `{dbp}polls` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}poll_voters_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}profiles`
  ADD CONSTRAINT `{dbp}profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}relations`
  ADD CONSTRAINT `{dbp}relations_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `{dbp}matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}relation_events`
  ADD CONSTRAINT `{dbp}relation_events_ibfk_1` FOREIGN KEY (`relation_id`) REFERENCES `{dbp}relations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}relation_events_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `{dbp}relation_players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}relation_messages`
  ADD CONSTRAINT `{dbp}relation_messages_ibfk_1` FOREIGN KEY (`relation_id`) REFERENCES `{dbp}relations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}relation_players`
  ADD CONSTRAINT `{dbp}relation_players_ibfk_1` FOREIGN KEY (`relation_id`) REFERENCES `{dbp}relations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}reports`
  ADD CONSTRAINT `{dbp}reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}shoutbox`
  ADD CONSTRAINT `{dbp}shoutbox_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}submitted_content`
  ADD CONSTRAINT `{dbp}submitted_content_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}tables`
  ADD CONSTRAINT `{dbp}tables_ibfk_1` FOREIGN KEY (`competition_id`) REFERENCES `{dbp}competitions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}tables_ibfk_2` FOREIGN KEY (`season_id`) REFERENCES `{dbp}seasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}table_positions`
  ADD CONSTRAINT `{dbp}table_positions_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `{dbp}teams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}table_positions_ibfk_2` FOREIGN KEY (`table_id`) REFERENCES `{dbp}tables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}users`
  ADD CONSTRAINT `{dbp}users_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `{dbp}groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}videos`
  ADD CONSTRAINT `{dbp}videos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}videos_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `{dbp}video_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `{dbp}warnings`
  ADD CONSTRAINT `{dbp}warnings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `{dbp}warnings_ibfk_2` FOREIGN KEY (`mod_id`) REFERENCES `{dbp}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;