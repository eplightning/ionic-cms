<?php

/**
 * Files controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Files_Controller extends Base_Controller {

    /**
     * Category view
     *
     * @param string $cat
     */
    public function action_category($cat)
    {
        $cat = DB::table('file_categories')->where('slug', '=', $cat)->first('*');

        if (!$cat)
            return Response::error(404);

        $this->page->set_title('Kategoria plików');
        $this->online('Pliki - '.$cat->title, 'files/category/'.$cat->slug);

        $this->page->breadcrumb_append('Pliki', 'files/index');

        foreach (Ionic\Tree::build_path('file_categories', $cat->left, $cat->right, $cat->depth, array(
            'title',
            'slug')) as $parent)
        {
            $this->page->breadcrumb_append($parent->title, 'files/category/'.$parent->slug);
        }

        $this->page->breadcrumb_append($cat->title, 'files/category/'.$cat->slug);

        $this->view = View::make('files.category', array(
                    'cat'           => $cat,
                    'subcategories' => DB::table('file_categories')->where('left', '>', $cat->left)
                            ->where('right', '<', $cat->right)
                            ->where('depth', '=', ($cat->depth + 1))
                            ->order_by('left', 'asc')->left_join('files', 'files.id', '=', 'file_categories.last_file_id')
                            ->get(array(
                                'file_categories.title',
                                'file_categories.slug',
                                'file_categories.description',
                                'file_categories.last_file_id',
                                'files.title as file_title',
                                'files.slug as file_slug',
                                'files.created_at as file_created_at',
                                'files.image as file_image')),
                    'files'         => DB::table('files')->where('category_id', '=', $cat->id)
                            ->order_by('id', 'desc')
                            ->paginate(20, array(
                                'title',
                                'image',
                                'created_at',
                                'description',
                                'downloads',
                                'comments_count',
                                'slug'))
                ));
    }

    /**
     * Download file
     *
     * @param string $file
     */
    public function action_download($file)
    {
        if (!ctype_digit($file))
            return Response::error(500);

        if (Auth::is_guest() and !Config::get('guests.files', false))
            return Response::error(403);

        $file = DB::table('files')->where('id', '=', (int) $file)->first(array(
            'filelocation',
            'filename',
            'downloads'));

        if (!$file)
            return Response::error(404);

        if (!is_file(path('storage').'files'.DS.$file->filelocation))
            return Response::error(500);

        return Response::download(path('storage').'files'.DS.$file->filelocation, $file->filename);
    }

    /**
     * Index
     */
    public function action_index()
    {
        $this->page->set_title('Pliki');
        $this->page->breadcrumb_append('Pliki', 'files/index');
        $this->online('Pliki', 'files/index');

        $this->view = View::make('files.index', array(
                    'categories' => DB::table('file_categories')->order_by('left', 'asc')
                            ->where('depth', '=', 0)
                            ->left_join('files', 'files.id', '=', 'file_categories.last_file_id')
                            ->get(array(
                                'file_categories.title',
                                'file_categories.slug',
                                'file_categories.description',
                                'file_categories.last_file_id',
                                'files.title as file_title',
                                'files.slug as file_slug',
                                'files.created_at as file_created_at',
                                'files.image as file_image'))
                ));
    }

    /**
     * File view
     *
     * @param type $file
     */
    public function action_show($file)
    {
        $file = DB::table('files')->where('files.slug', '=', $file)
                ->join('users', 'users.id', '=', 'files.user_id')
                ->first(array(
            'files.*',
            'users.display_name',
            'users.slug as user_slug'));

        if (!$file)
            return Response::error(404);

        $cat = DB::table('file_categories')->where('id', '=', $file->category_id)->first(array(
            'title',
            'slug'));

        $this->page->set_title('Plik - '.$file->title);
        $this->page->breadcrumb_append('Pliki', 'files/index');
        $this->page->breadcrumb_append($cat->title, 'files/category/'.$cat->slug);
        $this->page->breadcrumb_append('Plik - '.$file->title, 'files/show/'.$file->slug);
        $this->online('Plik - '.$file->title, 'files/show/'.$file->slug);

        Asset::add('lightbox', 'public/js/jquery.lightbox.min.js', 'jquery');
        Asset::add('lightbox', 'public/css/jquery.lightbox.css');

        $this->view = View::make('files.show', array(
                    'file'     => $file,
                    'comments' => $this->page->make_comments($file->id, 'file'),
                    'can_dl'   => Auth::is_logged() or Config::get('guests.files', false)
                ));
    }

}