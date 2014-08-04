<?php

/**
 * Gallery controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Gallery_Controller extends Base_Controller {

    /**
     * Category view
     *
     * @param string $cat
     */
    public function action_category($cat)
    {
        $cat = DB::table('photo_categories')->where('slug', '=', $cat)->first('*');

        if (!$cat)
            return Response::error(404);

        $this->page->set_title('Galeria');
        $this->online('Galeria - '.$cat->title, 'gallery/category/'.$cat->slug);

        $this->page->breadcrumb_append('Galeria', 'gallery/index');

        foreach (Ionic\Tree::build_path('photo_categories', $cat->left, $cat->right, $cat->depth, array(
            'title',
            'slug')) as $parent)
        {
            $this->page->breadcrumb_append($parent->title, 'gallery/category/'.$parent->slug);
        }

        $this->page->breadcrumb_append($cat->title, 'gallery/category/'.$cat->slug);

        Asset::add('lightbox', 'public/js/jquery.lightbox.min.js', 'jquery');
        Asset::add('lightbox', 'public/css/jquery.lightbox.css');

        $this->view = View::make('gallery.category', array(
                    'cat'           => $cat,
                    'subcategories' => DB::table('photo_categories')->where('left', '>', $cat->left)
                            ->where('right', '<', $cat->right)
                            ->where('depth', '=', ($cat->depth + 1))
                            ->left_join('photos', 'photos.id', '=', 'photo_categories.last_photo_id')
                            ->order_by('left', 'asc')->get(array(
                        'photo_categories.title',
                        'photo_categories.slug',
                        'photo_categories.description',
                        'photo_categories.comments_count',
                        'photos.image')),
                    'photos'        => DB::table('photos')->where('category_id', '=', $cat->id)
                            ->order_by('id', 'desc')
                            ->get(array(
                                'title',
                                'image')),
                    'comments'      => $this->page->make_comments($cat->id, 'photo_category'),
                ));
    }

    /**
     * Index
     */
    public function action_index()
    {
        $this->page->set_title('Galeria');
        $this->page->breadcrumb_append('Galeria', 'gallery/index');
        $this->online('Galeria', 'gallery/index');

        $this->view = View::make('gallery.index', array(
                    'categories' => DB::table('photo_categories')->order_by('left', 'asc')
                            ->where('depth', '=', 0)
                            ->left_join('photos', 'photos.id', '=', 'photo_categories.last_photo_id')
                            ->get(array(
                                'photo_categories.title',
                                'photo_categories.slug',
                                'photo_categories.description',
                                'photo_categories.comments_count',
                                'photos.image'))
                ));
    }

}