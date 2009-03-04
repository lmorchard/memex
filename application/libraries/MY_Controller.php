<?php
/**
 * Custom controller subclass used throughout application
 *
 * @package Memex
 * @author  l.m.orchard@pobox.com
 */
class Controller extends Controller_Core {

    // Wrapper layout for current view
    protected $layout = 'layout';

    // Wrapped view for current method
    protected $view = FALSE;

    // Automatically render the layout?
    protected $auto_render = FALSE;

    // Variables for templates
    protected $view_data;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        switch ($this->input->server('HTTP_X_USE_ENV','production')) {
            case 'testing':
                Kohana::config_set('model.enable_delete_all', true);
                Kohana::config_set('model.database', 'testing');
                break; 
            default:
                // no-op
                break;
        }

        // Start with empty set of view vars.
        $this->view_data = array(
            'auth_profile' => null,
            'profile_home_url' => '',
            'screen_name' => ''
        );

        $this->auth = Memex_Auth::getInstance();

        if ($this->auth->isLoggedIn()) {
            $this->auth_data = $auth_data = $this->auth->getUserData();
            $this->setViewData(array(
                'auth_login'   => $auth_data['login'],
                'auth_profile' => $auth_data['profile']
            ));
        } else {
            $this->auth_data = null;
            $this->setViewData(array(
                'auth_login'   => null,
                'auth_profile' => null
            ));
        }

        // Display the template immediately after the controller method
        Event::add('system.post_controller', array($this, '_display'));
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
     * Set the state of auto rendering at the end of controller method.
     *
     * @param  boolean whether or not to autorender
     * @return object
     */
    public function setAutoRender($state=TRUE)
    {
        $this->auto_render = $state;
        return $this;
    }

    /**
     * Set the name of the wrapper layout to use at rendering time.
     *
     * @param  string name of the layout wrapper view.
     * @return object
     */
    public function setLayout($name)
    {
        $this->layout = $name;
        return $this;
    }

    /**
     * Set the name of the wrapped view to use at rendering time.
     *
     * @param  string name of the view to use during rendering.
     * @return object
     */
    public function setView($name)
    {
        // Prepend path with the controller name, if not already a path.
        if (strpos($name, '/') === FALSE)
            $name = Router::$controller . '/' . $name;
        $this->view = $name;
        return $this;
    }

    /**
     * Sets one or more view variables for layout wrapper and contained view
     *
     * @param  string|array  name of variable or an array of variables
     * @param  mixed         value of the named variable
     * @return object
     */
    public function setViewData($name, $value=NULL)
    {
        if (func_num_args() === 1 AND is_array($name)) {
            // Given an array of variables, merge them into the working set.
            $this->view_data = array_merge($this->view_data, $name);
        } else {
            // Set one variable in the working set.
            $this->view_data[$name] = $value;
        }
        return $this;
    }

    /**
     * Get one or all view variables.
     *
     * @param string  name of the variable to get, or none for all
     * @return mixed
     */
    public function getViewData($name=FALSE, $default=NULL)
    {
        if ($name) {
            return isset($this->view_data[$name]) ?
                $this->view_data[$name] : $default;
        } else {
            return $this->view_data;
        }
    }

    /**
     * Remap routed controller method based on HTTP method and any other 
     * relevant details.  (ie. content-type?)
     *
     * @param
    public function _remap($method, $args)
    {
        // Tweak routed method to include HTTP request method, if named 
        // controller method exists.
        $request_method = strtoupper( $_SERVER['REQUEST_METHOD'] );
        if (method_exists($this, $method.'_'.$request_method) ) {
            $method .= '_' . $request_method;
        }

        // Update the router with tweaked method.
        Router::$method = $method;

        // Set the default view name to controller/method name.
        $this->setView(Router::$controller . '/' . $method);

        // Finally, call the appropriate controller method.
        call_user_func_array(array($this,$method),$args);
    }
     */

    /**
     * Render a template wrapped in the global layout.
     */
    public function _display()
    {
        // Do nothing if auto_render is false at this point.
        if ($this->auto_render) {

            if (FALSE === $this->view) {
                // If no view set, auto-set one based on the controller and method.
                $this->view = Router::$controller . '/' . Router::$method;
            }

            if (!empty($this->layout)) {
                if (!empty($this->view)) { 
                    // If a view is set, render it into the view data for layout.
                    $this->setViewData('content', View::factory(
                        $this->view, $this->getViewData()
                    )->render());
                }
                // Finally, render the layout wrapper to the browser.
                View::factory($this->layout, $this->getViewData())->render(true);
            } else {
                if (!empty($this->view)) { 
                    // No layout wrapper, so try outputting the rendered view.
                    View::factory($this->view, $this->getViewData())->render(true);
                }
            }

        }

    }

}
