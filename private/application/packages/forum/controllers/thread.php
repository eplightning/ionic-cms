<?php
use Ionic\Forum\MarkerManager;
use Ionic\Forum\PermissionManager;

/**
 * Thread controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    ionic
 * @subpackage forum
 */
class Thread_Controller extends Base_Controller {

    /**
     * @var Ionic\Forum\MarkerManager
     */
    protected $markers = null;

    /**
     * @var Ionic\Forum\PermissionManager
     */
    protected $permissions = null;

    /**
     * Reply
     *
     * @param   string    $thread
     * @param   string    $post
     * @return  Response
     */
    public function action_reply($thread, $post = null)
    {
        if (Auth::banned() or !ctype_digit($thread))
            return Response::error(500);

        $thread = new Model\Forum\Thread((int) $thread);

        if (!$thread->id or !$this->permissions->can($thread->board_id, PermissionManager::PERM_POST)) {
            $this->notice('Taki temat nie istnieje lub nie masz dostępu do tworzenia odpowiedzi');
            return Redirect::to('forum');
        }

        if ($thread->is_closed and !$this->permissions->can($thread->board_id, PermissionManager::PERM_MOD)) {
            $this->notice('Tworzenie postów w zamkniętych tematch wymaga uprawnień moderatorskich');
            return Redirect::to('thread/show/'.$thread->slug);
        }

        $board = DB::table('forum_boards')->where('id', '=', $thread->board_id)->first('*');

        $fail_uri = 'thread/reply/'.$thread->id;

        if ($post !== null and ctype_digit($post)) {
            $post = DB::table('forum_posts')->where('forum_posts.id', '=', $post)
                                            ->left_join('users', 'users.id', '=', 'forum_posts.user_id')
                                            ->first(array('users.display_name', 'forum_posts.content_raw', 'forum_posts.thread_id', 'forum_posts.id'));

            if (!$post or $post->thread_id != $thread->id)
                $post = null;

            $fail_uri = 'thread/reply/'.$thread->id.'/'.$post->id;
        } else {
            $post = null;
        }

        if (Auth::is_guest())
            require_once path('app').'vendor'.DS.'recaptchalib.php';

        if (!Request::forged() and Request::method() == 'POST') {
            if (Auth::is_guest()) {
                if (!isset($_POST['recaptcha_challenge_field']))
                    $_POST['recaptcha_challenge_field'] = '';
                if (!isset($_POST['recaptcha_response_field']))
                    $_POST['recaptcha_response_field'] = '';

                $response = recaptcha_check_answer(Config::get('advanced.recaptcha_private', ''), Request::ip(), $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);

                if (!$response->is_valid) {
                    $this->notice('Wprowadzony kod z obrazka jest nieprawidłowy');
                    return Redirect::to('board/new/'.$board->id)->with_input('only', array('title', 'post', 'is_closed', 'is_sticky'));
                }
            }

            $raw_data = array('post' => '');
            $raw_data = array_merge($raw_data, Input::only(array('post')));

            $rules = array(
                'post' => 'required'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails()) {
                return Redirect::to($fail_uri)->with_input('only', array('post'));
            }

            DB::connection()->pdo->beginTransaction();

            try {
                // Then first post inside
                $new_post = new Model\Forum\Post;

                $new_post->thread_id = $thread->id;
                $new_post->is_op = false;
                $new_post->set_content($raw_data['post']);
                $new_post->created_at = date('Y-m-d H:i:s');

                $new_post->create();

                // Update counters and last post data
                Model\Forum\Thread::update_last_post($thread->id, $thread->title, $thread->slug, $board->left, $board->right,
                                                     $new_post->id, $new_post->created_at, $new_post->user_id);
                Model\Forum\Board::update_board_counters($board->left, $board->right, 1, 0);
                Model\Forum\Thread::refresh_thread_counters($thread->id);
                Model\Forum\Thread::update_user_counters(Model\Forum\Thread::UPDATE_USER_NEW_POST);

                // Unmark
                $this->markers->unmark($thread->id);
            } catch (Exception $e) {
                // We failed somewhere...
                DB::connection()->pdo->rollBack();

                $this->notice('Nieznany błąd');
                return Redirect::to($fail_uri)->with_input('only', array('post'));
            }

            DB::connection()->pdo->commit();

            // Redirect to thread
            return Redirect::to('thread/show/'.$thread->slug.'?page=last');
        }

        // Old data
        $old_data = array('post' => '');
        $old_data = array_merge($old_data, Input::old());

        // Quote post
        if ($post !== null and empty($old_data['post'])) {
            $old_data['post'] = '[quote='.($post->display_name ? $post->display_name : 'Gość')."]\n".
                                $post->content_raw."\n[/quote]";
        }

        // Setup page display
        $this->page->set_title('Odpowiedź');
        $this->page->breadcrumb_append('Forum', 'forum');
        $this->page->breadcrumb_append($board->title, 'board/show/'.$board->slug);
        $this->page->breadcrumb_append('Odpowiedź', $fail_uri);
        $this->online('Tworzenie odpowiedzi', $fail_uri);

        Asset::add('markitup', 'public/js/jquery.markitup.js', 'jquery');
        Asset::add('markitup', 'public/js/skins/simple/style.css');

        $this->view = View::make('forum.reply', array(
            'board'     => $board,
            'thread'    => $thread,
            'old'       => $old_data,
            'action'    => $fail_uri,
            'recaptcha' => Auth::is_guest() ? recaptcha_get_html(Config::get('advanced.recaptcha_public', '')) : ''
        ));
    }

    /**
     * Report post
     *
     * @param  string   $comment
     * @return Response
     */
    public function action_report($post)
    {
        if (Auth::is_guest() or !ctype_digit($post))
            return Response::error(500);

        $post = DB::table('forum_posts')->join('forum_threads', 'forum_threads.id', '=', 'forum_posts.thread_id')
                                        ->where('forum_posts.id', '=', (int) $post)
                                        ->first(array('forum_posts.id', 'forum_posts.user_id', 'forum_posts.content', 'forum_posts.is_reported',
                                                      'forum_threads.slug', 'forum_threads.board_id', 'forum_threads.title'));

        if (!$post)
            return Response::error(404);

        if (!$this->permissions->can((int) $post->board_id, PermissionManager::PERM_READ))
            return Response::error(403);

        $item_link = 'thread/show/'.$post->slug.'?post='.$post->id.'#post-id-'.$post->id;

        if ($post->user_id == $this->user->id) {
            $this->notice('Nie możesz zgłosić swojego własnego postu');
            return Redirect::to($item_link);
        }

        $report = DB::table('reports')->where('item_id', '=', $post->id)
                                      ->where('item_type', '=', 'forum_post')
                                      ->where('user_id', '=', $this->user->id)
                                      ->first(array('id'));

        if ($report) {
            $this->notice('Ten post został już przez Ciebie zgłoszony');
            return Redirect::to($item_link);
        }

        if (!($status = $this->confirm())) {
            return;
        }
        elseif ($status == 2) {
            return Redirect::to($item_link);
        }

        if (!$post->is_reported)
            DB::table('forum_posts')->where('id', '=', $post->id)->update(array('is_reported' => 1));

        DB::table('reports')->insert(array(
            'user_id'       => $this->user->id,
            'title'         => $post->title,
            'saved_content' => $post->content,
            'created_at'    => date('Y-m-d H:i:s'),
            'item_type'     => 'forum_post',
            'item_id'       => $post->id,
            'item_link'     => $item_link
        ));

        $this->notice('Post został zgłoszony pomyślnie');
        return Redirect::to($item_link);
    }

    /**
     * Show thread
     *
     * @param   string      $thread
     * @return  Response
     */
    public function action_show($thread)
    {
        $thread = new Model\Forum\Thread($thread);

        if (!$thread->id or !$this->permissions->can($thread->board_id, PermissionManager::PERM_READ)) {
            $this->notice('Taki temat nie istnieje lub nie masz do niego dostępu');
            return Redirect::to('forum');
        }

        // For ArrayAccess
        $this->permissions->select_board($thread->board_id);

        $board = DB::table('forum_boards')->where('id', '=', $thread->board_id)->first('*');

        // Mark as read and update views
        $this->markers->mark($thread->id, strtotime($thread->last_date));
        Model\Forum\Thread::update_views($thread->id);

        // Pagination
        $paginator = Paginator::make(array(), $thread->posts_count, 20); // TODO: Posts per page

        if (Input::get('page', null) == 'last') {
            $paginator->page = $paginator->last;
        } elseif (ctype_digit(Input::get('post', null))) {
            $posts_before = DB::table('forum_posts')->where('thread_id', '=', $thread->id)
                                                    ->where('id', '<', (int) Input::get('post'))
                                                    ->count();

            $posts_before = floor($posts_before / $paginator->per_page) + 1;

            if ($posts_before <= $paginator->last) {
                $paginator->page = $posts_before;
            }
        }

        $posts = Model\Forum\Post::get_posts($thread->id, ($paginator->page - 1) * $paginator->per_page, $paginator->per_page);

        // Setup page display
        $this->page->set_title($thread->title);
        $this->page->breadcrumb_append('Forum', 'forum');
        $this->page->breadcrumb_append($board->title, 'board/show/'.$board->slug);
        $this->page->breadcrumb_append($thread->title, 'thread/show/'.$thread->slug);
        $this->online('Temat: '.$thread->title, 'thread/show/'.$thread->slug);

        $this->view = View::make('forum.thread', array(
            'thread'      => $thread,
            'board'       => $board,
            'jumpbox'     => $this->get_jumpbox(),
            'paginator'   => $paginator,
            'posts'       => $posts,
            'permissions' => $this->permissions,
            'is_owner'    => !Auth::is_guest() and Auth::get_user()->id == $thread->user_id,
            'can_post'    => !Auth::banned() and $this->permissions->can($thread->board_id, PermissionManager::PERM_POST)
                             and (!$thread->is_closed or $this->permissions->can($thread->board_id, PermissionManager::PERM_MOD)),
        ));
    }

    /**
     * Initialize permission and marker manager
     */
    public function before()
    {
        parent::before();

        $this->markers = new MarkerManager;
        $this->permissions = new PermissionManager(Auth::is_guest() ? 0 : (int) $this->user->group_id, Auth::can('admin_root'));
    }

    /**
     * Build jumpbox
     *
     * @return array
     */
    protected function get_jumpbox()
    {
        $jumpbox = Cache::get('forum-jumpbox');

        if ($jumpbox === null) {
            $jumpbox = Model\Forum\Board::build_jumpbox();

            Cache::put('forum-jumpbox', $jumpbox);
        }

        foreach ($jumpbox as $id => $val) {
            if (!$this->permissions->can($id, PermissionManager::PERM_VIEW)) {
                unset($jumpbox[$id]);
            }
        }

        return $jumpbox;
    }
}
