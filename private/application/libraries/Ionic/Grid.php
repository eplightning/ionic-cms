<?php
namespace Ionic;

use \Closure;
use \DB;
use \Request;
use \Response;
use \Redirect;
use \Input;

/**
 * Admin grid generator
 *
 * @author  EpicLegion
 * @package Ionic
 */
class Grid {

    // Action type enum
    const ACTION_STANDARD = 0;
    const ACTION_AJAX     = 1;
    const ACTION_BOTH     = 2;

    /**
     * @var array
     */
    protected $actions = array();

    /**
     * @var array
     */
    protected $buttons = array();

    /**
     * @var bool
     */
    protected $checkbox = false;

    /**
     * @var array
     */
    protected $columns = array();

    /**
     * @var array
     */
    protected $filters = array();

    /**
     * @var mixed
     */
    protected $groupby = '';

    /**
     * @var array
     */
    protected $help = array();

    /**
     * @var array
     */
    protected $inline_edit = array();

    /**
     * @var array
     */
    protected $multi_actions = array();

    /**
     * @var bool
     */
    protected $pagination = true;

    /**
     * @var int
     */
    protected $perpage = 20;

    /**
     * @var bool
     */
    protected $prefer_ajax = true;

    /**
     * @var array
     */
    protected $preview = array();

    /**
     * @var array
     */
    protected $related = array();

    /**
     * @var array
     */
    protected $selects = array();

    /**
     * @var string
     */
    protected $table = null;

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $url = '';

    /**
     * @var array
     */
    protected $where = array();

    /**
     * Constructor
     *
     * @param string $table
     * @param string $title
     * @param string $url
     * @param array $selects
     * @param number $perpage
     * @param bool $prefer_ajax
     */
    public function __construct($table, $title, $url, array $selects = array(), $perpage = 20, $prefer_ajax = null)
    {
        $this->table = $table;
        $this->selects = array_merge($selects, array($table.'.id'));
        $this->perpage = $perpage;
        $this->title = $title;
        $this->url = $url;
        $this->prefer_ajax = is_null($prefer_ajax) ? \Config::get('advanced.admin_prefer_ajax', true) : $prefer_ajax;
    }

    /**
     * Add action
     *
     * @param string $title
     * @param string $link
     * @param string $class
     * @param string $type
     */
    public function add_action($title, $link, $class = '', $type = 0)
    {
        if ($type == static::ACTION_AJAX or ($type == static::ACTION_BOTH and $this->prefer_ajax))
        {
            $class = $class ? $class.' grid-action-ajax' : 'grid-action-ajax';
        }

        $this->actions[] = array('title' => $title, 'link'  => \URL::to($link), 'class' => $class, 'type' => $type);
    }

    /**
     * Add button
     *
     * @param string $title
     * @param string $link
     * @param string $class
     */
    public function add_button($title, $link, $class = '')
    {
        $this->buttons[] = array('title' => $title, 'link'  => \URL::to($link), 'class' => $class);
    }

    /**
     * Add grid column
     *
     * @param string $name
     * @param string $title
     * @param mixed  $value
     * @param string $select_col
     * @param string $sort_column
     */
    public function add_column($name, $title, $value = null, $select_col = null, $sort_column = null)
    {
        $this->columns[$name] = array('name' => $name, 'title' => $title, 'value' => $value, 'sort_column' => $sort_column);

        if (is_string($select_col))
        {
            $this->add_selects(array($select_col));
        }
    }

    /**
     * Add autocomplete filter
     *
     * @param string  $name
     * @param string  $title
     * @param Closure $autocompleter
     * @param string  $column
     */
    public function add_filter_autocomplete($name, $title, Closure $autocompleter, $column = null)
    {
        $this->filters[$name] = array(
            'type'    => 'autocomplete',
            'name'    => $name,
            'title'   => $title,
            'default' => null,
            'options' => $autocompleter,
            'column'  => ($column ? : $name)
        );
    }

    /**
     * Add date filter
     *
     * @param string $name
     * @param string $title
     * @param string $column
     */
    public function add_filter_date($name, $title, $column = null)
    {
        $this->filters[$name] = array(
            'type'    => 'date',
            'name'    => $name,
            'title'   => $title,
            'default' => null,
            'options' => null,
            'column'  => ($column ? : $name)
        );
    }

    /**
     * Add items per page filter
     *
     * @param string $options
     * @param string $name
     * @param string $title
     * @param number $default
     */
    public function add_filter_perpage(array $options, $name = 'perpage', $title = 'Elementów na stronę', $default = 20)
    {
        $this->filters[$name] = array(
            'type'    => 'perpage',
            'name'    => $name,
            'title'   => $title,
            'default' => $default,
            'options' => $options
        );
    }

    /**
     * Add search filter
     *
     * @param string $name
     * @param string $title
     * @param string $column
     */
    public function add_filter_search($name, $title, $column = null)
    {
        $this->filters[$name] = array(
            'type'    => 'search',
            'name'    => $name,
            'title'   => $title,
            'default' => null,
            'options' => null,
            'column'  => ($column ? : $name)
        );
    }

    /**
     * Add selection filter
     *
     * @param string $name
     * @param string $title
     * @param array  $options
     * @param mixed  $default
     * @param mixed  $column
     */
    public function add_filter_select($name, $title, array $options, $default = null, $column = null)
    {
        $this->filters[$name] = array(
            'type'    => 'select',
            'name'    => $name,
            'title'   => $title,
            'default' => $default,
            'options' => $options,
            'column'  => ($column ? : $name)
        );
    }

    /**
     * Add group by command
     *
     * @param string $group
     */
    public function add_groupby($group)
    {
        $this->groupby = $group;
    }

    /**
     * Add tip
     *
     * @param string $id
     * @param string $help_string
     */
    public function add_help($id, $help_string)
    {
        $this->help[$id] = $help_string;
    }

    /**
     * Add inline edit
     *
     * @param string  $column
     * @param Closure $action
     */
    public function add_inline_edit($column, Closure $action)
    {
        $this->inline_edit[$column] = $action;
    }

    /**
     * Add multi action
     *
     * @param string  $name
     * @param string  $title
     * @param Closure $action
     */
    public function add_multi_action($name, $title, Closure $action)
    {
        $this->multi_actions[$name] = array('name' => $name, 'title' => $title, 'action' => $action);
    }

    /**
     * Add preview
     *
     * @param string $name
     * @param string $title
     * @param string $url
     */
    public function add_preview($name, $title, $url)
    {
        $this->preview[$name] = array('title' => $title, 'url' => $url);
    }

    /**
     * Add related table
     *
     * JOIN $table ON $c1 $c2 $c3
     *
     * @param string $table
     * @param string $c1
     * @param string $c2
     * @param string $c3
     * @param bool   $is_left
     * @param array  $selection
     */
    public function add_related($table, $c1, $c2, $c3, array $selection = array(), $is_left = false)
    {
        $this->related[$table] = array($table, $c1, $c2, $c3, $is_left);
        $this->selects = array_merge($this->selects, $selection);
    }

    /**
     * Add selects
     *
     * @param array $selection
     */
    public function add_selects(array $selection = array())
    {
        $this->selects = array_merge($this->selects, $selection);
    }

    /**
     * Force WHERE
     *
     * @param string $key
     * @param string $op
     * @param string $value
     */
    public function add_where($key, $op, $value)
    {
        $this->where[] = array($key, $op, $value);
    }

    /**
     * Enable multi selection
     *
     * @param bool $enable
     */
    public function enable_checkboxes($enable = true)
    {
        $this->checkbox = (bool) $enable;
    }

    /**
     * Enable pagination
     *
     * @param bool $enable
     */
    public function enable_pagination($enable = true)
    {
        $this->pagination = (bool) $enable;
    }

    /**
     * Autocompleter
     *
     * @param string $name
     */
    public function handle_autocomplete($name)
    {
        if (!isset($this->filters[$name]) or $this->filters[$name]['type'] != 'autocomplete' or !Input::get('term') or !Request::ajax())
        {
            return Response::error(500);
        }

        $completer = $this->filters[$name]['options'];

        return Response::json($completer(Input::get('term')));
    }

    /**
     * Handle this thing
     *
     * @param string $name
     */
    public function handle_multiaction($name)
    {
        if (!Request::ajax() or !Input::get('ids') or Request::forged() or !isset($this->multi_actions[$name]))
        {
            return Response::error(500);
        }

        $ids = array();

        foreach (explode(',', Input::get('ids')) as $i)
        {
            if (!ctype_digit($i))
            {
                return Response::error(500);
            }

            $ids[(int) $i] = (int) $i;
        }

        if (!empty($ids))
        {
            $ma = $this->multi_actions[$name]['action'];

            $ma($ids);
        }

        return Response::make('');
    }

    /**
     * Handle filter
     *
     * @param string $name
     * @param string $value
     */
    public function handle_filter($name, $value)
    {
        $is_ajax = Request::ajax();

        if ($name == '_clear_all')
        {
            \Session::put($this->table.'_filters', array());
            \Session::put($this->table.'_cfilters', array());
            return $is_ajax ? Response::json(array('status' => true)) : Redirect::to($this->url.'/index');
        }

        if ($name == '_customdel')
        {
            if (\Session::has($this->table.'_cfilters'))
            {
                $applied = \Session::get($this->table.'_cfilters');

                if (isset($applied[$value]))
                {
                    unset($applied[$value]);

                    \Session::put($this->table.'_cfilters', $applied);
                }
            }

            return $is_ajax ? Response::json(array('status' => true)) : Redirect::to($this->url.'/index');
        }

        if (!isset($this->filters[$name]))
        {
            return Response::error(500);
        }

        if (\Session::has($this->table.'_filters'))
        {
            $applied = \Session::get($this->table.'_filters');
        }
        else
        {
            $applied = array();
        }

        $filter = $this->filters[$name];

        if ($filter['type'] == 'perpage' and $value and ctype_digit($value))
        {
            $value = (int) $value;

            if (in_array($value, $filter['options']))
            {
                $applied[$filter['name']] = $value;
            }

            \Session::put($this->table.'_filters', $applied);
        }
        elseif ($filter['type'] == 'select' and $value != null)
        {
            if (isset($filter['options'][$value]))
            {
                $applied[$filter['name']] = $value;
            }

            \Session::put($this->table.'_filters', $applied);
        }
        elseif ($filter['type'] == 'autocomplete' and Request::method() == 'POST' and !Request::forged())
        {
            if (!Input::get('query'))
            {
                $applied[$filter['name']] = '';
            }
            else
            {
                $applied[$filter['name']] = Input::get('query');
            }

            \Session::put($this->table.'_filters', $applied);
        }
        elseif ($filter['type'] == 'search' and Request::method() == 'POST' and !Request::forged())
        {
            if (!Input::get('query'))
            {
                $applied[$filter['name']]['type'] = 'exact';
                $applied[$filter['name']]['query'] = '';
            }
            else
            {
                $applied[$filter['name']]['query'] = str_replace('%', '', Input::get('query'));

                if (!Input::get('how') or !in_array(Input::get('how'), array('exact', 'endswith', 'startswith', 'contains')))
                {
                    $applied[$filter['name']]['type'] = 'exact';
                }
                else
                {
                    $applied[$filter['name']]['type'] = Input::get('how');
                }
            }

            \Session::put($this->table.'_filters', $applied);
        }
        elseif ($filter['type'] == 'date' and Request::method() == 'POST' and !Request::forged())
        {
            $applied[$filter['name']] = array('from' => '', 'to'   => '');

            $from = 0;
            $to = 0;

            if (Input::has('from') and strtotime(Input::get('from')))
            {
                $from = strtotime(Input::get('from'));
            }

            if (Input::has('to') and strtotime(Input::get('to')))
            {
                $to = strtotime(Input::get('to'));
            }

            if ($from and $to)
            {
                if ($from > $to)
                {
                    $applied[$filter['name']]['from'] = date('Y-m-d', $to);
                    $applied[$filter['name']]['to'] = date('Y-m-d', $from);
                }
                else
                {
                    $applied[$filter['name']]['from'] = date('Y-m-d', $from);
                    $applied[$filter['name']]['to'] = date('Y-m-d', $to);
                }
            }
            elseif ($from)
            {
                $applied[$filter['name']]['from'] = date('Y-m-d', $from);
            }
            elseif ($to)
            {
                $applied[$filter['name']]['to'] = date('Y-m-d', $to);
            }

            \Session::put($this->table.'_filters', $applied);
        }

        return $is_ajax ? Response::json(array('status' => true)) : Redirect::to($this->url.'/index');
    }

    /**
     * Handle inline edit
     */
    public function handle_inline()
    {
        if (Request::forged() or !Input::has('id') or !Request::ajax() or Request::method() != 'POST')
            return Response::error(500);

        $id = Input::get('id');

        if (!preg_match('/^inline-edit-([^\-]+)-([0-9]+)$/', $id, $matches))
        {
            return Response::error(500);
        }

        if (!isset($this->inline_edit[$matches[1]]) or !ctype_digit($matches[2]))
            return Response::error(500);

        $id = DB::table($this->table)->where('id', '=', (int) $matches[2])->first('*');

        if (!$id)
            return Response::error(500);

        $closure = $this->inline_edit[$matches[1]];

        return $closure($id, Input::get('value', ''));
    }

    /**
     * Handle index action
     *
     * @param string $id
     */
    public function handle_index($id)
    {
        // Build damn query
        $query = DB::table($this->table);

        // Joins
        foreach ($this->related as $r)
        {
            if ($r[4])
            {
                $query->left_join($r[0], $r[1], $r[2], $r[3]);
            }
            else
            {
                $query->join($r[0], $r[1], $r[2], $r[3]);
            }
        }

        if ($this->groupby)
            $query->group_by($this->groupby);

        // Limit
        $limit = $this->perpage;

        // Custom filters
        if (\Session::has($this->table.'_cfilters'))
        {
            $custom_filters = \Session::get($this->table.'_cfilters');

            foreach ($custom_filters as $kfilter => $vfilter)
            {
                $query->where($kfilter, '=', $vfilter['val']);
            }
        }
        else
        {
            $custom_filters = array();
        }

        // Apply filters
        $filter_values = array();

        foreach ($this->filters as $f)
        {
            if (\Session::has($this->table.'_filters'))
            {
                $applied = \Session::get($this->table.'_filters');
            }
            else
            {
                $applied = array();
            }

            $filter_values[$f['name']] = '';

            if ($f['type'] == 'autocomplete' and !empty($applied[$f['name']]))
            {
                $filter_values[$f['name']] = $applied[$f['name']];

                if (is_array($f['column']))
                {
                    $query->where(function($query) use ($f, $applied) {
                                foreach ($f['column'] as $c)
                                {
                                    $query->or_where($c, '=', $applied[$f['name']]);
                                }
                            });
                }
                else
                {
                    $query->where($f['column'], '=', $applied[$f['name']]);
                }
            }
            elseif ($f['type'] == 'perpage')
            {
                if (!empty($applied[$f['name']]))
                {
                    $limit = $applied[$f['name']];
                }
                elseif ($f['default'])
                {
                    $limit = $f['default'];
                }
            }
            elseif ($f['type'] == 'search')
            {
                $filter_values[$f['name']] = array('query' => '', 'type'  => 'exact');

                if (!empty($applied[$f['name']]) and !empty($applied[$f['name']]['query']))
                {
                    $operator = 'LIKE';
                    $q = $applied[$f['name']]['query'];

                    $filter_values[$f['name']]['query'] = $q;
                    $filter_values[$f['name']]['type'] = $applied[$f['name']]['type'];

                    if ($applied[$f['name']]['type'] == 'exact')
                    {
                        $operator = '=';
                    }
                    elseif ($applied[$f['name']]['type'] == 'contains')
                    {
                        $q = '%'.$q.'%';
                    }
                    elseif ($applied[$f['name']]['type'] == 'startswith')
                    {
                        $q = $q.'%';
                    }
                    else
                    {
                        $q = '%'.$q;
                    }

                    if (is_array($f['column']))
                    {
                        $query->where(function($query) use ($f, $applied) {
                                    foreach ($f['column'] as $c)
                                    {
                                        $query->or_where($c, $operator, $q);
                                    }
                                });
                    }
                    else
                    {
                        $query->where($f['column'], $operator, $q);
                    }
                }
            }
            elseif ($f['type'] == 'select')
            {
                if (isset($applied[$f['name']]))
                {
                    $filter_values[$f['name']] = $applied[$f['name']];

                    if ($applied[$f['name']] != '_all_')
                        $query->where($f['column'], '=', $applied[$f['name']]);
                }
                elseif ($f['default'])
                {
                    $filter_values[$f['name']] = $f['default'];

                    if ($f['default'] != '_all_')
                        $query->where($f['column'], '=', $f['default']);
                }
            }
            elseif ($f['type'] == 'date')
            {
                $filter_values[$f['name']] = array('from' => '', 'to'   => '');

                if (!empty($applied[$f['name']]))
                {
                    $filter_values[$f['name']]['from'] = !empty($applied[$f['name']]['from']) ? $applied[$f['name']]['from'] : '';
                    $filter_values[$f['name']]['to'] = !empty($applied[$f['name']]['to']) ? $applied[$f['name']]['to'] : '';

                    if (!empty($applied[$f['name']]['from']))
                    {
                        $query->where($f['column'], '>=', $applied[$f['name']]['from']);
                    }

                    if (!empty($applied[$f['name']]['to']))
                    {
                        $query->where($f['column'], '<=', $applied[$f['name']]['to']);
                    }
                }
            }
        }

        // WHEREEEEEEEEE
        foreach ($this->where as $v)
        {
            $query->where($v[0], $v[1], $v[2]);
        }

        // Count records
        $records = (int) $query->count();
        $pages = ceil($records / $limit);
        if (!$pages)
            $pages = 1;

        // Ordering
        $order_column = '';

        if (\Session::has($this->table.'_ordering'))
        {
            $ordering = \Session::get($this->table.'_ordering');

            $order_column = $ordering['column'];
            $query->order_by($ordering['item'], $ordering['order']);
        }
        else
        {
            $order_column = 'id';
            $query->order_by($this->table.'.id', 'desc');
        }

        // Limit
        $query->take($limit);

        // Pagination
        if (Request::ajax())
        {
            if (ctype_digit($id))
            {
                $id = (int) $id;
            }
            else
            {
                $id = 1;
            }

            if ($id <= 0)
                $id = 1;
            if ($id > $pages)
                $id = $pages;

            $query->skip(($id - 1) * $limit);
        }

        // Data
        $data = array();

        foreach ($query->get($this->selects) as $row)
        {
            $d = array();
            $d['id'] = $row->id;

            foreach ($this->columns as $c)
            {
                if (is_string($c['value']))
                {
                    if ($c['name'] == 'created_at' || $c['name'] == 'date')
                    {
                        $d[$c['name']] = ionic_date($row->{$c['value']});
                    }
                    else
                    {
                        $d[$c['name']] = $row->{$c['value']};
                    }
                }
                elseif ($c['value'] instanceof \Closure)
                {
                    $d[$c['name']] = call_user_func($c['value'], $row);
                }

                if (isset($this->inline_edit[$c['name']]))
                {
                    $d[$c['name']] = '<span id="inline-edit-'.$c['name'].'-'.$d['id'].'" class="inline-edit">'.$d[$c['name']].'</span>';
                }
                elseif (isset($this->preview[$c['name']]))
                {
                    $d[$c['name']] = '<a class="preview-'.$c['name'].'" data-param="'.$d['id'].'" style="cursor: pointer" title="'.$this->preview[$c['name']]['title'].'">'.$d[$c['name']].'</a>';
                }
            }

            $data[] = $d;
        }

        // Ajax or standard
        if (Request::ajax())
        {
            $view = View::make('admin.grid_ajax');

            $colspan = count($this->columns);

            if (count($this->actions))
                $colspan++;

            if ($this->checkbox)
                $colspan++;

            $view->with(array(
                'draw_checkboxes' => $this->checkbox,
                'columns' => $this->columns,
                'colspan' => $colspan,
                'actions' => $this->actions,
                'data' => $data,
                'grid_url' => $this->url
            ));

            return \Response::json(array(
                'view' => $view->render(),
                'records' => $records,
                'page' => $id,
                'pages' => $pages
            ));
        }
        else
        {
            $view = View::make('admin.grid');

            $colspan = count($this->columns);

            $action_width = count($this->actions);

            if ($action_width)
                $colspan++;

            if ($this->checkbox)
                $colspan++;

            if (\Cookie::get('ionic-admin-skin') == 'admin_flat.css')
            {
                $action_width *= 48;
                $action_width++;
            }
            else
            {
                $action_width *= 39;
            }

            $view->with(array(
                'table' => $this->table,
                'columns' => $this->columns,
                'data' => $data,
                'colspan' => $colspan,
                'actions' => $this->actions,
                'action_width' => $action_width,
                'buttons' => $this->buttons,
                'draw_checkboxes' => $this->checkbox,
                'draw_pagination' => $this->pagination,
                'filters' => $this->filters,
                'multi_actions' => $this->multi_actions,
                'total_items' => $records,
                'total_pages' => $pages,
                'order_column' => $order_column,
                'perpage' => $limit,
                'filter_values' => $filter_values,
                'custom_filters' => $custom_filters,
                'grid_title' => $this->title,
                'grid_url' => $this->url,
                'inline_edit' => !empty($this->inline_edit),
                'prefer_ajax' => $this->prefer_ajax,
                'previews' => $this->preview,
                'help' => $this->help
            ));

            if (!empty($this->inline_edit))
            {
                Asset::add('jeditable', 'public/js/jquery.jeditable.min.js', 'jquery');
                $view->with('inline_edit', true);
            }
            else
            {
                $view->with('inline_edit', false);
            }

            return $view;
        }
    }

    /**
     * Handle sort item
     *
     * @param string $item
     */
    public function handle_sort($item)
    {
        if (\Session::has($this->table.'_ordering'))
        {
            $ordering = \Session::get($this->table.'_ordering');

            if ($ordering['column'] == $item)
            {
                $ordering['order'] = ($ordering['order'] == 'desc' ? 'asc' : 'desc');

                \Session::put($this->table.'_ordering', $ordering);
            }
            else
            {
                $ordering['order'] = 'desc';

                if (isset($this->columns[$item]))
                {
                    $c = $this->columns[$item];

                    if ($c['sort_column'])
                    {
                        $ordering['item'] = $c['sort_column'];
                        $ordering['column'] = $c['name'];
                    }
                    else
                    {
                        $ordering['item'] = $this->table.'.id';
                        $ordering['column'] = 'id';
                    }
                }

                \Session::put($this->table.'_ordering', $ordering);
            }
        }
        else
        {
            $ordering = array('order'  => 'asc', 'item'   => '', 'column' => '');

            if (isset($this->columns[$item]))
            {
                $c = $this->columns[$item];

                if ($c['sort_column'])
                {
                    $ordering['item'] = $c['sort_column'];
                    $ordering['column'] = $c['name'];
                }
                else
                {
                    $ordering['item'] = $this->table.'.id';
                    $ordering['column'] = 'id';
                }
            }

            \Session::put($this->table.'_ordering', $ordering);
        }

        return Redirect::to($this->url.'/index');
    }

    /**
     * Set standard filter
     *
     * @param string $name
     * @param mixed  $value
     */
    public function set_filter($name, $value)
    {
        if (!isset($this->filters[$name]))
        {
            return false;
        }

        if (\Session::has($this->table.'_filters'))
        {
            $applied = \Session::get($this->table.'_filters');
        }
        else
        {
            $applied = array();
        }

        $applied[$name] = $value;

        \Session::put($this->table.'_filters', $applied);

        return true;
    }

    /**
     * Set manual filter
     *
     * @param  string $filter
     * @param  mixed  $value
     */
    public function set_manual_filter($filter, $value, $type = null)
    {
        if ($type == null)
            $type = $filter;

        if (\Session::has($this->table.'_cfilters'))
        {
            $applied = \Session::get($this->table.'_cfilters');
        }
        else
        {
            $applied = array();
        }

        $applied[$filter] = array('val'  => $value, 'type' => $type);

        \Session::put($this->table.'_cfilters', $applied);
    }

}