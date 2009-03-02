<?php
/**
 * Markdown-based frontend to docs directory.
 *
 * @author l.m.orchard <l.m.orchard@pobox.com>
 * @package Memex
 */
class Doc_Controller extends Controller 
{
    protected $auto_render = TRUE;

    public function index() 
    {
        $params = $this->getParamsFromRoute(array(
            'doc_path' => 'README'
        ));

        $root_docs = array( 'README', 'TODO' );

        if (in_array($params['doc_path'], $root_docs)) {
            $path = dirname(APPPATH) . '/' . $params['doc_path'] . '.md';
        } else {
            $path = dirname(APPPATH) . '/docs/' . $params['doc_path'] . '.md';
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
            'doc_path'    => $params['doc_path'],
            'doc_content' => Markdown(file_get_contents($path))
        ));
    }
}
