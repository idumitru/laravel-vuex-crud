<?php

namespace SoftDreams\LaravelVuexCrud;

class CrudTableDetail
{
	protected $fields;
	
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
	
	public function GetConfig()
	{
		$config = array();
		
		foreach($this->fields as $table_field => $field_data)
		{
			$config[$table_field] = $field_data['field_details']->GetConfig();
		}
		
		return $config;
	}
}