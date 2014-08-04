<?php

/**
 * Menu management
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Menu_Controller extends Admin_Controller {

    /**
     * Get children of specified parent
     *
     * @return Response
     */
    public function action_children()
    {
        // Validate request
        if (!Request::ajax() or Request::method() != 'POST' or !Auth::can('admin_menu') or Request::forged())
        {
            return Response::error(500);
        }

        // Check input
        if ($_POST['id'] != '0' and !ctype_digit(Input::get('id')))
        {
            return Response::error(500);
        }

        // Return tree
        return View::make('admin.menu.tree', array('menu' => Ionic\Tree::build_children('menu', (int) Input::get('id'))));
    }

    /**
     * Create new menu
     *
     * @return Response
     */
    public function action_create()
    {
        // Validate request
        if (!Request::ajax() or Request::method() != 'POST' or !Auth::can('admin_menu') or Request::forged())
        {
            return Response::error(500);
        }

        // Check input
        if ((!Input::get('id') and $_POST['id'] != '0') or !Input::get('title'))
        {
            return Response::json(array('status' => false, 'id'     => 0));
        }

        // Get parent ID
        if (Input::get('id') == 'jstree' or $_POST['id'] == '0')
        {
            $parent = 0;
        }
        elseif (ctype_digit(Input::get('id')))
        {
            $parent = (int) Input::get('id');
        }
        else
        {
            return Response::json(array('status' => false, 'id'     => 0));
        }

        // Create node
        $tree = new Ionic\Tree('menu');
        $id = $tree->create_node($parent, array('title' => HTML::specialchars(Input::get('title'))));

        // Clear cache
        Cache::forget('menu');

        // Error
        if ($id === FALSE)
        {
            return Response::json(array('status' => false, 'id'     => 0));
        }

        // Log action
        $this->log('Dodał menu: '.HTML::specialchars(Input::get('title')));

        // Return success and ID
        return Response::json(array('status' => true, 'id'     => $id));
    }

    /**
     * Delete node
     *
     * @return Response
     */
    public function action_delete()
    {
        // Validate reuqest
        if (!Request::ajax() or Request::method() != 'POST' or !Auth::can('admin_menu') or Request::forged())
        {
            return Response::error(500);
        }

        // Check ID
        if (!Input::get('id') or !ctype_digit(Input::get('id')))
        {
            return Response::json(array('status' => false));
        }

        // Get node
        $id = (int) Input::get('id');
        $node = DB::table('menu')->where('id', '=', $id)->first('*');

        if (!$node)
        {
            return Response::json(array('status' => false));
        }

        // Delete node
        $tree = new Ionic\Tree('menu');
        $result = $tree->delete_node($id);

        // Clear cache
        Cache::forget('menu');

        // Log
        $this->log('Usunął menu: '.$node->title);

        // Return result
        return Response::json(array('status' => $result));
    }

    /**
     * Edit
     *
     * @return Response
     */
    public function action_edit()
    {
        // Validate
        if (!Request::ajax() or Request::method() != 'POST' or !Auth::can('admin_menu') or Request::forged())
        {
            return Response::error(500);
        }

        // Check input
        if (!Input::get('id') or !Input::get('title') or !ctype_digit(Input::get('id')))
        {
            return Response::json(array('status' => false));
        }

        // Get node
        $id = (int) Input::get('id');
        $node = DB::table('menu')->where('id', '=', $id)->first('*');

        if (!$node)
        {
            return Response::json(array('status' => false));
        }

        // Update node
        DB::table('menu')->where('id', '=', $id)->update(array(
            'title' => HTML::specialchars(Input::get('title')),
            'link'  => HTML::specialchars(Input::get('link')),
        ));

        // Log action
        $this->log('Edytował menu: '.$node->title);

        // Clear cache
        Cache::forget('menu');

        // Return success
        return Response::json(array('status' => true));
    }

    /**
     * Index page
     *
     * @return Response
     */
    public function action_index()
    {
        // Permissions
        if (!Auth::can('admin_menu'))
        {
            return Response::error(403);
        }

        // jsTree
        Asset::add('jstree', 'public/js/jquery.jstree.js', 'jquery');

        // Page setup
        $this->page->breadcrumb_append('Menu', 'admin/menu/index');
        $this->page->set_title('Menu');
        $this->view = View::make('admin.menu.index');
    }

    /**
     * Move node
     *
     * @return Response
     */
    public function action_move()
    {
        // Validate
        if (!Request::ajax() or Request::method() != 'POST' or !Auth::can('admin_menu') or Request::forged())
        {
            return Response::error(500);
        }

        // Check ID
        if (!Input::get('id') or !ctype_digit(Input::get('id')))
        {
            return Response::json(array('status' => false));
        }

        // Check parent
        if (!Input::get('parent') and $_POST['parent'] != '0')
        {
            return Response::json(array('status' => false));
        }

        // Check index
        if (!Input::get('i') and $_POST['i'] != '0')
        {
            return Response::json(array('status' => false));
        }

        // Get ID
        $id = (int) Input::get('id');

        // Get parent
        if ($_POST['parent'] == '0')
        {
            $parent = null;
        }
        elseif (ctype_digit(Input::get('parent')))
        {
            $parent = (int) Input::get('parent');
        }
        else
        {
            return Response::json(array('status' => false));
        }

        // Get index
        if ($_POST['i'] == '0')
        {
            $i = 0;
        }
        elseif (ctype_digit(Input::get('i')))
        {
            $i = (int) Input::get('i');
        }
        else
        {
            return Response::json(array('status' => false));
        }

        // Get node
        $node = DB::table('menu')->where('id', '=', $id)->first('*');

        if (!$node)
        {
            return Response::json(array('status' => false));
        }

        // Reorder node
        $tree = new Ionic\Tree('menu');
        $result = $tree->reorder_node($id, $i, $parent);

        // Clear cache
        Cache::forget('menu');

        // Log action
        $this->log('Przeniósł menu: '.$node->title);

        // Return result
        return Response::json(array('status' => $result));
    }

    /**
     * Rename
     *
     * @return Response
     */
    public function action_rename()
    {
        // Validate
        if (!Request::ajax() or Request::method() != 'POST' or !Auth::can('admin_menu') or Request::forged())
        {
            return Response::error(500);
        }

        if (!Input::get('id') or !Input::get('title') or !ctype_digit(Input::get('id')))
        {
            return Response::json(array('status' => false));
        }

        // Get ID
        $id = (int) Input::get('id');

        // Get node
        $node = DB::table('menu')->where('id', '=', $id)->first('*');

        if (!$node)
        {
            return Response::json(array('status' => false));
        }

        // Update title
        DB::table('menu')->where('id', '=', $id)->update(array('title' => HTML::specialchars(Input::get('title'))));

        // Log
        $this->log('Zmienił nazwę menu: '.$node->title);

        // Clear cache and return
        Cache::forget('menu');
        return Response::json(array('status' => true));
    }

}