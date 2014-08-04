<?php
namespace Model;

use \DB;

class Monthpick {

    public $is_init = false;
    public $best_option = 0;
    public $created_at = '';
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
            $id = DB::table('monthpicks')->where('is_active', '=', 1)->first('*');
        }
        else
        {
            $id = DB::table('monthpicks')->where('id', '=', (int) $id)->first('*');
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
        if ((!$user and !\Config::get('guests.monthpicks', false)) or ($user and in_array($user->id, $this->voters_id)) or in_array(inet_pton(\Request::ip()), $this->voters_ip))
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
        $this->id = $object->id;
        $this->is_active = $object->is_active;
        $this->title = $object->title;
        $this->votes = $object->votes;

        // voters
        foreach (DB::table('monthpick_voters')->where('monthpick_id', '=', $object->id)->get(array('user_id', 'ip')) as $vote)
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

    public function vote($option_id, $no_voter_info = false)
    {
        if (!isset($this->options[$option_id]))
        {
            return false;
        }

        $this->options[$option_id]['votes']++;

        $score = 0;
        $player = 0;

        foreach ($this->options as $p)
        {
            if ($p['votes'] > $score)
            {
                $score = $p['votes'];
                $player = $p['player_id'];
            }
        }

        DB::table('monthpicks')->where('id', '=', $this->id)->update(array(
            'votes'          => DB::raw('votes + 1'),
            'options'        => serialize($this->options),
            'best_player_id' => $player
        ));

        if (!$no_voter_info)
        {
            $user = \Auth::get_user();

            DB::table('monthpick_voters')->insert(array(
                'monthpick_id' => $this->id,
                'user_id'      => $user ? $user->id : null,
                'ip'           => \Request::ip()
            ));
        }

        \Cache::forget('monthpick');

        return true;
    }

}