<?php

/**
 * Monthpick controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Monthpick_Controller extends Base_Controller {

    /**
     * Monthpick listing
     */
    public function action_index()
    {
        $this->page->set_title('Piłkarz miesiąca');
        $this->page->breadcrumb_append('Piłkarz miesiąca', 'monthpick/index');
        $this->online('Piłkarz miesiąca', 'monthpick/index');

        $this->view = View::make('monthpick.index', array(
                    'picks' => DB::table('monthpicks')->order_by('is_active', 'desc')->order_by('created_at', 'desc')->paginate(20, array('monthpicks.id', 'monthpicks.title', 'monthpicks.votes', 'monthpicks.is_active'))
                ));
    }

    /**
     * Single pick
     *
     * @param  string   $id
     * @return Response
     */
    public function action_show($id)
    {
        if (!ctype_digit($id))
            return Response::error(500);

        $pick = new Model\Monthpick((int) $id);

        if (!$pick->is_init)
            return Response::error(404);

        $this->page->set_title('Głosowanie');
        $this->page->breadcrumb_append('Piłkarz miesiąca', 'monthpick/index');
        $this->page->breadcrumb_append('Głosowanie', 'monthpick/show/'.$pick->id);
        $this->online('Głosowanie', 'monthpick/show/'.$pick->id);

        $this->view = View::make('monthpick.show', array(
                    'pick' => $pick
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

        $pick = new Model\Monthpick;

        if (!$pick->is_init or !$pick->can_vote())
            return Redirect::to('monthpick/index');

        $option = (int) Input::get('option');

        if (!isset($pick->options[$option]))
            return Redirect::to('monthpick/show/'.$pick->id);

        $pick->vote($option);

        $this->notice('Twój głos został pomyślnie oddany');
        return Redirect::to('monthpick/show/'.$pick->id);
    }

}