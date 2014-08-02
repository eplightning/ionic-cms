<?php
namespace Model;

use \DB;

class Poll {

    public $is_init = false;
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
            $id = DB::table('polls')->where('is_active', '=', 1)->first('*');
        }
        else
        {
            $id = DB::table('polls')->where('id', '=', (int) $id)->first('*');
        }

        if ($id)
        {
            $this->load_poll($id);
        }
    }

    public function can_vote()
    {
        $user = \Auth::get_user();

        // No guests allowed, user already voted, IP already voted
        if ((!$user and !\Config::get('guests.polls', false)) or ($user and in_array($user->id, $this->voters_id)) or in_array(inet_pton(\Request::ip()), $this->voters_ip))
        {
            return false;
        }

        return $this->is_active;
    }

    protected function load_poll($object)
    {
        // object info
        $this->created_at = $object->created_at;
        $this->id = $object->id;
        $this->is_active = $object->is_active;
        $this->title = $object->title;
        $this->votes = $object->votes;

        // voters
        foreach (DB::table('poll_voters')->where('poll_id', '=', $object->id)->get(array('user_id', 'ip')) as $vote)
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
        foreach (DB::table('poll_options')->where('poll_id', '=', $object->id)->get(array('id', 'title', 'votes')) as $opt)
        {
            $opt->percent = round($this->votes == 0 ? 0 : ($opt->votes / $this->votes * 100), 2);

            $this->options[$opt->id] = $opt;
        }

        // make sure voters are unique (preserving storage space...)
        array_unique($this->voters_id);
        array_unique($this->voters_ip);

        // everything's ok
        $this->is_init = true;
    }

    public function vote($option_id, $no_voter_info = false)
    {
        DB::table('polls')->where('id', '=', $this->id)->update(array(
            'votes' => DB::raw('votes + 1')
        ));

        DB::table('poll_options')->where('id', '=', $option_id)->update(array(
            'votes' => DB::raw('votes + 1')
        ));

        if (!$no_voter_info)
        {
            $user = \Auth::get_user();

            DB::table('poll_voters')->insert(array(
                'poll_id' => $this->id,
                'user_id' => $user ? $user->id : null,
                'ip'      => \Request::ip()
            ));
        }

        \Cache::forget('poll');
    }

}