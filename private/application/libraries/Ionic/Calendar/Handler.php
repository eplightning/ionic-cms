<?php
namespace Ionic\Calendar;

use Ionic\Page;

/**
 * Calendar API
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
abstract class Handler {

    /**
     * Admin panel handler: Add
     *
     * @param   string      $type
     * @param   string      $uri
     * @param   \Ionic\Page $page
     * @return  mixed
     */
    abstract public function admin_add($type, $uri, Page $page);

    /**
     * Admin panel handler: Edit
     *
     * @param   object      $object
     * @param   string      $uri
     * @param   \Ionic\Page $page
     * @return  mixed
     */
    abstract public function admin_edit($object, $uri, Page $page);

    /**
     * Get events for specified span in format:
     *
     * array( array('day' => DAY_NUM, 'title' => 'TITLE', 'details' => 'DETAILS', 'url' => 'URL/URI', 'image' => 'IMAGE'), ... )
     *
     * Everything except day and title is optional, if image is provided its dimensions need to match specified in parameters
     *
     * @param   object  $object
     * @param   string  $from
     * @param   string  $to
     * @param   int     $image_width
     * @param   int     $image_height
     * @return  array
     */
    abstract public function collect_events($object, $from, $to, $image_width, $image_height);

    /**
     * Get event source types supported by this handler in format:
     *
     * array('HANDLER_NAME/TYPE_NAME' => 'TITLE', ...)
     *
     * @return  array
     */
    abstract public function get_sources();
}
