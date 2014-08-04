<?php

class Admin_Comments_Controller extends Admin_Controller {

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_comments'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_comments_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('comments')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/comments/index');
        }

        Model\Comment::delete($id);

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunięto komentarz: %d', $id->id));
        return Redirect::to('admin/comments/index');
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_comments_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('comments')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('comment_raw' => '', 'karma'       => '', 'guest_name'  => '');
            $raw_data = array_merge($raw_data, Input::only(array('comment_raw', 'karma', 'guest_name')));

            $rules = array(
                'comment_raw' => 'required',
                'karma'       => 'required|numeric',
                'guest_name'  => 'match:!^[\pL\pN\s]+$!u|max:20'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/comments/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('comment_raw', 'karma', 'guest_name'));
            }
            else
            {
                $prepared_data = array(
                    'comment_raw' => HTML::specialchars($raw_data['comment_raw']),
                    'comment'     => Model\Comment::prepare_content($raw_data['comment_raw']),
                    'karma'       => (int) $raw_data['karma'],
                    'guest_name'  => $raw_data['guest_name']
                );

                \DB::table('comments')->where('id', '=', $id->id)->update($prepared_data);

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono komentarz: %d', $id->id));
                return Redirect::to('admin/comments/index');
            }
        }

        $this->page->set_title('Edycja komentarza');

        $this->page->breadcrumb_append('Komentarze', 'admin/comments/index');
        $this->page->breadcrumb_append('Edycja komentarza', 'admin/comments/edit/'.$id->id);

        $this->view = View::make('admin.comments.edit');

        $old_data = array('raw_comment' => '', 'karma'       => '', 'guest_name'  => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_comments'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_comments'))
            return Response::error(403);

        $this->page->set_title('Komentarze');
        $this->page->breadcrumb_append('Komentarze', 'admin/comments/index');

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
		title: 'Podgląd komentarza'
	});

	$('a.preview').click(function(){
		$.get(IONIC_BASE_URL+'admin/comments/preview/'+$(this).attr('name').replace('preview-', ''), function(response) {
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
        if (!Auth::can('admin_comments'))
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
        if (!Auth::can('admin_comments'))
            return Response::error(403);

        if (!ctype_digit($id) or !Request::ajax())
            return Response::error(500);

        $id = DB::table('comments')->where('id', '=', (int) $id)->first(array('id', 'comment', 'content_link'));

        if (!$id)
            return Response::error(500);

        return View::make('admin.comments.preview', array('item' => $id));
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_comments'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('comments', 'Komentarze', 'admin/comments');

        if (Auth::can('admin_comments_edit'))
            $grid->add_action('Edytuj', 'admin/comments/edit/%d', 'edit-button');
        if (Auth::can('admin_comments_delete'))
            $grid->add_action('Usuń', 'admin/comments/delete/%d', 'delete-button');

        $types = array();
        $types['_all_'] = 'Wszystkie';
        $types = array_merge($types, Model\Comment::get_types());

        $grid->add_related('users', 'users.id', '=', 'comments.user_id', array('comments.guest_name'), true);

        $grid->add_column('id', 'ID', 'id', null, 'comments.id');
        $grid->add_column('display_name', 'Autor', function($obj) {
                    if ($obj->display_name)
                    {
                        return '<a class="preview" style="cursor: pointer" name="preview-'.$obj->id.'" title="Podgląd">'.$obj->display_name.'</a>';
                    }
                    else
                    {
                        return '<a class="preview" style="cursor: pointer" name="preview-'.$obj->id.'" title="Podgląd">Anonimowy <small>('.$obj->guest_name.')</small></a>';
                    }
                }, 'users.display_name', 'users.display_name');
        $grid->add_column('created_at', 'Dodano', 'created_at', 'comments.created_at', 'comments.created_at');
        $grid->add_column('content_type', 'Gdzie', function($obj) use ($types) {
                    return isset($types[$obj->content_type]) ? $types[$obj->content_type] : $obj->content_type;
                }, 'comments.content_type', 'comments.content_type');
        $grid->add_column('ip', 'Adres IP', 'ip', 'comments.ip', 'comments.ip');

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_select('content_type', 'Gdzie', $types, '_all_');
        $grid->add_filter_date('created_at', 'Data dodania');
        $grid->add_filter_search('display_name', 'Użytkownik', 'users.display_name');
        $grid->add_filter_search('ip', 'Adres IP');

        return $grid;
    }

}