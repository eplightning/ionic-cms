<?php

/**
 * Newsletter
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Newsletter_Controller extends Admin_Controller {

    /**
     * Add action
     *
     * @return Response
     */
    public function action_add()
    {
        if (!Auth::can('admin_newsletter'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'           => '', 'message'         => '', 'type'            => '', 'ignore_settings' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'message', 'type', 'ignore_settings')));

            $rules = array(
                'title'   => 'required|max:255',
                'message' => 'required',
                'type'    => 'required|in:email,list,pm'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/newsletter/add')->with_errors($validator)
                                ->with_input('only', array('title', 'message', 'type', 'ignore_settings'));
            }
            else
            {
                $prepared_data = array(
                    'title'   => HTML::specialchars($raw_data['title']),
                    'message' => $raw_data['message'],
                    'type'    => $raw_data['type']
                );

                if (!Auth::can('admin_xss'))
                {
                    require_once path('app').'vendor'.DS.'htmLawed.php';

                    $prepared_data['message'] = htmLawed($prepared_data['message'], array('safe' => 1));
                }

                if ($raw_data['type'] == 'list')
                {
                    $emails = array();

                    foreach (DB::table('mailing_list')->get('email') as $e)
                    {
                        $emails[] = $e->email;
                    }

                    $prepared_data['count'] = count($emails);
                    $prepared_data['emails'] = serialize($emails);
                }
                elseif ($raw_data['type'] == 'email')
                {
                    $emails = array();

                    if ($raw_data['ignore_settings'] == '1')
                    {
                        foreach (DB::table('users')->get('email') as $e)
                        {
                            $emails[] = $e->email;
                        }
                    }
                    else
                    {
                        foreach (DB::table('users')->left_join('profiles', 'profiles.user_id', '=', 'users.id')->where('profiles.setting_email', '<>', 0)->get('users.email') as $e)
                        {
                            $emails[] = $e->email;
                        }
                    }

                    $prepared_data['count'] = count($emails);
                    $prepared_data['emails'] = serialize($emails);
                }
                else
                {
                    $emails = array();

                    foreach (DB::table('users')->get('id') as $e)
                    {
                        $emails[] = $e->id;
                    }

                    $prepared_data['count'] = count($emails);
                    $prepared_data['emails'] = serialize($emails);
                }

                $obj_id = DB::table('newsletter')->insert_get_id($prepared_data);

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodał newsletter: %s', $prepared_data['title']));
                return Redirect::to('admin/newsletter/index');
            }
        }

        $this->page->set_title('Dodawanie newslettera');

        $this->page->breadcrumb_append('Newsletter', 'admin/newsletter/index');
        $this->page->breadcrumb_append('Dodawanie newslettera', 'admin/newsletter/add');

        $this->view = View::make('admin.newsletter.add');

        $old_data = array('title'           => '', 'message'         => '', 'type'            => '', 'ignore_settings' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        Ionic\Editor::init();
    }

    /**
     * Autocompletion
     *
     * @param  string   $id
     * @return Response
     */
    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_newsletter'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    /**
     * Delete
     *
     * @param  string   $id
     * @return Response
     */
    public function action_delete($id)
    {
        if (!Auth::can('admin_newsletter') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('newsletter')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/newsletter/index');
        }

        DB::table('newsletter')->where('id', '=', $id->id)->delete();

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunął newsletter: %s', $id->title));
        return Redirect::to('admin/newsletter/index');
    }

    /**
     * Edit
     *
     * @param  string   $id
     * @return Response
     */
    public function action_edit($id)
    {
        if (!Auth::can('admin_newsletter') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('newsletter')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title'   => '', 'message' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'message')));

            $rules = array(
                'title'   => 'required|max:255',
                'message' => 'required'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/newsletter/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('title', 'message'));
            }
            else
            {
                $prepared_data = array(
                    'title'   => HTML::specialchars($raw_data['title']),
                    'message' => $raw_data['message']
                );

                if (!Auth::can('admin_xss'))
                {
                    require_once path('app').'vendor'.DS.'htmLawed.php';

                    $prepared_data['message'] = htmLawed($prepared_data['message'], array('safe' => 1));
                }

                if (Input::get('rebuild_list') == '1')
                {
                    if ($id->type == 'list')
                    {
                        $emails = array();

                        foreach (DB::table('mailing_list')->get('email') as $e)
                        {
                            $emails[] = $e->email;
                        }

                        $prepared_data['count'] = count($emails);
                        $prepared_data['emails'] = serialize($emails);
                    }
                    elseif ($id->type == 'email')
                    {
                        $emails = array();

                        if (Input::get('ignore_settings') == '1')
                        {
                            foreach (DB::table('users')->get('email') as $e)
                            {
                                $emails[] = $e->email;
                            }
                        }
                        else
                        {
                            foreach (DB::table('users')->left_join('profiles', 'profiles.user_id', '=', 'users.id')->where('profiles.setting_email', '<>', 0)->get('users.email') as $e)
                            {
                                $emails[] = $e->email;
                            }
                        }

                        $prepared_data['count'] = count($emails);
                        $prepared_data['emails'] = serialize($emails);
                    }
                    else
                    {
                        $emails = array();

                        foreach (DB::table('users')->get('id') as $e)
                        {
                            $emails[] = $e->id;
                        }

                        $prepared_data['count'] = count($emails);
                        $prepared_data['emails'] = serialize($emails);
                    }
                }

                \DB::table('newsletter')->where('id', '=', $id->id)->update($prepared_data);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmienił newsletter: %s', $prepared_data['title']));
                return Redirect::to('admin/newsletter/index');
            }
        }

        $this->page->set_title('Edycja newslettera');

        $this->page->breadcrumb_append('Newsletter', 'admin/newsletter/index');
        $this->page->breadcrumb_append('Edycja newslettera', 'admin/newsletter/edit/'.$id->id);

        $this->view = View::make('admin.newsletter.edit');

        $old_data = array('title'   => '', 'message' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);

        Ionic\Editor::init();
    }

    /**
     * Set filter
     *
     * @param  string   $id
     * @param  mixed    $value
     * @return Response
     */
    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_newsletter'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    /**
     * Listing
     *
     * @param  string  $id
     * @return Response
     */
    public function action_index($id = null)
    {
        if (!Auth::can('admin_newsletter'))
            return Response::error(403);

        $this->page->set_title('Newsletter');
        $this->page->breadcrumb_append('Newsletter', 'admin/newsletter/index');

        $grid = $this->make_grid();

        $result = $grid->handle_index($id);

        if ($result instanceof View)
        {
            $this->view = $result;
        }
        elseif ($result instanceof Response)
        {
            return $result;
        }
    }

    /**
     * Do multiaction
     *
     * @param  string   $name
     * @return Response
     */
    public function action_multiaction($name)
    {
        if (!Auth::can('admin_newsletter'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    /**
     * Send newsletter
     *
     * @param  string   $id
     * @return Response
     */
    public function action_send($id)
    {
        // Permission check
        if (!Auth::can('admin_newsletter_send') or !ctype_digit($id))
        {
            return Response::error(403);
        }

        // Get newsletter
        $id = DB::table('newsletter')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        // Not in progress? We need to confirm it first then
        if ($id->in_progress == 0)
        {
            if (!($status = $this->confirm()))
            {
                return;
            }
            elseif ($status == 2)
            {
                return Redirect::to('admin/newsletter/index');
            }

            DB::table('newsletter')->where('id', '=', $id->id)->update(array('in_progress' => 1));
        }

        if ($id->type == 'pm')
        {
            $conversation = DB::table('conversations')->where('poster_id', '=', $this->user->id)
                    ->where('title', '=', $id->title)
                    ->where('is_newsletter', '=', 1)
                    ->order_by('id', 'desc')
                    ->first('id');

            if (!$conversation)
            {
                $conversation = DB::table('conversations')->insert_get_id(array(
                    'poster_id'      => $this->user->id,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'title'          => $id->title,
                    'is_closed'      => 1,
                    'messages_count' => 1,
                    'last_post_date' => date('Y-m-d H:i:s'),
                    'last_post_user' => $this->user->display_name,
                    'is_newsletter'  => 1
                        ));

                DB::table('messages')->insert(array(
                    'conversation_id' => $conversation,
                    'user_id'         => $this->user->id,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'message'         => $id->message,
                    'is_reported'     => 0
                ));
            }
            else
            {
                $conversation = $conversation->id;
            }
        }

        // Anything to send?
        if ($id->emails)
        {
            // Unserialize email list
            $emails = unserialize($id->emails);

            // Anything left?
            if (is_array($emails) and count($emails))
            {
                // Grab list for this session?
                $to_send = array();

                for ($i = 0; $i < Config::get('email.per_session'); $i++)
                {
                    if (empty($emails))
                        break;

                    $to_send[] = array_shift($emails);
                }

                // Empty session?
                if (!empty($to_send))
                {
                    if ($id->type == 'email' or $id->type == 'list')
                    {
                        $mailer = IoC::resolve('mailer');

                        foreach ($to_send as $email)
                        {
                            $message = \Swift_Message::newInstance();
                            $message->setFrom(array(Config::get('email.from') => Config::get('email.from_name')));
                            $message->setTo($email);

                            $message->setSubject($id->title);
                            $message->setBody($id->message, 'text/html');

                            $mailer->send($message);
                        }
                    }
                    else
                    {
                        foreach ($to_send as $uid)
                        {
                            if ($uid != $this->user->id)
                            {
                                DB::table('conversation_users')->insert(array('user_id'         => $uid, 'conversation_id' => $conversation, 'notifications'   => 0));

                                DB::table('notifications')->insert(array(
                                    'link'       => 'conversations/show/'.$conversation,
                                    'message'    => 'Otrzymałeś newsletter.',
                                    'type'       => 'conversation_newsletter',
                                    'user_id'    => $uid,
                                    'content_id' => $conversation
                                ));
                            }
                        }
                    }
                }

                // Emails left? Then prepare next session
                if (!empty($emails))
                {
                    // Count remaining
                    $c = count($emails);

                    // Update database
                    DB::table('newsletter')->where('id', '=', $id->id)->update(array('emails' => serialize($emails), 'count'  => $c));

                    // Display progress page
                    $this->view = View::make('admin.newsletter.send', array('count' => $c, 'id'    => $id->id));

                    $this->page->set_title('Wysyłka');
                    $this->page->breadcrumb_append('Newsletter', 'admin/newsletter/index');
                    $this->page->breadcrumb_append('Wysyłka', 'admin/newsletter/send/'.$id->id);

                    $this->page->set_http_equiv('refresh', 5);

                    return;
                }
            }
        }

        // Remove after completion
        DB::table('newsletter')->where('id', '=', $id->id)->delete();

        // Finish
        $this->notice('Wysłano pomyślnie');
        $this->log(sprintf('Wysłał newsletter: %s', $id->title));
        return Redirect::to('admin/newsletter/index');
    }

    /**
     * Sorting
     *
     * @param  string   $item
     * @return Response
     */
    public function action_sort($item)
    {
        if (!Auth::can('admin_newsletter'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    /**
     * Make grid
     *
     * @return Ionic\Grid
     */
    protected function make_grid()
    {
        $grid = new Ionic\Grid('newsletter', 'Newsletter', 'admin/newsletter');

        $grid->add_button('Dodaj newsletter', 'admin/newsletter/add', 'add-button');
        $grid->add_action('Edytuj', 'admin/newsletter/edit/%d', 'edit-button');
        $grid->add_action('Usuń', 'admin/newsletter/delete/%d', 'delete-button');

        if (Auth::can('admin_newsletter_send'))
            $grid->add_action('Wyślij', 'admin/newsletter/send/%d', 'accept-button');

        $grid->enable_checkboxes(true);

        $id = $this->user->id;

        $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                    $affected = DB::table('newsletter')->where_in('id', $ids)->delete();

                    if ($affected)
                        \Model\Log::add('Masowo usunięto newsletter ('.$affected.')', $id);
                });

        $grid->add_column('id', 'ID', 'id', null, 'newsletter.id');
        $grid->add_column('title', 'Tytuł', 'title', 'newsletter.title', 'newsletter.title');
        $grid->add_column('type', 'Rodzaj', function($obj) {
                    if ($obj->type == 'email')
                    {
                        return 'E-mail';
                    }
                    elseif ($obj->type == 'list')
                    {
                        return 'Lista mailingowa';
                    }
                    else
                    {
                        return 'Prywatne wiadomości';
                    }
                }, 'newsletter.type', 'newsletter.type');
        $grid->add_column('count', 'Do wysłania', 'count', 'newsletter.count', 'newsletter.count');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('title', 'Tytuł');
        $grid->add_filter_select('type', 'Rodzaj', array('_all_' => 'Wszystkie', 'email' => 'E-mail użytkowników', 'list'  => 'Lista mailingowa', 'pm'    => 'Prywatne wiadomości'), '_all_');

        return $grid;
    }

}