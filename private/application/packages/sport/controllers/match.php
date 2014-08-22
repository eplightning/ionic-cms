<?php

/**
 * Match controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Match_Controller extends Base_Controller {

    /**
     * Report
     *
     * @param string $match
     */
    public function action_report($match)
    {
        $match = DB::table('matches')->where('matches.slug', '=', $match)
                ->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                ->join('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id')
                ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
                ->join('seasons', 'seasons.id', '=', 'fixtures.season_id')
                ->first(array(
            'matches.*', 'home.name as home_name', 'away.name as away_name', 'home.image as home_image', 'away.image as away_image',
            'fixtures.name as fixture_name', 'competitions.name as competition_name', 'seasons.year as season_year'
                ));

        if (!$match)
            return Response::error(404);

        if (empty($match->report_data) and !$match->report_slug)
            return Redirect::to('index');

        $this->page->set_title('Raport pomeczowy');
        $this->page->breadcrumb_append('Mecz', 'match/show/'.$match->slug);
        $this->page->breadcrumb_append('Raport pomeczowy', 'match/report/'.$match->slug);
        $this->online('Raport pomeczowy', 'match/report/'.$match->slug);

        $this->view = View::make('match.report', array(
                    'match'       => $match,
                    'report_data' => empty($match->report_data) ? array() : unserialize($match->report_data)
        ));

        if ($match->report_slug)
        {
            $news = DB::table('news')->left_join('users', 'users.id', '=', 'news.user_id')
                                    ->where('news.slug', '=', $match->report_slug)
                                    ->first(array('news.*', 'users.display_name', 'users.slug as user_slug'));

            if ($news)
            {
                if (Config::get('advanced.news_counter', false))
                {
                    DB::table('news')->where('id', '=', $news->id)->update(array('views' => $news->views + 1));
                }

                // Tags
                $tags = array();

                foreach (DB::table('news_tags')->join('tags', 'tags.id', '=', 'news_tags.tag_id')->where('news_id', '=', $news->id)
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
                    $similar = DB::table('news_tags')->distinct()
                            ->where('is_published', '=', 1)
                            ->where('id', '<>', $news->id)
                            ->order_by('id', 'desc')
                            ->take(3)
                            ->where_in('tag_id', array_keys($tags))
                            ->join('news', 'news_id', '=', 'id')
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
                $this->page->set_property('og:description', str_replace('"', '&quot;', strip_tags(html_entity_decode($news->content_intro, ENT_NOQUOTES))));

                $this->view->with(array(
                    'news'      => $news,
                    'tags'      => $tags,
                    'similar'   => $similar,
                    'can_karma' => Model\Karma::can_karma($news->id, 'news'),
                    'comments'  => $this->page->make_comments($news->id, 'news')
                ));
            }
            else
            {
                $this->view->with('news', false);
            }
        }
        else
        {
            $this->view->with('news', false);
        }
    }

    /**
     * Show match
     *
     * @param string $match
     */
    public function action_show($match)
    {
        $match = DB::table('matches')->where('matches.slug', '=', $match)
                ->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                ->join('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id')
                ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
                ->join('seasons', 'seasons.id', '=', 'fixtures.season_id')
                ->first(array(
            'matches.*', 'home.name as home_name', 'away.name as away_name', 'home.image as home_image', 'away.image as away_image',
            'fixtures.name as fixture_name', 'competitions.name as competition_name', 'seasons.year as season_year'
                ));

        if (!$match)
            return Response::error(404);

        $this->page->set_title('Mecz');
        $this->page->breadcrumb_append('Mecz', 'match/show/'.$match->slug);
        $this->online('Mecz', 'match/show/'.$match->slug);

        $this->view = View::make('match.show', array(
                    'match'   => $match,
                    'history' => DB::table('matches')->where('matches.id', '<>', $match->id)
                            ->where(function($q) use ($match) {
                                        $q->where('home_id', '=', $match->home_id);
                                        $q->where('away_id', '=', $match->away_id);
                                        $q->or_where('home_id', '=', $match->away_id);
                                        $q->where('away_id', '=', $match->home_id);
                                    })
                            ->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                            ->join('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id')
                            ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                            ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
                            ->join('seasons', 'seasons.id', '=', 'fixtures.season_id')
                            ->order_by('matches.date', 'desc')
                            ->where('score', '<>', '')
                            ->take(5)
                            ->get(array(
                                'matches.*', 'home.name as home_name', 'away.name as away_name', 'home.image as home_image', 'away.image as away_image',
                                'fixtures.name as fixture_name', 'competitions.name as competition_name', 'seasons.year as season_year'
                            ))
                ));
    }

}
