<?php
/**
 *
 * @package    DecafbadUtils
 * @subpackage controllers
 * @author     l.m.orchard@pobox.com
 */
class Controller extends Controller_Core {

    // Wrapper layout for current view
    protected $layout = NULL;

    // Wrapped view for current method
    protected $view = NULL;

    // Automatically render the layout?
    protected $auto_render = FALSE;

    /**
     * Constructor, sets up the layout and core views, as well as registering 
     * the display handler
     */
    public function __construct()
    {
        parent::__construct();

        $this->layout = View::factory();
        $this->view   = View::factory();

        // Register the final display handler.
        Event::add('system.post_controller', array($this, '_display'));
    }

    /**
     * Perform model-based form POST data validation.
     *
     * @param  string / Model   name of model or model instance
     * @param  string / array   name of model validation method, or callback array
     * @param  string           name of the error messages file
     * @return Validation       validation object with data, or null on validation failure
     */
    public function validate_form($model, $callback, $errors, $require_post=true) {
        
        if ($require_post && 'post' != request::method()) {
            return;
        }
        
        if (!is_object($model)) {
            $cls = ucfirst($model).'_Model';
            $model = new $cls();
        }
        
        if (is_string($callback)) {
            $callback = array($model, $callback);
        }

        $form_data = ('post' == request::method()) ? 
            $this->input->post() : $this->input->get();
        
        $is_valid = call_user_func_array($callback, array(&$form_data));

        if (!$is_valid) {
            $this->view->form_errors = $form_data->errors($errors);
            return null;
        }

        return $form_data;
    }

    /**
     * Convert the arguments in the route to name/value parameters.
     *
     * @return array Parameters based on current route.
     */
    public function getParamsFromRoute($defaults=null)
    {
        $args = Router::$arguments;
        $params = empty($defaults) ? array() : $defaults;
        while (!empty($args)) {
            $name = array_shift($args);
            if ('tags' == $name || 'path' == $name) {
                $params[$name] = join('/', $args);
                break;
            } else {
                $params[$name] = array_shift($args);
            }
        }
        return $params;
    }

    /**
     * Render a template wrapped in the global layout.
     */
    public function _display()
    {
        if (TRUE === $this->auto_render) {

            Event::run('DecafbadUtils.layout.before_auto_render', $this);

            if ($this->layout && !$this->layout->get_filename()) {
                // If no filename set for layout, use "layout"
                $this->layout->set_filename('layout');
            }

            if ($this->view && !$this->view->get_filename()) {
                // If no view filename set, use controller/method by default.
                $this->view->set_filename(
                    Router::$controller . '/' . Router::$method
                );
            }

            if (!empty($this->view) && !empty($this->layout)) {
                // Render the core view as a var inside layout, then render layout.
                $this->layout
                    ->set('content', $this->view->render())
                    ->render(true);
            } else if (!empty($this->layout)) {
                // Only render the layout, since core view emptied.
                $this->layout->render(true);
            } else if (!empty($this->view)) {
                // Only render the core view, since the layout emptied.
                $this->view->render(true);
            }

            Event::run('DecafbadUtils.layout.auto_rendered', $this);

        }
    }

}
