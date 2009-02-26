<?php
/**
 * Markdown-based frontend to docs directory.
 */
class Doc_Controller extends Controller 
{
    protected $auto_render = TRUE;

    public function index() 
    {
        $doc_path = join('/', Router::$arguments);
        if (!$doc_path) $doc_path = 'README';

        $root_docs = array( 'README', 'TODO' );

        if (in_array($doc_path, $root_docs)) {
            $path = dirname(APPPATH) . '/' . $doc_path . '.md';
        } else {
            $path = dirname(APPPATH) . '/docs/' . $doc_path . '.md';
        }

        if (!is_file($path)) {
            return Event::run('system.404');
        }
        if (realpath($path) != $path) {
            header('HTTP/1.1 403 Forbidden');
            return;
        }

        require_once 'Markdown.php';
        $this->setViewData(array(
            'doc_path'    => $doc_path,
            'doc_content' => Markdown(file_get_contents($path))
        ));
    }
}
