<?php

class VP_Option_Control_Set
{

	private $_menus;

	private $_title;

	private $_page;

	private $_logo;

	public function __construct()
	{
		$this->_menus = array();
	}

	public function render()
	{
		// Setup data
		$data = array('set' => $this);
		return VP_View::get_instance()->load('option/set', $data);
	}

	/**
	 * Get Option Set Title
	 *
	 * @return String Option set title
	 */
	public function get_title() {
	    return $this->_title;
	}
	
	/**
	 * Set Option Set title
	 *
	 * @param String $_title Option set title
	 */
	public function set_title($_title) {
	    $this->_title = $_title;
	    return $this;
	}

	/**
	 * Get page name
	 *
	 * @return String Page name
	 */
	public function get_page() {
	    return $this->_page;
	}
	
	/**
	 * Set page name
	 *
	 * @param String $_page Page name
	 */
	public function set_page($_page) {
	    $this->_page = $_page;
	    return $this;
	}

	/**
	 * Get logo
	 *
	 * @return String Logo URL
	 */
	public function get_logo() {
	    return $this->_logo;
	}
	
	/**
	 * Set logo
	 *
	 * @param String $_logo Logo URL
	 */
	public function set_logo($_logo) {
	    $this->_logo = $_logo;
	    return $this;
	}

	public function add_menu($menu)
	{
		$this->_menus[] = $menu;
	}

	/**
	 * Getter of $_menus
	 *
	 * @return Array Collection of menus object
	 */
	public function get_menus() {
		return $this->_menus;
	}
	
	/**
	 * Setter of $_menus
	 *
	 * @param Array $_menus Collection of menus object
	 */
	public function set_menus($_menus) {
		$this->_menus = $_menus;
		return $this;
	}

	public function get_fields($include_section = false)
	{
		if(!function_exists('loop_controls'))
		{
			function loop_controls($menu, $include_section)
			{
				$fields = array();
				foreach ( $menu->get_controls() as $control )
				{
					if( get_class($control) === 'VP_Option_Control_Group_Section' )
					{
						if($include_section)
						{
							$fields[$control->get_name()] = $control;
						}
						foreach ( $control->get_fields() as $field )
						{
							if( VP_Util_Text::field_type_from_class(get_class($field)) != 'impexp' )
							{
								$fields[$field->get_name()] = $field;
							}
						}
					}
					else
					{
						if( VP_Util_Text::field_type_from_class(get_class($control)) != 'impexp' )
						{
							$fields[$control->get_name()] = $control;
						}
					}
				}
				return $fields;
			}
		}

		$fields = array();

		foreach ( $this->_menus as $menu )
		{
			$submenus = $menu->get_menus();
			if( !empty($submenus) )
			{
				foreach ( $submenus as $submenu )
				{
					$fields = array_merge($fields, loop_controls($submenu, $include_section));
				}
			}
			else
			{
				$fields = array_merge($fields, loop_controls($menu, $include_section));
			}
		}
		return $fields;
	}

	public function get_field($name)
	{
		$fields = $this->get_fields();
		if(array_key_exists($name, $fields))
		{
			return $fields[$name];
		}
		return null;
	}

	public function process_binding()
	{
		$fields = $this->get_fields();

		foreach ($fields as $field)
		{
			if($field instanceof VP_Control_FieldMulti)
			{
				$bind = $field->get_bind();
				if(!empty($bind))
				{
					$bind   = explode('|', $bind);
					$func   = $bind[0];
					$params = $bind[1];
					$params = explode(',', $params);
					$values = array();
					foreach ($params as $param)
					{
						if(array_key_exists($param, $fields))
						{
							$values[] = $fields[$param]->get_value();
						}
					}
					$items  = call_user_func_array($func, $values);
					$field->set_items_from_array($items);
				}
			}
		}
	}

	public function process_dependencies()
	{
		$fields = $this->get_fields(true);

		foreach ($fields as $field)
		{
			$dependency = $field->get_dependency();
			if(!empty($dependency))
			{
				$dependency = explode('|', $dependency);
				$func       = $dependency[0];
				$params     = $dependency[1];
				$params     = explode(',', $params);
				$values     = array();
				foreach ($params as $param)
				{
					if(array_key_exists($param, $fields))
					{
						$values[] = $fields[$param]->get_value();
					}
				}
				$result  = call_user_func_array($func, $values);
				if(!$result)
				{
					$field->is_hidden(true);
				}
			}
		}
	}


	public function normalize_values($opt_arr)
	{
		$fields        = $this->get_fields();
		// fields whose items can be chosen in multiple way
		$multi_classes = array( 'checkbox', 'checkimage', 'multiselect' );

		foreach ($opt_arr as $key => $value)
		{
			if(array_key_exists($key, $fields))
			{
				$type     = VP_Util_text::field_type_from_class(get_class($fields[$key]));
				$is_multi = in_array($type, $multi_classes);
				if( $is_multi and !is_array($value) )
				{
					$opt_arr[$key] = array($value);
				}
				if( !$is_multi and  is_array($value))
				{
					$opt_arr[$key] = '';
				}
			}
		}
		return $opt_arr;
	}

	public function get_defaults()
	{
		$defaults = array();
		$fields   = $this->get_fields();
		foreach ( $fields as $field )
		{
			$defaults[$field->get_name()] = $field->get_default();
		}
		return $defaults;
	}

	public function get_values()
	{
		$values = array();
		$fields = $this->get_fields();
		foreach ( $fields as $field )
		{
			$values[$field->get_name()] = $field->get_value();
		}
		return $values;
	}

	public function save($option_key)
	{
		$opt = $this->get_values();
		if(update_option($option_key, $opt))
		{
			$result['status']  = true;
			$result['message'] = "Saving success.";
		}
		else
		{
			$curr_opt = get_option($option_key, array());
			$changed  = $opt !== $curr_opt;
			if($changed)
			{
				$result['status']  = false;
				$result['message'] = "Saving failed.";
			}
			else
			{
				$result['status']  = true;
				$result['message'] = "No changes made.";
			}
		}
		return $result;
	}

	public function populate_values($opt, $force_update = false)
	{
		$fields        = $this->get_fields();
		$multi_classes = array( 'checkbox', 'checkimage', 'multiselect' );
		foreach ( $fields as $field )
		{
			$type     = VP_Util_text::field_type_from_class(get_class($field));
			$is_multi = in_array($type, $multi_classes);
			
			if( array_key_exists($field->get_name(), $opt) )
			{
				if( $is_multi and is_array($opt[$field->get_name()]) )
				{
					$field->set_value($opt[$field->get_name()]);
				}
				if( !$is_multi and !is_array($opt[$field->get_name()]) )
				{
					$field->set_value($opt[$field->get_name()]);
				}
			}
			else
			{
				if($force_update)
				{
					if($is_multi)
					{
						$field->set_value(array());
					}
					else
					{
						$field->set_value('');
					}
				}
			}
		}
	}

}

/**
 * EOF
 */