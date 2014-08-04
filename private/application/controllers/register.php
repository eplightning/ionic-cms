<?php

use Laravel\Validator;

/**
 * Registration
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Register_Controller extends Base_Controller {

    /**
     * Form
     *
     * @return Response
     */
    public function action_accepted()
    {
        if (Auth::is_logged())
        {
            return Redirect::to('index');
        }

        require_once path('app').'vendor'.DS.'recaptchalib.php';

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
                return Redirect::to('register/accepted')->with_input('only', array('username', 'password', 'password_confirm', 'display_name', 'email'));
            }

            $data = array('username'         => '', 'password'         => '', 'password_confirm' => '', 'display_name'     => '', 'email'            => '');
            $data = array_merge($data, Input::only(array('username', 'password', 'password_confirm', 'display_name', 'email')));

            $validator = Validator::make($data, array(
                        'username'         => 'required|max:20|match:!^[\pL\pN\s]+$!u|unique:users,username|unique:validating_users,username',
                        'display_name'     => 'required|max:20|match:!^[\pL\pN\s]+$!u|unique:users,display_name|unique:validating_users,display_name',
                        'email'            => 'required|max:70|email|unique:users,email|unique:validating_users,email',
                        'password'         => 'required|min:6',
                        'password_confirm' => 'required|same:password'
                    ));

            if ($validator->fails())
            {
                return Redirect::to('register/accepted')->with_errors($validator)
                                ->with_input('only', array('username', 'password', 'password_confirm', 'display_name', 'email'));
            }
            else
            {
                if (Config::get('register.activation', 'email') == 'auto')
                {
                    Model\User::add_user(HTML::specialchars($data['username']), $data['password'], HTML::specialchars($data['email']), HTML::specialchars($data['display_name']));

                    $this->notice('Twoje konto zostało pomyślnie utworzone. Możesz teraz się zalogować');
                    return Redirect::to('login');
                }

                do
                {
                    $code = Str::random(20);
                }
                while (DB::table('validating_users')->where('activation_code', '=', $code)->first('id'));

                DB::table('validating_users')->insert(array(
                    'activation_code' => $code,
                    'username'        => HTML::specialchars($data['username']),
                    'password'        => Hash::make($data['password']),
                    'display_name'    => HTML::specialchars($data['display_name']),
                    'email'           => HTML::specialchars($data['email']),
                    'created_at'      => date('Y-m-d H:i:s'),
                    'ip'              => Request::ip()
                ));

                if (Config::get('register.activation', 'email') == 'email')
                {
                    ionic_mail(3, $data['email'], array(
                        ':website' => URL::to('register/activate/'.$code),
                        ':name'    => HTML::specialchars($data['username'])
                    ));

                    $this->notice('Rejestracja przebiegła pomyślnie, jednak abyś mógł zacząć korzystać z konta musi ono zostać aktywowane poprzez link wysłany w wiadomości na twój adres e-mail');
                }
                else
                {
                    $this->notice('Rejestracja przebiegła pomyślnie, jednak abyś mógł zacząć korzystać z konta musi ono zostać aktywowane przez administratora. Zostaniesz powiadomiony drogą mailową');
                }

                return Redirect::to('login');
            }
        }

        $this->online('Rejestracja', 'register/accepted');

        $this->page->set_title('Rejestracja');
        $this->page->breadcrumb_append('Rejestracja', 'register');
        $this->page->breadcrumb_append('Formularz', 'register/accepted');

        $this->view = View::make('register.accepted', array(
                    'old'       => Input::old(),
                    'recaptcha' => recaptcha_get_html(Config::get('advanced.recaptcha_public', ''))
                ));
    }

    /**
     * Validate account
     *
     * @param  string   $id
     * @return Response
     */
    public function action_activate($id)
    {
        if (Auth::is_logged() or Config::get('register.activation', 'email') != 'email')
        {
            return Redirect::to('index');
        }

        $id = DB::table('validating_users')->where('activation_code', '=', $id)->first('*');

        if (!$id)
        {
            $this->notice('Podany kod aktywacji jest nieprawidłowy');
            return Redirect::to('index');
        }

        // First users table
        $id_new = DB::table('users')->insert_get_id(array(
            'username'     => $id->username,
            'password'     => $id->password,
            'email'        => $id->email,
            'display_name' => $id->display_name,
            'group_id'     => 2,
            'slug'         => ionic_tmp_slug('users')
                ));

        DB::table('users')->where('id', '=', $id_new)->update(array('slug' => ionic_find_slug($id->display_name, $id_new, 'users', 30)));

        // Profile
        DB::table('profiles')->insert(array(
            'user_id'    => $id_new,
            'ip'         => $id->ip,
            'created_at' => $id->created_at
        ));

        DB::table('validating_users')->where('id', '=', $id->id)->delete();

        $this->notice('Konto zostało pomyślnie aktywowane, teraz możesz się zalogować korzystając z formularza poniżej');
        return Redirect::to('login/index');
    }

    /**
     * Ajax validation
     *
     * @return Response
     */
    public function action_ajax()
    {
        if (Auth::is_logged() or !Request::ajax() or Request::method() != 'POST' or !Input::has('id'))
        {
            return Response::json(array('error'   => true, 'message' => 'Nieznany błąd'));
        }

        if (!Input::has('value'))
        {
            return Response::json(array('error'   => true, 'message' => 'To pole jest wymagane'));
        }

        if (Input::get('id') == 'username')
        {
            if (Str::length(Input::get('value')) > 20 or Str::length(Input::get('value')) < 3)
            {
                return Response::json(array('error'   => true, 'message' => 'Nazwa użytkownika musi mieć 3-20 znaków'));
            }

            if (!preg_match('!^[\pL\pN\s]+$!u', Input::get('value')))
            {
                return Response::json(array('error'   => true, 'message' => 'Nazwa użytkownika może zawierać litery, liczby oraz spacje'));
            }

            if (DB::table('users')->where('username', '=', Input::get('value'))->first('id') or DB::table('validating_users')->where('username', '=', Input::get('value'))->first('id'))
            {
                return Response::json(array('error'   => true, 'message' => 'Taka nazwa użytkownika została już zarejestrowana'));
            }
        }
        elseif (Input::get('id') == 'display_name')
        {
            if (Str::length(Input::get('value')) > 20 or Str::length(Input::get('value')) < 3)
            {
                return Response::json(array('error'   => true, 'message' => 'Nazwa użytkownika musi mieć 3-20 znaków'));
            }

            if (!preg_match('!^[\pL\pN\s]+$!u', Input::get('value')))
            {
                return Response::json(array('error'   => true, 'message' => 'Nazwa użytkownika może zawierać litery, liczby oraz spacje'));
            }

            if (DB::table('users')->where('display_name', '=', Input::get('value'))->first('id') or DB::table('validating_users')->where('display_name', '=', Input::get('value'))->first('id'))
            {
                return Response::json(array('error'   => true, 'message' => 'Taka nazwa użytkownika została już zarejestrowana'));
            }
        }
        else
        {
            if (Str::length(Input::get('value')) > 70)
            {
                return Response::json(array('error'   => true, 'message' => 'Adres e-mail może mieć do 70 znaków'));
            }

            if (!filter_var(Input::get('value'), FILTER_VALIDATE_EMAIL))
            {
                return Response::json(array('error'   => true, 'message' => 'Adres e-mail musi mieć prawidłowy format'));
            }

            if (DB::table('users')->where('email', '=', Input::get('value'))->first('id') or DB::table('validating_users')->where('email', '=', Input::get('value'))->first('id'))
            {
                return Response::json(array('error'   => true, 'message' => 'Taki adres e-mail jest już zarejestrowany'));
            }
        }

        return Response::json(array('error' => false));
    }

    /**
     * Rules
     *
     * @return Response
     */
    public function action_index()
    {
        if (Auth::is_logged())
        {
            return Redirect::to('index');
        }

        $this->online('Rejestracja', 'register');

        $this->page->set_title('Rejestracja');
        $this->page->breadcrumb_append('Rejestracja', 'register');

        $this->view = View::make('register.index', array('rules' => Config::get('register.rules', '')));
    }

}