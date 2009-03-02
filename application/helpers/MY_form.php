<?php
/**
 * Custom form helpers
 *
 * @package Memex
 * @author  l.m.orchard@pobox.com
 */
class form extends form_Core
{

    /**
     * Build a form from an array of lines.
     *
     * @param string Form URL
     * @param array Form element attributes
     * @param array List of form elements
     */
    public static function build($url, $attrs, $errors, $arr)
    {
        $out = array();

        if (!empty($errors)) {
            $out = array_merge($out, array('<p class="errors highlight">', '<ul>'));
            foreach ($errors as $field=>$error) {
                $out[] = '<li class="'.out::H($field, false).'">'.out::H($error, false).'</li>';
            }
            $out = array_merge($out, array('</ul>', '</p>'));
        }

        $out = array_merge($out, array(
            form::open($url, $attrs),
            join("\n", $arr),
            form::close()
        ));

        return join("\n", $out);
    }

    /**
     * Build a fieldset from an array of lines
     *
     * @param string Fieldset legend
     * @param array  Fieldset element attributes
     * @param array  List of form elements
     */
    public static function fieldset($legend, $attrs, $arr)
    {
        return join("\n", array(
            form::open_fieldset($attrs),
            form::legend($legend),
            html::ul($arr),
            form::close_fieldset()
        ));
    }

    /**
     * Form field as a list element, with label and form field
     *
     * @param string Field type, corresponding form:: helper
     * @param string Field name
     * @param string Field label text
     * @param array  Field attributes
     */
    public static function field($type, $name, $label, $params=null)
    {
        if (null == $params) $params = array();

        if ('checkbox' == $type) {
            $value = form::value($name, @$params['checked']);
        } else {
            $value = form::value($name, @$params['value']);
        }

        if ('hidden' == $type) {
            return form::hidden(array($name => $value));
        } else {

            if ('checkbox' == $type) {
                $field = form::checkbox(
                    array_merge(array('name' => $name, 'class' => $type), $params),
                    @$params['value'], $value, false
                );
            } else {
                $field = call_user_func(
                    array('form', $type), 
                    array('name' => $name, 'class' => $type),
                    $value, '', false
                );
            }

            return join("\n", array(
                '<li ' . html::attributes(array('class' => $type)) .'>',
                ($label != null) ?
                    form::label($name, $label) : 
                    form::label(array('for'=>$name, 'class'=>'hidden'), ''),
                $field,
                '</li>'
            ));
        }
    }

    /**
     * Build a captcha field
     *
     * @param string Field name
     * @param string Field label text
     * @param array  Field attributes
     */
    public static function captcha($name, $label, $params=null)
    {
        if (null == $params) $params = array();

        $value = form::value($name, $params);

        return join("\n", array(
            '<li class="captcha">',
            form::label($name, $label),
            form::input('captcha', $value),
            Captcha::factory()->render(),
            '</li>'
        ));
    }

    /**
     * Attempt to come up with a value for a form field based on POST, GET, and field 
     * parameters
     *
     * @param  string Field name
     * @param  array  Field parameters
     * @return string
     */
    public static function value($name, $default=null)
    {
        if (!empty($_POST[$name]))
            $value = $_POST[$name];
        else if (!empty($_GET[$name]))
            $value = $_GET[$name];
        else if (!empty($default))
            $value = $default;
        else
            $value = '';
        return $value;
    }

	/**
	 * Creates an HTML form input tag. Defaults to a text type.
	 *
	 * @param   string|array  input name or an array of HTML attributes
	 * @param   string        input value, when using a name
	 * @param   string        a string to be attached to the end of the attributes
	 * @param   boolean       encode existing entities
	 * @return  string
	 */
	public static function input($data, $value = '', $extra = '', $double_encode = TRUE )
	{
		if ( ! is_array($data))
		{
			$data = array('name' => $data);
		}

		// Type and value are required attributes
		$data += array
		(
			'type'  => 'text',
			'value' => $value
		);

		return '<input'.form::attributes($data).' '.$extra.' />';
	}

}
