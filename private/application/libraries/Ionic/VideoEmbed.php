<?php
namespace Ionic;

/**
 * Used to parse video links and return video code and thumbnail url
 *
 * Supported websites:
 * - Youtube
 * - Dailymotion
 * - Vimeo
 * - Flickr
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class VideoEmbed {

    /**
     * @var string
     */
    protected $embed = '';

    /**
     * @var int
     */
    protected $height = 315;

    /**
     * @var string
     */
    protected $thumbnail = '';

    /**
     * @var int
     */
    protected $width = 560;

    /**
     * Get embed code
     *
     * @return string
     */
    public function embed()
    {
        return $this->embed;
    }

    /**
     * Fetch video data
     *
     * @param string $url
     */
    public function fetch($url)
    {
        $matches = array();

        if (preg_match('~(http|https)://(?:video\.google\.(?:com|com\.au|co\.uk|de|es|fr|it|nl|pl|ca|cn)/(?:[^"]*?))?(?:(?:www|au|br|ca|es|fr|de|hk|ie|in|il|it|jp|kr|mx|nl|nz|pl|ru|tw|uk)\.)?youtube\.com(?:[^"]*?)?(?:&|&amp;|/|\?|;|\%3F|\%2F)(?:video_id=|v(?:/|=|\%3D|\%2F))([0-9a-z-_]{11})~imu', $url, $matches))
        {
            $this->handle_youtube($matches[2]);
        }
        elseif (preg_match('~http://(?:www\.)?dailymotion\.(?:com|alice\.it)/(?:(?:[^"]*?)?video|swf)/([a-z0-9]{1,18})~imu', $url, $matches))
        {
            $this->handle_dailymotion($matches[1]);
        }
        elseif (preg_match('~http://(?:www\.)?vimeo\.com/([0-9]{1,12})~imu', $url, $matches))
        {
            $this->handle_vimeo($matches[1]);
        }
        elseif (preg_match("~http://(?:www\.)?metacafe\.com/watch/([0-9]{1,8})/([^/]+)/~imu", $url, $matches))
        {
            $this->handle_metacafe($matches[1], $matches[2]);
        }
        elseif (preg_match("~http://(?:www\.)?ekstraklasa.tv/ekstraklasa/([0-9]{1,2}),([0-9]{1,5}),([0-9]{3,9}),.*\.html~imu", $url, $matches))
        {
            $this->handle_ekstraklasa($matches[1], $matches[2], $matches[3]);
        }
        elseif (preg_match('~http://(?:www\.|www2\.)?flickr\.com/photos/([a-z0-9-_]*)/([0-9]{8,12})~imu', $url, $matches))
        {
            $this->handle_flickr($matches[1], $matches[2]);
        }
    }

    /**
     * Dailymotion video support
     *
     * @param string $id
     */
    protected function handle_dailymotion($id)
    {
        $this->embed = '<iframe frameborder="0" width="'.$this->width.'" height="'.$this->height.'" src="http://www.dailymotion.com/embed/video/'.$id.'"></iframe>';
        $this->thumbnail = 'http://www.dailymotion.com/thumbnail/160x120/video/'.$id;
    }

    /**
     * Handle ekstraklasa.tv
     *
     * @param string $id
     * @param string $id2
     * @param string  $id3
     */
    protected function handle_ekstraklasa($id, $id2, $id3)
    {
        $this->embed = '<object type="application/x-shockwave-flash" data="http://bi.gazeta.pl/im/Player.swf" width="'.$this->width.'" height="'.$this->height.'">
<param name="allowFullScreen" value="true">
<param name="allowScriptAccess" value="always">
<param name="wmode" value="opaque">
<param name="flashvars" value="m=http://serwisy.gazeta.pl/getDaneWideo?xx='.$id3.'%26xxd='.$id2.'&f=http://bi.gazeta.pl/im/"></object>';
    }

    /**
     * Handle Flickr videos
     *
     * Requires cURL for both embed and thumbnail
     *
     * @param string $user
     * @param string $id
     */
    protected function handle_flickr($user, $id)
    {
        if (function_exists('curl_init'))
        {
            $curl = curl_init('http://www.flickr.com/services/oembed?maxwidth='.$this->width.'&maxheight='.$this->height.'&format=json&url=http://flickr.com/photos/'.$user.'/'.$id);

            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5); // More than enough
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $data = json_decode(trim(curl_exec($curl), false));

            curl_close($curl);

            if (is_object($data))
            {
                $this->thumbnail = $data->thumbnail_url;
                $this->embed = $data->html;
            }
        }
    }

    /**
     * Handle metacafe
     *
     * @param string $id
     * @param string $slug
     */
    protected function handle_metacafe($id, $slug)
    {
        $this->embed = '<embed flashVars="playerVars=autoPlay=no" src="http://www.metacafe.com/fplayer/'.$id.'/'.$slug.'.swf" width="'.$this->width.'" height="'.$this->height.'" wmode="transparent" allowFullScreen="true" allowScriptAccess="always" name="Metacafe_'.$id.'" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash"></embed>';

        $this->thumbnail = 'http://www.metacafe.com/thumb/'.$id.'.jpg';
    }

    /**
     * Handle Vimeo videos
     *
     * Requires cURL to fetch thumbnails
     *
     * @param string $id
     */
    protected function handle_vimeo($id)
    {
        // Embed is actually pretty simple
        $this->embed = '<iframe src="http://player.vimeo.com/video/'.$id.'" width="'.$this->width.'" height="'.$this->height.'" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';

        // Now we need API for thumbnail
        if (function_exists('curl_init'))
        {
            $curl = curl_init('http://vimeo.com/api/v2/video/'.$id.'.php');

            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5); // More than enough
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $data = unserialize(curl_exec($curl));

            curl_close($curl);

            if (is_array($data))
            {
                $this->thumbnail = $data[0]['thumbnail_medium'];
            }
        }
    }

    /**
     * Handle Youtube video
     *
     * @param string $id
     */
    protected function handle_youtube($id)
    {
        $this->embed = '<iframe width="'.$this->width.'" height="'.$this->height.'" src="http://www.youtube.com/embed/'.$id.'" frameborder="0" allowfullscreen></iframe>';
        $this->thumbnail = 'http://img.youtube.com/vi/'.$id.'/2.jpg';
    }

    /**
     * Set height
     *
     * @param  int               $height
     * @return \Ionic\VideoEmbed
     */
    public function height($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get thumbnail
     *
     * @return string
     */
    public function thumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Set width
     *
     * @param int $width
     * @return \Ionic\VideoEmbed
     */
    public function width($width)
    {
        $this->width = $width;

        return $this;
    }

}