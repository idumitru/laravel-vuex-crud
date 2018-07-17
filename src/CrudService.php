<?php

namespace SoftDreams\LaravelVuexCrud;

use App\Company;
use App\Plant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class CrudService
{
	public static $service_name = 'default';
	public static $model_name = '';

	protected static $default_methods = [
		'CreateItem',
		'FetchItems',
		'DeleteItem',
		'EditItem'
	];

	protected static $extra_methods = [];

	public static function GetDefaultMethods()
	{
		return static::$default_methods;
	}

	public static function GetExtraMethods()
	{
		return static::$extra_methods;
	}

	public static function HandleRequest($method, $data)
	{
		return static::$method($data);
	}

	public static function GetCrudTableDetails()
	{
		$table = new CrudTableDetail();
		return $table;
	}

	public static function FetchItems($data)
	{
		$error_tag = static::$model_name . ' FetchItems - ';
		
		if (!isset($data['index_start']))
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'Missing index start field'
			];
			return $response;
		}

		if (!isset($data['items_count']))
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'Missing items count field'
			];
			return $response;
		}

		$my_model = app()->getNamespace() . static::$model_name;

		$table_detail = static::GetCrudTableDetails();
		$table_config = $table_detail->GetConfig();

		$fields = array();
		foreach($table_config as $field_name => $field_data)
		{
			if($field_data['hidden'] === true)
			{
				continue;
			}

			$fields[] = $field_name;
		}

		try
		{
			$items = $my_model::all($fields)->toArray();
		} catch (\Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . $e->getMessage()
			];
			return $response;
		}

		$response = [
			'status' => 'OK',
			'data' => $items
		];
		return $response;

	}

	public static function CreateItem($data)
	{
		$error_tag = static::$model_name . ' CreateItem - ';

		$my_model = app()->getNamespace() . static::$model_name;

		$table_detail = static::GetCrudTableDetails();
		$table_config = $table_detail->GetConfig();

		$required_fields = array();
		foreach($table_config as $field_name => $field_data)
		{
			if($field_data['primary'] === true || $field_data['hidden'] === true || $field_data['nullable'] === true)
			{
				continue;
			}

			$required_fields[] = $field_name;
		}

		foreach ($required_fields as $required_field)
		{
			if (!isset($data[$required_field]))
			{
				$response = [
					'status' => 'FAILED',
					'reason' => $error_tag . 'Missing ' . $required_field . ' field'
				];
				return $response;
			}
		}


		$trim_fields = array();
		foreach($table_config as $field_name => $field_data)
		{
			if($field_data['trim'] !== true)
			{
				continue;
			}

			$trim_fields[] = $field_name;
		}

		foreach ($trim_fields as $trim_field)
		{
			$data[$trim_field] = trim($data[$trim_field]);
		}

//		if (!empty($create_info['where']))
//		{
//			$where_clause = [];
//			foreach ($create_info['where'] as $where_rule)
//			{
//				if (count($where_rule) == 1)
//				{
//					$where_clause[] = [$where_rule[0], $create_data[$where_rule[0]]];
//				}
//				else if (count($where_rule) == 2)
//				{
//					$where_clause[] = [$where_rule[0], $where_rule[1], $create_data[$where_rule[0]]];
//				}
//				else if (count($where_rule) == 3)
//				{
//					$where_clause[] = [$where_rule[0], $where_rule[1], $where_rule[2]];
//				}
//			}
//
//			if (count($where_clause) > 0)
//			{
//				try
//				{
//					$exists = $model::where($where_clause)->count();
//				} catch (\Exception $e)
//				{
//					$response = [
//						'status' => 'FAILED',
//						'reason' => $error_tag . 'Error retrieving database data in CreateItem: ' . $e->getMessage()
//					];
//					return $response;
//				}
//
//				if ($exists > 0)
//				{
//					$response = [
//						'status' => 'FAILED',
//						'reason' => $error_tag . 'Item is a duplicate'
//					];
//					return $response;
//				}
//			}
//		}

		try
		{
			$item = new $my_model;
			foreach ($data as $field_name => $field_value)
			{
				$item->$field_name = $field_value;
			}
			$item->save();
		} catch (\Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'CreateItem failed on database: ' . $e->getMessage()
			];
			return $response;
		}

		$response = [
			'status' => 'OK',
			'data' => $item->toArray()
		];
		return $response;
	}

	public static function DeleteItem($data)
	{
		$error_tag = static::$model_name . ' FetchItems - ';
		$my_model = app()->getNamespace() . static::$model_name;

		try
		{
			$my_model::destroy($data);
		} catch (\Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'DeleteItem failed on database: ' . $e->getMessage()
			];
			return $response;
		}

		$response = [
			'status' => 'OK',
			'data' => ''
		];
		return $response;
	}

	public static function EditItem($model, $fields, $create_info, $edit_data, $error_tag)
	{
		if (!isset($edit_data['id']))
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'Missing item id field'
			];
			return $response;
		}

		if (!isset($edit_data['columns']))
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'Missing item edit columns field'
			];
			return $response;
		}

		if (count($edit_data['columns']) == 0)
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'No columns to save in edit item id ' . $edit_data['id']
			];
			return $response;
		}

		try
		{
			$item = $model::find($edit_data['id']);
		} catch (\Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'Error retrieving database data in EditItem: ' . $e->getMessage()
			];
			return $response;
		}

		if ($item->count() == 0)
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'Could not find database information for item id: ' . $edit_data['id']
			];
			return $response;
		}

		foreach ($edit_data['columns'] as $column)
		{
			if (!array_key_exists($column['column_name'], $item->getAttributes()))
			{
				$response = [
					'status' => 'FAILED',
					'reason' => $error_tag . 'Column name ' . $column['column_name'] . ' does not exist in EditItem'
				];
				return $response;
			}

			$item[$column['column_name']] = $column['column_value'];
		}

		try
		{
			$item->save();
		} catch (\Exception $e)
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'Error saving database data in EditItem: ' . $e->getMessage()
			];
			return $response;
		}

		$response = [
			'status' => 'OK',
			'data' => ''
		];

		return $response;
	}
}