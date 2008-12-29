<?php
require_once 'markdown.php';

/**
 * Markdown-based frontend to docs directory.
 */
class DocController extends Zend_Controller_Action 
{
    public function indexAction() 
    {
        $request = $this->getRequest();

        $doc_path = $request->getParam('path');
        if (!$doc_path) $doc_path = 'README';

        $root_docs = array( 'README', 'TODO' );

        if (in_array($doc_path, $root_docs)) {
            $path = dirname(APPLICATION_PATH) . '/' . $doc_path . '.md';
        } else {
            $path = dirname(APPLICATION_PATH) . '/docs/' . $doc_path . '.md';
        }

        if (!is_file($path))
            throw new Zend_Exception('Not Found', 404);
        if (realpath($path) != $path)
            throw new Zend_Exception('Forbidden ' . realpath($path) . ' != ' . $path, 403);

        $this->view->doc_path = 
            $doc_path;
        $this->view->doc_content = 
            Markdown(file_get_contents($path));
    }
}
