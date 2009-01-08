<?php
/**
 * Installation actions
 *
 * @author l.m.orchard <l.m.orchard@pobox.com>
 * @package Memex
 */
class InstallController extends Zend_Controller_Action
{
    public function preDispatch()
    {
        $config = Zend_Registry::get('config');
        if ($config->needs_installation === false) {
            // If the site doesn't need installation, bounce any attempts to 
            // the site root.
            return $this->_helper->redirector->gotoUrl('/', array(
                'prependBase' => true
            ));
        }
    }

    /**
     * Installer index action.
     */
    public function indexAction()
    {
        $request = $this->getRequest();

        // STEP 1: Create the database.
        $this->view->install_step = 1;

        // Throw the form into the view and try getting valid data.
        $this->view->form = $form = $this->_getForm();
        if (!$this->getRequest()->isPost()) {
            return;
        }
        $post_data = $request->getPost();
        if (!$form->isValid($post_data)) {
            return;
        }
        $data = $form->getValues();

        // With valid data in hand, try creating the database.
        $create_result = $this->createDatabase($data, $form);
        if (!$create_result) return;

        // STEP 2: Create the config.
        $this->view->install_step = 2;

        list($db, $stmt_cnt) = $create_result;

        $this->view->stmt_cnt = $stmt_cnt;
        $this->view->base_url = $data['base_url'];

        $config_out = array(
            'site_title' => $data['site_title'],
            'base_url' => $data['base_url'],
            'needs_installation' => false,
            'auth' => array(
                // TODO: Better secret?
                'secret' => md5(uniqid('', true))
            ),
            'form' => array(
                // TODO: Better salt?
                'salt' => md5(uniqid('', true))
            ),
            'database' => array(
                'adapter' => $data['adapter'],
                'params'  => array(
                    'host'     => $data['host'],
                    'dbname'   => $data['dbname'],
                    'username' => $data['user_name'],
                    'password' => $data['password']
                ),
                'profile' => false
            )
        );

        $this->view->config_src = $config_src =
            "<?php return " . var_export($config_out, true) . ";";
        $this->view->config_fn = $config_fn =
            APPLICATION_PATH . '/../config/local.php';

        if (!is_writable($config_fn)) {
            $this->view->config_writable = false;
        } else {
            file_put_contents($config_fn, $config_src);
            $this->view->config_writable = true;
        }

    }

    /**
     * Create database
     */
    public function createDatabase($data, $form)
    {
        try {

            // Attempt to connect to the database using the given credentials.
            $db = Zend_Db::factory($data['adapter'], array(
                'host'     => $data['host'],
                'username' => $data['user_name'],
                'password' => $data['password'],
                'dbname'   => $data['dbname']
            ));
            $conn = $db->getConnection();

        } catch (Zend_Db_Adapter_Exception $e) {
            $msg = $e->getMessage();

            if (strpos($msg, 'Access denied for user') !== false) {

                // Report the login failure.
                $form->setDescription('Database connection failed. ' . 
                    'Incorrect user name or password. ' . $msg);
                return false;

            } elseif (strpos($msg, 'Unknown database') !== false) {

                // Try connecting without naming the database, since 
                // it's unknown.
                $db = Zend_Db::factory($data['adapter'], array(
                    'host'     => $data['host'],
                    'username' => $data['user_name'],
                    'password' => $data['password'],
                    'dbname'   => ''
                ));
                $conn = $db->getConnection();

                // Issue SQL to create the database and grant privileges.
                $db->query("CREATE DATABASE {$data['dbname']}");
                $db->query("GRANT ALL PRIVILEGES ON {$data['dbname']}.* " . 
                    "TO {$data['user_name']}");
                $db->query("USE {$data['dbname']}");

            } else {

                // Who knows what else happened, so just report the problem.
                $form->setDescription('Database connection failed. ' . 
                    $e->getMessage() );
                return false;

            }

        }

        // Now, try loading the schema and issue the individual SQL statements 
        // to build the tables.
        try {
            $config     = Zend_Registry::get('config');
            $schema_fn  = $config->database->schema;
            $schema_sql = file_get_contents(APPLICATION_PATH.'/schema/'.$schema_fn);
            $schema_sql_parts = explode(';', $schema_sql);
            $cnt = 0;
            foreach ($schema_sql_parts as $part) {
                $cnt++;
                $part = trim($part);
                if (!$part) continue;
                $db->query($part);
            }
        } catch (Zend_Db_Adapter_Exception $e) {
            // Who knows what happened, so just report the problem.
            $form->setDescription('Database initialization failed. ' . 
                $e->getMessage() );
            return false;
        }

        return array($db, $cnt);

    }

    /**
     * Build the installation form.
     */
    private function _getForm()
    {
        $request = $this->getRequest();

        $form = new Zend_Form();
        $form->setAttrib('id', 'installation')
            ->setMethod('post')
            ->addElementPrefixPath(
                'Memex_Validate', APPLICATION_PATH . '/models/Validate/', 
                'validate'
            )
            ->addElement('text', 'site_title', array(
                'label'       => 'Site Title',
                'value'       => 'memex',
                'required'    => true,
                'filters'     => array('StringTrim'),
                'validators'  => array(
                    array('StringLength', false, array(1, 64))
                )
            ))
            ->addElement('text', 'base_url', array(
                'label'       => 'Base URL',
                'value'       => 'http://' . $request->getHttpHost() . $request->getBaseUrl(),
                'required'    => true,
                'filters'     => array('StringTrim'),
                'validators'  => array(
                    array('StringLength', false, array(1, 64))
                )
            ))
            ->addDisplayGroup(
                array('site_title', 'base_url'), 
                'application',
                array('legend' => 'application')
            )
            ->addElement('select', 'adapter', array(
                'label'        => 'MySQL Adapter',
                'value'        => 'mysqli',
                'multioptions' => array(
                    'mysqli'    => 'mysqli', 
                    'pdo_mysql' => 'pdo_mysql'
                ),
                'required'     => true,
                'validators'   => array()
            ))
            ->addElement('text', 'host', array(
                'label'      => 'Host',
                'value'      => '127.0.0.1',
                'required'   => true,
                'filters'    => array('StringTrim'),
                'validators' => array(
                    array('Hostname', false, array(Zend_Validate_Hostname::ALLOW_ALL)),
                    array('StringLength', false, array(1, 64))
                )
            ))
            ->addElement('text', 'dbname', array(
                'label'      => 'Database Name',
                'value'      => 'memex',
                'required'   => true,
                'filters'    => array('StringTrim'),
                'validators' => array(
                    array('StringLength', false, array(1, 64))
                )
            ))
            ->addElement('text', 'user_name', array(
                'label'      => 'User Name',
                'value'      => 'memex',
                'required'   => true,
                'filters'    => array('StringTrim'),
                'validators' => array(
                    array('StringLength', false, array(1, 64))
                )
            ))
            ->addElement('text', 'password', array(
                'label'      => 'Password',
                'value'      => 'memex',
                'required'   => true,
                'filters'    => array('StringTrim'),
                'validators' => array(
                    array('StringLength', false, array(1, 255))
                )
            ))
            ->addDisplayGroup(
                array('adapter', 'host', 'dbname', 'user_name', 'password'), 
                'database',
                array('legend' => 'database')
            )
            ->addElement('submit', 'save', array(
                'label' => 'configure & install'
            ))
            ->addDisplayGroup(
                array('save'), 
                'install',
                array('legend' => 'finished')
            )
            ->setDecorators(array(
                'FormElements',
                array('HtmlTag', array('tag' => 'dl', 'class' => 'zend_form')),
                array('Description', array('placement' => 'prepend')),
                'Form'
            ))
            ;

        return $form;
    }

}
