<?php
namespace Ionic;

use \DB;

/**
 * Nested tree
 *
 * @author  Wrexdot <wrexdot@gmail.com>
 * @package Ionic
 */
class Tree {

    protected $table = '';

    public function __construct($table)
    {
        $this->table = $table;
    }

    public static function build_children($table, $id)
    {
        if (!$id)
        {
            $data = DB::table($table)->where('depth', '=', 0)->order_by('left', 'asc')->get('*');

            $tree = array();

            foreach ($data as $l)
            {
                $tree[] = array('data'         => $l, 'has_children' => (($l->right - $l->left) > 1));
            }

            return $tree;
        }
        else
        {
            $parent = DB::table($table)->where('id', '=', (int) $id)->first('*');

            if (!$parent)
                return array();

            if (($parent->right - $parent->left) <= 1)
                return array();

            $data = DB::table($table)->where('left', '>', $parent->left)->where('right', '<', $parent->right)->where('depth', '=', ($parent->depth + 1))->order_by('left', 'asc')->get('*');

            $tree = array();

            foreach ($data as $l)
            {
                $tree[] = array('data'         => $l, 'has_children' => (($l->right - $l->left) > 1));
            }

            return $tree;
        }
    }

    private static function _build_tree(&$data, &$tree, $depth)
    {
        $last_id = null;

        while ($e = current($data))
        {
            if ($e->depth == $depth)
            {
                $tree[$e->id] = array('data'     => $e, 'children' => array());
                $last_id = $e->id;
                next($data);
            }
            elseif ($e->depth > $depth)
            {
                static::_build_tree($data, $tree[$last_id]['children'], $e->depth);
            }
            else
            {
                return;
            }
        }
    }

    public static function build_path($table, $left, $right, $depth, array $select = array('id'))
    {
        return DB::table($table)->order_by('left', 'asc')
                        ->where('left', '<', $left)
                        ->where('right', '>=', $right)->where('depth', '<', $depth)->get($select);
    }

    public static function build_select($table, $title_column, $sep = ' / ')
    {
        $path = array();

        $select = array();
        $prev_depth = 0;

        foreach (DB::table($table)->order_by('left', 'asc')->get(array('depth', $title_column, 'id')) as $elem)
        {
            if ($prev_depth > $elem->depth)
            {
                for (; $prev_depth > $elem->depth; $prev_depth--)
                {
                    if (isset($path[$prev_depth]))
                        unset($path[$prev_depth]);
                }
            }

            $path[$elem->depth] = $elem->{$title_column};

            $select[$elem->id] = implode($sep, $path);

            $prev_depth = $elem->depth;
        }

        return $select;
    }

    public static function build_tree($table, $start_depth = 0, $max_depth = 2)
    {
        if ($max_depth)
        {
            $data = DB::table($table)->where('depth', '>=', $start_depth)->where('depth', '<=', $max_depth)->order_by('left', 'asc')->get('*');
        }
        else
        {
            $data = DB::table($table)->where('depth', '>=', $start_depth)->order_by('left', 'asc')->get('*');
        }

        $tree = array();
        $last_id = null;

        while ($e = current($data))
        {
            if ($e->depth == $start_depth)
            {
                $tree[$e->id] = array('data'     => $e, 'children' => array());
                $last_id = $e->id;
                next($data);
            }
            elseif ($e->depth == ($start_depth + 1) && $last_id)
            {
                static::_build_tree($data, $tree[$last_id]['children'], $e->depth);
            }
        }

        return $tree;
    }

    public function create_node($parent = 0, array $data = array())
    {
        // No parent
        if (!$parent)
        {
            // Get left
            $left = (int) DB::table($this->table)->order_by('right', 'desc')->only('right');
            $left += 1;

            $data['left'] = $left;
            $data['right'] = ($left + 1);
            $data['depth'] = 0;

            return DB::table($this->table)->insert_get_id($data);
        }

        $data2 = DB::table($this->table)->where('id', '=', $parent)->first(array('right', 'depth'));

        if (!$data2)
        {
            return FALSE;
        }

        $left = (int) $data2->right;

        $data['left'] = $left;
        $data['right'] = ($left + 1);
        $data['depth'] = (int) $data2->depth;
        $data['depth']++;

        // Update LEFT/RIGHT value of other nodes
        DB::table($this->table)->where('right', '>=', $left)->update(array('right' => DB::raw('`right` + 2')));
        DB::table($this->table)->where('left', '>=', $left)->update(array('left' => DB::raw('`left` + 2')));

        // Insert ours
        return DB::table($this->table)->insert_get_id($data);
    }

    public function delete_node($id)
    {
        // Get LEFT/RIGHT
        $node = DB::table($this->table)->where('id', '=', $id)->first(array('left', 'right'));

        if (!$node)
            return FALSE;

        // Remove children + this node
        DB::table($this->table)->where('left', '>=', $node->left)->where('right', '<=', $node->right)->delete();

        // Width
        $width = ($node->right - $node->left);
        $width++;

        // Move nodes on the right
        DB::table($this->table)->where('left', '>', $node->left)->update(array('left' => DB::raw('`left` - '.$width)));
        DB::table($this->table)->where('right', '>', $node->left)->update(array('right' => DB::raw('`right` - '.$width)));

        return TRUE;
    }

    public function get_children_ids($left, $right)
    {
        $ids = array();

        foreach (DB::table($this->table)->where('left', '>', $left)->where('right', '<', $right)->get('id') as $id)
        {
            $ids[] = $id->id;
        }

        return $ids;
    }

    public function get_children($left, $right)
    {
        return DB::table($this->table)->where('left', '>', $left)->where('right', '<', $right)->get('*');
    }

    public function get_root_parent($left)
    {
        $t = DB::table($this->table)->where('left', '<', $left)->order_by(DB::raw('(`right` - `left`)'), 'desc')->where('right', '>', $left)->first('*');

        return $t;
    }

    public function get_parent($left)
    {
        $t = DB::table($this->table)->where('left', '<', $left)->order_by(DB::raw('(`right` - `left`)'), 'asc')->where('right', '>', $left)->first('*');

        return $t;
    }

    public function move_node($id, $new_left = 1)
    {
        // Get LEFT/RIGHT
        $node = DB::table($this->table)->where('id', '=', $id)->first(array('left', 'right', 'depth'));

        if (!$node)
            return FALSE;

        // ????
        if ($node->left == $new_left)
            return FALSE;
        if ($node->left < $new_left && $node->right > $new_left)
            return FALSE;

        // Old left
        $old_left = $node->left;

        // Node width
        $width = (($node->right - $node->left) + 1);

        // Reserve space
        DB::table($this->table)->where('right', '>=', $new_left)->update(array('right' => DB::raw('`right` + '.$width)));
        DB::table($this->table)->where('left', '>=', $new_left)->update(array('left' => DB::raw('`left` + '.$width)));

        // Get parent
        $parent = $this->get_parent($new_left);

        // ???
        if (!$parent)
        {
            $parent = -1;
        }
        else
        {
            $parent = $parent->depth;
        }

        // Node is on the right
        if ($node->left > $new_left)
        {
            $old_left += $width;
            $difference = '- '.($old_left - $new_left);
        }
        else
        {
            $difference = '+ '.($new_left - $old_left);
        }

        // Depth difference
        $depth_diff = (($parent - $node->depth) + 1);

        if ($depth_diff > 0)
        {
            $depth_diff = '+ '.$depth_diff;
        }
        elseif ($depth_diff < 0)
        {
            $depth_diff = '- '.abs($depth_diff);
        }

        // Actually move nodes now
        if ($depth_diff)
        {
            DB::table($this->table)->where('left', '>=', $old_left)->where('right', '<', ($old_left + $width))->update(array(
                'left'  => DB::raw('`left` '.$difference),
                'right' => DB::raw('`right` '.$difference),
                'depth' => DB::raw('`depth` '.$depth_diff)
            ));
        }
        else
        {
            DB::table($this->table)->where('left', '>=', $old_left)->where('right', '<', ($old_left + $width))->update(array(
                'left'  => DB::raw('`left` '.$difference),
                'right' => DB::raw('`right` '.$difference)
            ));
        }

        // Remove blank space
        DB::table($this->table)->where('left', '>=', $old_left)->update(array('left' => DB::raw('`left` - '.$width)));
        DB::table($this->table)->where('right', '>=', $old_left)->update(array('right' => DB::raw('`right` - '.$width)));

        return TRUE;
    }

    public function reorder_node($id, $target_i, $parent = null)
    {
        // Get immediate children
        if (!$parent)
        {
            $children = DB::table($this->table)->where('depth', '=', 0)->order_by('left', 'asc')->get('*');
        }
        else
        {
            $parent = DB::table($this->table)->where('id', '=', $parent)->order_by('left', 'asc')->first('*');

            if (!$parent)
            {
                return FALSE;
            }

            $children = DB::table($this->table)->order_by('left', 'asc')->where('left', '>', $parent->left)->where('right', '<', $parent->right)->where('depth', '=', ($parent->depth + 1))->get('*');
        }

        // Try to get target left
        $left = null;

        if ($target_i == 0)
        {
            $c = current($children);

            if ($c && $c->id == $id)
            {
                return FALSE;
            }

            if ($parent)
            {
                $left = ($parent->left + 1);
            }
            else
            {
                $left = 1;
            }
        }
        else
        {
            $i = 0;
            $target_i--;

            foreach ($children as $c)
            {
                $left = ($c->right + 1);

                if ($target_i == $i)
                {
                    if ($c->id == $id)
                        return FALSE;

                    break;
                }

                $i++;
            }
        }

        // This means parent is empty?
        if ($left === null)
        {
            if ($parent)
            {
                $left = $parent->right;
            }
            else
            {
                $left = 1;
            }
        }

        // Move
        return $this->move_node($id, $left);
    }

}