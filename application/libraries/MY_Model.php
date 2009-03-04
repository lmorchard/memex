<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 *
 */
class Model extends Model_Core {

	/**
	 * Loads the database instance, if the database is not already loaded.
	 *
	 * @return  void
	 */
	public function __construct()
	{
		if ( ! is_object($this->db))
		{
			// Load the current database group via config.
            $this->db = Database::instance(
                Kohana::config('model.database')
            );
		}
	}

} // End Model

