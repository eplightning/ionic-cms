<?php

/**
 * Poll controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Poll_Controller extends Base_Controller {

    /**
     * Poll listing
     */
    public function action_index()
    {
        $this->page->set_title('Archiwum sond');
        $this->page->breadcrumb_append('Archiwum sond', 'poll/index');
        $this->online('Archiwum sond', 'poll/index');

        $this->view = View::make('poll.index', array(
                    'polls' => DB::table('polls')->order_by('is_active', 'desc')->order_by('created_at', 'desc')->paginate(20, array('polls.id', 'polls.title', 'polls.votes', 'polls.is_active'))
                ));
    }

    /**
     * Single poll
     *
     * @param  string   $id
     * @return Response
     */
    public function action_show($id)
    {
        if (!ctype_digit($id))
            return Response::error(500);

        $poll = new Model\Poll((int) $id);

        if (!$poll->is_init)
            return Response::error(404);

        $this->page->set_title('Sonda');
        $this->page->breadcrumb_append('Archiwum sond', 'poll/index');
        $this->page->breadcrumb_append('Sonda', 'poll/show/'.$poll->id);
        $this->online('Sonda', 'poll/show/'.$poll->id);

        $this->view = View::make('poll.show', array(
                    'poll' => $poll
                ));
    }

    /**
     * Vote
     *
     * @return Response
     */
    public function action_vote()
    {
        if (Request::method() != 'POST' or Request::forged() or !Input::has('option') or !ctype_digit(Input::get('option')))
            return Response::error(500);

        $poll = new Model\Poll;

        if (!$poll->is_init or !$poll->can_vote())
            return Redirect::to('poll/index');

        $option = (int) Input::get('option');

        if (!isset($poll->options[$option]))
            return Redirect::to('poll/show/'.$poll->id);

        $poll->vote($option);

        $this->notice('Twój głos został pomyślnie oddany');
        return Redirect::to('poll/show/'.$poll->id);
    }

}