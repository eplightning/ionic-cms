<?php

/**
 * Reports
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Reports_Controller extends Admin_Controller {

    /**
     * Autocompletion
     *
     * @param  string   $id
     * @return Response
     */
    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_reports'))
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
        if (!Auth::can('admin_reports_delete') || !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('reports')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/reports/index');
        }

        DB::table('reports')->where('id', '=', $id->id)->delete();

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunięto zgłoszenie: %s', $id->title));
        return Redirect::to('admin/reports/index');
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
        if (!Auth::can('admin_reports'))
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
        if (!Auth::can('admin_reports'))
            return Response::error(403);

        $this->page->set_title('Zgłoszenia');
        $this->page->breadcrumb_append('Zgłoszenia', 'admin/reports/index');

        $this->page->add_footer_js("$(function() {
	$('#grid-right-column').after($('<div id=\"dialog-preview-content\" style=\"display: none\"><div id=\"dialog-preview-content-in\"></div></div>'));
	$('#dialog-preview-content').dialog({
		autoOpen: false,
		width: 600,
		height: 400,
		modal: true,
		buttons: {
			'Zamknij': function() { $(this).dialog('close'); }
		},
		title: 'Podgląd zgłoszenia'
	});

	$('a.preview').click(function(){
		$.get(IONIC_BASE_URL+'admin/reports/preview/'+$(this).attr('name').replace('report-', ''), function(response) {
			$('#dialog-preview-content-in').html(response);
			$('#dialog-preview-content').dialog('open');
		});
	});
});");

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
        if (!Auth::can('admin_reports_delete'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_multiaction($name);
    }

    /**
     * Load preview
     *
     * @param  string   $id
     * @return Response
     */
    public function action_preview($id)
    {
        if (!Auth::can('admin_reports'))
            return Response::error(403);

        if (!ctype_digit($id) or !Request::ajax())
            return Response::error(500);

        $id = DB::table('reports')->where('id', '=', (int) $id)->first(array('saved_content',
            'item_link'));

        if (!$id)
            return Response::error(500);

        return View::make('admin.reports.preview', array('item' => $id));
    }

    /**
     * Sorting
     *
     * @param  string   $item
     * @return Response
     */
    public function action_sort($item)
    {
        if (!Auth::can('admin_reports'))
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
        $grid = new Ionic\Grid('reports', 'Zgłoszenia', 'admin/reports', array('reports.item_link'));

        $types = array(
            '_all_'   => 'Wszystkie',
            'comment' => 'Komentarze',
            'message' => 'Wiadomości'
        );

        foreach (\Event::fire('ionic.reports_list') as $r)
        {
            if (is_array($r))
            {
                $types = array_merge($types, $r);
            }
        }

        $grid->add_column('id', 'ID', 'id', null, 'reports.id');
        $grid->add_column('title', 'Tytuł', function($obj)
                {
                    return '<a style="cursor: pointer" class="preview" name="report-'.$obj->id.'" title="Podgląd">'.Str::limit($obj->title, 20).'</a>';
                }, 'reports.title', 'reports.title');
        $grid->add_column('user', 'Zgłosił', 'display_name', 'users.display_name', 'users.display_name');
        $grid->add_column('created_at', 'Data zgłoszenia', 'created_at', 'reports.created_at', 'reports.created_at');
        $grid->add_column('type', 'Typ', function($obj) use ($types)
                {
                    return isset($types[$obj->item_type]) ? $types[$obj->item_type] : $obj->item_type;
                }, 'reports.item_type', 'reports.item_type', 'reports.item_type');

        $grid->add_related('users', 'users.id', '=', 'reports.user_id');

        if (Auth::can('admin_reports_delete'))
        {
            $grid->add_action('Usuń', 'admin/reports/delete/%d', 'delete-button');
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id)
                    {
                        $affected = DB::table('reports')->where_in('id', $ids)->delete();

                        if ($affected)
                            Model\Log::add('Masowo usunięto zgłoszenia ('.$affected.')', $id);
                    });
        }

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_date('created_at', 'Data zgłoszenia');
        $grid->add_filter_select('item_type', 'Rodzaj materiału', $types, '_all_');
        $grid->add_filter_search('display_name', 'Zgłaszający', 'users.display_name');

        return $grid;
    }

}