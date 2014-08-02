<?php

return array(

/*
 |--------------------------------------------------------------------------
| Validation Language Lines
|--------------------------------------------------------------------------
|
| The following language lines contain the default error messages used
| by the validator class. Some of the rules contain multiple versions,
| such as the size (max, min, between) rules. These versions are used
| for different input types such as strings and files.
|
| These language lines may be easily changed to provide custom error
| messages in your application. Error messages for custom validation
| rules may also be added to this file.
|
*/

"accepted"       => ":attribute musi zostać zaakceptowany.",
"active_url"     => ":attribute nie jest prawidłowym URL.",
"after"          => ":attribute musi być po dacie :date.",
"alpha"          => ":attribute może zawierać wyłącznie litery.",
"alpha_dash"     => ":attribute może zawierać wyłącznie litery, liczby oraz myślniki.",
"alpha_num"      => "attribute może zawierać wyłącznie litery oraz liczby.",
"before"         => ":attribute musi być przed datą :date.",
"between"        => array(
"numeric" => ":attribute musi być pomiędzy :min - :max.",
"file"    => ":attribute musi mieć rozmiar pomiędzy :min - :max kb.",
"string"  => ":attribute musi mieć ilość znaków pomiędzy :min - :max.",
),
"confirmed"      => ":attribute ma niepasujące potwierdzenie.",
"different"      => ":attribute oraz :other muszą się różnić.",
"email"          => ":attribute musi być prawidłowym adresem e-mail.",
"exists"         => ":attribute jest nieprawidłowy.",
"image"          => ":attribute musi być prawidłowym obrazem.",
"in"             => ":attribute jest nieprawidłowy.",
"integer"        => ":attribute musi być liczbą całkowitą.",
"ip"             => ":attribute musi być prawidłowym adresem IP.",
"match"          => ":attribute jest w nieprawidłowym formacie.",
"max"            => array(
"numeric" => ":attribute może mieć maksymalnie :max.",
"file"    => ":attribute musi być mniejsze niż :max kb.",
"string"  => ":attribute musi mieć mniej znaków niż :max.",
),
"mimes"          => ":attribute musi być plikiem typu: :values.",
"min"            => array(
"numeric" => ":attribute musi być conajmniej :min.",
"file"    => ":attribute musi mieć conajmniej :min kb.",
"string"  => ":attribute musi zawierać conajmniej :min znaków.",
),
"not_in"         => ":attribute jest nieprawidłowy.",
"numeric"        => ":attribute musi być liczbą.",
"required"       => ":attribute jest wymagany(a).",
"same"           => ":attribute oraz :other muszą być takie same.",
"size"           => array(
"numeric" => ":attribute musi mieć :size liczb.",
"file"    => ":attribute musi mieć :size kb.",
"string"  => ":attribute musi mieć :size znaków.",
),
"unique"         => ":attribute już znajduje się w naszej bazie danych.",
"url"            => ":attribute jest w nieprawidłowym formacie.",

/*
 |--------------------------------------------------------------------------
| Custom Validation Language Lines
|--------------------------------------------------------------------------
|
| Here you may specify custom validation messages for attributes using the
| convention "attribute_rule" to name the lines. This helps keep your
| custom validation clean and tidy.
|
| So, say you want to use a custom validation message when vlidating that
| the "email" attribute is unique. Just add "email_unique" to this array
| with your custom message. The Validator will handle the rest!
|
*/

'custom' => array(),

/*
 |--------------------------------------------------------------------------
| Validation Attributes
|--------------------------------------------------------------------------
|
| The following language lines are used to swap attribute place-holders
| with something more reader friendly such as "E-Mail Address" instead
| of "email". Your users will thank you.
|
| The Validator class will automatically search this array of lines it
| is attempting to replace the :attribute place-holder in messages.
| It's pretty slick. We think you'll like it.
|
*/

'attributes' => array(
	'subject' => 'Temat',
	'message' => 'Wiadomość',
	'display_name' => 'Nazwa wyświetlana',
	'username' => 'Login',
	'email' => 'E-mail',
	'password' => 'Hasło',
	'slug' => 'Slug',
	'points' => 'Punkty',
	'title' => 'Tytuł',
	'description' => 'Opis',
	'name' => 'Nazwa',
	'user' => 'Użytkownik',
	'reason' => 'Powód',
	'page_content' => 'Treść',
	'news_content' => 'Treść',
	'source' => 'Źródło',
	'image_text' => 'Podpis',
	'news_short' => 'Treść',
	'guest_name' => 'Nazwa gościa',
	'comment_raw' => 'Komentarz',
	'content_raw' => 'Treść',
	'created_at' => 'Data dodania',
	'publish_at' => 'Data publikacji',
	'filelocation' => 'Plik',
	'image' => 'Obraz',
	'thumbnail' => 'Miniaturka',
	'link' => 'Link',
	'embed' => 'Kod HTML',
	'year' => 'Rok',
	'number' => 'Numer',
	'position' => 'Pozycja',
	'goals' => 'Bramki',
	'yellow_cards' => 'Żółte kartki',
	'red_cards' => 'Czerwone kartki',
	'date' => 'Data',
	'injury' => 'Kontuzja',
	'recovery_date' => 'Data wyzdrowienia',
	'player_id' => 'Zawodnik',
	'password_confirm' => 'Potwierdzenie hasła',
	'style' => 'Styl'
),

);