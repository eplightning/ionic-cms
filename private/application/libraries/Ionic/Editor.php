<?php
namespace Ionic;

/**
 * Editor
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class Editor {

    protected static $was_init = false;

    /**
     * Create editor with id and name $id
     *
     * @param string $id
     */
    public static function create($id, $val = null)
    {
        $html = '<textarea id="'.$id.'" name="'.$id.'">'.$val.'</textarea>';
        $html .= "\n<script type=\"text/javascript\">tinymce.init({\n
        selector: '#".$id."',\n
        theme: 'modern',\n
        language: 'pl',\n
        browser_spellcheck: true,\n
        document_base_url: '".\URL::base()."/',\n
        resize: 'both',\n
        height: 400,\n
        remove_script_host: false,\n
        relative_urls: false,\n
        theme_advanced_font_sizes: \"10px,12px,13px,14px,16px,18px,20px\",\n
        font_size_style_values: \"12px,13px,14px,16px,18px,20px\",\n
        plugins: [
            \"advlist autolink lists link image charmap print hr anchor\",\n
            \"searchreplace wordcount visualblocks visualchars code fullscreen\",\n
            \"insertdatetime media nonbreaking save table contextmenu\",\n
            \"paste textcolor\"\n
        ],\n
        toolbar1: \"undo redo | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent blockquote | link image media\",\n
        toolbar2: \"print code fullscreen | charmap hr subscript superscript | fontselect fontsizeselect | forecolor backcolor\",\n
        image_advtab: true,\n
        image_list: \"".\URL::base().'/admin/dashboard/imagelist'."\",\n
        });</script>";

        return $html;
    }

    /**
     * Add assets
     */
    public static function init()
    {
        if (self::$was_init)
        {
            return;
        }

        self::$was_init = true;

        Asset::add_nominify('tinymce', 'public/js/tinymce/tinymce.min.js');
    }

}