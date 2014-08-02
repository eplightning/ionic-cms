<?php
namespace Ionic;

/**
 * Extended core twig extension
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class TwigExtension extends \Twig_Extension_Core {

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters()
    {
        return array_merge(parent::getFilters(), array(
            'date'           => new \Twig_Filter_Function('ionic_date'),
            'relativedate'   => new \Twig_Filter_Function('ionic_date_rel'),
            'url'            => new \Twig_Filter_Function('url'),
            'nl2br_noescape' => new \Twig_Filter_Function('nl2br'),
            'limit'          => new \Twig_Filter_Function('Str::limit'),
            'md5'            => new \Twig_Filter_Function('md5'),
            'addslashes'     => new \Twig_Filter_Function('addslashes')
                ));
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array_merge(parent::getFunctions(), array(
            'base'       => new \Twig_Function_Function('URL::base'),
            'form_token' => new \Twig_Function_Function('Form::token'),
            'can'        => new \Twig_Function_Function('Auth::can'),
            'editor'     => new \Twig_Function_Function('\Ionic\Editor::create'),
            'widget'     => new \Twig_Function_Function('\Ionic\Widget::factory_show'),
            'make'       => new \Twig_Function_Function('ionic_make_link'),
            'thumb'      => new \Twig_Function_Function('ionic_thumb')
                ));
    }

}