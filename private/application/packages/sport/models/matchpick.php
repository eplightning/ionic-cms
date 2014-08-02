<?php
namespace Model;

use \DB;

class Matchpick {

    public $is_init = false;
    public $best_option = 0;
    public $created_at = '';
    public $expires = null;
    public $id = 0;
    public $is_active = false;
    public $title = '';
    public $votes = 0;
    public $options = array();
    public $voters_id = array();
    public $voters_ip = array();

    public function __construct($id = null)
    {
        if (!$id)
        {
            $id = DB::table('matchpicks')->where('is_active', '=', 1)->first('*');
        }
        else
        {
            $id = DB::table('matchpicks')->where('id', '=', (int) $id)->first('*');
        }

        if ($id)
        {
            $this->load_pick($id);
        }
    }

    public function can_vote()
    {
        $user = \Auth::get_user();

        // No guests allowed, user already voted, IP already voted
        if ((!$user and !\Config::get('guests.matchpicks', false)) or ($user and in_array($user->id, $this->voters_id)) or in_array(inet_pton(\Request::ip()), $this->voters_ip))
        {
            return false;
        }

        if ($this->expires and $this->expires <= time())
        {
            return false;
        }

        return $this->is_active;
    }

    protected function load_pick($object)
    {
        // object info
        $this->best_option = $object->best_player_id;
        $this->created_at = $object->created_at;
        $this->expires = (int) strtotime($object->expires);
        $this->id = $object->id;
        $this->is_active = $object->is_active;
        $this->title = $object->title;
        $this->votes = $object->votes;

        // voters
        foreach (DB::table('matchpick_voters')->where('matchpick_id', '=', $object->id)->get(array('user_id', 'ip')) as $vote)
        {
            if ($vote->user_id)
            {
                $this->voters_id[] = $vote->user_id;
            }

            if ($vote->ip != '0.0.0.0')
            {
                $this->voters_ip[] = inet_pton($vote->ip);
            }
        }

        // options
        $this->options = $object->options ? unserialize($object->options) : array();

        // make sure voters are unique (preserving storage space...)
        array_unique($this->voters_id);
        array_unique($this->voters_ip);

        // everything's ok
        $this->is_init = true;
    }

    public function vote($option_array, $no_voter_info = false)
    {
        foreach ($this->options as $k => $v)
        {
            if (!isset($option_array[(string) $k]))
            {
                return false;
            }

            $rate = $option_array[(string) $k];

            if (!ctype_digit($rate))
                return false;

            $rate = (int) $rate;

            // Make sure $rate is in <1;10>
            $rate = ($rate > 10) ? 10 : ($rate < 1 ? 1 : $rate);

            $this->options[$k]['votes']++;
            $this->options[$k]['total'] += $rate;
            $this->options[$k]['rating'] = round($this->options[$k]['total'] / $this->options[$k]['votes'], 2);
        }

        $score = 0;
        $player = 0;

        foreach ($this->options as $p)
        {
            if ($p['rating'] > $score)
            {
                $score = $p['rating'];
                $player = $p['player_id'];
            }
        }

        DB::table('matchpicks')->where('id', '=', $this->id)->update(array(
            'votes'          => DB::raw('votes + 1'),
            'options'        => serialize($this->options),
            'best_player_id' => $player
        ));

        if (!$no_voter_info)
        {
            $user = \Auth::get_user();

            DB::table('matchpick_voters')->insert(array(
                'matchpick_id' => $this->id,
                'user_id'      => $user ? $user->id : null,
                'ip'           => \Request::ip()
            ));
        }

        \Cache::forget('matchpick');

        return true;
    }

}