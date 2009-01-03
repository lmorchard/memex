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

        $this->view->form = $form = $this->_getForm();

        if (!$this->getRequest()->isPost()) {
            return;
        }

        $post_data = $request->getPost();
        if (!$form->isValid($post_data)) {
            return;
        }

        $data = $form->getValues();

        $db = $this->createDatabase($data, $form);

        var_dump($data); die;

    }

    /**
     * Create database
     */
    public function createDatabase($data, $form)
    {
        try {

            // Attempt to connect to the database using the given credentials.
            $db = Zend_Db::factory('PDO_MYSQL', array(
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
                return;

            } elseif (strpos($msg, 'Unknown database') !== false) {

                // Try connecting without naming the database, since 
                // it's unknown.
                $db = Zend_Db::factory('PDO_MYSQL', array(
                    'host'     => $data['host'],
                    'username' => $data['user_name'],
                    'password' => $data['password'],
                    'dbname'   => ''
                ));
                $conn = $db->getConnection();

                // Issue SQL to create the database and grant privileges.
                $conn->exec("CREATE DATABASE {$data['dbname']}");
                $conn->exec("GRANT ALL PRIVILEGES ON {$data['dbname']}.* " . 
                    "TO {$data['user_name']}");
                $conn->exec("USE {$data['dbname']}");

            } else {

                // Who knows what else happened, so just report the problem.
                $form->setDescription('Database connection failed. ' . 
                    $e->getMessage() );
                return;

            }

        }

        // Now, try loading the schema and issue the individual SQL statements 
        // to build the tables.
        $config     = Zend_Registry::get('config');
        $schema_fn  = $config->database->schema;
        $schema_sql = file_get_contents(APPLICATION_PATH.'/schema/'.$schema_fn);
        $schema_sql_parts = explode(';', $schema_sql);
        foreach ($schema_sql_parts as $part) {
            $part = trim($part);
            if (!$part) continue;
            $conn->exec($part);
        }

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
                'value'       => 'http://' . $_SERVER['HTTP_HOST'] . $request->getBaseUrl(),
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
            ->addElement('text', 'host', array(
                'label'      => 'MySQL Host',
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
            ->addElement('password', 'password', array(
                'label'      => 'Password',
                'value'      => 'memex',
                'required'   => true,
                'filters'    => array('StringTrim'),
                'validators' => array(
                    array('StringLength', false, array(1, 255))
                )
            ))
            ->addDisplayGroup(
                array('host', 'dbname', 'user_name', 'password'), 
                'database',
                array('legend' => 'database')
            )
            ->addElement('submit', 'save', array(
                'label' => 'configure & install'
            ))
            ->addDisplayGroup(
                array('save'), 
                'install',
                array('legend' => 'install')
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
