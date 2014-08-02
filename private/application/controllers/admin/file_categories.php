<?php

/**
 * Categories management
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_File_Categories_Controller extends Admin_Controller {

    /**
     * Get children of specified parent
     *
     * @return Response
     */
    public function action_children()
    {
        // Validate request
        if (!Request::ajax() or Request::method() != 'POST' or !Auth::can('admin_file_categories') or Request::forged())
        {
            return Response::error(500);
        }

        // Check input
        if ($_POST['id'] != '0' and !ctype_digit(Input::get('id')))
        {
            return Response::error(500);
        }

        // Return tree
        return View::make('admin.file_categories.tree', array('menu' => Ionic\Tree::build_children('file_categories', (int) Input::get('id'))));
    }

    /**
     * Create new menu
     *
     * @return Response
     */
    public function action_create()
    {
        // Validate request
        if (!Request::ajax() or Request::method() != 'POST' or !Auth::can('admin_file_categories') or Request::forged())
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
        $tree = new Ionic\Tree('file_categories');
        $id = $tree->create_node($parent, array('title' => HTML::specialchars(Input::get('title')), 'slug'  => ionic_tmp_slug('file_categories')));

        // Error
        if ($id === FALSE)
        {
            return Response::json(array('status' => false, 'id'     => 0));
        }

        // Log action
        $this->log('Dodał kategorię plików: '.HTML::specialchars(Input::get('title')));

        // Update slug
        DB::table('file_categories')->where('id', '=', $id)->update(array('slug' => ionic_find_slug(Input::get('title'), $id, 'file_categories')));

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
        if (!Request::ajax() or Request::method() != 'POST' or !Auth::can('admin_file_categories') or Request::forged())
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
        $node = DB::table('file_categories')->where('id', '=', $id)->first('*');

        if (!$node)
        {
            return Response::json(array('status' => false));
        }

        // Remove comments and storage
        $ids = array();

        foreach (DB::table('files')->join('file_categories', 'file_categories.id', '=', 'files.category_id')
                ->where('left', '>=', $node->left)->where('right', '<=', $node->right)
                ->get(array('files.id', 'files.image', 'files.filelocation')) as $f)
        {
            $ids[] = $f->id;

            if (file_exists(path('storage').'files'.DS.$f->filelocation))
            {
                @unlink(path('storage').'files'.DS.$f->filelocation);
            }

            if ($f->image and is_file(path('public').'upload'.DS.'files'.DS.$f->image))
            {
                @unlink(path('public').'upload'.DS.'files'.DS.$f->image);
                ionic_clear_thumbnails('files', $f->image);
            }
        }

        if (!empty($ids))
        {
            $user_counts = array();
            $prepared_counts = array();

            foreach (DB::table('comments')->where_in('content_id', $ids)->where('content_type', '=', 'file')->get(array('user_id')) as $c)
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

            foreach ($prepared_counts as $c => $uids)
            {
                DB::table('profiles')->where('comments_count', '>=', $c)->where_in('user_id', $uids)->update(array('comments_count' => DB::raw('comments_count - '.$c)));
            }

            DB::table('comments')->where('content_type', '=', 'file')->where_in('content_id', $ids)->delete();
        }

        // Tree manager
        $tree = new Ionic\Tree('file_categories');

        // Get root parent
        $parent = $tree->get_root_parent($node->left);

        // Delete node
        $result = $tree->delete_node($id);

        // Update last file
        if ($parent and $node->last_file_id)
        {
            // Update parent (we need new right first)
            $parent = DB::table('file_categories')->where('id', '=', $parent->id)->first(array('id', 'left', 'right'));

            $file = DB::table('files')->order_by('files.id', 'desc')->join('file_categories', 'file_categories.id', '=', 'files.category_id')
                    ->where('file_categories.left', '>=', $parent->left)
                    ->where('file_categories.right', '<=', $parent->right)
                    ->first('files.id');

            if ($file)
            {
                DB::table('file_categories')->where('id', '=', $parent->id)->update(array('last_file_id' => $file->id));

                foreach (DB::table('file_categories')->where('left', '>', $parent->left)->where('right', '<', $parent->right)
                        ->where('last_file_id', '=', $node->last_file_id)->get(array('id', 'left', 'right')) as $c)
                {
                    $file = DB::table('files')->order_by('files.id', 'desc')->join('file_categories', 'file_categories.id', '=', 'files.category_id')
                            ->where('file_categories.left', '>=', $c->left)
                            ->where('file_categories.right', '<=', $c->right)
                            ->first('files.id');

                    if ($file)
                    {
                        DB::table('file_categories')->where('id', '=', $c->id)->update(array('last_file_id' => $file->id));
                    }
                    else
                    {
                        DB::table('file_categories')->where('id', '=', $c->id)->update(array('last_file_id' => 0));
                    }
                }
            }
            else
            {
                DB::table('file_categories')->where('left', '>=', $parent->left)
                        ->where('right', '<=', $parent->right)
                        ->update(array('last_file_id' => 0));
            }
        }

        // Log
        $this->log('Usunął kategorie plików: '.$node->title);

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
        if (!Request::ajax() or Request::method() != 'POST' or !Auth::can('admin_file_categories') or Request::forged())
        {
            return Response::error(500);
        }

        // Check input
        if (!Input::get('id') or !Input::get('title') or !Input::get('slug') or !ctype_digit(Input::get('id')))
        {
            return Response::json(array('status' => false));
        }

        // Get node
        $id = (int) Input::get('id');
        $node = DB::table('file_categories')->where('id', '=', $id)->first('*');

        if (!$node)
        {
            return Response::json(array('status' => false));
        }

        if ($node->slug == Input::get('slug'))
        {
            // Update node
            DB::table('file_categories')->where('id', '=', $id)->update(array(
                'title'       => HTML::specialchars(Input::get('title')),
                'description' => HTML::specialchars(Input::get('description')),
            ));
        }
        else
        {
            $slug = HTML::specialchars(Str::slug(Input::get('slug')));

            if (DB::table('file_categories')->where('id', '<>', $node->id)->where('slug', '=', $slug)->first('id'))
            {
                return Response::json(array('status' => false));
            }
            else
            {
                DB::table('file_categories')->where('id', '=', $id)->update(array(
                    'title'       => HTML::specialchars(Input::get('title')),
                    'description' => HTML::specialchars(Input::get('description')),
                    'slug'        => $slug
                ));
            }
        }

        // Log action
        $this->log('Edytował kategorie plików: '.$node->title);

        // Return success
        return Response::json(array('status' => true));
    }

    /**
     * Get description
     *
     * @return Response
     */
    public function action_get_desc()
    {
        // Validate reuqest
        if (!Request::ajax() or Request::method() != 'POST' or !Auth::can('admin_file_categories') or Request::forged())
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
        $node = DB::table('file_categories')->where('id', '=', $id)->first(array('description', 'slug'));

        if (!$node)
        {
            return Response::json(array('status' => false));
        }

        return Response::json(array('status'      => true, 'description' => $node->description, 'slug'        => $node->slug));
    }

    /**
     * Index page
     *
     * @return Response
     */
    public function action_index()
    {
        // Permissions
        if (!Auth::can('admin_file_categories'))
        {
            return Response::error(403);
        }

        // jsTree
        Asset::add('jstree', 'public/js/jquery.jstree.js', 'jquery');

        // Page setup
        $this->page->breadcrumb_append('Kategorie plików', 'admin/file_categories/index');
        $this->page->set_title('Kategorie plików');
        $this->view = View::make('admin.file_categories.index');
    }

    /**
     * Move node
     *
     * @return Response
     */
    public function action_move()
    {
        // Validate
        if (!Request::ajax() or Request::method() != 'POST' or !Auth::can('admin_file_categories') or Request::forged())
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
        $node = DB::table('file_categories')->where('id', '=', $id)->first('*');

        if (!$node)
        {
            return Response::json(array('status' => false));
        }

        $tree = new Ionic\Tree('file_categories');

        // Get old parent
        $old_parent = $tree->get_root_parent($node->left);

        // Reorder node
        $result = $tree->reorder_node($id, $i, $parent);

        // Update last file
        if ($result and $node->last_file_id)
        {
            // Update old
            if ($old_parent)
            {
                // Update parent (we need new right first)
                $old_parent = DB::table('file_categories')->where('id', '=', $old_parent->id)->first(array('id', 'left', 'right'));

                $file = DB::table('files')->order_by('files.id', 'desc')->join('file_categories', 'file_categories.id', '=', 'files.category_id')
                        ->where('file_categories.left', '>=', $old_parent->left)
                        ->where('file_categories.right', '<=', $old_parent->right)
                        ->first('files.id');

                if ($file)
                {
                    DB::table('file_categories')->where('id', '=', $old_parent->id)->update(array('last_file_id' => $file->id));

                    foreach (DB::table('file_categories')->where('left', '>', $old_parent->left)->where('right', '<', $old_parent->right)
                            ->where('last_file_id', '=', $node->last_file_id)->get(array('id', 'left', 'right')) as $c)
                    {
                        $file = DB::table('files')->order_by('files.id', 'desc')->join('file_categories', 'file_categories.id', '=', 'files.category_id')
                                ->where('file_categories.left', '>=', $c->left)
                                ->where('file_categories.right', '<=', $c->right)
                                ->first('files.id');

                        if ($file)
                        {
                            DB::table('file_categories')->where('id', '=', $c->id)->update(array('last_file_id' => $file->id));
                        }
                        else
                        {
                            DB::table('file_categories')->where('id', '=', $c->id)->update(array('last_file_id' => 0));
                        }
                    }
                }
                else
                {
                    DB::table('file_categories')->where('left', '>=', $old_parent->left)
                            ->where('right', '<=', $old_parent->right)
                            ->update(array('last_file_id' => 0));
                }
            }

            // Update new
            $node = DB::table('file_categories')->where('id', '=', $id)->first(array('id', 'left', 'right', 'last_file_id', 'title'));

            foreach (DB::table('file_categories')->where('last_file_id', '<>', $node->last_file_id)
                    ->where('left', '<=', $node->left)
                    ->where('right', '>=', $node->right)->get(array('id', 'left', 'right')) as $c)
            {
                $file = DB::table('files')->order_by('files.id', 'desc')->join('file_categories', 'file_categories.id', '=', 'files.category_id')
                        ->where('file_categories.left', '>=', $c->left)
                        ->where('file_categories.right', '<=', $c->right)
                        ->first('files.id');

                if ($file)
                {
                    DB::table('file_categories')->where('id', '=', $c->id)->update(array('last_file_id' => $file->id));
                }
                else
                {
                    DB::table('file_categories')->where('id', '=', $c->id)->update(array('last_file_id' => 0));
                }
            }
        }
        // Log action
        $this->log('Przeniósł kategorię plików: '.$node->title);

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
        if (!Request::ajax() or Request::method() != 'POST' or !Auth::can('admin_file_categories') or Request::forged())
        {
            return Response::error(500);
        }

        if (!Input::get('id') or !Input::get('title') or !ctype_digit(Input::get('id')))
        {
            return Response::json(array('status' => false));
        }

        // Get ID
        $id = (int) Input::get('id');
        $title = Input::get('title');

        // Get node
        $node = DB::table('file_categories')->where('id', '=', $id)->first('*');

        if (!$node)
        {
            return Response::json(array('status' => false));
        }

        // Update title
        if ($node->title != HTML::specialchars($title))
        {
            if ($node->slug == Str::slug($title.'-'.$id))
            {
                DB::table('file_categories')->where('id', '=', $id)->update(array('title' => HTML::specialchars($title)));
            }
            else
            {
                DB::table('file_categories')->where('id', '=', $id)->update(array('title' => HTML::specialchars($title), 'slug'  => ionic_find_slug($title, $id, 'file_categories')));
            }

            // Log
            $this->log('Zmienił nazwę kategorii plików: '.$node->title);
        }

        // Ret
        return Response::json(array('status' => true));
    }

}