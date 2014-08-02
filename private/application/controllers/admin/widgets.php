<?php

/**
 * Administration - widgets
 *
 * @package Controllers
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class Admin_Widgets_Controller extends Admin_Controller {

    /**
     * Delete
     *
     * @param  string   $id
     * @return Response
     */
    public function action_delete($id)
    {
        // ACL
        if (!Auth::can('admin_widgets') || !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('widgets')->where('widgets.id', '=', (int) $id)->first(array('widgets.id', 'widgets.title'));

        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/widgets/index');
        }

        DB::table('widgets')->where('id', '=', $id->id)->delete();

        // Notice and redirect
        $this->notice('Pomyślnie usunięto widżet');
        $this->log('Usunięto widżet: '.$id->title);
        return Redirect::to('admin/widgets/index');
    }

    /**
     * Edit
     *
     * @param  string   $id
     * @return Response
     */
    public function action_edit($id, $opt = '')
    {
        // ACL
        if (!Auth::can('admin_widgets') || !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('widgets')->where('widgets.id', '=', (int) $id)->first(array('widgets.id', 'widgets.title', 'widgets.type', 'widgets.options'));

        if (!$id)
            return Response::error(500);

        $widget = Ionic\Widget::factory_simple($id->title, $id->type, ($id->options ? unserialize($id->options) : array()));

        $prepared = $widget->prepare_options();

        if (is_array($prepared))
        {
            DB::table('widgets')->where('widgets.id', '=', $id->id)->update(array('options' => serialize($prepared)));
            Cache::forget('widgets');

            $this->notice('Pomyślnie zaaktualizowano widżet');
            $this->log('Edytowano widżet: '.$id->title);
            return Redirect::to('admin/widgets/index');
        }

        // Title
        $this->page->set_title('Edycja widżetu');
        $this->page->breadcrumb_append('Widżety', 'admin/widgets/index');
        $this->page->breadcrumb_append('Edycja', 'admin/widgets/edit/'.$id->id);

        $this->view = View::make('admin.widgets.edit', array('title'         => $id->title, 'type'          => Ionic\Widget::name($id->type), 'widget_config' => $widget->display_options('admin/widgets/edit/'.$id->id, $opt)));
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
        // ACL
        if (!Auth::can('admin_widgets'))
            return Response::error(403);

        // Make grid
        $grid = $this->make_grid();

        // Handle
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
        // ACL
        if (!Auth::can('admin_widgets'))
            return Response::error(403);

        // Title
        $this->page->set_title('Widżety');
        $this->page->breadcrumb_append('Widżety', 'admin/widgets/index');

        // Make grid
        $grid = $this->make_grid();

        // Handle request
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
     * Sorting
     *
     * @param  string   $item
     * @return Response
     */
    public function action_sort($item)
    {
        // ACL
        if (!Auth::can('admin_widgets'))
            return Response::error(403);

        // Make grid
        $grid = $this->make_grid();

        // Handle
        return $grid->handle_sort($item);
    }

    /**
     * Make grid
     *
     * @return Ionic\Grid
     */
    protected function make_grid()
    {
        $grid = new Ionic\Grid('widgets', 'Widżety', 'admin/widgets');

        $grid->add_column('id', 'ID', 'id', null, 'widgets.id');
        $grid->add_column('title', 'Tytuł', 'title', 'widgets.title', 'widgets.title');
        $grid->add_column('type', 'Rodzaj', function($data) {
                    return Ionic\Widget::name($data->type);
                }, 'widgets.type');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_search('title', 'Tytuł', 'widgets.title');

        $grid->add_action('Edytuj', 'admin/widgets/edit/%d', 'edit-button');
        $grid->add_action('Usuń', 'admin/widgets/delete/%d', 'delete-button');

        return $grid;
    }

}