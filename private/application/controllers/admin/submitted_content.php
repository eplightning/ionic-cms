<?php

class Admin_Submitted_content_Controller extends Admin_Controller {

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_submitted_content'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_submitted_content') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('submitted_content')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/submitted_content/index');
        }

        DB::table('submitted_content')->where('id', '=', $id->id)->delete();

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunięto podesłany materiał: %s', $id->title));
        return Redirect::to('admin/submitted_content/index');
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_submitted_content'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_submitted_content'))
            return Response::error(403);

        $this->page->set_title('Podesłane materiały');
        $this->page->breadcrumb_append('Podesłane materiały', 'admin/submitted_content/index');

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
		title: 'Podgląd podesłanego materiału'
	});

	$('a.preview').click(function(){
		$.get(IONIC_BASE_URL+'admin/submitted_content/preview/'+$(this).attr('name').replace('preview-', ''), function(response) {
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

    public function action_multiaction($name)
    {
        if (!Auth::can('admin_submitted_content_multi'))
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
        if (!Auth::can('admin_submitted_content'))
            return Response::error(403);

        if (!ctype_digit($id) or !Request::ajax())
            return Response::error(500);

        $id = DB::table('submitted_content')->where('id', '=', (int) $id)->first(array('content'));

        if (!$id)
            return Response::error(500);

        return View::make('admin.submitted_content.preview', array('item' => $id));
    }

    /**
     * Publish content
     *
     * @param  string   $id
     * @return Response
     */
    public function action_publish($id)
    {
        if (!Auth::can('admin_submitted_content'))
            return Response::error(403);

        if (!ctype_digit($id))
            return Response::error(500);

        $id = DB::table('submitted_content')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/submitted_content/index');
        }

        if ($id->type == 'news')
        {
            $obj_id = DB::table('news')->insert_get_id(array(
                'user_id'       => $id->user_id,
                'title'         => $id->title,
                'content'       => $id->content,
                'content_intro' => Str::limit(strip_tags($id->content), 300),
                'created_at'    => $id->created_at,
                'is_published'  => 0,
                'slug'          => ionic_tmp_slug('news')
                    ));

            $add_points = (int) Config::get('points.points_for_news');

            if ($add_points)
            {
                DB::table('profiles')->where('user_id', '=', $id->user_id)->update(array(
                    'points'     => DB::raw('points + '.$add_points),
                    'news_count' => DB::raw('news_count + 1')
                ));
            }
            else
            {
                DB::table('profiles')->where('user_id', '=', $id->user_id)->update(array(
                    'news_count' => DB::raw('news_count + 1')
                ));
            }

            DB::table('news')->where('id', '=', $obj_id)->update(array('slug' => Str::slug($id->title.'-'.$obj_id)));
        }
        else
        {
            $result = \Event::until('ionic.submitted_content_publish', array($id));

            if (is_bool($result) or is_null($result))
            {
                if (!$result)
                {
                    $this->notice('Wystąpił nieznany błąd');
                    return Redirect::to('admin/submitted_content/index');
                }
            }
            else
            {
                $this->notice($result);
                return Redirect::to('admin/submitted_content/index');
            }
        }

        DB::table('submitted_content')->where('id', '=', $id->id)->delete();

        $this->notice('Materiał został zatwierdzony. Możliwe ,że wymagana jest jego publikacja w odpowiednim panelu');
        $this->log(sprintf('Zatwierdzono materiał: %s', $id->title));
        return Redirect::to('admin/submitted_content/index');
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_submitted_content'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('submitted_content', 'Podesłane materiały', 'admin/submitted_content');

        $types = array(
            '_all_' => 'Wszystkie',
            'news'  => 'News'
        );

        foreach (\Event::fire('ionic.submitted_content_list') as $r)
        {
            if (is_array($r))
            {
                $types = array_merge($types, $r);
            }
        }

        $grid->add_action('Opublikuj', 'admin/submitted_content/publish/%d', 'accept-button');
        $grid->add_action('Usuń', 'admin/submitted_content/delete/%d', 'delete-button');

        $grid->add_column('id', 'ID', 'id', null, 'submitted_content.id');
        $grid->add_column('title', 'Tytuł', function($obj) {
                    return '<a class="preview" style="cursor: pointer" name="preview-'.$obj->id.'" title="Podgląd">'.Str::limit($obj->title, 30).'</a>';
                }, 'submitted_content.title', 'submitted_content.title');
        $grid->add_column('display_name', 'Użytkownik', 'display_name', 'users.display_name', 'users.display_name');
        $grid->add_column('created_at', 'Podesłano', 'created_at', 'submitted_content.created_at', 'submitted_content.created_at');
        $grid->add_column('type', 'Rodzaj', function($obj) use ($types) {
                    return isset($types[$obj->type]) ? $types[$obj->type] : $obj->type;
                }, 'submitted_content.type', 'submitted_content.type');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_select('type', 'Rodzaj', $types, '_all_');
        $grid->add_filter_search('title', 'Tytuł');
        $grid->add_filter_date('created_at', 'Data podesłania');
        $grid->add_filter_search('display_name', 'Użytkownik', 'users.display_name');

        $grid->add_related('users', 'users.id', '=', 'submitted_content.user_id');

        if (Auth::can('admin_submitted_content_multi'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                        $affected = DB::table('submitted_content')->where_in('id', $ids)->delete();

                        if ($affected)
                            \Model\Log::add('Masowo usunięto podesłane materiały ('.$affected.')', $id);
                    });
        }

        return $grid;
    }

}