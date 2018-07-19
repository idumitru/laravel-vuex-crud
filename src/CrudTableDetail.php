<?php

namespace SoftDreams\LaravelVuexCrud;

class CrudTableDetail
{
	protected $fields;

	protected $filter_change_update = false;
	protected $filter_change_update_vars = [];
	protected $has_trigger_filter = false;
	protected $triger_filters = [];


	public function __construct()
	{
		$this->fields = array();
		return $this;
	}
	
	public function field($name)
	{
		if(!isset($this->fields[$name]))
		{
			$table_field = new CrudTableField($name , $this);
			$this->fields[$name] = array(
				'field_details' => $table_field
			);

			return $table_field;
		}
	}

	public function onFilterChangeUpdate($tag , $getter)
	{
		$this->filter_change_update = true;
		$this->filter_change_update_vars[] = [
			'tag' => $tag,
			'getter' => $getter,
		];

		return $this;
	}

	public function triggerFilter($type , $compare , $data_getter)
	{
		$this->has_trigger_filter = true;
		$this->triger_filters[] = [
			'type' => $type,
			'compare' => $compare,
			'data_getter' => $data_getter
		];
		return $this;
	}

	public function GetConfig()
	{
		$config = [
			'table' => [],
			'fields' => []
		];

		$config['table']['filter_change_update'] = $this->filter_change_update;
		$config['table']['filter_change_update_vars'] = $this->filter_change_update_vars;
		$config['table']['has_trigger_filter'] = $this->has_trigger_filter;
		$config['table']['triger_filters'] = $this->triger_filters;

		foreach($this->fields as $table_field => $field_data)
		{
			$config['fields'][$table_field] = $field_data['field_details']->GetConfig();
		}
		
		return $config;
	}
}