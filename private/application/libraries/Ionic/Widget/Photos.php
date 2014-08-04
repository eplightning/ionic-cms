<?php
namespace Ionic\Widget;

use \View;
use \Ionic\Widget;
use \DB;
use \Input;

class Photos extends Widget {

    /**
     * Display options
     *
     * @return string
     */
    public function display_options()
    {
        $options = array_merge(array(
            'limit'    => 10,
            'type'     => 'photos',
            'template' => 'widgets.photos'), $this->options);

        return View::make('admin.widgets.widget_photos', array(
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

        $options = array_merge(array(
            'limit'    => 10,
            'type'     => 'photos',
            'template' => 'widgets.photos'), $this->options);

        $options['limit'] = (int) Input::get('limit', 0);
        $options['type'] = Input::get('type') == 'categories' ? 'categories' : 'photos';

        // template
        $options['template'] = Input::has('template') ? basename(Input::get('template')) : 'widgets.photos';

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
            'limit'    => 10,
            'type'     => 'photos',
            'template' => 'widgets.photos'), $this->options);

        $photos = 'photos-'.$options['limit'].'-'.$options['type'];

        if (\Cache::has($photos))
        {
            $photos = \Cache::get($photos);
        }
        else
        {
            if ($options['type'] == 'photos')
            {
                $photos = DB::table('photos')->join('photo_categories', 'photo_categories.id', '=', 'photos.category_id')
                        ->take($options['limit'])
                        ->order_by('photos.created_at', 'desc')
                        ->get(array(
                    'photo_categories.title as category_title',
                    'photo_categories.description',
                    'photos.id',
                    'photos.title',
                    'photos.created_at',
                    'photos.image',
                    'photo_categories.slug',
                    'photo_categories.comments_count'
                        ));
            }
            else
            {
                $photos_ids = array();

                foreach (DB::table('photo_categories')
                                ->distinct()
                                ->where('last_photo_id', '<>', 0)
                                ->take($options['limit'])
                                ->order_by('last_photo_id', 'desc')
                                ->get('last_photo_id') as $p)
                {
                    $photos_ids[] = $p->last_photo_id;
                }

                if (empty($photos_ids))
                {
                    $photos = array();
                }
                else
                {
                    $photos = DB::table('photos')->join('photo_categories', 'photo_categories.id', '=', 'photos.category_id')
                            ->take($options['limit'])
                            ->where_in('photos.id', $photos_ids)
                            ->order_by('photos.created_at', 'desc')
                            ->get(array(
                        'photo_categories.title as category_title',
                        'photo_categories.description',
                        'photos.id',
                        'photos.title',
                        'photos.created_at',
                        'photos.image',
                        'photo_categories.slug',
                        'photo_categories.comments_count'
                            ));
                }
            }

            $photos = (string) View::make($options['template'], array(
                        'photos' => $photos));

            \Cache::put('photos-'.$options['limit'].'-'.$options['type'], $photos);
        }

        return $photos;
    }

}