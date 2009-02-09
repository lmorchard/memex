<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Replacement checkbox element, made more consistent with other elements.
 *
 * @package    OpenInterocitor
 * @author     l.m.orchard@pobox.com
 */
class Form_Checkbox extends Form_Checkbox_Core {

	protected function html_element()
	{
		$data = $this->data;
		return form::checkbox($data);
	}

    public function label($val = NULL)
    {
        return Form_Input::label($val);
    }

}
