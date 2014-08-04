<?php

/**
 * Bet controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Bet_Controller extends Base_Controller {

    /**
     * Bets archive
     */
    public function action_archive()
    {
        if ($this->require_auth())
            return Redirect::to('index');

        $type = Config::get('bets.type', 'betting');

        if ($type == 'betting' and $this->user->bet_points <= 0)
        {
            if (!DB::table('bets')->where('user_id', '=', $this->user->id)->count())
            {
                DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array('bet_points' => (int) Config::get('bets.bet_starting', 0)));
                $this->user->bet_points = (int) Config::get('bets.bet_starting', 0);
            }
        }

        $this->page->set_title('Archiwum typów');
        $this->page->breadcrumb_append('Typer', 'bet/index');
        $this->page->breadcrumb_append('Archiwum typów', 'bet/archive');
        $this->online('Archiwum typów', 'bet/archive');

        $this->view = View::make('bet.archive', array(
                    'type'    => $type,
                    'matches' => DB::table('bets')->join('bet_matches', 'bet_matches.id', '=', 'match_id')
                            ->where('user_id', '=', $this->user->id)
                            ->where('archive', '=', 1)
                            ->order_by('date_end', 'desc')
                            ->paginate(20, array(
                                'bets.bet', 'bets.used_points', 'bets.acquired_points',
                                'bet_matches.home', 'bet_matches.away', 'bet_matches.score'
                            ))
                ));
    }

    /**
     * Leaderboard
     */
    public function action_index()
    {
        if ($this->require_auth())
            return Redirect::to('index');

        $type = Config::get('bets.type', 'betting');

        if ($type == 'betting' and $this->user->bet_points <= 0)
        {
            if (!DB::table('bets')->where('user_id', '=', $this->user->id)->count())
            {
                DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array('bet_points' => (int) Config::get('bets.bet_starting', 0)));
                $this->user->bet_points = (int) Config::get('bets.bet_starting', 0);
            }
        }

        $this->page->set_title('Typer');
        $this->page->breadcrumb_append('Typer', 'bet/index');
        $this->online('Typer', 'bet/index');

        $this->view = View::make('bet.index', array(
                    'type'    => $type,
                    'place'   => (DB::table('profiles')->where('bet_points', '>', $this->user->bet_points)->count() + 1),
                    'players' => DB::table('profiles')->order_by('bet_points', 'desc')->join('users', 'profiles.user_id', '=', 'users.id')
                            ->where('bet_points', '>', 0)
                            ->paginate(20, array(
                                'users.display_name', 'users.slug', 'profiles.bet_points'
                            )),
                ));
    }

    /**
     * Matches to bet
     */
    public function action_matches()
    {
        if ($this->require_auth())
            return Redirect::to('index');

        $type = Config::get('bets.type', 'betting');

        if ($type == 'betting' and $this->user->bet_points <= 0)
        {
            if (!DB::table('bets')->where('user_id', '=', $this->user->id)->count())
            {
                DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array('bet_points' => (int) Config::get('bets.bet_starting', 0)));
                $this->user->bet_points = (int) Config::get('bets.bet_starting', 0);
            }
        }

        $bets = array();

        foreach (DB::table('bets')->where('user_id', '=', $this->user->id)->where('acquired_points', '=', 0)->get(array('bet', 'match_id')) as $b)
        {
            $bets[$b->match_id] = $b->bet;
        }

        $this->page->set_title('Typowanie');
        $this->page->breadcrumb_append('Typer', 'bet/index');
        $this->page->breadcrumb_append('Typowanie', 'bet/matches');
        $this->online('Typowanie', 'bet/matches');

        $this->view = View::make('bet.matches', array(
                    'type'    => $type,
                    'bets'    => $bets,
                    'matches' => DB::table('bet_matches')->where('archive', '<>', 1)
                            ->where('date_start', '<=', date('Y-m-d H:i:s'))
                            ->where('date_end', '>=', date('Y-m-d H:i:s'))
                            ->get(array('home', 'away', 'id', 'date_end'))
                ));
    }

    /**
     * Match betting
     *
     * @param  string $match
     */
    public function action_match($match)
    {
        if ($this->require_auth())
            return Redirect::to('index');

        $type = Config::get('bets.type', 'betting');

        if ($type == 'betting' and $this->user->bet_points <= 0)
        {
            if (!DB::table('bets')->where('user_id', '=', $this->user->id)->count())
            {
                DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array('bet_points' => (int) Config::get('bets.bet_starting', 0)));
                $this->user->bet_points = (int) Config::get('bets.bet_starting', 0);
            }
        }

        if (!ctype_digit($match))
            return Response::error(500);

        $match = DB::table('bet_matches')->where('archive', '<>', 1)
                ->where('date_start', '<=', date('Y-m-d H:i:s'))
                ->where('date_end', '>=', date('Y-m-d H:i:s'))
                ->where('id', '=', (int) $match)
                ->first('*');

        if (!$match)
            return Response::error(404);

        $bet = DB::table('bets')->where('user_id', '=', $this->user->id)->where('match_id', '=', $match->id)->first('*');

        if (Request::method() == 'POST' and !Request::forged() and Input::has('bet'))
        {
            switch (Input::get('bet'))
            {
                case 'home':
                    $bet_result = 0;
                    break;

                case 'draw':
                    $bet_result = 1;
                    break;

                default:
                    $bet_result = 2;
            }

            if ($type == 'betting')
            {
                if (!isset($_POST['points']) or $_POST['points'] == '')
                {
                    $this->notice('Musisz podać liczbe punktów do wykorzystania');
                    return Redirect::to('bet/match/'.$match->id);
                }

                $points = Input::get('points');

                if (!ctype_digit($points))
                {
                    $this->notice('Punkty muszą być podane w formie liczby całkowitej');
                    return Redirect::to('bet/match/'.$match->id);
                }

                $points = (int) $points;

                if ($bet)
                {
                    $this->user->bet_points += (int) $bet->used_points;

                    if ($points == 0)
                    {
                        DB::table('bets')->where('id', '=', $bet->id)->delete();
                        DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array('bet_points' => $this->user->bet_points));

                        $this->notice('Typ usunięty pomyślnie');
                        return Redirect::to('bet/match/'.$match->id);
                    }

                    if ($points < Config::get('bets.bet_minimum', 10))
                    {
                        $this->notice('Minimalna liczba punktów ,które musisz użyć wynosi '.Config::get('bets.bet_minimum', 10));
                        return Redirect::to('bet/match/'.$match->id);
                    }

                    if ($points > $this->user->bet_points)
                    {
                        $this->notice('Nie posiadasz takiej ilości punktów');
                        return Redirect::to('bet/match/'.$match->id);
                    }

                    DB::table('bets')->where('id', '=', $bet->id)->update(array('used_points' => $points, 'bet'         => $bet_result));
                    DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array('bet_points' => ($this->user->bet_points - $points)));

                    $this->notice('Typ zaaktualizowany pomyślnie');
                    return Redirect::to('bet/match/'.$match->id);
                }
                else
                {
                    if ($points < Config::get('bets.bet_minimum', 10))
                    {
                        $this->notice('Minimalna liczba punktów ,które musisz użyć wynosi '.Config::get('bets.bet_minimum', 10));
                        return Redirect::to('bet/match/'.$match->id);
                    }

                    if ($points > $this->user->bet_points)
                    {
                        $this->notice('Nie posiadasz takiej ilości punktów');
                        return Redirect::to('bet/match/'.$match->id);
                    }

                    DB::table('bets')->insert(array(
                        'match_id'        => $match->id,
                        'user_id'         => $this->user->id,
                        'bet'             => $bet_result,
                        'used_points'     => $points,
                        'acquired_points' => 0
                    ));

                    DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array('bet_points' => ($this->user->bet_points - $points)));

                    $this->notice('Typ dodany pomyślnie');
                    return Redirect::to('bet/match/'.$match->id);
                }
            }

            if ($bet)
            {
                DB::table('bets')->where('id', '=', $bet->id)->update(array('bet' => $bet_result));

                $this->notice('Typ zaaktualizowany pomyślnie');
                return Redirect::to('bet/match/'.$match->id);
            }

            DB::table('bets')->insert(array(
                'match_id'        => $match->id,
                'user_id'         => $this->user->id,
                'bet'             => $bet_result,
                'used_points'     => 0,
                'acquired_points' => 0
            ));

            $this->notice('Typ dodany pomyślnie');
            return Redirect::to('bet/match/'.$match->id);
        }

        $this->page->set_title('Typuj mecz');
        $this->page->breadcrumb_append('Typer', 'bet/index');
        $this->page->breadcrumb_append('Typowanie', 'bet/matches');
        $this->page->breadcrumb_append('Typowanie meczu', 'bet/match/'.$match->id);
        $this->online('Typowanie meczu', 'bet/match/'.$match->id);

        $this->view = View::make('bet.match', array(
                    'type'  => $type,
                    'bet'   => $bet,
                    'match' => $match,
                    'min'   => Config::get('bets.bet_minimum', 10)
                ));
    }

    /**
     * Start from the beginning
     */
    public function action_reset()
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (Config::get('bets.type', 'betting') != 'betting')
            return Redirect::to('bet/index');

        if (!($status = $this->confirm()))
        {
            return;
        }
        elseif ($status == 2)
        {
            return Redirect::to('bet/index');
        }

        DB::table('bets')->where('user_id', '=', $this->user->id)->delete();
        DB::table('profiles')->where('user_id', '=', $this->user->id)->update(array('bet_points' => (int) Config::get('bets.bet_starting', 0)));

        $this->notice('Twoje konto w typerze zostało zresetowane pomyślnie');
        return Redirect::to('bet/index');
    }

}