<?php

/**
 * Submit content
 *
 * @author     Wrexdot <wrexdot@gmail.com>
 * @package    Ionic
 * @subpackage Controllers
 */
class Submit_Controller extends Base_Controller {

    /**
     * News
     *
     * @return Responses
     */
    public function action_news()
    {
        if ($this->require_auth())
            return Redirect::to('index');

        if (Auth::banned())
        {
            $this->notice('Zablokowani użytkownicy nie mogą dodawać oraz edytować treści w serwisie');
            return Redirect::to('index');
        }

        if (Request::method() == 'POST' and !Request::forged())
        {
            $title = Input::get('title');
            $content = Input::get('content');

            if (empty($title) or empty($content))
            {
                $this->notice('Wszystkie pola są wymagane');
                return Redirect::to('submit/news')->with_input('only', array('title', 'content'));
            }

            DB::table('submitted_content')->insert(array(
                'user_id'    => $this->user->id,
                'content'    => Model\Blog::prepare_content($content),
                'title'      => HTML::specialchars($title),
                'created_at' => date('Y-m-d H:i:s'),
                'type'       => 'news'
            ));

            $this->notice('News został wysłany pomyślnie');

            return Redirect::to('submit/news');
        }

        $this->page->set_title('Zaproponuj news');
        $this->online('Zaproponuj news', 'submit/news');
        $this->page->breadcrumb_append('Newsy', 'news/index');
        $this->page->breadcrumb_append('Zaproponuj news', 'submit/news');

        Asset::add('markitup', 'public/js/jquery.markitup.js', 'jquery');
        Asset::add('markitup', 'public/js/skins/simple/style.css');

        $this->view = View::make('submit.news', array(
                    'old' => Input::old()
                ));
    }

}