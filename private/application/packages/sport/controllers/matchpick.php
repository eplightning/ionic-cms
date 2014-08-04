<?php

/**
 * Matchpick controller
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Matchpick_Controller extends Base_Controller {

    /**
     * Monthpick listing
     */
    public function action_index()
    {
        $this->page->set_title('Piłkarz meczu');
        $this->page->breadcrumb_append('Piłkarz meczu', 'matchpick/index');
        $this->online('Piłkarz meczu', 'matchpick/index');

        DB::table('matchpicks')->where('is_active', '=', 1)
                ->where('expires', '<>', '0000-00-00 00:00:00')
                ->where('expires', '<=', date('Y-m-d H:i:s'))
                ->update(array('is_active' => 0, 'expires'   => '0000-00-00 00:00:00'));

        $this->view = View::make('matchpick.index', array(
                    'picks' => DB::table('matchpicks')->order_by('is_active', 'desc')->order_by('created_at', 'desc')->paginate(20, array('matchpicks.id', 'matchpicks.title', 'matchpicks.expires', 'matchpicks.votes', 'matchpicks.is_active'))
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

        DB::table('matchpicks')->where('is_active', '=', 1)
                ->where('expires', '<>', '0000-00-00 00:00:00')
                ->where('expires', '<=', date('Y-m-d H:i:s'))
                ->update(array('is_active' => 0, 'expires'   => '0000-00-00 00:00:00'));

        $pick = new Model\Matchpick((int) $id);

        if (!$pick->is_init)
            return Response::error(404);

        $this->page->set_title('Głosowanie');
        $this->page->breadcrumb_append('Piłkarz meczu', 'matchpick/index');
        $this->page->breadcrumb_append('Głosowanie', 'matchpick/show/'.$pick->id);
        $this->online('Głosowanie', 'matchpick/show/'.$pick->id);

        $this->view = View::make('matchpick.show', array(
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
        if (Request::method() != 'POST' or Request::forged() or empty($_POST['options']) or !is_array($_POST['options']))
            return Response::error(500);

        $pick = new Model\Matchpick;

        if (!$pick->is_init or !$pick->can_vote())
            return Redirect::to('matchpick/index');

        $result = $pick->vote($_POST['options']);

        if ($result)
        {
            $this->notice('Twój głos został pomyślnie oddany');
        }
        else
        {
            $this->notice('Podczas głosowania wystąpił błąd, upewnij się ,że wprowadziłeś wszystkie oceny w formie liczby całkowitej');
        }

        return Redirect::to('matchpick/show/'.$pick->id);
    }

}