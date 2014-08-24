<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;
use Cache;

class Table extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array(
            'table'          => null,
            'limit'          => 10,
            'sort_order'     => 'asc',
            'force_distinct' => false,
            'template'       => 'widgets.table'), $this->options);

        return View::make('admin.widgets.widget_table', array(
                    'options' => $options,
                    'action'  => \URI::current(),
                    'tables'  => DB::table('tables')->get(array(
                        'id',
                        'title'))
                ));
    }

    /**
     * Prepare options field
     *
     * @return string
     */
    public function prepare_options()
    {
        if (\Request::forged() or \Request::method() != 'POST' or !Input::has('table') or !ctype_digit(Input::get('table')))
        {
            return false;
        }

        $options = array_merge(array(
            'table'          => null,
            'limit'          => 10,
            'sort_order'     => 'asc',
            'force_distinct' => false,
            'template'       => 'widgets.table'), $this->options);

        $c = DB::table('tables')->where('id', '=', (int) Input::get('table'))->first('id');

        if (!$c)
        {
            return false;
        }

        $options['table'] = $c->id;

        // limit
        $options['limit'] = (int) Input::get('limit', 0);

        // sorting
        $options['sort_order'] = Input::get('sort_order', 'desc') == 'desc' ? 'desc' : 'asc';
        $options['force_distinct'] = Input::get('force_distinct', '0') == '1' ? true : false;

        // template
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.table';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array(
            'table'          => null,
            'limit'          => 10,
            'sort_order'     => 'asc',
            'force_distinct' => false,
            'template'       => 'widgets.table'), $this->options);

        if (!$options['table'])
        {
            return;
        }

        if ($options['limit'] < 0)
        {
            return;
        }

        $table = 'table-'.$options['table'].'-'.(int) $options['force_distinct'].'-'.$options['sort_order'].'-'.$options['limit'];

        if (($table = Cache::get($table)) === null)
        {
            $table = \Ionic\TableManager::get($options['table'], 'table_positions.position', $options['sort_order'], $options['limit'], $options['force_distinct']);

            $table = View::make($options['template'], array('table' => $table))->render();

            \Cache::put('table-'.$options['table'].'-'.(int) $options['force_distinct'].'-'.$options['sort_order'].'-'.$options['limit'], $table);
        }

        return $table;
    }

}
