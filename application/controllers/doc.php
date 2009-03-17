<?php
/**
 * Markdown-based frontend to docs directory.
 *
 * @author l.m.orchard <l.m.orchard@pobox.com>
 * @package Memex
 */
class Doc_Controller extends Local_Controller 
{
    protected $auto_render = TRUE;

    public function index() 
    {
        $params = $this->getParamsFromRoute(array(
            'path' => 'README'
        ));

        $root_docs = array( 'README', 'TODO' );

        if (in_array($params['path'], $root_docs)) {
            $path = dirname(APPPATH) . '/' . $params['path'] . '.md';
        } else {
            $path = dirname(APPPATH) . '/docs/' . $params['path'] . '.md';
        }

        if (!is_file($path)) {
            return Event::run('system.404');
        }
        if (realpath($path) != $path) {
            header('HTTP/1.1 403 Forbidden');
            return;
        }

        $this->view->set(array(
            'doc_path'    => $params['path'],
            'doc_content' => Markdown(file_get_contents($path))
        ));
    }
}
