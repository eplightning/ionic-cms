<?php

/**
 * Image management
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Admin_Images_Controller extends Admin_Controller {

    /**
     * Add directory
     *
     * @return Response
     */
    public function action_add_directory()
    {
        if (!Auth::can('admin_images_add'))
            return Response::error(403);

        if (Request::forged() or Request::method() != 'POST' or !Input::has('directory-name'))
            return Response::error(500);

        $current_path = 'upload'.DS.'images';

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            foreach (Session::get('images-current-path') as $sub)
            {
                $current_path .= DS.urldecode($sub);
            }

            if (!is_dir(path('public').$current_path))
            {
                Session::forget('images-current-path');
                $this->notice('Wystąpił nieznany błąd. Ścieżka została zresetowana');
                return Redirect::to('admin/images/index');
            }
        }

        $name = preg_replace('![^\.\_\pL\pN\s]+!u', '', Str::ascii(Input::get('directory-name')));
        $name = preg_replace('/\s+/', '_', $name);

        if (file_exists(path('public').$current_path.DS.$name))
        {
            $this->notice('Taki katalog już istnieje');
            return Redirect::to('admin/images/index');
        }

        $status = mkdir(path('public').$current_path.DS.$name);

        if (!$status)
        {
            $this->notice('Wystąpił nieznany błąd podczas tworzenia katalogu. Sprawdź uprawnienia katalogu.');
            return Redirect::to('admin/images/index');
        }

        $this->notice('Katalog utworzony pomyślnie');
        $this->log('Utworzono katalog obrazków: '.$name);

        return Redirect::to('admin/images/index');
    }

    /**
     * Resize image
     *
     * @return Response
     */
    public function action_crop()
    {
        if (!Auth::can('admin_images_edit'))
            return Response::error(403);

        if (Request::forged() or !Request::ajax() or Request::method() != 'POST' or !Input::has('id'))
            return Response::error(500);

        if (!Input::get('width') or !Input::get('height') or !ctype_digit(Input::get('width')) or !ctype_digit(Input::get('height')))
            return Response::error(500);

        $width = (int) Input::get('width');
        $height = (int) Input::get('height');

        if ($width > 2048 or $height > 2048 or $width < 0 or $height < 0)
        {
            return Response::error(500);
        }

        if (!isset($_POST['x']) or !isset($_POST['y']) or !ctype_digit($_POST['x']) or !ctype_digit($_POST['y']))
        {
            return Response::error(500);
        }

        $x = (int) $_POST['x'];
        $y = (int) $_POST['y'];

        if ($x < 0)
            $x = 0;
        if ($y < 0)
            $y = 0;

        $current_path = 'upload'.DS.'images';

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            foreach (Session::get('images-current-path') as $sub)
            {
                $current_path .= DS.urldecode($sub);
            }

            if (!is_dir(path('public').$current_path))
            {
                Session::forget('images-current-path');
                return Response::json(array('status' => false));
            }
        }

        $id = basename(base64_decode(Input::get('id')));

        if (!is_file(path('public').$current_path.DS.$id))
        {
            return Response::json(array('status' => false));
        }

        try {
            $editor = WideImage::loadFromFile(path('public').$current_path.DS.$id);

            $result = $editor->crop($x, $y, $width, $height);

            $result->saveToFile(path('public').$current_path.DS.$id);
        } catch (Exception $e) {
            return Response::json(array('status' => false));
        }

        $this->log('Wykadrowano obrazek: '.$this->prepare_filename($id));

        return Response::json(array('status' => true));
    }

    /**
     * Remove image
     *
     * @return Response
     */
    public function action_delete()
    {
        if (!Auth::can('admin_images_delete'))
            return Response::error(403);

        if (Request::forged() or !Request::ajax() or Request::method() != 'POST' or !Input::has('id'))
            return Response::error(500);

        $current_path = 'upload'.DS.'images';

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            foreach (Session::get('images-current-path') as $sub)
            {
                $current_path .= DS.urldecode($sub);
            }

            if (!is_dir(path('public').$current_path))
            {
                Session::forget('images-current-path');
                return Response::json(array('status' => false));
            }
        }

        $id = basename(base64_decode(Input::get('id')));

        if (!is_file(path('public').$current_path.DS.$id))
        {
            return Response::json(array('status' => false));
        }

        $status = @unlink(path('public').$current_path.DS.$id);

        if ($status)
        {
            $this->log('Usunięto obrazek: '.$this->prepare_filename($id));

            return Response::json(array('status' => true));
        }
        else
        {
            return Response::json(array('status' => false));
        }
    }

    /**
     * Add directory
     *
     * @param  string   $directory
     * @return Response
     */
    public function action_delete_directory($directory)
    {
        if (!Auth::can('admin_images_delete'))
            return Response::error(403);

        $current_path = 'upload'.DS.'images';

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            foreach (Session::get('images-current-path') as $sub)
            {
                $current_path .= DS.urldecode($sub);
            }

            if (!is_dir(path('public').$current_path))
            {
                Session::forget('images-current-path');
                $this->notice('Wystąpił nieznany błąd. Ścieżka została zresetowana');
                return Redirect::to('admin/images/index');
            }
        }

        $directory = basename(base64_decode(urldecode($directory)));

        if (!is_dir(path('public').$current_path.DS.$directory))
        {
            return Redirect::to('admin/images/index');
        }

        // Confirmation
        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('admin/images/index');
        }

        File::rmdir(path('public').$current_path.DS.$directory);

        $this->notice('Katalog usunięty pomyślnie');
        $this->log('Usunięto katalog: '.$this->prepare_filename($directory));

        return Redirect::to('admin/images/index');
    }

    /**
     * Edit filename
     *
     * @return Response
     */
    public function action_edit_name()
    {
        if (!Auth::can('admin_images_edit'))
            return Response::error(403);

        if (Request::forged() or !Input::has('value') or !Request::ajax() or Request::method() != 'POST' or !Input::has('id') or !starts_with(Input::get('id'), 'image-name-'))
            return Response::error(500);

        $current_path = 'upload'.DS.'images';
        $path = '';

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            foreach (Session::get('images-current-path') as $sub)
            {
                $current_path .= DS.urldecode($sub);
                $path .= $sub.'/';
            }

            if (!is_dir(path('public').$current_path))
            {
                Session::forget('images-current-path');
                return Response::error(500);
            }
        }

        $id = substr(Input::get('id'), 11);

        $name = Input::get('value');
        $name = preg_replace('![^\.\_\pL\pN\s]+!u', '', Str::ascii($name));
        $name = preg_replace('/\s+/', '_', $name);

        $id = basename(base64_decode($id));

        if (!is_file(path('public').$current_path.DS.$id))
        {
            return Response::error(500);
        }

        $name .= '.'.pathinfo($id, PATHINFO_EXTENSION);

        if (File::move(path('public').$current_path.DS.$id, path('public').$current_path.DS.$name))
        {
            DB::table('news')->where('big_image', '=', $path.urlencode($id))->update(array(
                'big_image' => $path.urlencode($name)
            ));

            DB::table('news')->where('small_image', '=', $path.urlencode($id))->update(array(
                'small_image' => $path.urlencode($name)
            ));
        }

        return Response::make(HTML::specialchars($name));
    }

    /**
     * Apply effect to image
     *
     * @return Response
     */
    public function action_effects()
    {
        if (!Auth::can('admin_images_edit'))
            return Response::error(403);

        if (Request::forged() or !Request::ajax() or Request::method() != 'POST' or !Input::has('id'))
            return Response::error(500);

        if (!Input::get('type'))
            return Response::error(500);

        $type = Input::get('type');

        $current_path = 'upload'.DS.'images';

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            foreach (Session::get('images-current-path') as $sub)
            {
                $current_path .= DS.urldecode($sub);
            }

            if (!is_dir(path('public').$current_path))
            {
                Session::forget('images-current-path');
                return Response::json(array('status' => false));
            }
        }

        $id = basename(base64_decode(Input::get('id')));

        if (!is_file(path('public').$current_path.DS.$id))
        {
            return Response::json(array('status' => false));
        }

        try {
            $editor = WideImage::loadFromFile(path('public').$current_path.DS.$id);

            switch ($type)
            {
                case 'negate':
                    $result = $editor->applyFilter(IMG_FILTER_NEGATE);
                    break;

                case 'grayscale':
                    $result = $editor->applyFilter(IMG_FILTER_GRAYSCALE);
                    break;

                case 'blur':
                    $result = $editor->applyFilter(IMG_FILTER_GAUSSIAN_BLUR);
                    break;

                default:
                    $result = $editor->unsharp(300, 3, 2);
            }

            $result->saveToFile(path('public').$current_path.DS.$id);
        } catch (Exception $e) {
            return Response::json(array('status' => false));
        }

        $this->log('Zastosowano efekt na obrazku: '.$this->prepare_filename($id));

        return Response::json(array('status' => true));
    }

    /**
     * Get data on image
     *
     * @return Response
     */
    public function action_get_info()
    {
        if (!Auth::can('admin_images_edit'))
            return Response::error(403);

        if (Request::forged() or !Request::ajax() or Request::method() != 'POST' or !Input::has('id'))
            return Response::error(500);

        $current_path = 'upload'.DS.'images';

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            foreach (Session::get('images-current-path') as $sub)
            {
                $current_path .= DS.urldecode($sub);
            }

            if (!is_dir(path('public').$current_path))
            {
                Session::forget('images-current-path');
                return Response::json(array('status' => false));
            }
        }

        $id = basename(base64_decode(Input::get('id')));

        if (!is_file(path('public').$current_path.DS.$id))
        {
            return Response::json(array('status' => false));
        }

        $info = @getimagesize(path('public').$current_path.DS.$id);

        if (is_array($info) and $info[0] and $info[1])
        {
            return Response::json(array(
                        'status' => true,
                        'width'  => $info[0],
                        'height' => $info[1],
                        'src'    => URL::base().'/public/'.(DS == '\\' ? str_replace('\\', '/', $current_path) : $current_path).'/'.$this->prepare_filename($id)
                    ));
        }
        else
        {
            return Response::json(array('status' => false));
        }
    }

    /**
     * Get filename
     *
     * @return Response
     */
    public function action_get_name()
    {
        if (!Auth::can('admin_images_edit'))
            return Response::error(403);

        if (!Request::ajax() or Request::method() != 'POST' or !Input::has('id') or !starts_with(Input::get('id'), 'image-name-'))
            return Response::error(500);

        $current_path = 'upload'.DS.'images';

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            foreach (Session::get('images-current-path') as $sub)
            {
                $current_path .= DS.urldecode($sub);
            }

            if (!is_dir(path('public').$current_path))
            {
                Session::forget('images-current-path');
                return Response::error(500);
            }
        }

        $id = basename(base64_decode(substr(Input::get('id'), 11)));

        if (!is_file(path('public').$current_path.DS.$id))
        {
            return Response::error(500);
        }

        $id = $this->prepare_filename($id);
        $ext_position = strrpos($id, '.');

        if (!$ext_position)
        {
            return Response::make($id);
        }
        else
        {
            return Response::make(substr($id, 0, $ext_position));
        }
    }

    /**
     * Index
     *
     * @return Response
     */
    public function action_index()
    {
        if (!Auth::can('admin_images'))
            return Response::error(403);

        $current_path = 'upload'.DS.'images';

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            foreach (Session::get('images-current-path') as $sub)
            {
                $current_path .= DS.urldecode($sub);
            }

            if (!is_dir(path('public').$current_path))
            {
                $current_path = 'upload'.DS.'images';
                Session::forget('images-current-path');
            }
        }

        $directories = array();
        $images = array();

        foreach (new \FilesystemIterator(path('public').$current_path, \FilesystemIterator::SKIP_DOTS) as $file)
        {
            if ($file->isDir())
            {
                $directories[urlencode(base64_encode($file->getFilename()))] = $this->prepare_filename($file->getFilename());
            }
            elseif ($file->isFile())
            {
                $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));

                if ($extension == 'jpg' or $extension == 'gif' or $extension == 'png' or $extension == 'jpeg')
                {
                    $images[base64_encode($file->getFilename())] = $this->prepare_filename($file->getFilename());
                }
            }
        }

        $this->page->set_title('Obrazki');
        $this->page->breadcrumb_append('Obrazki', 'admin/images/index');

        if (Auth::can('admin_images_edit'))
            Asset::add('jcrop', 'public/js/jquery.Jcrop.min.js', 'jquery');

        Asset::add('jeditable', 'public/js/jquery.jeditable.min.js', 'jquery');
        Asset::add('lazyload', 'public/js/jquery.lazyload.min.js', 'jquery');
        
        $this->view = View::make('admin.images.index', array(
                    'directories' => $directories,
                    'images'      => $images,
                    'path'        => (DS == '\\' ? str_replace('\\', '/', $current_path) : $current_path),
                    'show_back'   => ($current_path == 'upload'.DS.'images' ? false : true)
                ));
    }

    /**
     * Resize image
     *
     * @return Response
     */
    public function action_resize()
    {
        if (!Auth::can('admin_images_edit'))
            return Response::error(403);

        if (Request::forged() or !Request::ajax() or Request::method() != 'POST' or !Input::has('id'))
            return Response::error(500);

        if (!Input::get('width') or !Input::get('height') or !ctype_digit(Input::get('width')) or !ctype_digit(Input::get('height')))
            return Response::error(500);

        $width = (int) Input::get('width');
        $height = (int) Input::get('height');

        if ($width > 2048 or $height > 2048 or $width < 0 or $height < 0)
        {
            return Response::error(500);
        }

        $current_path = 'upload'.DS.'images';

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            foreach (Session::get('images-current-path') as $sub)
            {
                $current_path .= DS.urldecode($sub);
            }

            if (!is_dir(path('public').$current_path))
            {
                Session::forget('images-current-path');
                return Response::json(array('status' => false));
            }
        }

        $id = basename(base64_decode(Input::get('id')));

        if (!is_file(path('public').$current_path.DS.$id))
        {
            return Response::json(array('status' => false));
        }

        try {
            $editor = WideImage::loadFromFile(path('public').$current_path.DS.$id);

            $result = $editor->resize($width, $height, 'fill');

            $result->saveToFile(path('public').$current_path.DS.$id);
        } catch (Exception $e) {
            return Response::json(array('status' => false));
        }

        $this->log('Zmieniono wymiary obrazka: '.$this->prepare_filename($id));

        return Response::json(array('status' => true));
    }

    /**
     * Rotate image
     *
     * @return Response
     */
    public function action_rotate()
    {
        if (!Auth::can('admin_images_edit'))
            return Response::error(403);

        if (Request::forged() or !Request::ajax() or Request::method() != 'POST' or !Input::has('id'))
            return Response::error(500);

        if (!Input::get('degree') or !ctype_digit(Input::get('degree')))
            return Response::error(500);

        $degree = (int) Input::get('degree');

        if ($degree > 360)
        {
            $degree = 360;
        }
        elseif ($degree < 0)
        {
            $degree = 0;
        }

        $current_path = 'upload'.DS.'images';

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            foreach (Session::get('images-current-path') as $sub)
            {
                $current_path .= DS.urldecode($sub);
            }

            if (!is_dir(path('public').$current_path))
            {
                Session::forget('images-current-path');
                return Response::json(array('status' => false));
            }
        }

        $id = basename(base64_decode(Input::get('id')));

        if (!is_file(path('public').$current_path.DS.$id))
        {
            return Response::json(array('status' => false));
        }

        try {
            $editor = WideImage::loadFromFile(path('public').$current_path.DS.$id);

            $result = $editor->rotate($degree);

            $result->saveToFile(path('public').$current_path.DS.$id);
        } catch (Exception $e) {
            return Response::json(array('status' => false));
        }

        $this->log('Obrócono obrazek: '.$this->prepare_filename($id));

        return Response::json(array('status' => true));
    }

    /**
     * Traverse directory
     *
     * @param  string   $directory
     * @return Response
     */
    public function action_traverse($directory)
    {
        if (!Auth::can('admin_images'))
            return Response::error(403);

        $current_path = 'upload'.DS.'images';
        $path = array();

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            $path = Session::get('images-current-path');

            foreach ($path as $sub)
            {
                $current_path .= DS.urldecode($sub);
            }

            if (!is_dir(path('public').$current_path))
            {
                Session::forget('images-current-path');
                $this->notice('Wystąpił nieznany błąd. Ścieżka została zresetowana');
                return Redirect::to('admin/images/index');
            }
        }

        if ($directory == 'back')
        {
            if (!empty($path))
            {
                array_pop($path);

                if (!empty($path))
                {
                    Session::put('images-current-path', $path);
                }
                else
                {
                    Session::forget('images-current-path');
                }
            }

            return Redirect::to('admin/images/index');
        }

        $directory = basename(base64_decode(urldecode($directory)));

        if (is_dir(path('public').$current_path.DS.$directory))
        {
            $path[] = urlencode($directory);

            Session::put('images-current-path', $path);
        }

        return Redirect::to('admin/images/index');
    }

    /**
     * Replace image
     *
     * @return Response
     */
    public function action_replace_image()
    {
        if (!Auth::can('admin_images_edit'))
            return Response::error(403);

        if (Request::forged() or Request::method() != 'POST' or !Input::has_file('upload-replace') or !Input::has('id'))
        {
            return Response::error(500);
        }

        $current_path = 'upload'.DS.'images';

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            foreach (Session::get('images-current-path') as $sub)
            {
                $current_path .= DS.urldecode($sub);
            }

            if (!is_dir(path('public').$current_path))
            {
                Session::forget('images-current-path');
                return Response::json(array('status' => false));
            }
        }

        $id = basename(base64_decode(Input::get('id')));

        // File validation
        $file = Input::file('upload-replace');

        if (!is_array($file) or empty($file['tmp_name']) or empty($file['name']) or $file['error'] != UPLOAD_ERR_OK or !File::is(array('gif', 'jpg', 'png'), $file['tmp_name'], $file['name']))
        {
            $this->notice('Nieprawidłowo wrzucony obrazek');
            return Redirect::to('admin/images/index');
        }

        // Make sure it's really image
        $info = @getimagesize($file['tmp_name']);

        if (!$info or $info[0] == 0 or $info[1] == 0)
        {
            $this->notice('Nieprawidłowo wrzucony obrazek');
            return Redirect::to('admin/images/index');
        }

        move_uploaded_file($file['tmp_name'], path('public').$current_path.DS.$id);

        $this->notice('Plik podmieniony pomyślnie');
        $this->log('Podmieniono plik: '.$id);

        return Redirect::to('admin/images/index');
    }

    /**
     * Upload image
     *
     * @return Response
     */
    public function action_upload_image()
    {
        if (!Auth::can('admin_images_add'))
            return Response::error(403);

        if (Request::forged() or Request::method() != 'POST' or !Input::has_file('upload-file'))
        {
            return Response::error(500);
        }

        $current_path = 'upload'.DS.'images';

        if (Session::has('images-current-path') and is_array(Session::get('images-current-path')))
        {
            foreach (Session::get('images-current-path') as $sub)
            {
                $current_path .= DS.urldecode($sub);
            }

            if (!is_dir(path('public').$current_path))
            {
                Session::forget('images-current-path');
                $this->notice('Wystąpił nieznany błąd. Ścieżka została zresetowana');
                return Redirect::to('admin/images/index');
            }
        }

        // File validation
        $file = Input::file('upload-file');

        if (!is_array($file) or empty($file['tmp_name']) or empty($file['name']) or $file['error'] != UPLOAD_ERR_OK or !File::is(array('gif', 'jpg', 'png'), $file['tmp_name'], $file['name']))
        {
            $this->notice('Nieprawidłowo wrzucony obrazek');
            return Redirect::to('admin/images/index');
        }

        // Make sure it's really image
        $info = @getimagesize($file['tmp_name']);

        if (!$info or $info[0] == 0 or $info[1] == 0)
        {
            $this->notice('Nieprawidłowo wrzucony obrazek');
            return Redirect::to('admin/images/index');
        }

        $name = preg_replace('![^\.\_\pL\pN\s]+!u', '', Str::ascii($file['name']));
        $name = preg_replace('/\s+/', '_', $name);
        $extension = strtolower(substr(strrchr($name, '.'), 1));

        while (file_exists(path('public').$current_path.DS.$name))
        {
            $name = Str::random(10).'.'.$extension;
        }

        move_uploaded_file($file['tmp_name'], path('public').$current_path.DS.$name);

        $this->notice('Plik dodany pomyślnie');
        $this->log('Dodano plik: '.$name);

        return Redirect::to('admin/images/index');
    }

    /**
     * Prepare filename to be displayed for user
     *
     * @param string $raw
     */
    protected function prepare_filename($raw)
    {
        return HTML::specialchars(ionic_filesystem_name_to_utf8($raw));
    }

}