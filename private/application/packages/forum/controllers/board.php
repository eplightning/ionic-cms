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
     * Show board
     *
     * @param   string  $slug
     */
    public function action_show($board)
    {
        $board = DB::table('forum_boards')->where('slug', '=', $slug)->first('*');

        if (!$board or $board->depth == 0)
            return Response::error(404);

        // Read permission
        if (!$this->permissions->can((int) $board->id, PermissionManager::PERM_READ)) {
            $this->notice('Nie posiadasz wystarczających uprawnień, aby przeglądać zawartość tego forum.');
            return Redirect::to('forum');
        }

        // Sub boards
        $sub_boards = Model\Forum\Board::get_3level($board->left, $board->right, $board->depth + 1, $board->depth + 2);

        // Process sub boards
        $unread = array();
        $board_ids = array();
        $skip_check = Auth::can('admin_root');
        $expiration = time() - Config::get('forum.marker_expire', 7) * 86400;

        foreach ($sub_boards as $root) {
            $id = (int) $root[0]->id;

            if (!$skip_check) {
                if (!$this->permissions->can($id, PermissionManager::PERM_VIEW)) {
                    unset($sub_boards[$root[0]->id]);
                    continue;
                } elseif (!$this->permissions->can($id, PermissionManager::PERM_READ)) {
                    $sub_boards[$root[0]->id][0]->last_title = 'Ukryte';
                    $sub_boards[$root[0]->id][0]->last_slug = '';
                }
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
            if (!$skip_check) {
                foreach ($root[1] as $sub) {
                    if (!$this->permissions->can((int) $sub[0]->id, PermissionManager::PERM_VIEW)) {
                        unset($sub_boards[$root[0]->id][1][$sub[0]->id]);
                    }
                }
            }
        }

        // Fill unread array
        // Unfortunately, array_merge is too stupid to handle arrays with int keys so we let model fill referenced array
        Model\Forum\Board::get_unread_boards($board_ids, $this->markers->markers, $unread);

        // TODO: Filtering and per page option
        $paginator = Paginator::make(array(), $board->topics_count, 20);

        // Get threads
        $threads = array();

        foreach (Model\Forum\Thread::get_threads($board->id, ($paginator->page - 1) * $paginator->per_page, $paginator->per_page) as $t) {
            if ($t->last_id == 0 or $t->last_date == '0000-00-00 00:00:00' or strtotime($t->last_date) < $expiration) {
                $t->is_unread = false;
            } else {
                $t->is_unread = $this->markers->marked((int) $t->id);
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
        ));
    }

    /**
     * Initialize permission and marker manager
     */
    public function before()
    {
        parent::before();

        $this->markers = new MarkerManager;
        $this->permissions = new PermissionManager(Auth::is_guest() ? 0 : (int) $this->user->group_id);
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

        if (!Auth::can('admin_root')) {
            foreach ($jumpbox as $id => $val) {
                if (!$this->permissions->can($id, PermissionManager::PERM_VIEW)) {
                    unset($jumpbox[$id]);
                }
            }
        }

        return $jumpbox;
    }
}
