<?php
use Ionic\Forum\MarkerManager;
use Ionic\Forum\PermissionManager;

/**
 * Forum controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    ionic
 * @subpackage forum
 */
class Forum_Controller extends Base_Controller {

    /**
     * @var Ionic\Forum\MarkerManager
     */
    protected $markers = null;

    /**
     * @var Ionic\Forum\PermissionManager
     */
    protected $permissions = null;

    /**
     * Board index
     *
     * @return Response
     */
    public function action_index()
    {
        // Load boards
        $boards = Model\Forum\Board::get_3level();

        // Process boards
        $unread = array();
        $board_ids = array();
        $expiration = time() - Config::get('forum.marker_expire', 7) * 86400;

        foreach ($boards as $root) {
            if (!$this->permissions->can((int) $root[0]->id, PermissionManager::PERM_VIEW)) {
                unset($boards[$root[0]->id]);
                continue;
            }

            // Second level
            foreach ($root[1] as $board) {
                $id = (int) $board[0]->id;

                if (!$this->permissions->can($id, PermissionManager::PERM_VIEW)) {
                    unset($boards[$root[0]->id][1][$board[0]->id]);
                    continue;
                } elseif (!$this->permissions->can($id, PermissionManager::PERM_READ)) {
                    $boards[$root[0]->id][1][$board[0]->id][0]->last_title = 'Ukryte';
                    $boards[$root[0]->id][1][$board[0]->id][0]->last_slug = '';
                }

                // No threads or last post older than expiration date = always read forum
                if ($board[0]->last_id == 0 or $board[0]->last_date == '0000-00-00 00:00:00' or strtotime($board[0]->last_date) < $expiration) {
                    $unread[$id] = false;
                // Last post unmarked
                } elseif (!$this->markers->marked((int) $board[0]->last_id)) {
                    $unread[$id] = true;
                } else {
                    $board_ids[] = $id;
                }

                // Third level (only permission check)
                foreach ($board[1] as $sub) {
                    if (!$this->permissions->can((int) $sub->id, PermissionManager::PERM_VIEW)) {
                        unset($boards[$root[0]->id][1][$board[0]->id][1][$sub->id]);
                    }
                }
            }

            // Empty root category?
            if (count($boards[$root[0]->id][1]) <= 0)
                unset($boards[$root[0]->id]);
        }

        // Fill unread array
        // Unfortunately, array_merge is too stupid to handle arrays with int keys so we let model fill referenced array
        Model\Forum\Board::get_unread_boards($board_ids, $this->markers->markers, $unread);

        // Setup page display
        $this->page->set_title('Forum');
        $this->page->breadcrumb_append('Forum', 'forum');
        $this->online('Forum - Strona główna', 'forum');

        $this->view = View::make('forum.index', array(
            'boards'  => $boards,
            'unread'  => $unread,
            'jumpbox' => $this->get_jumpbox()
        ));
    }

    /**
     * Show threads with unread posts
     *
     * @return  Response
     */
    public function action_new_threads()
    {
        if (Auth::is_guest())
            return Response::error(403);

        $no_access = array();

        foreach ($this->permissions->permissions as $board => $mask) {
            if (!($mask & PermissionManager::PERM_READ))
                $no_access[] = $board;
        }

        $threads = Model\Forum\Thread::get_new_threads($no_access, $this->markers->markers, 0, 30);

        // Setup page display
        $this->page->set_title('Nieprzeczytane tematy');
        $this->page->breadcrumb_append('Forum', 'forum');
        $this->page->breadcrumb_append('Nieprzeczytane tematy', 'forum/new_threads');
        $this->online('Nieprzeczytane tematy', 'forum/new_threads');

        $this->view = View::make('forum.new_threads', array(
            'jumpbox'    => $this->get_jumpbox(),
            'threads'    => $threads
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
