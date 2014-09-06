<?php

/**
 * News controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class News_Controller extends Base_Controller {

    /**
     * News archive
     *
     * @return Response
     */
    public function action_archive()
    {
        $news = DB::table('news')->left_join('users', 'users.id', '=', 'news.user_id')
                                 ->order_by('news.created_at', 'desc')
                                 ->where('news.is_published', '=', 1)
                                 ->or_where('news.publish_at', '<=', date('Y-m-d H:i:s'))
                                 ->where('news.publish_at', '<>', '0000-00-00 00:00:00')
                                 ->paginate(20, array('news.id', 'news.created_at', 'news.title', 'news.comments_count', 'news.slug', 'news.external_url',
                                                      'news.big_image', 'news.small_image', 'users.display_name', 'users.slug as user_slug'));

        $grouped_news = array();

        foreach ($news->results as $res)
        {
            $date = date('Y-m-d', strtotime($res->created_at));

            if (empty($grouped_news[$date]))
                $grouped_news[$date] = array();

            $grouped_news[$date][] = $res;
        }

        krsort($grouped_news);

        $this->page->set_title('Archiwum newsów');
        $this->online('Archiwum newsów', 'news/archive');
        $this->page->breadcrumb_append('Archiwum newsów', 'news/archive');

        $this->view = View::make('news.archive', array('grouped_news' => $grouped_news, 'news' => $news));
    }

    /**
     * Atom
     *
     * @return Response
     */
    public function action_atom()
    {
        $rss = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="'.Config::get('application.language', 'pl').'">'."\n";

        $rss .= "\t<title>".sprintf(Config::get('meta.title'), 'Atom')."</title>\n";

        $rss .= "\t<id>".URL::base()."/</id>\n";
        $rss .= "\t<link href=\"".URL::to('news/atom')."\" rel=\"self\" />\n";
        $rss .= "\t<link href=\"".URL::base()."\" rel=\"alternate\" type=\"text/html\" />\n";
        $rss .= "\t<link href=\"".URL::to('news/rss')."\" rel=\"alternate\" type=\"application/rss+xml\" />\n";

        $rss .= "\t<generator version=\"1.3\">IonicCMS</generator>\n";
        $rss .= "\t<subtitle>".Config::get('meta.description')."</subtitle>\n";

        $updated = null;
        $items = '';

        foreach (Model\News::get(10) as $n)
        {
            $date = date('c', strtotime($n->created_at));
            $link = URL::to(ionic_make_link('news', $n->slug, $n->external_url));

            if (!$updated)
                $updated = $date;

            $intro = trim(html_entity_decode($n->content_intro, ENT_COMPAT, 'UTF-8'));

            if (empty($intro))
            {
                $intro = trim(html_entity_decode(Str::limit(strip_tags($n->content), 200), ENT_COMPAT, 'UTF-8'));
            }

            if ($n->small_image)
            {
                $intro = '<img src="'.URL::base().'/public/upload/images/'.$n->small_image.'" width="200" height="150" alt="" /> '.$intro;
            }

            $items .= "\t<entry>\n";

            $items .= "\t\t<title>".$n->title."</title>\n";
            $items .= "\t\t<updated>".$date."</updated>\n";
            $items .= "\t\t<link href=\"".$link."\" rel=\"alternate\" />\n";
            $items .= "\t\t<id>".URL::to(ionic_make_link('news', $n->slug, $n->external_url))."</id>\n";
            $items .= "\t\t<author>\n\t\t\t<name>".$n->display_name."</name>\n\t\t\t<uri>".URL::to(ionic_make_link('user', $n->user_slug))."</uri>\n\t\t</author>\n";
            $items .= "\t\t<summary type=\"html\">".HTML::specialchars($intro)."</summary>\n";

            $items .= "\t</entry>\n";
        }

        $rss .= "\t<updated>".$updated."</updated>\n".$items."</feed>";

        return Response::make($rss, 200, array(
            'Content-type' => 'application/atom+xml; charset=UTF-8'
        ));
    }

    /**
     * News in different format
     *
     * @param  string   $id
     * @param  string   $type
     * @return Response
     */
    public function action_format($id, $type)
    {
        if (!ctype_digit($id) or !in_array($type, array('pdf', 'printable')))
            return Response::error(404);

        $news = DB::table('news')->left_join('users', 'users.id', '=', 'news.user_id')
                ->where('news.id', '=', $id)
                ->first(array('news.*', 'users.display_name', 'users.slug as user_slug'));

        if (!$news)
        {
            return Response::error(404);
        }

        if ($news->is_published == 0 and ($news->publish_at == '0000-00-00 00:00:00' or (strtotime($news->publish_at) > time())))
        {
            return Response::error(404);
        }

        if ($type == 'printable')
        {
            return Response::make(View::make('news.printable', array('news' => $news)));
        }
        else if ($type == 'pdf')
        {
            // Create document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true);

            // PDF information
            $pdf->setCreator(PDF_CREATOR);
            $pdf->setAuthor($news->display_name);
            $pdf->setTitle($news->title);
            $pdf->setSubject($news->title);
            $pdf->setKeywords('news');

            // Default header info
            $pdf->setHeaderData('', '', $news->title, sprintf(Config::get('meta.title'), 'News'));

            $pdf->setHeaderFont(array('dejavusans', '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            // Margins
            $pdf->setMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->setHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->setFooterMargin(PDF_MARGIN_FOOTER);

            // Auto page break
            $pdf->setAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            // Image scale
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // Init doc
            $pdf->aliasNbPages();
            $pdf->addPage();

            // Set font
            $pdf->setFont('dejavusans', '', 12);

            // Write content
            $pdf->writeHTML($news->content, TRUE);

            // Write small footer
            $pdf->setFont('dejavusans', '', 10);
            $pdf->write(1, "\n\nAutor: ".($news->display_name), '', 0, 'R');

            return Response::make($pdf->output('news.pdf', 'S'), 200, array(
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="news.pdf"',
                'Cache-Control'       => 'private, max-age=0, must-revalidate',
                'Pragma'              => 'public'
            ));
        }
    }

    /**
     * Index page
     */
    public function action_index()
    {
        // Preload news model
        Autoloader::load('Model\News');

        // Setup page display
        $this->online('Strona główna', 'news/index');
        $this->layout = View::make('layouts.index');

        // Cache
        if (($news = Cache::get('news-index')))
        {
            $this->main_news = $news['main'];

            $this->view = View::make('news.index', array(
                'main_news' => $news['main'],
                'news'      => $news['news'],
                'archive'   => $news['archive'],
                'tags'      => $news['tags']
            ));

            return;
        }

        // Tags
        $tags = new Model\NewsTagAccess;

        // Retrieve news with "main_news" tag
        $exclude = array();

        if (Config::get('limits.main_news', 0))
        {
            $this->main_news = Model\News::get_with_tag(1, Config::get('limits.main_news', 0));

            // Make sure they won't get duplicated
            foreach ($this->main_news as $n)
            {
                $exclude[] = $n->id;
                $tags->news_ids[] = $n->id;
            }
        }

        // Retrieve standard news
        $news = array();
        $archive = array();
        $news_limit = (int) Config::get('limits.news', 5);
        $headlines_limit = (int) Config::get('limits.headlines', 5);
        $i = 0;

        foreach (Model\News::get($news_limit + $headlines_limit, $exclude) as $n)
        {
            if ($i < $news_limit)
            {
                $news[] = $n;
            }
            else
            {
                $archive[] = $n;
            }

            $tags->news_ids[] = $n->id;

            $i++;
        }

        Cache::put('news-index', array('main' => $this->main_news, 'news' => $news, 'archive' => $archive, 'tags' => $tags), 6);

        // View
        $this->view = View::make('news.index', array(
            'main_news' => $this->main_news,
            'news'      => $news,
            'archive'   => $archive,
            'tags'      => $tags
        ));
    }

    /**
     * RSS
     *
     * @return Response
     */
    public function action_rss()
    {
        $rss = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n\t<channel>\n";

        $rss .= "\t\t<atom:link href=\"".URL::to('news/rss')."\" rel=\"self\" type=\"application/rss+xml\" />\n";

        $rss .= "\t\t<link>".URL::to('news/rss')."</link>\n";
        $rss .= "\t\t<title>".sprintf(Config::get('meta.title'), 'RSS')."</title>\n";
        $rss .= "\t\t<generator>IonicCMS</generator>\n";
        $rss .= "\t\t<description>".Config::get('meta.description')."</description>\n";
        $rss .= "\t\t<language>".Config::get('application.language', 'pl')."</language>\n";

        foreach (Model\News::get(10) as $n)
        {
            $intro = trim(html_entity_decode($n->content_intro, ENT_COMPAT, 'UTF-8'));

            if (empty($intro))
            {
                $intro = trim(html_entity_decode(Str::limit(strip_tags($n->content), 200), ENT_COMPAT, 'UTF-8'));
            }

            if ($n->small_image)
            {
                $intro = '<img src="'.URL::base().'/public/upload/images/'.$n->small_image.'" width="200" height="150" alt="" /> '.$intro;
            }

            $rss .= "\t\t<item>\n";

            $rss .= "\t\t\t<title>".$n->title."</title>\n";
            $rss .= "\t\t\t<pubDate>".date('D, d M Y H:i:s O', strtotime($n->created_at))."</pubDate>\n";
            $rss .= "\t\t\t<link>".URL::to(ionic_make_link('news', $n->slug, $n->external_url))."</link>\n";
            $rss .= "\t\t\t<guid>".URL::to(ionic_make_link('news', $n->slug, $n->external_url))."</guid>\n";
            $rss .= "\t\t\t<description>".HTML::specialchars($intro)."</description>\n";

            $rss .= "\t\t</item>\n";
        }

        $rss .= "\t</channel>\n</rss>";

        return Response::make($rss, 200, array(
            'Content-type' => 'application/rss+xml; charset=UTF-8'
        ));
    }

    /**
     * Display news
     *
     * @param  string   $slug
     * @return Response
     */
    public function action_show($slug)
    {
        // Find news
        $news = DB::table('news')->left_join('users', 'users.id', '=', 'news.user_id')
                ->where('news.slug', '=', $slug)
                ->first(array('news.*', 'users.display_name', 'users.slug as user_slug'));

        if (!$news)
        {
            return Response::error(404);
        }

        // Prevent unpublished news from being displayed
        if ($news->is_published == 0 and ($news->publish_at == '0000-00-00 00:00:00' or (strtotime($news->publish_at) > time())))
        {
            return Response::error(404);
        }

        if ($news->is_published == 0)
        {
            DB::table('news')->where('id', '=', $news->id)->update(array('publish_at'   => '0000-00-00 00:00:00', 'is_published' => 1, 'views'        => $news->views + 1));
        }
        else if (Config::get('advanced.news_counter', false))
        {
            DB::table('news')->where('id', '=', $news->id)->update(array('views' => $news->views + 1));
        }

        if ($news->external_url)
        {
            return Redirect::to($news->external_url);
        }

        // Tags
        $tags = array();

        foreach (DB::table('news_tags')->join('tags', 'tags.id', '=', 'news_tags.tag_id')
                                       ->where('news_id', '=', $news->id)
                                       ->get(array('tags.title', 'tags.id', 'tags.slug')) as $t)
        {
            $tags[$t->id] = $t;
        }

        // Similar news
        if (empty($tags))
        {
            $similar = DB::table('news')->where('is_published', '=', 1)
                                        ->where('id', '<>', $news->id)
                                        ->order_by('id', 'desc')
                                        ->take(3)
                                        ->get(array('title', 'slug', 'created_at', 'comments_count', 'external_url'));
        }
        else
        {
            $similar = DB::table('news_tags')->join('news', 'news_id', '=', 'id')
                                             ->where('is_published', '=', 1)
                                             ->where('news_id', '<>', $news->id)
                                             ->where_in('tag_id', array_keys($tags))
                                             ->order_by('news_id', 'desc')
                                             ->take(3)
                                             ->group_by('news_id')
                                             ->get(array('title', 'slug', 'created_at', 'comments_count', 'external_url'));
        }

        // Open graph
        if (Config::get('advanced.og_bigimage', false) and $news->big_image)
        {
            $this->page->set_property('og:image', URL::base().'/public/upload/images/'.$news->big_image);
        }
        elseif ($news->small_image)
        {
            $this->page->set_property('og:image', URL::base().'/public/upload/images/'.$news->small_image);
        }

        $this->page->set_property('og:type', 'article');
        $this->page->set_property('og:description', HTML::specialchars(strip_tags(html_entity_decode($news->content_intro, ENT_QUOTES, 'UTF-8'))));

        // Setup page display
        $this->online($news->title, 'news/show/'.$news->slug);
        $this->page->set_title($news->title);
        $this->page->breadcrumb_append($news->title, 'news/show/'.$news->slug);

        if (($lay = Config::get('homepage.news_show_layout', 'layouts.index')) != 'layouts.main')
            $this->layout = View::make($lay);

        // View
        $this->view = View::make('news.show', array(
            'news'      => $news,
            'tags'      => $tags,
            'similar'   => $similar,
            'can_karma' => Model\Karma::can_karma($news->id, 'news'),
            'comments'  => $this->page->make_comments($news->id, 'news')
        ));
    }

    /**
     * Tagged news
     *
     * @param  string   $slug
     * @return Response
     */
    public function action_tag($slug)
    {
        $tag = DB::table('tags')->where('slug', '=', $slug)->first(array('id', 'title', 'slug'));

        if (!$tag)
            return Response::error(404);

        $news = DB::table('news_tags')->left_join('news', 'news.id', '=', 'news_tags.news_id')->left_join('users', 'users.id', '=', 'news.user_id')
                ->order_by('news.created_at', 'desc')
                ->where(function($q) {
                            $q->where('news.is_published', '=', 1);
                            $q->or_where('news.publish_at', '<=', date('Y-m-d H:i:s'));
                            $q->where('news.publish_at', '<>', '0000-00-00 00:00:00');
                        })
                ->where('news_tags.tag_id', '=', $tag->id)
                ->paginate(20, array('news.id', 'news.created_at', 'news.title', 'news.comments_count', 'news.slug', 'news.external_url', 'news.content_intro', 'news.big_image', 'news.small_image',
            'users.display_name', 'users.slug as user_slug'));

        $grouped_news = array();

        foreach ($news->results as $res)
        {
            $date = strtotime(date('d.m.Y', strtotime($res->created_at)));

            if (empty($grouped_news[$date]))
                $grouped_news[$date] = array();

            $grouped_news[$date][] = $res;
        }

        krsort($grouped_news);

        $this->page->set_title('Otagowane newsy');
        $this->online('Otagowane newsy', 'news/tag/'.$tag->slug);
        $this->page->breadcrumb_append('Otagowane newsy', 'news/tag/'.$tag->slug);

        $this->view = View::make('news.tag', array('grouped_news' => $grouped_news, 'tag'          => $tag, 'news'         => $news));
    }

}
