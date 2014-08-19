<?php
namespace Ionic;

use \DB;

class TableManager {

    public static function clear($id)
    {
        $id = (int) $id;

        DB::table('table_positions')->where('table_id', '=', $id)->delete();

        ionic_clear_cache('table-'.$id.'-*');
    }

    public static function generate($table)
    {
        $table = DB::table('tables')->where('id', '=', (int) $table)->first(array('id', 'season_id', 'competition_id', 'sorting_rules'));

        if (!$table)
            return false;

        static::clear($table->id);

        $teams = array();
        $data = array();
        $breaker = array();

        foreach (DB::table('competition_teams')->where('season_id', '=', $table->season_id)->where('competition_id', '=', $table->competition_id)->get('team_id') as $t)
        {
            $teams[] = $t->team_id;

            $data[$t->team_id] = array(
                'team_id'    => $t->team_id,
                'points'     => 0,
                'matches'    => 0,
                'wins'       => 0,
                'losses'     => 0,
                'draws'      => 0,
                'goals_shot' => 0,
                'goals_lost' => 0
            );

            $breaker[$t->team_id] = array();
        }

        if (empty($teams))
            return true;

        foreach (DB::table('matches')->where('fixtures.competition_id', '=', $table->competition_id)
                ->where('fixtures.season_id', '=', $table->season_id)
                ->join('fixtures', 'fixtures.id', '=', 'matches.fixture_id')
                ->where_in('matches.home_id', $teams)
                ->where_in('matches.away_id', $teams)
                ->where('score', '<>', '')
                ->get(array('home_id', 'away_id', 'score')) as $m)
        {
            if ($m->home_id == $m->away_id)
                continue;

            $score = ionic_parse_score($m->score);
            if (!$score)
                continue;

            if ($table->sorting_rules == 'laliga')
            {
                if (!isset($breaker[$m->home_id][$m->away_id]))
                {
                    $breaker[$m->home_id][$m->away_id] = array('matches' => 1, 'balance' => ($score[0] - $score[1]));
                }
                else
                {
                    $breaker[$m->home_id][$m->away_id]['matches']++;
                    $breaker[$m->home_id][$m->away_id]['balance'] += ($score[0] - $score[1]);
                }

                if (!isset($breaker[$m->away_id][$m->home_id]))
                {
                    $breaker[$m->away_id][$m->home_id] = array('matches' => 1, 'balance' => ($score[1] - $score[0]));
                }
                else
                {
                    $breaker[$m->away_id][$m->home_id]['matches']++;
                    $breaker[$m->away_id][$m->home_id]['balance'] += ($score[1] - $score[0]);
                }
            }
            elseif ($table->sorting_rules == 'ekstraklasa')
            {
                if (!isset($breaker[$m->home_id][$m->away_id]))
                {
                    $breaker[$m->home_id][$m->away_id] = array(
                        'matches'  => 1,
                        'balance'  => ($score[0] - $score[1]),
                        'balance2' => ($score[0] - ($score[1] * 2)),
                        'points'   => 0
                    );
                }
                else
                {
                    $breaker[$m->home_id][$m->away_id]['matches']++;
                    $breaker[$m->home_id][$m->away_id]['balance'] += ($score[0] - $score[1]);
                    $breaker[$m->home_id][$m->away_id]['balance2'] += ($score[0] - ($score[1] * 2));
                }

                if (!isset($breaker[$m->away_id][$m->home_id]))
                {
                    $breaker[$m->away_id][$m->home_id] = array(
                        'matches'  => 1,
                        'balance'  => ($score[1] - $score[0]),
                        'balance2' => (($score[1] * 2) - $score[0]),
                        'points'   => 0
                    );
                }
                else
                {
                    $breaker[$m->away_id][$m->home_id]['matches']++;
                    $breaker[$m->away_id][$m->home_id]['balance'] += ($score[1] - $score[0]);
                    $breaker[$m->away_id][$m->home_id]['balance2'] += (($score[1] * 2) - $score[0]);
                }

                if ($score[0] > $score[1])
                {
                    $breaker[$m->home_id][$m->away_id]['points'] += 3;
                }
                elseif ($score[0] == $score[1])
                {
                    $breaker[$m->home_id][$m->away_id]['points'] += 1;
                    $breaker[$m->away_id][$m->home_id]['points'] += 1;
                }
                else
                {
                    $breaker[$m->away_id][$m->home_id]['points'] += 3;
                }
            }

            $data[$m->home_id]['goals_shot'] += $score[0];
            $data[$m->away_id]['goals_shot'] += $score[1];
            $data[$m->home_id]['goals_lost'] += $score[1];
            $data[$m->away_id]['goals_lost'] += $score[0];
            $data[$m->home_id]['matches']++;
            $data[$m->away_id]['matches']++;

            if ($score[0] == $score[1])
            {
                $data[$m->home_id]['points']++;
                $data[$m->home_id]['draws']++;
                $data[$m->away_id]['points']++;
                $data[$m->away_id]['draws']++;
            }
            elseif ($score[0] > $score[1])
            {
                $data[$m->home_id]['points'] += 3;
                $data[$m->home_id]['wins']++;
                $data[$m->away_id]['losses']++;
            }
            else
            {
                $data[$m->away_id]['points'] += 3;
                $data[$m->away_id]['wins']++;
                $data[$m->home_id]['losses']++;
            }
        }

        if ($table->sorting_rules == 'laliga')
        {
            usort($data, function($a, $b) use ($breaker) {
                        if ($a['points'] == $b['points'])
                        {
                            // If all clubs involved have played each other twice
                            if (isset($breaker[$a['team_id']][$b['team_id']]) and ($breaker[$a['team_id']][$b['team_id']]['matches'] >= 2))
                            {
                                // If the tie is between two clubs, then the tie is broken using the head-to-head goal difference (without away goals rule)
                                if ($breaker[$a['team_id']][$b['team_id']]['balance'] != 0)
                                {
                                    return ($breaker[$a['team_id']][$b['team_id']]['balance'] > 0) ? -1 : 1;
                                }
                            }

                            // If two legged games between all clubs involved have not been played, or the tie is not broken by the rules above
                            if (($a['goals_shot'] - $a['goals_lost']) == ($b['goals_shot'] - $b['goals_lost']))
                            {
                                if ($a['goals_shot'] == $b['goals_shot'])
                                {
                                    // You gotta be pretty lucky to get there anyway
                                    return 0;
                                }

                                return ($a['goals_shot'] > $b['goals_shot']) ? -1 : 1;
                            }

                            return (($a['goals_shot'] - $a['goals_lost']) > ($b['goals_shot'] - $b['goals_lost'])) ? -1 : 1;
                        }

                        return ($a['points'] > $b['points']) ? -1 : 1;
                    });
        }
        elseif ($table->sorting_rules == 'ekstraklasa')
        {
            usort($data, function($a, $b) use ($breaker) {
                        if ($a['points'] == $b['points'])
                        {
                            // If all clubs involved have played each other twice
                            if (isset($breaker[$a['team_id']][$b['team_id']]) and ($breaker[$a['team_id']][$b['team_id']]['matches'] >= 2))
                            {
                                if ($breaker[$a['team_id']][$b['team_id']]['points'] != $breaker[$b['team_id']][$a['team_id']]['points'])
                                {
                                    return ($breaker[$a['team_id']][$b['team_id']]['points'] > $breaker[$b['team_id']][$a['team_id']]['points']) ? -1 : 1;
                                }

                                if ($breaker[$a['team_id']][$b['team_id']]['balance'] != 0)
                                {
                                    return ($breaker[$a['team_id']][$b['team_id']]['balance'] > 0) ? -1 : 1;
                                }

                                if ($breaker[$a['team_id']][$b['team_id']]['balance2'] != 0)
                                {
                                    return ($breaker[$a['team_id']][$b['team_id']]['balance2'] > 0) ? -1 : 1;
                                }
                            }

                            // If two legged games between all clubs involved have not been played, or the tie is not broken by the rules above
                            if (($a['goals_shot'] - $a['goals_lost']) == ($b['goals_shot'] - $b['goals_lost']))
                            {
                                if ($a['goals_shot'] == $b['goals_shot'])
                                {
                                    // You gotta be pretty lucky to get there anyway
                                    return 0;
                                }

                                return ($a['goals_shot'] > $b['goals_shot']) ? -1 : 1;
                            }

                            return (($a['goals_shot'] - $a['goals_lost']) > ($b['goals_shot'] - $b['goals_lost'])) ? -1 : 1;
                        }

                        return ($a['points'] > $b['points']) ? -1 : 1;
                    });
        }
        else
        {
            usort($data, function($a, $b) {
                        if ($a['points'] == $b['points'])
                        {
                            if (($a['goals_shot'] - $a['goals_lost']) == ($b['goals_shot'] - $b['goals_lost']))
                            {
                                if ($a['goals_shot'] == $b['goals_shot'])
                                {
                                    return 0;
                                }

                                return ($a['goals_shot'] > $b['goals_shot']) ? -1 : 1;
                            }

                            return (($a['goals_shot'] - $a['goals_lost']) > ($b['goals_shot'] - $b['goals_lost'])) ? -1 : 1;
                        }

                        return ($a['points'] > $b['points']) ? -1 : 1;
                    });
        }

        foreach ($data as $k => $v)
        {
            DB::table('table_positions')->insert(array_merge($v, array(
                        'position' => ($k + 1),
                        'table_id' => $table->id
                    )));
        }

        ionic_clear_cache('table-'.$table->id.'-*');

        return true;
    }

    public static function get($table, $sort_item = 'table_positions.position', $sort_order = 'asc', $limit = null, $force_distinct = false)
    {
        $generated = array();

        if (!$limit)
        {
            $i = 1;

            foreach (DB::table('table_positions')->order_by($sort_item, $sort_order)
                    ->join('teams', 'teams.id', '=', 'table_positions.team_id')
                    ->where('table_positions.table_id', '=', (int) $table)
                    ->get(array('table_positions.*', 'teams.name', 'teams.is_distinct', 'teams.slug', 'teams.image')) as $t)
            {
                $t->position = $i;

                $generated[] = $t;

                $i++;
            }
        }
        else
        {
            $has_distinct = false;
            $i = 1;

            foreach (DB::table('table_positions')->order_by($sort_item, $sort_order)
                    ->join('teams', 'teams.id', '=', 'table_positions.team_id')
                    ->where('table_positions.table_id', '=', (int) $table)
                    ->take($limit)
                    ->get(array('table_positions.*', 'teams.name', 'teams.is_distinct', 'teams.slug', 'teams.image')) as $t)
            {
                $t->position = $i;

                if (!$has_distinct and $t->is_distinct == 1)
                    $has_distinct = true;

                $generated[$i] = $t;

                $i++;
            }

            if ($force_distinct and !$has_distinct and $i > 1 and $i > $limit)
            {
                $t = DB::table('table_positions')->join('teams', 'teams.id', '=', 'table_positions.team_id')
                        ->where('table_positions.table_id', '=', (int) $table)
                        ->where('teams.is_distinct', '=', 1)
                        ->first(array('table_positions.*', 'teams.name', 'teams.is_distinct', 'teams.slug', 'teams.image'));

                if ($t)
                {
                    $generated[($i - 1)] = $t;
                }
            }
        }

        return $generated;
    }

    public static function reload($table)
    {
        $table = DB::table('tables')->where('id', '=', (int) $table)->first(array('id', 'season_id', 'competition_id'));

        if (!$table)
            return false;

        $teams = array();

        foreach (DB::table('competition_teams')->where('season_id', '=', $table->season_id)->where('competition_id', '=', $table->competition_id)->get('team_id') as $t)
        {
            $teams[$t->team_id] = $t->team_id;
        }

        if (empty($teams))
            return false;

        DB::table('table_positions')->where('table_id', '=', $table->id)->where_not_in('team_id', $teams)->delete();

        foreach (DB::table('table_positions')->where('table_id', '=', $table->id)->where_in('team_id', $teams)->get('team_id') as $t)
        {
            if (isset($teams[$t->team_id]))
                unset($teams[$t->team_id]);
        }

        if (!empty($teams))
        {
            foreach ($teams as $id)
            {
                DB::table('table_positions')->insert(array(
                    'table_id' => $table->id,
                    'team_id'  => $id
                ));
            }
        }

        ionic_clear_cache('table-'.$table->id.'-*');

        return true;
    }

}
