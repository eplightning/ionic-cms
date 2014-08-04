<?php

/**
 * Thumbnail generation
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Thumbnail_Controller extends Controller {

    /**
     * @var array
     */
    protected $allowed_types = array(
        'files', 'photos'
    );

    /**
     * Create thumbnail
     *
     * @param  string   $type
     * @param  string   filename
     * @param  string   $size
     * @return Response
     */
    public function action_index($type, $filename, $size)
    {
        if (!in_array($type, $this->allowed_types))
        {
            $allowed = \Event::until('ionic.thumbnail_allowed', array($type));

            if (!$allowed)
                return Response::error(404);
        }

        $sizes = array();

        if (!preg_match('/^([0-9]+)x([0-9]+)$/', $size, $sizes))
        {
            return Response::error(404);
        }

        $sizes[1] = (int) $sizes[1];
        $sizes[2] = (int) $sizes[2];

        if (($sizes[1] > 1024 or $sizes[2] > 1024) or ($sizes[1] == 0 and $sizes[2] == 0))
        {
            return Response::error(500);
        }

        $filename = basename($filename);

        if (!is_file(path('public').'upload'.DS.$type.DS.$filename))
        {
            return Response::error(404);
        }

        if (is_file(path('public').'upload'.DS.$type.DS.'thumbnail'.DS.$filename.'_'.$size.'.png'))
        {
            $image = WideImage::loadFromFile(path('public').'upload'.DS.$type.DS.'thumbnail'.DS.$filename.'_'.$size.'.png');
        }
        else
        {
            $image = WideImage::loadFromFile(path('public').'upload'.DS.$type.DS.$filename);
            $image = $image->resize($sizes[1] ? $sizes[1] : null, $sizes[2] ? $sizes[2] : null, 'fill');

            $image->saveToFile(path('public').'upload'.DS.$type.DS.'thumbnail'.DS.$filename.'_'.$size.'.png');
        }

        header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + Config::get('advanced.thumbnail_expires', 86400)));

        $image->output('png');
        exit;
    }

}