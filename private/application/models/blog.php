<?php
namespace Model;

use \DB;

class Blog {

    public static function prepare_content($raw_content)
    {
        require_once path('app').'vendor'.DS.'nbbc.php';

        $code = new \BBCode;

        $code->SetDetectURLs(true);
        $code->SetSmileyURL(\URL::base().'/public/img/smileys');
        $code->RemoveRule('wiki');
        $code->RemoveRule('columns');
        $code->RemoveRule('nextcol');

        return $code->Parse(ionic_censor($raw_content));
    }

}