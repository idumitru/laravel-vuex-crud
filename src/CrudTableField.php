<?php

namespace SoftDreams\LaravelVuexCrud;

class CrudTableField
{
	protected $field_name;
	protected $primary = false;
	protected $hidden = false;
	protected $nullable = false;
	protected $can_be_0 = false;
	protected $default_value = '';
	protected $unique = false;
	protected $field_type = '';
	protected $has_relation = false;
	protected $relation = [];
	protected $is_dropdown = false;
	protected $dropdown_options = [];
	protected $trim = false;
	protected $is_filter = false;
	protected $filter_compare = '';
	protected $filter_source = '';
	protected $with_filters = '';
	protected $hide_for_create = false;
	protected $generate = '';
	protected $db_ignored = false;

	/**
	 * @var CrudTableDetail;
	 */
	protected $table;

	public function __construct($my_field_name , $parent_table)
	{
		$this->field_name = $my_field_name;
		$this->table = $parent_table;
		return $this;
	}

	public function GetConfig()
	{
		$config = array();
		$config['primary'] = $this->primary;
		$config['hidden'] = $this->hidden;
		$config['nullable'] = $this->nullable;
		$config['can_be_0'] = $this->can_be_0;
		$config['default_value'] = $this->default_value;
		$config['unique'] = $this->unique;
		$config['field_type'] = $this->field_type;
		$config['has_relation'] = $this->has_relation;
		$config['relation'] = $this->relation;
		$config['is_dropdown'] = $this->is_dropdown;
		$config['dropdown_options'] = $this->dropdown_options;
		$config['trim'] = $this->trim;
		$config['is_filter'] = $this->is_filter;
		$config['filter_compare'] = $this->filter_compare;
		$config['filter_source'] = $this->filter_source;
		$config['with_filters'] = $this->with_filters;
		$config['hide_for_create'] = $this->hide_for_create;
		$config['generate'] = $this->generate;
		$config['db_ignored'] = $this->db_ignored;

		return $config;
	}

	public function primary()
	{
		$this->primary = true;
		return $this;
	}

	public function filter($compare , $source)
	{
		$this->is_filter = true;
		$this->filter_compare = $compare;
		$this->filter_source = $source;
		return $this;
	}

	public function hide()
	{
		$this->hidden = true;
		return $this;
	}

	public function hide_for_create()
	{
		$this->hide_for_create = true;
		return $this;
	}

	public function generate($generate_function)
	{
		$this->generate = trim($generate_function);
		return $this;
	}

	public function nullable()
	{
		$this->nullable = true;
		return $this;
	}

	public function can_be_0()
	{
		$this->can_be_0 = true;
		return $this;
	}

	public function set_default($value)
	{
		$this->default_value = $value;
		return $this;
	}

	public function unique()
	{
		$this->unique = true;
		return $this;
	}

	public function type_date()
	{
		$this->field_type = 'date';
		return $this;
	}

	public function type_datetime()
	{
		$this->field_type = 'datetime';
		return $this;
	}

	public function type_timestamp()
	{
		$this->field_type = 'timestamp';
		return $this;
	}

	public function type_text()
	{
		$this->field_type = 'text';
		return $this;
	}

	public function type_string()
	{
		$this->field_type = 'string';
		return $this;
	}

	public function type_number()
	{
		$this->field_type = 'number';
		return $this;
	}

	public function type_smart_input()
	{
		$this->field_type = 'smart_input';
		return $this;
	}

	public function relation($vuex_module , $match_column , $match_value)
	{
		$this->has_relation = true;
		$this->relation = array(
			'vuex_module' => $vuex_module,
			'match_column' => $match_column,
			'match_value' => $match_value,
		);
		return $this;
	}

	public function with_filters($crud_service)
	{
		$this->with_filters = trim($crud_service);
		return $this;
	}

	public function dropdown($options)
	{
		$this->is_dropdown = true;
		$this->dropdown_options = $options;
		return $this;
	}

	public function db_ignore()
	{
		$this->db_ignored = true;
		return $this;
	}

	public function trim()
	{
		$this->trim = true;
		return $this;
	}
}