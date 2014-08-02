<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;

class Videos extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array('limit'    => 10, 'template' => 'widgets.videos'), $this->options);

        return View::make('admin.widgets.widget_videos', array(
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

        $options = array_merge(array('limit'    => 10, 'template' => 'widgets.videos'), $this->options);

        $options['limit'] = (int) Input::get('limit', 0);

        // template
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.videos';

        return $options;
    }

    /**
     * Show widget
     *
     * @param array $options
     */
    public function show()
    {
        $options = array_merge(array('limit'    => 10, 'template' => 'widgets.videos'), $this->options);

        $videos = 'videos-'.$options['limit'];

        if (\Cache::has($videos))
        {
            $videos = \Cache::get($videos);
        }
        else
        {
            $videos = DB::table('videos')->join('video_categories', 'video_categories.id', '=', 'videos.category_id')
                    ->take($options['limit'])
                    ->order_by('videos.created_at', 'desc')
                    ->get(array(
                'videos.id', 'videos.title', 'videos.created_at', 'videos.slug', 'videos.thumbnail', 'videos.comments_count',
                'video_categories.slug as category_slug', 'videos.description'
                    ));

            $videos = (string) View::make($options['template'], array('videos' => $videos));

            \Cache::put('videos-'.$options['limit'], $videos);
        }

        return $videos;
    }

}