<?php
use Ionic\Forum\MarkerManager;
use Ionic\Forum\PermissionManager;

/**
 * Board controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    ionic
 * @subpackage forum
 */
class Board_Controller extends Base_Controller {

    /**
     * New thread
     */
    public function action_new($board)
    {
        $board = DB::table('forum_boards')->where('id', '=', $board)->first('*');

        if (!$board or $board->depth == 0)
            return Response::error(404);

        $board_id = (int) $board->id;

        if (!$this->permissions->can($board_id, PermissionManager::PERM_NEW_THREAD)) {
            $this->notice('Nie posiadasz wystarczających uprawnień, aby utworzyć temat na tym forum.');
            return Redirect::to('forum');
        }

        if (!Request::forged() and Request::method() == 'POST') {
            $raw_data = array('title' => '', 'post' => '', 'is_closed' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'post', 'is_closed')));

            $rules = array(
                'title'     => 'required|max:127',
                'post'      => 'required'
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails()) {
                return Redirect::to('board/new/'.$board->id)->with_input('only', array('title', 'post', 'is_closed'));
            }

            DB::connection()->pdo->beginTransaction();

            // Make sure post and thread have matching date and time
            $now = date('Y-m-d H:i:s');

            try {
                // First create thread
                $thread = new Model\Forum\Thread;

                $thread->board_id = $board_id;
                $thread->title = HTML::specialchars($raw_data['title']);
                $thread->posts_count = 1;
                $thread->created_at = $now;

                if ($this->permissions->can($board_id, PermissionManager::PERM_MOD_CLOSE)) {
                    $thread->is_closed = Input::get('is_closed', '0') == '1';
                } else {
                    $thread->is_closed = false;
                }

                $thread->create();

                // Then first post inside
                $post = new Model\Forum\Post;

                $post->thread_id = $thread->id;
                $post->is_op = true;
                $post->created_at = $now;
                $post->set_content($raw_data['post']);

                $post->create();

                // Update counters and last post data
                Model\Forum\Thread::update_last_post($thread->id, $thread->title, $thread->slug, $board->left, $board->right,
                                                     $post->id, $post->created_at, $post->user_id);
                Model\Forum\Board::update_board_counters($board->left, $board->right, 1, 1);
                Model\Forum\Thread::update_user_counters(Model\Forum\Thread::UPDATE_USER_NEW_THREAD);
            } catch (Exception $e) {
                // We failed somewhere...
                DB::connection()->pdo->rollBack();

                $this->notice('Nieznany błąd');
                return Redirect::to('board/new/'.$board->id)->with_input('only', array('title', 'post', 'is_closed'));
            }

            DB::connection()->pdo->commit();

            // Redirect to thread
            return Redirect::to('thread/show/'.$thread->slug);
        }

        // Old data
        $old_data = array('title' => '', 'post' => '', 'is_closed' => '');
        $old_data = array_merge($old_data, Input::old());

        // Setup page display
        $this->page->set_title('Nowy temat');
        $this->page->breadcrumb_append('Forum', 'forum');
        $this->page->breadcrumb_append($board->title, 'board/show/'.$board->slug);
        $this->page->breadcrumb_append('Nowy temat', 'board/new/'.$board->id);
        $this->online('Tworzenie nowego tematu', 'forum');

        Asset::add('markitup', 'public/js/jquery.markitup.js', 'jquery');
        Asset::add('markitup', 'public/js/skins/simple/style.css');

        $this->view = View::make('forum.new_thread', array(
            'board'     => $board,
            'can_close' => $this->permissions->can($board_id, PermissionManager::PERM_MOD_CLOSE),
            'old'       => $old_data
        ));
    }

    /**
     * Show board
     *
     * @param   string  $slug
     */
    public function action_show($board)
    {
        $board = DB::table('forum_boards')->where('slug', '=', $board)->first('*');

        if (!$board or $board->depth == 0)
            return Response::error(404);

        $board_id = (int) $board->id;

        // Read permission
        if (!$this->permissions->can($board_id, PermissionManager::PERM_READ)) {
            $this->notice('Nie posiadasz wystarczających uprawnień, aby przeglądać zawartość tego forum.');
            return Redirect::to('forum');
        }

        // Sub boards
        $sub_boards = Model\Forum\Board::get_3level($board->left, $board->right, $board->depth + 1, $board->depth + 2);

        // Process sub boards
        $unread = array();
        $board_ids = array();
        $expiration = time() - Config::get('forum.marker_expire', 7) * 86400;

        foreach ($sub_boards as $root) {
            $id = (int) $root[0]->id;

            if (!$this->permissions->can($id, PermissionManager::PERM_VIEW)) {
                unset($sub_boards[$root[0]->id]);
                continue;
            } elseif (!$this->permissions->can($id, PermissionManager::PERM_READ)) {
                $sub_boards[$root[0]->id][0]->last_title = 'Ukryte';
                $sub_boards[$root[0]->id][0]->last_slug = '';
            }

            // No threads or last post older than expiration date = always read forum
            if ($root[0]->last_id == 0 or $root[0]->last_date == '0000-00-00 00:00:00' or strtotime($root[0]->last_date) < $expiration) {
                $unread[$id] = false;
            // Last post unmarked
            } elseif (!$this->markers->marked((int) $root[0]->last_id)) {
                $unread[$id] = true;
            } else {
                $board_ids[] = $id;
            }

            // Second level
            foreach ($root[1] as $sub) {
                if (!$this->permissions->can((int) $sub[0]->id, PermissionManager::PERM_VIEW)) {
                    unset($sub_boards[$root[0]->id][1][$sub[0]->id]);
                }
            }
        }

        // Fill unread array
        // Unfortunately, array_merge is too stupid to handle arrays with int keys so we let model fill referenced array
        Model\Forum\Board::get_unread_boards($board_ids, $this->markers->markers, $unread);

        // TODO: Filtering and per page option
        $paginator = Paginator::make(array(), $board->threads_count, 20);

        // Get threads
        $threads = array();

        foreach (Model\Forum\Thread::get_threads($board->id, ($paginator->page - 1) * $paginator->per_page, $paginator->per_page) as $t) {
            if ($t->last_id == 0 or $t->last_date == '0000-00-00 00:00:00' or strtotime($t->last_date) < $expiration) {
                $t->is_unread = false;
            } else {
                $t->is_unread = !$this->markers->marked((int) $t->id);
            }

            $threads[] = $t;
        }

        // Setup page display
        $this->page->set_title($board->title);
        $this->page->breadcrumb_append('Forum', 'forum');
        $this->page->breadcrumb_append($board->title, 'board/show/'.$board->slug);
        $this->online('Forum: '.$board->title, 'board/show/'.$board->slug);

        $this->view = View::make('forum.board', array(
            'board'      => $board,
            'sub_boards' => $sub_boards,
            'unread'     => $unread,
            'jumpbox'    => $this->get_jumpbox(),
            'paginator'  => $paginator,
            'threads'    => $threads,
            'can_new'    => $this->permissions->can($board_id, PermissionManager::PERM_NEW_THREAD)
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
