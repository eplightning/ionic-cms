<?php

/**
 * Mailing list
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Mailing_list_Controller extends Admin_Controller {

    /**
     * Autocompletion
     *
     * @param  string   $id
     * @return Response
     */
    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_mailing_list'))
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
        if (!Auth::can('admin_mailing_list') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('mailing_list')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::ajax() or !Config::get('advanced.admin_prefer_ajax', true))
        {
            if (!($status = $this->confirm()))
            {
                return;
            }
            elseif ($status == 2)
            {
                return Redirect::to('admin/mailing_list/index');
            }
        }
        elseif (Request::forged())
        {
            return Response::error(500);
        }

        DB::table('mailing_list')->where('id', '=', $id->id)->delete();
        $this->log(sprintf('Usunął z listy mailingowej: %s', $id->email));

        if (!Request::ajax())
        {
            $this->notice('Adres usunięty pomyślnie');
            return Redirect::to('admin/mailing_list/index');
        }
        else
        {
            return Response::json(array('status' => true));
        }
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
        if (!Auth::can('admin_mailing_list'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    /**
     * Import emails to mailing list
     *
     * @return Response
     */
    public function action_import()
    {
        if (!Auth::can('admin_mailing_list'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST' and Input::has('emails'))
        {
            $existing = array();

            foreach (DB::table('mailing_list')->get('email') as $e)
            {
                $existing[] = $e->email;
            }

            $added = 0;

            foreach (explode("\n", ionic_normalize_lines(Input::get('emails'))) as $e)
            {
                if (strlen($e) <= 70 and !in_array($e, $existing) and filter_var($e, FILTER_VALIDATE_EMAIL) !== false)
                {
                    DB::table('mailing_list')->insert(array('email' => $e));

                    $added++;
                }
            }

            if ($added > 0)
            {
                $this->log('Zaimportował '.$added.' adresów do listy mailingowej');
                $this->notice('Zaimportowano '.$added.' adresów do listy mailingowej');
            }
            else
            {
                $this->notice('Żaden z podanych adresów nie mógł zostać zaimportowany');
            }

            return Redirect::to('admin/mailing_list/index');
        }

        $this->page->set_title('Importuj adresy');
        $this->page->breadcrumb_append('Lista mailingowa', 'admin/mailing_list/index');
        $this->page->breadcrumb_append('Importuj', 'admin/mailing_list/import');

        $this->view = View::make('admin.mailing_list.import');
    }

    /**
     * Listing
     *
     * @param  string  $id
     * @return Response
     */
    public function action_index($id = null)
    {
        if (!Auth::can('admin_mailing_list'))
            return Response::error(403);

        $this->page->set_title('Lista mailingowa');
        $this->page->breadcrumb_append('Lista mailingowa', 'admin/mailing_list/index');

        $grid = $this->make_grid();

        $result = $grid->handle_index($id);

        if ($result instanceof Ionic\View)
        {
            $this->view = $result;
        }
        elseif ($result instanceof Laravel\Response)
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
        if (!Auth::can('admin_mailing_list'))
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
        if (!Auth::can('admin_mailing_list'))
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
        $grid = new Ionic\Grid('mailing_list', 'Lista mailingowa', 'admin/mailing_list');

        $grid->add_button('Importuj adresy', 'admin/mailing_list/import', 'add-button');
        $grid->add_action('Usuń', 'admin/mailing_list/delete/%d', 'delete-button', Ionic\Grid::ACTION_BOTH);
        $grid->enable_checkboxes(true);

        $id = $this->user->id;

        $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
            $affected = DB::table('mailing_list')->where_in('id', $ids)->delete();

            if ($affected)
                Model\Log::add('Masowo usunął z listy mailingowej ('.$affected.')', $id);
        });

        $grid->add_column('id', 'ID', 'id', null, 'mailing_list.id');
        $grid->add_column('email', 'Adres e-mail', 'email', 'mailing_list.email', 'mailing_list.mail');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('email', 'Adres e-mail');

        return $grid;
    }

}