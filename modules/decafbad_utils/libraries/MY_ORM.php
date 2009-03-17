<?php
/**
 * ORM class that consults config for which database to use.
 *
 * @package    DecafbadUtils
 * @subpackage libraries
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class ORM extends ORM_Core {

	public function __initialize()
	{
		if (!is_object($this->db)) {
            $this->db = Database::instance(
                Kohana::config('model.database')
            );
        }
        parent::__initialize();
	}

}
