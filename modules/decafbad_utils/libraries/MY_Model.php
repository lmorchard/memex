<?php
/**
 * Model class that consults config for which database to use.
 *
 * @package    DecafbadUtils
 * @subpackage libraries
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Model extends Model_Core {

	public function __construct()
	{
		if (!is_object($this->db)) {
            $this->db = Database::instance(
                Kohana::config('model.database')
            );
		}
	}

}
