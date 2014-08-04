<?php
namespace Ionic;

/**
 * Twig view
 *
 * @package Ionic
 * @author  Wrexdot <wrexdot@gmail.com>
 */
class View extends \Laravel\View {

    /**
     * Create a new view instance.
     *
     * <code>
     * 		// Create a new view instance
     * 		$view = new View('home.index');
     *
     * 		// Create a new view instance of a bundle's view
     * 		$view = new View('admin::home.index');
     *
     * 		// Create a new view instance with bound data
     * 		$view = new View('home.index', array('name' => 'Taylor'));
     * </code>
     *
     * @param  string  $view
     * @param  array   $data
     * @return void
     */
    public function __construct($view, $data = array())
    {
        $this->view = $view;
        $this->data = $data;

        // In order to allow developers to load views outside of the normal loading
        // conventions, we'll allow for a raw path to be given in place of the
        // typical view name, giving total freedom on view loading.
        if (starts_with($view, 'path: '))
        {
            $this->path = substr($view, 6);
        }
        else
        {
            $this->path = str_replace('.', DS, $view).'.twig';
        }

        // If a session driver has been specified, we will bind an instance of the
        // validation error message container to every view. If an error instance
        // exists in the session, we will use that instance.
        if (!isset($this->data['errors']))
        {
            if (\Session::started() and \Session::has('errors'))
            {
                $this->data['errors'] = \Session::get('errors');
            }
            else
            {
                $this->data['errors'] = new \Laravel\Messages;
            }
        }
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @return string
     */
    public function get()
    {
        try {
            return \IoC::resolve('twig')->render($this->path, $this->data());
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Get the evaluated string content of the view.
     *
     * @return string
     */
    public function render()
    {
        return $this->get();
    }

}