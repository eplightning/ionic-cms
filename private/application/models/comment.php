<?php
namespace Model;

use \DB;

class Comment {

    public static function delete($id)
    {
        if (!is_object($id))
        {
            $id = DB::table('comments')->where('id', '=', (int) $id)->first(array('id', 'user_id', 'content_id', 'content_type', 'content_link'));
            if (!$id)
                return null;
        }

        DB::table('comments')->where('id', '=', $id->id)->delete();

        if ($id->user_id)
        {
            DB::table('profiles')->where('user_id', '=', $id->user_id)->update(array(
                'comments_count' => DB::table('comments')->where('user_id', '=', $id->user_id)->where('is_hidden', '=', 0)->count()
            ));
        }

        if ($id->content_id)
        {
            switch ($id->content_type)
            {
                case 'news':
                    DB::table('news')->where('id', '=', $id->content_id)->update(array(
                        'comments_count' => DB::table('comments')->where('is_hidden', '=', 0)->where('content_id', '=', $id->content_id)->where('content_type', '=', 'news')->count()
                    ));
                    break;

                case 'blog':
                    DB::table('blogs')->where('id', '=', $id->content_id)->update(array(
                        'comments_count' => DB::table('comments')->where('is_hidden', '=', 0)->where('content_id', '=', $id->content_id)->where('content_type', '=', 'blog')->count()
                    ));
                    break;

                case 'file':
                    DB::table('files')->where('id', '=', $id->content_id)->update(array(
                        'comments_count' => DB::table('comments')->where('is_hidden', '=', 0)->where('content_id', '=', $id->content_id)->where('content_type', '=', 'file')->count()
                    ));
                    break;

                case 'video':
                    DB::table('videos')->where('id', '=', $id->content_id)->update(array(
                        'comments_count' => DB::table('comments')->where('is_hidden', '=', 0)->where('content_id', '=', $id->content_id)->where('content_type', '=', 'video')->count()
                    ));
                    break;

                case 'photo_category':
                    DB::table('photo_categories')->where('id', '=', $id->content_id)->update(array(
                        'comments_count' => DB::table('comments')->where('is_hidden', '=', 0)->where('content_id', '=', $id->content_id)->where('content_type', '=', 'photo_category')->count()
                    ));
                    break;

                default:
                    \Event::until('ionic.comment_delete', array($id->content_type, $id->content_id));
            }
        }

        return $id->content_link ? : 'index';
    }

    public static function delete_comments_for($content_id, $content_type)
    {
        $user_counts = array();
        $prepared_counts = array();

        if (is_array($content_id))
        {
            foreach (DB::table('comments')->where_in('content_id', $content_id)->where('content_type', '=', $content_type)->get(array('user_id')) as $c)
            {
                if ($c->user_id != null)
                {
                    if (!isset($user_counts[$c->user_id]))
                        $user_counts[$c->user_id] = 0;

                    $user_counts[$c->user_id]++;
                }
            }
        }
        else
        {
            foreach (DB::table('comments')->where('content_id', '=', $content_id)->where('content_type', '=', $content_type)->get(array('user_id')) as $c)
            {
                if ($c->user_id != null)
                {
                    if (!isset($user_counts[$c->user_id]))
                        $user_counts[$c->user_id] = 0;

                    $user_counts[$c->user_id]++;
                }
            }
        }

        foreach ($user_counts as $idd => $c)
        {
            if (!isset($prepared_counts[$c]))
                $prepared_counts[$c] = array();

            $prepared_counts[$c][] = $idd;
        }

        foreach ($prepared_counts as $c => $ids)
        {
            DB::table('profiles')->where('comments_count', '>=', $c)->where_in('user_id', $ids)->update(array('comments_count' => DB::raw('comments_count - '.$c)));
        }

        if (is_array($content_id))
        {
            DB::table('comments')->where_in('content_id', $content_id)->where('content_type', '=', $content_type)->delete();
        }
        else
        {
            DB::table('comments')->where('content_id', '=', $content_id)->where('content_type', '=', $content_type)->delete();
        }
    }

    public static function get_types()
    {
        $types = array(
            'news'           => 'Newsy',
            'blog'           => 'Blogi',
            'file'           => 'Download',
            'photo_category' => 'Galeria',
            'video'          => 'Video'
        );

        foreach (\Event::fire('ionic.comment_list') as $r)
        {
            if (is_array($r))
            {
                $types = array_merge($types, $r);
            }
        }

        return $types;
    }

    public static function prepare_content($raw_content)
    {
        if (\Config::get('advanced.comment_bbcode', false))
        {
            require_once path('app').'vendor'.DS.'nbbc.php';

            $code = new \BBCode;

            $code->SetDetectURLs(true);
            $code->SetSmileyURL(\URL::base().'/public/img/smileys');
            $code->RemoveRule('wiki');
            $code->RemoveRule('columns');
            $code->RemoveRule('nextcol');
            $code->RemoveRule('img');

            return $code->Parse(ionic_censor($raw_content));
        }

        return nl2br(\HTML::specialchars(ionic_censor($raw_content)));
    }

}