<?php
namespace Ionic;

use \DB;

class BetHandler {

	const NO_RESULT = -1;
	const HOME_WIN = 0;
	const DRAW = 1;
	const AWAY_WIN = 2;

	protected $match_id = null;
	protected $new_result = null;
	protected $new_score = '';
	protected $old_result = null;
	protected $old_score = '';
	protected $points = 3;
	protected $ratios = array();
	protected $type = '';

	public function __construct($old_score, $new_score, $match_id)
	{
		$this->type = \Config::get('bets.type', 'betting');
		$this->points = \Config::get('bets.simple_points', 3);

		$this->new_score = $new_score;
		$this->old_score = $old_score;
		$this->match_id = $match_id;

		$new_score = ionic_parse_score($new_score);
		$old_score = ionic_parse_score($old_score);

		if ($new_score !== null)
		{
			if ($new_score[0] > $new_score[1])
			{
				$this->new_result = self::HOME_WIN;
			}
			elseif ($new_score[0] < $new_score[1])
			{
				$this->new_result = self::AWAY_WIN;
			}
			else
			{
				$this->new_result = self::DRAW;
			}
		}
		else
		{
			$this->new_result = self::NO_RESULT;
		}

		if ($old_score !== null)
		{
			if ($old_score[0] > $old_score[1])
			{
				$this->old_result = self::HOME_WIN;
			}
			elseif ($old_score[0] < $old_score[1])
			{
				$this->old_result = self::AWAY_WIN;
			}
			else
			{
				$this->old_result = self::DRAW;
			}
		}
		else
		{
			$this->old_result = self::NO_RESULT;
		}
	}

	protected function calculate_points($used_points)
	{
		if ($this->type == 'betting')
		{
			return round($this->ratios[$this->new_result] * $used_points);
		}
		else
		{
			return $this->points;
		}
	}

	public function handle($is_archived)
	{
		if ($this->type == 'betting')
		{
			return $this->handle_betting($is_archived);
		}
		else
		{
			return $this->handle_simple($is_archived);
		}
	}

	protected function handle_betting($is_archived)
	{
		// Queries: 2 + X*2 where X is number of players which won the bet
        if ($this->old_result == self::NO_RESULT and $this->new_result != self::NO_RESULT)
        {
            $is_archived = 1;

            foreach (DB::table('bets')->where('match_id', '=', $this->match_id)->where('bet', '=', $this->new_result)
                    ->get(array('user_id', 'used_points', 'id')) as $bet)
            {
                $points = $this->calculate_points($bet->used_points);

                DB::table('bets')->where('id', '=', $bet->id)->update(array(
                    'acquired_points' => $points
                ));

                DB::table('profiles')->where('user_id', '=', $bet->user_id)->update(array(
                    'bet_points' => DB::raw('bet_points + '.$points)
                ));
            }

            DB::table('bets')->where('match_id', '=', $this->match_id)->where('bet', '<>', $this->new_result)->update(array('acquired_points' => 0));
        }
        // Queries: 2 + X where X is number of players which won anything
        elseif ($this->old_result != self::NO_RESULT and $this->new_result == self::NO_RESULT)
        {
            $is_archived = 0;

            foreach (DB::table('bets')->where('bets.match_id', '=', $this->match_id)->where('acquired_points', '<>', 0)
                    ->join('profiles', 'profiles.user_id', '=', 'bets.user_id')
                    ->get(array('bets.user_id', 'bets.acquired_points', 'bets.id', 'profiles.bet_points')) as $bet)
            {
                $p = ($bet->bet_points - $bet->acquired_points);
                if ($p < 0)
                    $p = 0;

                DB::table('profiles')->where('user_id', '=', $bet->user_id)
                        ->update(array('bet_points' => $p));
            }

            DB::table('bets')->where('match_id', '=', $this->match_id)->update(array(
                'acquired_points' => 0
            ));
        }
        // Queries: 2 + X*2 + Y where X is the number of players that are now the winning ones and Y are losing ones which previously were the winners
        elseif (($this->old_result != self::NO_RESULT and $this->new_result != self::NO_RESULT) and $this->old_result != $this->new_result)
        {
            foreach (DB::table('bets')->where('bets.match_id', '=', $this->match_id)
                    ->join('profiles', 'profiles.user_id', '=', 'bets.user_id')
                    ->get(array('bets.user_id', 'bets.used_points', 'bets.id', 'bets.bet', 'bets.acquired_points', 'profiles.bet_points')) as $bet)
            {
                if ($bet->bet == $this->old_result)
                {
                    $p = ($bet->bet_points - $bet->acquired_points);
                    if ($p < 0)
                        $p = 0;

                    DB::table('profiles')->where('user_id', '=', $bet->user_id)
                            ->update(array('bet_points' => $p));
                }
                elseif ($bet->bet == $this->new_result)
                {
                	$points = $this->calculate_points($bet->used_points);

                    DB::table('bets')->where('id', '=', $bet->id)->update(array(
                        'acquired_points' => $points
                    ));

                    DB::table('profiles')->where('user_id', '=', $bet->user_id)->update(array(
                        'bet_points' => DB::raw('bet_points + '.$points)
                    ));
                }
            }

            DB::table('bets')->where('match_id', '=', $this->match_id)->where('bet', '=', $this->old_result)->update(array(
                'acquired_points' => 0
            ));
        }

        return $is_archived;
	}

	protected function handle_simple($is_archived)
	{
		// Queries: 4
        if ($this->old_result == self::NO_RESULT and $this->new_result != self::NO_RESULT)
        {
            $is_archived = 1;
            $players_won = array();

            foreach (DB::table('bets')->where('match_id', '=', $this->match_id)->where('bet', '=', $this->new_result)
                    ->get(array('user_id')) as $bet)
            {
                $players_won[] = $bet->user_id;
            }

            DB::table('bets')->where('match_id', '=', $this->match_id)->where('bet', '=', $this->new_result)->update(array('acquired_points' => $this->calculate_points(0)));
            DB::table('bets')->where('match_id', '=', $this->match_id)->where('bet', '<>', $this->new_result)->update(array('acquired_points' => 0));

            if (!empty($players_won))
            {
                DB::table('profiles')->where_in('user_id', $players_won)->update(array(
                    'bet_points' => DB::raw('bet_points + '.$this->calculate_points(0))
                ));
            }
        }
        // Queries: 3 in normal cases
        elseif ($this->old_result != self::NO_RESULT and $this->new_result == self::NO_RESULT)
        {
            $is_archived = 0;

            // just in case someone changed points configuration
            $points_lost = array();

            foreach (DB::table('bets')->where('match_id', '=', $this->match_id)->where('acquired_points', '<>', 0)
                    ->get(array('user_id', 'acquired_points')) as $bet)
            {
            	if (!isset($points_lost[$bet->acquired_points]))
            	{
            		$points_lost[$bet->acquired_points] = array();
            	}

                $points_lost[$bet->acquired_points][] = $bet->user_id;
            }

            foreach ($points_lost as $pt => $list)
            {
            	DB::table('profiles')->where_in('user_id', $list)->where('bet_points', '>=', $pt)->update(array(
            		'bet_points' => DB::raw('bet_points - '.$pt)
            	));
            }

            DB::table('bets')->where('match_id', '=', $this->match_id)->update(array(
                'acquired_points' => 0
            ));
        }
        // Queries: 5 in normal cases
        elseif (($this->old_result != self::NO_RESULT and $this->new_result != self::NO_RESULT) and $this->old_result != $this->new_result)
        {
            $players_won = array();
            $points_lost = array();

            foreach (DB::table('bets')->where('match_id', '=', $this->match_id)
                    ->get(array('user_id', 'bet', 'acquired_points')) as $bet)
            {
                if ($bet->bet == $this->old_result)
                {
	            	if (!isset($points_lost[$bet->acquired_points]))
	            	{
	            		$points_lost[$bet->acquired_points] = array();
	            	}

	                $points_lost[$bet->acquired_points][] = $bet->user_id;
                }
                elseif ($bet->bet == $this->new_result)
                {
                	$players_won[] = $bet->user_id;
                }
            }

            DB::table('bets')->where('match_id', '=', $this->match_id)->where('bet', '=', $this->new_result)->update(array('acquired_points' => $this->calculate_points(0)));
            DB::table('bets')->where('match_id', '=', $this->match_id)->where('bet', '<>', $this->new_result)->update(array('acquired_points' => 0));

            if (!empty($players_won))
            {
                DB::table('profiles')->where_in('user_id', $players_won)->update(array(
                    'bet_points' => DB::raw('bet_points + '.$this->calculate_points(0))
                ));
            }

            foreach ($points_lost as $pt => $list)
            {
            	DB::table('profiles')->where_in('user_id', $list)->where('bet_points', '>=', $pt)->update(array(
            		'bet_points' => DB::raw('bet_points - '.$pt)
            	));
            }
        }

        return $is_archived;
	}

	public function set_ratios($home_win, $draw, $away_win)
	{
		$this->ratios = array(
			self::HOME_WIN => $home_win,
			self::DRAW => $draw,
			self::AWAY_WIN => $away_win,
			self::NO_RESULT => 0.0
		);
	}
}