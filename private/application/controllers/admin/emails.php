<?php

/**
 * Email management
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Emails_Controller extends Admin_Controller {

    /**
     * Autocompletion
     *
     * @param  string   $id
     * @return Response
     */
    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_emails'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    /**
     * Edit
     *
     * @param  string   $id
     * @return Response
     */
    public function action_edit($id)
    {
        if (!Auth::can('admin_emails') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('emails')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('subject' => '', 'message' => '');
            $raw_data = array_merge($raw_data, Input::only(array('subject', 'message')));

            $rules = array(
                'subject' => 'required|max:255',
                'message' => 'required'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/emails/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('subject', 'message'));
            }
            else
            {
                $prepared_data = array(
                    'subject' => HTML::specialchars($raw_data['subject']),
                    'message' => $raw_data['message']
                );

                if (!Auth::can('admin_root'))
                {
                    require_once path('app').'vendor'.DS.'htmLawed.php';

                    $prepared_data['message'] = htmLawed($prepared_data['message'], array('safe' => 1));
                }

                \DB::table('emails')->where('id', '=', $id->id)->update($prepared_data);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmienił email: %s', $id->title));
                return Redirect::to('admin/emails/index');
            }
        }

        $this->page->set_title('Edycja emaila');

        $this->page->breadcrumb_append('E-maile systemowe', 'admin/emails/index');
        $this->page->breadcrumb_append('Edycja emaila', 'admin/emails/edit/'.$id->id);

        $this->view = View::make('admin.emails.edit');

        $old_data = array('subject' => '', 'message' => '');
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
        if (!Auth::can('admin_emails'))
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
        if (!Auth::can('admin_emails'))
            return Response::error(403);

        $this->page->set_title('E-maile systemowe');
        $this->page->breadcrumb_append('E-maile systemowe', 'admin/emails/index');

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
        if (!Auth::can('admin_emails'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    /**
     * Sorting
     *
     * @param  string   $item
     * @return Response
     */
    public function action_sort($item)
    {
        if (!Auth::can('admin_emails'))
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
        $grid = new Ionic\Grid('emails', 'E-maile systemowe', 'admin/emails');

        $grid->add_action('Edytuj', 'admin/emails/edit/%d', 'edit-button');

        $grid->add_column('id', 'ID', 'id', null, 'emails.id');
        $grid->add_column('title', 'Tytuł', 'title', 'emails.title', 'emails.title');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('title', 'Tytuł', 'emails.title');

        return $grid;
    }

}