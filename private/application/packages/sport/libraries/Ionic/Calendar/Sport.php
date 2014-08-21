<?php
namespace Ionic\Calendar;

use Ionic\Page;
use DB;
use Request;
use View;
use Input;
use Validator;
use Redirect;
use HTML;

/**
 * Calendar API implementation: Sport module
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class Sport extends Handler {

    /**
     * Admin panel handler: Add
     *
     * @param   string      $type
     * @param   string      $uri
     * @param   \Ionic\Page $page
     * @return  mixed
     */
    public function admin_add($type, $uri, Page $page)
    {
        switch ($type)
        {
            case 'matches':
                return $this->admin_add_matches($uri, $page);

            default:
                return Response::error(404);
        }
    }

    /**
     * Admin panel handler: Edit
     *
     * @param   object      $object
     * @param   string      $uri
     * @param   \Ionic\Page $page
     * @return  mixed
     */
    public function admin_edit($object, $uri, Page $page)
    {
        switch ($object->type)
        {
            case 'matches':
                return $this->admin_edit_matches($object, $uri, $page);

            default:
                return Response::error(404);
        }
    }

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
    public function collect_events($object, $from, $to, $image_width, $image_height)
    {
        if (empty($object->options))
            return array();

        $options = unserialize($object->options);

        switch ($object->type)
        {
            case 'matches':
                return $this->event_matches($object, $options, $from, $to, $image_width, $image_height);
        }

        return array();
    }

    /**
     * Get event source types supported by this handler in format:
     *
     * array('HANDLER_NAME/TYPE_NAME' => 'TITLE', ...)
     *
     * @return  array
     */
    public function get_sources()
    {
        return array('sport/matches' => 'Mecze z terminarza');
    }

    /**
     * Matches add
     *
     * @param   string      $uri
     * @param   \Ionic\Page $page
     * @return  mixed
     */
    protected function admin_add_matches($uri, Page $page)
    {
        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title' => '', 'team' => '', 'competition_id' => '', 'season_id'  => '', 'date_start' => '', 'date_end' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'team', 'competition_id', 'season_id', 'date_start', 'date_end')));

            $rules = array(
                'title'             => 'required|max:127',
                'team'              => 'exists:teams,name',
                'competition_id'    => 'exists:competitions,id',
                'season_id'         => 'exists:seasons,id',
                'date_start'        => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!',
                'date_end'          => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!',
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to($uri)->with_errors($validator)
                                         ->with_input('only', array('title', 'team', 'competition_id', 'season_id', 'date_start', 'date_end'));
            }
            else
            {
                $options = array('team' => '', 'competition' => $raw_data['competition_id'], 'season' => $raw_data['season_id']);

                if ($raw_data['team'])
                {
                    $team = DB::table('teams')->where('name', '=', $raw_data['team'])->first('id');

                    if ($team)
                        $options['team'] = $team->id;
                }

                return array(
                    'title'      => HTML::specialchars($raw_data['title']),
                    'date_start' => $raw_data['date_start'],
                    'date_end'   => $raw_data['date_end'],
                    'options'    => $options
                );
            }
        }

        $view = View::make('admin.calendar.add_sport_matches', array('action_uri' => $uri));

        $related = array();

        foreach (DB::table('competitions')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $view->with('related_competition_id', $related);

        $related = array();

        foreach (DB::table('seasons')->order_by('year', 'desc')->get(array('id', 'year')) as $v)
        {
            $related[$v->id] = $v->year.' / '.($v->year + 1);
        }

        $view->with('related_season_id', $related);

        $old_data = array('title' => '', 'team' => '', 'competition_id' => '', 'season_id'  => '', 'date_start' => '', 'date_end' => '');
        $old_data = array_merge($old_data, Input::old());
        $view->with('old_data', $old_data);

        return $view;
    }

    /**
     * Matches edit
     *
     * @param   object      $object
     * @param   string      $uri
     * @param   \Ionic\Page $page
     * @return  mixed
     */
    protected function admin_edit_matches($object, $uri, Page $page)
    {
        if (!Request::forged() and Request::method() == 'POST')
        {
            $raw_data = array('title' => '', 'team' => '', 'competition_id' => '', 'season_id'  => '', 'date_start' => '', 'date_end' => '');
            $raw_data = array_merge($raw_data, Input::only(array('title', 'team', 'competition_id', 'season_id', 'date_start', 'date_end')));

            $rules = array(
                'title'             => 'required|max:127',
                'team'              => 'exists:teams,name',
                'competition_id'    => 'exists:competitions,id',
                'season_id'         => 'exists:seasons,id',
                'date_start'        => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!',
                'date_end'          => 'required|match:!^[0-9]{4}-[0-9]{2}-[0-9]{2}$!',
            );

            $validator = Validator::make($raw_data, $rules);

            if ($validator->fails())
            {
                return Redirect::to($uri)->with_errors($validator)
                                         ->with_input('only', array('title', 'team', 'competition_id', 'season_id', 'date_start', 'date_end'));
            }
            else
            {
                $options = array('team' => '', 'competition' => $raw_data['competition_id'], 'season' => $raw_data['season_id']);

                if ($raw_data['team'])
                {
                    $team = DB::table('teams')->where('name', '=', $raw_data['team'])->first('id');

                    if ($team)
                        $options['team'] = $team->id;
                }

                return array(
                    'title'      => HTML::specialchars($raw_data['title']),
                    'date_start' => $raw_data['date_start'],
                    'date_end'   => $raw_data['date_end'],
                    'options'    => $options
                );
            }
        }

        $options = array('team' => '', 'competition' => '', 'season' => '');

        if (!empty($object->options))
        {
            $options = unserialize($object->options);

            if ($options['team'])
            {
                $team = DB::table('teams')->where('id', '=', $options['team'])->first('name');

                if ($team)
                {
                    $options['team'] = $team->name;
                }
                else
                {
                    $options['team'] = '';
                }
            }
            else
            {
                $options['team'] = '';
            }
        }

        $view = View::make('admin.calendar.edit_sport_matches', array('action_uri' => $uri));

        $related = array();

        foreach (DB::table('competitions')->get(array('id', 'name')) as $v)
        {
            $related[$v->id] = $v->name;
        }

        $view->with('related_competition_id', $related);

        $related = array();

        foreach (DB::table('seasons')->order_by('year', 'desc')->get(array('id', 'year')) as $v)
        {
            $related[$v->id] = $v->year.' / '.($v->year + 1);
        }

        $view->with('related_season_id', $related);

        $old_data = array('title' => '', 'team' => '', 'competition_id' => '', 'season_id'  => '', 'date_start' => '', 'date_end' => '');
        $old_data = array_merge($old_data, Input::old());
        $view->with('old_data', $old_data);
        $view->with('object', $object);
        $view->with('options', $options);

        return $view;
    }

    /**
     * Matches event handler
     *
     * @param   object  $object
     * @param   array   $options
     * @param   string  $from
     * @param   string  $to
     * @param   int     $image_width
     * @param   int     $image_height
     * @return  array
     */
    protected function event_matches($object, $options, $from, $to, $image_width, $image_height)
    {
        $events = array();

        $matches = DB::table('matches')->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                                       ->join('competitions', 'competitions.id', '=', 'fixtures.competition_id')
                                       ->join('teams as '.DB::prefix().'home', 'home.id', '=', 'matches.home_id')
                                       ->join('teams as '.DB::prefix().'away', 'away.id', '=', 'matches.away_id')
                                       ->where('matches.date', '>=', $from.' 00:00:00')
                                       ->where('matches.date', '<=', $to.' 23:59:59')
                                       ->take(50);

        if ($options['team'])
        {
            $matches->where(function($q) use ($options) {
                $q->where('matches.home_id', '=', $options['team']);
                $q->or_where('matches.away_id', '=', $options['team']);
            });
        }

        if ($options['competition'])
        {
            $matches->where('fixtures.competition_id', '=', $options['competition']);
        }

        if ($options['season'])
        {
            $matches->where('fixtures.season_id', '=', $options['season']);
        }

        foreach ($matches->get(array('matches.slug', 'matches.date', 'matches.score', 'competitions.name as comp_name', 'fixtures.name as fixture_name',
                                     'home.name as home_name', 'away.name as away_name', 'home.image as home_image', 'away.image as away_image',
                                     'home.is_distinct as home_is_distinct')) as $m)
        {
            $events[] = array(
                'day'       => (int) substr($m->date, 8, 2),
                'title'     => $m->score ? $m->home_name.' '.$m->score.' '.$m->away_name : $m->home_name.' vs. '.$m->away_name,
                'details'   => $m->comp_name.', '.$m->fixture_name,
                'url'       => 'match/show/'.$m->slug,
                'image'     => $m->home_is_distinct == '1' ? ionic_thumb('teams', $m->away_image, $image_width.'x'.$image_height)
                                                           : ionic_thumb('teams', $m->home_image, $image_width.'x'.$image_height)
            );
        }

        return $events;
    }
}
