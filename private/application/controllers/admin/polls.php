<?php

class Admin_Polls_Controller extends Admin_Controller {

    public function action_active($id)
    {
        if (!Auth::can('admin_polls') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('polls')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/polls/index');
        }

        if ($id->is_active == 0)
        {
            DB::table('polls')->where('is_active', '=', 1)->update(array('is_active' => 0));
            DB::table('polls')->where('id', '=', $id->id)->update(array('is_active' => 1));
        }
        else
        {
            DB::table('polls')->where('id', '=', $id->id)->update(array('is_active' => 0));
        }

        \Cache::forget('poll');

        $this->notice('Operacja wykonana pomyślnie');
        $this->log(sprintf('Aktywowano/deaktywowano sondę: %s', $id->title));
        return Redirect::to('admin/polls/index');
    }

    public function action_add()
    {
        if (!Auth::can('admin_polls_add'))
            return Response::error(403);

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title')));

            $rules = array(
                'title' => 'required|max:127'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/polls/add')->with_errors($validator)
                                ->with_input('only', array('title'));
            }
            else
            {
                $prepared_data = array(
                    'title'      => HTML::specialchars($raw_data['title']),
                    'created_at' => date('Y-m-d H:i:s')
                );

                $obj_id = DB::table('polls')->insert_get_id($prepared_data);

                if (!empty($_POST['options']) and is_array($_POST['options']))
                {
                    foreach ($_POST['options'] as $opt)
                    {
                        $opt = trim($opt);

                        if (empty($opt))
                            continue;

                        DB::table('poll_options')->insert(array('title'   => HTML::specialchars($opt), 'poll_id' => $obj_id));
                    }
                }

                \Cache::forget('poll');

                $this->notice('Obiekt dodany pomyślnie');
                $this->log(sprintf('Dodano sondę: %s', $prepared_data['title']));
                return Redirect::to('admin/polls/index');
            }
        }

        $this->page->set_title('Dodawanie sondy');

        $this->page->breadcrumb_append('Sondy', 'admin/polls/index');
        $this->page->breadcrumb_append('Dodawanie sondy', 'admin/polls/add');

        $this->view = View::make('admin.polls.add');

        $old_data = array('title' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);
    }

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_polls'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_autocomplete($id);
    }

    public function action_delete($id)
    {
        if (!Auth::can('admin_polls_delete') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('polls')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/polls/index');
        }

        DB::table('polls')->where('id', '=', $id->id)->delete();
        \Cache::forget('poll');

        $this->notice('Obiekt usunięty pomyślnie');
        $this->log(sprintf('Usunięto sondę: %s', $id->title));
        return Redirect::to('admin/polls/index');
    }

    public function action_edit($id)
    {
        if (!Auth::can('admin_polls_edit') or !ctype_digit($id))
            return Response::error(403);

        $id = DB::table('polls')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);

        $current_options = array();
        $current_votes = array();

        foreach (DB::table('poll_options')->where('poll_id', '=', $id->id)->get(array('id', 'title', 'votes')) as $p)
        {
            $current_options[$p->id] = $p->title;
            $current_votes[$p->id] = (int) $p->votes;
        }

        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title')));

            $rules = array(
                'title' => 'required|max:127'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to('admin/polls/edit/'.$id->id)->with_errors($validator)
                                ->with_input('only', array('title'));
            }
            else
            {
                $prepared_data = array(
                    'title' => HTML::specialchars($raw_data['title']),
                    'votes' => 0
                );

                if (!empty($_POST['options']) and is_array($_POST['options']))
                {
                    foreach ($_POST['options'] as $opt)
                    {
                        $opt = trim($opt);

                        if (empty($opt))
                            continue;

                        $search = array_search($opt, $current_options);

                        if ($search === FALSE or $search === NULL)
                        {
                            DB::table('poll_options')->insert(array('title'   => HTML::specialchars($opt), 'poll_id' => $id->id));
                        }
                        else
                        {
                            $prepared_data['votes'] += $current_votes[$search];
                            unset($current_options[$search]);
                        }
                    }
                }

                if (!empty($current_options))
                {
                    DB::table('poll_options')->where('poll_id', '=', $id->id)->where_in('id', array_keys($current_options))->delete();
                }

                \DB::table('polls')->where('id', '=', $id->id)->update($prepared_data);

                \Cache::forget('poll');

                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log(sprintf('Zmieniono sondę: %s', $prepared_data['title']));
                return Redirect::to('admin/polls/index');
            }
        }

        $this->page->set_title('Edycja sondy');

        $this->page->breadcrumb_append('Sondy', 'admin/polls/index');
        $this->page->breadcrumb_append('Edycja sondy', 'admin/polls/edit/'.$id->id);

        $this->view = View::make('admin.polls.edit');

        $old_data = array('title' => '');
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);

        $this->view->with('object', $id);
        $this->view->with('current_options', $current_options);
    }

    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_polls'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_filter($id, $value);
    }

    public function action_index($id = null)
    {
        if (!Auth::can('admin_polls'))
            return Response::error(403);

        $this->page->set_title('Sondy');
        $this->page->breadcrumb_append('Sondy', 'admin/polls/index');

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
		title: 'Podgląd wyników'
	});

	$('a.preview').click(function(){
		$.get(IONIC_BASE_URL+'admin/polls/preview/'+$(this).attr('name').replace('preview-', ''), function(response) {
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
        if (!Auth::can('admin_polls_multi'))
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
        if (!Auth::can('admin_polls'))
            return Response::error(403);

        if (!ctype_digit($id) or !Request::ajax())
            return Response::error(500);

        $id = DB::table('polls')->where('id', '=', (int) $id)->first(array('id', 'votes'));

        if (!$id)
            return Response::error(500);

        return View::make('admin.polls.preview', array('item'    => $id, 'options' => DB::table('poll_options')->where('poll_id', '=', $id->id)->get(array('title', 'votes'))));
    }

    public function action_sort($item)
    {
        if (!Auth::can('admin_polls'))
            return Response::error(403);

        $grid = $this->make_grid();

        return $grid->handle_sort($item);
    }

    protected function make_grid()
    {
        $grid = new Ionic\Grid('polls', 'Sondy', 'admin/polls');

        if (Auth::can('admin_polls_add'))
            $grid->add_button('Dodaj sondę', 'admin/polls/add', 'add-button');
        if (Auth::can('admin_polls_edit'))
            $grid->add_action('Edytuj', 'admin/polls/edit/%d', 'edit-button');
        $grid->add_action('Aktywuj/deaktywuj', 'admin/polls/active/%d', 'accept-button');
        if (Auth::can('admin_polls_delete'))
            $grid->add_action('Usuń', 'admin/polls/delete/%d', 'delete-button');

        $grid->add_column('id', 'ID', 'id', null, 'polls.id');
        $grid->add_column('title', 'Tytuł', function($obj) {
                    return '<a class="preview" style="cursor: pointer" name="preview-'.$obj->id.'" title="Podgląd">'.Str::limit($obj->title, 30).'</a>'.($obj->is_active == 1 ? ' (<strong>aktywna</strong>)' : '');
                }, 'polls.title', 'polls.title');
        $grid->add_column('created_at', 'Dodano', 'created_at', 'polls.created_at', 'polls.created_at');
        $grid->add_column('votes', 'Głosów', 'votes', 'polls.votes', 'polls.votes');

        $grid->add_selects(array('polls.is_active'));

        $grid->add_filter_perpage(array(20, 30, 50));
        $grid->add_filter_select('is_active', 'Aktywna', array('_all_' => 'Wszystkie', 1       => 'Tak', 0       => 'Nie'), '_all_');
        $grid->add_filter_search('title', 'Tytuł');
        $grid->add_filter_date('created_at', 'Utworzono');

        if (Auth::can('admin_polls_multi') and Auth::can('admin_polls_delete'))
        {
            $grid->enable_checkboxes(true);

            $id = $this->user->id;

            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id) {
                        $affected = DB::table('polls')->where_in('id', $ids)->delete();

                        if ($affected)
                        {
                            \Cache::forget('poll');
                            \Model\Log::add('Masowo usunięto sondy ('.$affected.')', $id);
                        }
                    });
        }

        return $grid;
    }

}