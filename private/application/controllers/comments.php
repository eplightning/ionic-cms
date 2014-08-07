<?php

/**
 * Comments controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Comments_Controller extends Base_Controller {

    /**
     * Add new comment
     *
     * @param  string   $content_type
     * @param  string   $content_id
     * @return Response
     */
    public function action_add($content_type, $content_id)
    {
        if (Auth::is_logged() and Auth::banned())
        {
            return Response::error(403);
        }
        
        if (Auth::is_guest() and !Config::get('guests.comments', false))
        {
            return Response::error(403);
        }

        $types = Model\Comment::get_types();

        if (!isset($types[$content_type]) or !ctype_digit($content_id) or Request::forged())
        {
            return Response::error(500);
        }

        $table = null;
        $link = null;
        $content_id = (int) $content_id;

        switch ($content_type)
        {
            case 'blog':
                $table = 'blogs';
                $link = DB::table('blogs')->where('id', '=', $content_id)->first('slug');

                if (!$link)
                    return Response::error(500);

                $link = 'blog/post/'.$link->slug;
                break;

            case 'news':
                $table = 'news';
                $link = DB::table('news')->where('id', '=', $content_id)->first(array('slug', 'external_url'));

                if (!$link)
                    return Response::error(500);

                $link = $link->external_url ? : 'news/show/'.$link->slug;
                break;

            case 'file':
                $table = 'files';
                $link = DB::table('files')->where('id', '=', $content_id)->first(array('slug'));

                if (!$link)
                    return Response::error(500);

                $link = 'files/show/'.$link->slug;
                break;

            case 'video':
                $table = 'videos';
                $link = DB::table('videos')->where('id', '=', $content_id)->first(array('slug'));

                if (!$link)
                    return Response::error(500);

                $link = 'video/show/'.$link->slug;
                break;

            case 'photo_category':
                $table = 'photo_categories';
                $link = DB::table('photo_categories')->where('id', '=', $content_id)->first(array('slug'));

                if (!$link)
                    return Response::error(500);

                $link = 'gallery/category/'.$link->slug;
                break;

            default:
                $event = \Event::until('ionic.comment_add', array($content_type, $content_id));

                if (is_array($event))
                {
                    list($table, $link) = $event;
                }
                else
                {
                    return Response::error(500);
                }
        }

        if (!Input::has('comment'))
        {
            $this->notice('Komentarz nie może być pusty');
            return Redirect::to($link);
        }

        $comment = Input::get('comment');

        $data = array(
            'comment'      => Model\Comment::prepare_content($comment),
            'comment_raw'  => $comment,
            'created_at'   => date('Y-m-d H:i:s'),
            'ip'           => Request::ip(),
            'content_id'   => $content_id,
            'content_type' => $content_type,
            'content_link' => $link
        );

        if (Auth::is_logged())
        {
            $flood = (int) Config::get('advanced.comment_flood', 30);

            if ($flood)
            {
                if (DB::table('comments')->where('created_at', '>=', date('Y-m-d H:i:s', (time() - $flood)))->where('user_id', '=', $this->user->id)->first('id'))
                {
                    $this->notice('Musisz odczekać '.$flood.' sekund zanim dodasz kolejny komentarz');
                    return Redirect::to($link);
                }
            }

            $data['user_id'] = $this->user->id;
        }
        else
        {
            $flood = (int) Config::get('advanced.comment_flood', 30);

            if ($flood)
            {
                if (DB::table('comments')->where('created_at', '>=', date('Y-m-d H:i:s', (time() - $flood)))->where('ip', '=', Request::ip())->first('id'))
                {
                    $this->notice('Musisz odczekać '.$flood.' sekund zanim dodasz kolejny komentarz');
                    return Redirect::to($link);
                }
            }

            require_once path('app').'vendor'.DS.'recaptchalib.php';

            if (empty($_POST['recaptcha_challenge_field']) or empty($_POST['recaptcha_response_field']))
            {
                $this->notice('Wprowadzony kod z obrazka jest nieprawidłowy');
                return Redirect::to($link);
            }
            else
            {
                $response = recaptcha_check_answer(Config::get('advanced.recaptcha_private', ''), Request::ip(), $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);

                if (!$response->is_valid)
                {
                    $this->notice('Wprowadzony kod z obrazka jest nieprawidłowy');
                    return Redirect::to($link);
                }
            }

            $data['user_id'] = null;
            $data['guest_name'] = Input::has('guest_name') ? Str::limit(HTML::specialchars(Input::get('guest_name')), 20) : 'Gość';
        }

        DB::table('comments')->insert($data);

        if (Auth::is_logged())
        {
            $points = Config::get('points.points_for_comment', 0);

            if ($points)
            {
                DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array(
                    'comments_count' => DB::table('comments')->where('user_id', '=', $this->user->id)->where('is_hidden', '=', 0)->count(),
                    'points'         => DB::raw('`points` + '.$points)
                ));
            }
            else
            {
                DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array(
                    'comments_count' => DB::table('comments')->where('user_id', '=', $this->user->id)->where('is_hidden', '=', 0)->count()
                ));
            }
        }

        if ($table)
        {
            DB::table($table)->where('id', '=', $content_id)->update(array(
                'comments_count' => DB::table('comments')->where('content_type', '=', $content_type)->where('content_id', '=', $content_id)->where('is_hidden', '=', 0)->count()
            ));
        }

        $this->notice('Komentarz został dodany pomyślnie');

        return Redirect::to($link);
    }

    /**
     * Delete comment
     *
     * @param  string   $id
     * @return Response
     */
    public function action_delete($id)
    {
        if (!Auth::can('mod_comments') and !Auth::can('admin_comments'))
            return Response::json(array('status' => false));

        if (!Request::ajax() or Request::method() != 'POST' or Request::forged() or !ctype_digit($id))
            return Response::json(array('status' => false));

        $id = DB::table('comments')->left_join('users', 'users.id', '=', 'comments.user_id')
                                   ->where('comments.id', '=', (int) $id)
                                   ->first(array('comments.id', 'comments.user_id', 'comments.content_id', 'comments.content_type', 'comments.content_link',
                                                 'comments.guest_name', 'users.display_name'));

        // let's assume it was removed by someone else
        if (!$id)
            return Response::json(array('status' => true));

        Model\Comment::delete($id);
        
        if (!$id->user_id or empty($id->display_name))
        {
            Model\Log::add('Usunięto komentarz gościa '.$id->guest_name, $this->user->id);
        }
        else
        {
            Model\Log::add('Usunięto komentarz użytkownika '.$id->display_name, $this->user->id);
        }

        return Response::json(array('status' => true));
    }

    /**
     * Edit comment
     *
     * @param  string   $id
     * @return Response
     */
    public function action_edit($id)
    {
        if (!Auth::can('mod_comments') and !Auth::can('admin_comments'))
            return Response::error(403);

        if (!ctype_digit($id))
            return Response::error(500);

        $id = DB::table('comments')->where('id', '=', (int) $id)->first(array('id', 'user_id', 'content_id', 'content_type', 'comment_raw', 'content_link'));

        if (!$id)
            return Response::error(404);

        if (!Request::forged() and Request::method() == 'POST' and Input::has('comment'))
        {
            DB::table('comments')->where('id', '=', $id->id)->update(array(
                'comment'     => Model\Comment::prepare_content(Input::get('comment')),
                'comment_raw' => Input::get('comment')
            ));

            $this->notice('Komentarz zapisany pomyślnie');

            Model\Log::add('Edytował komentarz', $this->user->id);

            return Redirect::to($id->content_link ? : 'index');
        }

        $this->page->set_title('Edycja komentarza');
        $this->online('Edycja komentarza', 'comments/edit/'.$id->id);
        $this->page->breadcrumb_append('Edycja komentarza', 'comments/edit/'.$id->id);

        $this->view = View::make('comments.edit', array(
                    'comment' => $id
                ));
    }

    /**
     * Upvote/downvote comment
     *
     * @param  string   $t
     * @return Response
     */
    public function action_karma($t)
    {
        if ($t != 'up' and $t != 'down')
            return Response::error(500);

        if (!Request::ajax() or Request::method() != 'POST' or Request::forged() or !Input::has('id') or !ctype_digit(Input::get('id')))
            return Response::error(500);

        $id = (int) Input::get('id');

        $comment = DB::table('comments')->where('id', '=', $id)->first(array('user_id', 'id', 'karma'));

        if (!$comment)
            return Response::error(404);

        if (Auth::is_logged())
        {
            if ($comment->user_id and $comment->user_id == $this->user->id)
            {
                return Response::error(403);
            }

            $id = $this->user->id;

            if (DB::table('karma_comments')->where('comment_id', '=', $comment->id)
                            ->where(function($q) use ($id) {
                                        $q->where('ip', '=', Request::ip());
                                        $q->or_where('user_id', '=', $id);
                                    })
                            ->first('ip'))
            {
                return Response::error(403);
            }
        }
        else
        {
            if (!Config::get('guests.karma', false))
            {
                return Response::error(403);
            }

            if (DB::table('karma_comments')->where('comment_id', '=', $comment->id)->where('ip', '=', Request::ip())->first('ip'))
            {
                return Response::error(403);
            }
        }

        $karma = $comment->karma;
        $karma += ($t == 'up' ? 1 : -1);

        DB::table('comments')->where('id', '=', $comment->id)->update(array(
            'karma' => $karma,
        ));

        DB::table('karma_comments')->insert(array(
            'user_id'    => Auth::is_logged() ? $this->user->id : null,
            'ip'         => Request::ip(),
            'comment_id' => $comment->id,
        ));

        if ($karma >= 0)
        {
            return Response::json(array('status' => true, 'points' => $karma, 'color'  => 'green'));
        }

        return Response::json(array('status' => true, 'points' => $karma, 'color'  => 'red'));
    }

    /**
     * Paginate comments
     *
     * @param  string   $last_id
     * @param  string   $content_id
     * @param  string   $content_type
     * @return Response
     */
    public function action_pagination($last_id, $content_id, $content_type)
    {
        if (!ctype_digit($last_id) or !Request::ajax() or !ctype_digit($content_id))
            return Response::json(array('status' => false));

        $last_id = (int) $last_id;
        $content_id = (int) $content_id;
        $per_page = (int) Config::get('limits.comments', 20);
        $types = Model\Comment::get_types();

        if (!isset($types[$content_type]))
        {
            return Response::json(array('status' => false));
        }

        if (Auth::can('mod_comments') or Auth::can('admin_comments'))
        {
            $moderation = true;

            $count = DB::table('comments')->where('content_id', '=', $content_id)
                    ->where('content_type', '=', $content_type)
                    ->where('comments.id', (Config::get('advanced.comment_sort') == 'asc' ? '>' : '<'), $last_id)
                    ->count();

            $comments = DB::table('comments')->where('content_id', '=', $content_id)
                    ->where('content_type', '=', $content_type)
                    ->where('comments.id', (Config::get('advanced.comment_sort') == 'asc' ? '>' : '<'), $last_id)
                    ->left_join('users', 'users.id', '=', 'comments.user_id')
                    ->left_join('profiles', 'profiles.user_id', '=', 'users.id')
                    ->take($per_page)->order_by('comments.id', \Config::get('advanced.comment_sort'))
                    ->get(array(
                'comments.id', 'comments.user_id', 'comments.comment', 'comments.created_at', 'comments.ip', 'comments.karma', 'comments.guest_name', 'comments.is_hidden', 'comments.is_reported',
                'users.display_name', 'profiles.comments_count', 'profiles.news_count', 'profiles.avatar', 'users.slug', 'users.email'
                    ));
        }
        else
        {
            $moderation = false;

            $count = DB::table('comments')->where('content_id', '=', $content_id)
                    ->where('content_type', '=', $content_type)
                    ->where('is_hidden', '=', 0)
                    ->where('comments.id', (Config::get('advanced.comment_sort') == 'asc' ? '>' : '<'), $last_id)
                    ->count();

            $comments = DB::table('comments')->where('content_id', '=', $content_id)
                    ->where('content_type', '=', $content_type)
                    ->where('is_hidden', '=', 0)
                    ->where('comments.id', (Config::get('advanced.comment_sort') == 'asc' ? '>' : '<'), $last_id)
                    ->left_join('users', 'users.id', '=', 'comments.user_id')
                    ->left_join('profiles', 'profiles.user_id', '=', 'users.id')
                    ->take($per_page)->order_by('comments.id', \Config::get('advanced.comment_sort'))
                    ->get(array(
                'comments.id', 'comments.user_id', 'comments.comment', 'comments.created_at', 'comments.ip', 'comments.karma', 'comments.guest_name', 'comments.is_hidden', 'comments.is_reported',
                'users.display_name', 'profiles.comments_count', 'profiles.news_count', 'profiles.avatar', 'users.slug', 'users.email'
                    ));
        }

        $used_karma = array();
        $last_id = null;

        if (Auth::is_guest())
        {
            if (!Config::get('guests.karma', false))
            {
                foreach ($comments as $c)
                {
                    $used_karma[] = $c->id;
                    $last_id = $c->id;
                }
            }
            else
            {
                $ids = array();

                foreach ($comments as $c)
                {
                    $ids[] = $c->id;
                    $last_id = $c->id;
                }

                if (!empty($ids))
                {
                    foreach (DB::table('karma_comments')->where_in('comment_id', $ids)->where('ip', '=', \Request::ip())->take($per_page)->get('comment_id') as $id)
                    {
                        $used_karma[] = $id->comment_id;
                    }
                }
            }
        }
        else
        {
            $ids = array();

            $user_id = Auth::get_user()->id;

            foreach ($comments as $c)
            {
                if ($c->user_id == $user_id)
                {
                    $used_karma[] = $c->id;
                }
                else
                {
                    $ids[] = $c->id;
                }

                $last_id = $c->id;
            }

            if (!empty($ids))
            {
                foreach (DB::table('karma_comments')->where_in('comment_id', $ids)->where('ip', '=', \Request::ip())
                        ->or_where('user_id', '=', $user_id)->where_in('comment_id', $ids)->take($per_page)->get('comment_id') as $id)
                {
                    $used_karma[] = $id->comment_id;
                }
            }
        }

        $view = View::make('comments.list');

        $view->with('comments', $comments)
                ->with('used_karma', $used_karma)
                ->with('moderation', $moderation);

        return Response::json(array('status' => true, 'count' => $count, 'per_page' => $per_page, 'comments' => $view->render(), 'last_comment' => $last_id));
    }

    /**
     * Report comment
     *
     * @param  string   $comment
     * @return Response
     */
    public function action_report($comment)
    {
        if (Auth::is_guest() or !ctype_digit($comment))
            return Response::error(500);

        $comment = DB::table('comments')->where('id', '=', (int) $comment)->first(array('user_id', 'id', 'comment', 'is_reported', 'content_link', 'content_type'));

        if (!$comment)
            return Response::error(404);

        if ($comment->is_reported)
        {
            $this->notice('Komentarz został już zgłoszony');
            return Redirect::to($comment->content_link ? : 'index');
        }

        if ($comment->user_id == $this->user->id)
        {
            $this->notice('Nie możesz zgłosić swojego własnego komentarza');
            return Redirect::to($comment->content_link ? : 'index');
        }

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to($comment->content_link ? : 'index');
        }

        DB::table('comments')->where('id', '=', $comment->id)->update(array(
            'is_reported' => 1
        ));

        $title = 'Komentarz';

        $types = \Model\Comment::get_types();

        if (isset($types[$comment->content_type]))
        {
            $title = 'Komentarz: '.$types[$comment->content_type];
        }

        DB::table('reports')->insert(array(
            'user_id'       => $this->user->id,
            'title'         => $title,
            'saved_content' => $comment->comment,
            'created_at'    => date('Y-m-d H:i:s'),
            'item_type'     => 'comment',
            'item_id'       => $comment->id,
            'item_link'     => $comment->content_link.'#comment-id-'.$comment->id
        ));

        $this->notice('Komentarz został zgłoszony pomyślnie');
        return Redirect::to($comment->content_link ? : 'index');
    }

}