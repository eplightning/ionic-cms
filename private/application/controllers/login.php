<?php

/**
 * Login / logout / recover password
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Login_Controller extends Base_Controller {

    /**
     * Login
     *
     * @return Response
     */
    public function action_index()
    {
        if (Auth::is_logged())
        {
            return Redirect::to('index');
        }

        if (!Request::forged() and Request::method() == 'POST' and Input::has('username') and Input::has('password'))
        {
            $status = Auth::login(Input::get('username'), Input::get('password'), Input::get('remember') == '1', Input::get('anonymous') == '1');

            switch ($status)
            {
                case Auth::LOGIN_SUCCESS:
                    $this->notice('Zostałeś pomyślnie zalogowany w serwisie.');
                    return Redirect::to('index');

                case Auth::LOGIN_BRUTEFORCE:
                    $this->notice('Z powodów bezpieczeństwa musisz poczekać do 10 minut przed ponownym wprowadzeniem danych.');
                    return Redirect::to('login/index')->with_input('only', array('username'));

                case Auth::LOGIN_INVALID_PASSWORD:
                    $this->notice('Podane hasło jest nieprawidłowe.');
                    return Redirect::to('login/index')->with_input('only', array('username'));

                default:
                    $this->notice('Nie znaleziono takiego użytkownika w naszej bazie danych.');
                    return Redirect::to('login/index')->with_input('only', array('username'));
            }
        }

        $this->online('Logowanie', 'login/index');

        $this->page->set_title('Logowanie');
        $this->page->breadcrumb_append('Logowanie', 'login/index');

        $this->view = View::make('login.index', array('old' => Input::old()));
    }

    /**
     * Logout
     *
     * @return Response
     */
    public function action_logout()
    {
        if (Auth::is_guest())
        {
            return Redirect::to('index');
        }

        Auth::logout();

        $this->notice('Zostałeś pomyślnie wylogowany z serwisu.');
        return Redirect::to('index');
    }

    /**
     * Recover password
     *
     * @param  string   $id
     * @return Response
     */
    public function action_password($id = null)
    {
        DB::table('password_recovery')->where('expires', '<=', date('Y-m-d H:i:s'))->delete();

        if (Auth::is_logged())
        {
            return Redirect::to('index');
        }

        require_once path('app').'vendor'.DS.'recaptchalib.php';

        $this->online('Przywracanie hasła', 'login/password');

        $this->page->set_title('Przywracanie hasła');
        $this->page->breadcrumb_append('Przywracanie hasła', 'login/password');

        if ($id)
        {
            $id = DB::table('password_recovery')->where('id', '=', $id)->first(array('id', 'user_id'));

            if (!$id)
            {
                $this->notice('Ten kod przywracania hasła już wygasł. Wymagane jest ponowne rozpoczęcie procesu.');
                return Redirect::to('login/password');
            }

            if (Request::method() == 'POST' and !Request::forged())
            {
                if (!Input::has('password') or !Input::has('password_confirm'))
                {
                    $this->notice('Obydwa pola są wymagane');
                    return Redirect::to('login/password/'.$id->id);
                }

                $password = Input::get('password');

                if ($password != Input::get('password_confirm'))
                {
                    $this->notice('Potwierdzenie hasła jest nieprawidłowe');
                    return Redirect::to('login/password/'.$id->id);
                }

                if (Str::length($password) < 6)
                {
                    $this->notice('Hasło musi mieć conajmniej 6 znaków');
                    return Redirect::to('login/password/'.$id->id);
                }

                DB::table('users')->where('id', '=', $id->user_id)->update(array(
                    'password' => Hash::make($password)
                ));

                DB::table('password_recovery')->where('id', '=', $id->id)->delete();

                $this->notice('Hasło zostało pomyślnie zmienione. Możesz teraz się zalogować.');
                return Redirect::to('login/index');
            }

            $this->view = View::make('login.password_change', array('action' => URL::to('login/password/'.$id->id)));
            return;
        }

        if (Request::method() == 'POST' and !Request::forged())
        {
            if (!isset($_POST['recaptcha_challenge_field']))
                $_POST['recaptcha_challenge_field'] = '';
            if (!isset($_POST['recaptcha_response_field']))
                $_POST['recaptcha_response_field'] = '';

            $response = recaptcha_check_answer(Config::get('advanced.recaptcha_private', ''), Request::ip(), $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);

            if (!$response->is_valid)
            {
                $this->notice('Wprowadzony kod z obrazka jest nieprawidłowy');
                return Redirect::to('login/password');
            }

            if (!Input::has('username') and !Input::has('email'))
            {
                $this->notice('Przynajmniej jedno pole jest wymagane');
                return Redirect::to('login/password');
            }

            if (Input::has('username'))
            {
                $user = DB::table('users')->where('username', '=', Input::get('username'))->first(array('id', 'email', 'username'));
            }
            else
            {
                $user = DB::table('users')->where('email', '=', Input::get('email'))->first(array('id', 'email', 'username'));
            }

            if (!$user)
            {
                $this->notice('Nie znaleziono takiego użytkownika');
                return Redirect::to('login/password');
            }

            $password = DB::table('password_recovery')->where('user_id', '=', $user->id)->first('id');

            if ($password)
            {
                $this->notice('Ten użytkownik nie zakończył jeszcze poprzedniego procesu przywracania hasła');
                return Redirect::to('login/password');
            }

            $id = Str::random(20);

            DB::table('password_recovery')->insert(array(
                'id'      => $id,
                'user_id' => $user->id,
                'expires' => date('Y-m-d H:i:s', (time() + 86400))
            ));

            ionic_mail(2, $user->email, array(
                ':name'    => $user->username,
                ':ip'      => Request::ip(),
                ':website' => URL::to('login/password/'.$id)
            ));

            $this->notice('Wiadomość e-mail potrzebna do potwierdzenia została wysłana na adres e-mail powiązany z tym kontem');
            return Redirect::to('login/password');
        }

        $this->view = View::make('login.password', array(
                    'recaptcha' => recaptcha_get_html(Config::get('advanced.recaptcha_public', ''))
                ));
    }

}