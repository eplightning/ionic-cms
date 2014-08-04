<?php

/**
 * Blog entries management
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Blogs_Controller extends Admin_Controller {

    public function action_autocomplete($id)
    {
        if (!Auth::can('admin_blogs'))
            return Response::error(403);
        
        $grid = $this->make_grid();
        
        return $grid->handle_autocomplete($id);
    }
    
    public function action_delete($id)
    {
        if (!Auth::can('admin_blogs_delete') or !ctype_digit($id))
            return Response::error(403);
        
        $id = DB::table('blogs')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);
        
        if (!($status = $this->confirm()))
        {
            return;
        } //!($status = $this->confirm())
        elseif ($status == 2)
        {
            return Redirect::to('admin/blogs/index');
        } //$status == 2
        
        DB::table('blogs')->where('id', '=', $id->id)->delete();
        DB::table('karma')->where('content_id', '=', $id->id)->where('content_type', '=', 'blog')->delete();
        Model\Comment::delete_comments_for($id->id, 'blog');
        
        $this->notice('Obiekt usunięty pomyślnie');
        $this->log('Usunięto wpis w blogu: ' . $id->title);
        return Redirect::to('admin/blogs/index');
    }
    
    public function action_edit($id)
    {
        if (!Auth::can('admin_blogs_edit') or !ctype_digit($id))
            return Response::error(403);
        
        $id = DB::table('blogs')->where('id', '=', (int) $id)->first('*');
        if (!$id)
            return Response::error(500);
        
        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array(
                'title' => '',
                'slug' => '',
                'content_raw' => ''
            );
            $raw_data = array_merge($raw_data, Input::only(array(
                'title',
                'slug',
                'content_raw'
            )));
            
            $rules = array(
                'title' => 'required|max:127',
                'slug' => 'required|max:127|alpha_dash|unique:blogs,slug,' . $id->id,
                'content_raw' => 'required'
            );
            
            $validator = Validator::make($raw_data, $rules);
            
            if ($validator->fails())
            {
                return Redirect::to('admin/blogs/edit/' . $id->id)->with_errors($validator)->with_input('only', array(
                    'title',
                    'slug',
                    'content_raw'
                ));
            } //$validator->fails()
            else
            {
                $prepared_data = array(
                    'title' => HTML::specialchars($raw_data['title']),
                    'slug' => HTML::specialchars($raw_data['slug']),
                    'content_raw' => HTML::specialchars($raw_data['content_raw']),
                    'content' => Model\Blog::prepare_content($raw_data['content_raw'])
                );
                
                \DB::table('blogs')->where('id', '=', $id->id)->update($prepared_data);
                
                if ($prepared_data['slug'] != $id->slug)
                {
                    DB::table('comments')->where('content_type', '=', 'blog')->where('content_id', '=', $id->id)->update(array(
                        'content_link' => 'blog/post/' . $prepared_data['slug']
                    ));
                } //$prepared_data['slug'] != $id->slug
                
                $this->notice('Obiekt zaaktualizowany pomyślnie');
                $this->log('Zmieniono wpis w blogu: ' . $prepared_data['title']);
                return Redirect::to('admin/blogs/index');
            }
        } //!Request::forged() and Request::method() == 'POST'
        
        $this->page->set_title('Edycja wpisu');
        
        $this->page->breadcrumb_append('Wpisy w blogach', 'admin/blogs/index');
        $this->page->breadcrumb_append('Edycja wpisu', 'admin/blogs/edit/' . $id->id);
        
        $this->view = View::make('admin.blogs.edit');
        
        $old_data = array(
            'title' => '',
            'slug' => '',
            'content_raw' => ''
        );
        $old_data = array_merge($old_data, Input::old());
        $this->view->with('old_data', $old_data);
        
        $this->view->with('object', $id);
    }
    
    public function action_filter($id, $value = null)
    {
        if (!Auth::can('admin_blogs'))
            return Response::error(403);
        
        $grid = $this->make_grid();
        
        return $grid->handle_filter($id, $value);
    }
    
    public function action_index($id = null)
    {
        if (!Auth::can('admin_blogs'))
            return Response::error(403);
        
        $this->page->set_title('Wpisy w blogach');
        $this->page->breadcrumb_append('Wpisy w blogach', 'admin/blogs/index');
        
        $grid = $this->make_grid();
        
        $result = $grid->handle_index($id);
        
        if ($result instanceof View)
        {
            $this->view = $result;
        } //$result instanceof View
        elseif ($result instanceof Response)
        {
            return $result;
        } //$result instanceof Response
    }
    
    public function action_multiaction($name)
    {
        if (!Auth::can('admin_blogs_multi'))
            return Response::error(403);
        
        $grid = $this->make_grid();
        
        return $grid->handle_multiaction($name);
    }
    
    public function action_sort($item)
    {
        if (!Auth::can('admin_blogs'))
            return Response::error(403);
        
        $grid = $this->make_grid();
        
        return $grid->handle_sort($item);
    }
    
    protected function make_grid()
    {
        $grid = new Ionic\Grid('blogs', 'Wpisy w blogach', 'admin/blogs');
        
        if (Auth::can('admin_blogs_edit'))
            $grid->add_action('Edytuj', 'admin/blogs/edit/%d', 'edit-button');
        if (Auth::can('admin_blogs_delete'))
            $grid->add_action('Usuń', 'admin/blogs/delete/%d', 'delete-button');
        
        $grid->add_related('users', 'users.id', '=', 'blogs.user_id');
        
        $grid->add_column('id', 'ID', 'id', null, 'blogs.id');
        $grid->add_column('title', 'Tytuł', 'title', 'blogs.title', 'blogs.title');
        $grid->add_column('display_name', 'Autor', 'display_name', 'users.display_name', 'users.display_name');
        $grid->add_column('created_at', 'Data dodania', 'created_at', 'blogs.created_at', 'blogs.created_at');
        
        $grid->add_filter_perpage(array(
            20,
            30,
            50
        ));
        $grid->add_filter_date('created_at', 'Data dodania');
        $grid->add_filter_search('display_name', 'Autor', 'users.display_name');
        $grid->add_filter_search('title', 'Tytuł');
        
        if (Auth::can('admin_blogs_delete') and Auth::can('admin_blogs_multi'))
        {
            $id = $this->user->id;
            
            $grid->enable_checkboxes(true);
            
            $grid->add_multi_action('delete_selected', 'Usuń zaznaczone', function($ids) use ($id)
            {
                $affected = DB::table('blogs')->where_in('id', $ids)->delete();
                
                if ($affected > 0)
                {
                    DB::table('karma')->where_in('content_id', $ids)->where('content_type', '=', 'blog')->delete();
                    Model\Comment::delete_comments_for($ids, 'blog');
                    
                    Model\Log::add('Usunięto ' . $affected . ' wpisów w blogach', $id);
                } //$affected > 0
            });
        } //Auth::can('admin_blogs_delete') and Auth::can('admin_blogs_multi')
        
        return $grid;
    }
    
}