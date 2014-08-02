<?php

/**
 * Blog controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Blog_Controller extends Base_Controller {

    /**
     * Delete blog post
     *
     * @param  string   $id
     * @return Response
     */
    public function action_delete($id)
    {
        if ($this->require_auth())
            return Redirect::to('blog/index');

        if (!ctype_digit($id))
            return Response::error(500);

        $id = DB::table('blogs')->where('id', '=', (int) $id)->first(array('id', 'user_id', 'content_raw', 'title', 'slug'));

        if (!$id)
            return Response::error(404);

        if ($this->user->id != $id->user_id and (!Auth::can('mod_blogs') and !Auth::can('admin_blogs')))
        {
            return Response::error(403);
        }

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('blog/post/'.$id->slug);
        }

        DB::table('blogs')->where('id', '=', $id->id)->delete();

        DB::table('karma')->where('content_id', '=', $id->id)->where('content_type', '=', 'blog')->delete();

        $user_counts = array();
        $prepared_counts = array();

        foreach (DB::table('comments')->where('content_id', '=', $id->id)->where('content_type', '=', 'blog')->get(array('user_id')) as $c)
        {
            if ($c->user_id != null)
            {
                if (!isset($user_counts[$c->user_id]))
                    $user_counts[$c->user_id] = 0;

                $user_counts[$c->user_id]++;
            }
        }

        foreach ($user_counts as $idd => $c)
        {
            if (!isset($prepared_counts[$c]))
                $prepared_counts[$c] = array();

            $prepared_counts[$c][] = $idd;
        }

        foreach ($prepared_counts as $c => $ids)
        {
            DB::table('profiles')->where('comments_count', '>=', $c)->where_in('user_id', $ids)->update(array('comments_count' => DB::raw('comments_count - '.$c)));
        }

        DB::table('comments')->where('content_id', '=', $id->id)->where('content_type', '=', 'blog')->delete();

        if ($this->user->id != $id->user_id)
        {
            \Model\Log::add('Usunięto wpis w blogu', $this->user->id);
        }

        $this->notice('Wpis został pomyślnie usunięty');

        return Redirect::to('blog');
    }

    /**
     * Edit blog post
     *
     * @param  string   $id
     * @return Response
     */
    public function action_edit($id)
    {
        if ($this->require_auth())
            return Redirect::to('blog/index');

        if (!ctype_digit($id))
            return Response::error(500);

        $id = DB::table('blogs')->where('id', '=', (int) $id)->first(array('id', 'user_id', 'content_raw', 'title', 'slug'));

        if (!$id)
            return Response::error(404);

        if ($this->user->id != $id->user_id and (!Auth::can('mod_blogs') and !Auth::can('admin_blogs')))
        {
            return Response::error(403);
        }

        if (!Request::forged() and Request::method() == 'POST')
        {
            $title = HTML::specialchars(Input::get('title', ''));
            $content = Input::get('content', '');

            if (!$title or !$content)
            {
                $this->notice('Wszystkie pola są wymagane');
                return Redirect::to('blog/edit/'.$id->id);
            }

            $data = array(
                'content_raw' => $content,
                'content'     => Model\Blog::prepare_content($content)
            );

            if ($title != $id->title)
            {
                $data['title'] = $title;
                $data['slug'] = ionic_find_slug($title, $id->id, 'blogs');
            }

            DB::table('blogs')->where('id', '=', $id->id)->update($data);

            $this->notice('Wpis został zaaktualizowany pomyślnie');

            return Redirect::to('blog/post/'.(isset($data['slug']) ? $data['slug'] : $id->slug));
        }

        $this->page->set_title('Edycja wpisu');
        $this->online('Edycja wpisu w blogu', 'blog/edit/'.$id->id);
        $this->page->breadcrumb_append('Blogi', 'blog');
        $this->page->breadcrumb_append('Edycja wpisu w blogu', 'blog/edit/'.$id->id);

        Asset::add('markitup', 'public/js/jquery.markitup.js', 'jquery');
        Asset::add('markitup', 'public/js/skins/simple/style.css');

        $this->view = View::make('blog.edit', array('blog' => $id));
    }

    /**
     * Display recent blog posts
     */
    public function action_index()
    {
        $posts = DB::table('blogs')->order_by('blogs.created_at', 'desc')
                ->join('users', 'users.id', '=', 'blogs.user_id')
                ->paginate(10, array('blogs.id', 'blogs.title', 'blogs.created_at', 'blogs.comments_count', 'blogs.slug', 'users.display_name', 'users.slug as user_slug'));

        $this->online('Blogi', 'blog');
        $this->page->set_title('Blogi');
        $this->page->breadcrumb_append('Blogi', 'blog');

        $this->view = View::make('blog.index', array('posts' => $posts));
    }

    /**
     * Show post
     *
     * @param  string   $post
     * @return Response
     */
    public function action_post($post)
    {
        $post = DB::table('blogs')->where('blogs.slug', '=', $post)->join('users', 'users.id', '=', 'blogs.user_id')->first(array('blogs.*', 'users.display_name', 'users.slug as user_slug'));

        if (!$post)
            return Response::error(404);

        $this->page->set_title($post->title);
        $this->online('Wpis bloga: '.$post->title, 'blog/post/'.$post->slug);

        $this->page->breadcrumb_append('Blogi', 'blog');
        $this->page->breadcrumb_append('Blog użytkownika', 'blog/user/'.$post->user_slug);
        $this->page->breadcrumb_append($post->title, 'blog/post/'.$post->slug);

        $this->view = View::make('blog.post', array(
                    'post'       => $post,
                    'moderation' => ((Auth::can('mod_blogs') or Auth::can('admin_blogs')) or (Auth::is_logged() and $this->user->id == $post->user_id)),
                    'can_karma'  => Model\Karma::can_karma($post->id, 'blog'),
                    'comments'   => $this->page->make_comments($post->id, 'blog')
                ));
    }

    /**
     * Show user blog
     *
     * @param  string   $user
     * @return Response
     */
    public function action_user($user)
    {
        $user = DB::table('users')->where('slug', '=', $user)->first(array('id', 'display_name', 'slug'));

        if (!$user)
            return Redirect::to('blog');

        $posts = DB::table('blogs')->order_by('blogs.created_at', 'desc')
                ->where('user_id', '=', $user->id)
                ->paginate(5, array('blogs.id', 'blogs.title', 'blogs.created_at', 'blogs.comments_count', 'blogs.slug', 'blogs.content'));

        $this->page->set_title('Blog użytkownika '.$user->display_name);
        $this->online('Blog użytkownika '.$user->display_name, 'blog/user/'.$user->slug);

        $this->page->breadcrumb_append('Blogi', 'blog');
        $this->page->breadcrumb_append('Blog użytkownika', 'blog/user/'.$user->slug);

        $this->view = View::make('blog.user', array('user'       => $user, 'posts'      => $posts, 'moderation' => ((Auth::can('mod_blogs') or Auth::can('admin_blogs')) or (Auth::is_logged() and $this->user->id == $user->id))));
    }

    /**
     * Write new blog entry
     *
     * @return Response
     */
    public function action_write()
    {
        if ($this->require_auth())
            return Redirect::to('blog/index');

        if (Auth::banned())
        {
            $this->notice('Zablokowani użytkownicy nie mogą dodawać oraz edytować treści w serwisie');
            return Redirect::to('blog');
        }

        if (Request::method() == 'POST' and !Request::forged())
        {
            $title = Input::get('title');
            $content = Input::get('content');

            if (empty($title) or empty($content))
            {
                $this->notice('Wszystkie pola są wymagane');
                return Redirect::to('blog/write')->with_input('only', array('title', 'content'));
            }

            $title = HTML::specialchars($title);

            $id = DB::table('blogs')->insert_get_id(array(
                'user_id'     => $this->user->id,
                'content_raw' => $content,
                'content'     => Model\Blog::prepare_content($content),
                'title'       => $title,
                'created_at'  => date('Y-m-d H:i:s'),
                'slug'        => ionic_tmp_slug('blogs')
                    ));

            $slug = ionic_find_slug($title, $id, 'blogs');

            DB::table('blogs')->where('id', '=', $id)->update(array(
                'slug' => $slug
            ));

            $this->notice('Wpis w blogu został pomyślnie dodany');

            return Redirect::to('blog/post/'.$slug);
        }

        $this->page->set_title('Dodaj wpis');
        $this->online('Dodawanie wpisu do blogu', 'blog/write');
        $this->page->breadcrumb_append('Blogi', 'blog');
        $this->page->breadcrumb_append('Dodawanie wpisu do blogu', 'blog/write');

        Asset::add('markitup', 'public/js/jquery.markitup.js', 'jquery');
        Asset::add('markitup', 'public/js/skins/simple/style.css');

        $this->view = View::make('blog.write', array(
                    'old' => Input::old()
                ));
    }

}