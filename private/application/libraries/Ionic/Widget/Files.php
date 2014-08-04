<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;

class Files extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('limit'    => 10, 'template' => 'widgets.files'), $this->options);

        return View::make('admin.widgets.widget_files', array(
                    'options' => $options,
                    'action'  => \URI::current()
                ));
    }

    /**
     * Prepare options field
     *
     * @return string
     */
    public function prepare_options()
    {
        if (\Request::forged() or \Request::method() != 'POST')
        {
            return false;
        }

        $options = array_merge(array('limit'    => 10, 'template' => 'widgets.files'), $this->options);

        $options['limit'] = (int) Input::get('limit', 0);
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.files';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('limit'    => 10, 'template' => 'widgets.files'), $this->options);

        $files = 'files-'.$options['limit'];

        if (\Cache::has($files))
        {
            return \Cache::get($files);
        }
        else
        {
            $files = DB::table('files')->join('file_categories', 'file_categories.id', '=', 'files.category_id')
                    ->take($options['limit'])
                    ->order_by('files.created_at', 'desc')
                    ->get(array(
                'files.id', 'files.title', 'files.created_at', 'files.slug', 'files.image', 'files.comments_count', 'files.downloads',
                'file_categories.slug as category_slug', 'files.description'
                    ));

            $files = (string) View::make($options['template'], array('files' => $files));

            \Cache::put('files-'.$options['limit'], $files);

            return $files;
        }
    }

}