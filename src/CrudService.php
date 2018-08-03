<?php

namespace SoftDreams\LaravelVuexCrud;

use App\Company;
use App\Plant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

	protected static function AfterCreate($create_data , &$item)
	{
		return true;
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
		foreach($table_config['fields'] as $field_name => $field_data)
		{
			if($field_data['hidden'] === true || $field_data['db_ignored'] === true)
			{
				continue;
			}

			$fields[] = $field_name;
		}

		try
		{
			if(isset($data['server_filters']))
			{
				$filters = [];
				foreach($data['server_filters'] as $server_filter)
				{
					$filters[] = [
						$server_filter['column'],
						$server_filter['compare'],
						$server_filter['value']
					];
				}

				$filtered_items = $my_model::where($filters)->get();

				$items = [];
				foreach($filtered_items as $filtered_item)
				{
					$items[] = $filtered_item->only($fields);
				}
			}
			else
			{
				$items = $my_model::all($fields)->toArray();
			}
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
		$generate_calls = array();
		$db_ignored = array();
		foreach($table_config['fields'] as $field_name => $field_data)
		{
			if($field_data['generate'] != '')
			{
				$generate_calls[$field_name] = $field_data['generate'];
			}
			if($field_data['db_ignored'] != '')
			{
				$db_ignored[$field_name] = 1;
			}
			if($field_data['primary'] === true || $field_data['hidden'] === true || $field_data['nullable'] === true || $field_data['has_default_value'] === true)
			{
				continue;
			}

			$required_fields[] = $field_name;
		}

		foreach ($required_fields as $required_field)
		{
			if (!isset($data[$required_field]) && !isset($generate_calls[$required_field]))
			{
				$response = [
					'status' => 'FAILED',
					'reason' => $error_tag . 'Missing ' . $required_field . ' field'
				];
				return $response;
			}
		}


		$trim_fields = array();
		foreach($table_config['fields'] as $field_name => $field_data)
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
				if(isset($db_ignored[$field_name]))
				{
					continue;
				}
				$item->$field_name = $field_value;
			}
			foreach($generate_calls as $generate_call)
			{
				static::$generate_call($item);
			}

			DB::beginTransaction();
			$item->save();

			$result = static::AfterCreate($data , $item);
			if($result === true)
			{
				DB::commit();
			}
			else if($result === false)
			{
				DB::rollBack();
				$response = [
					'status' => 'FAILED',
					'reason' => $error_tag . 'CreateItem failed on post create step'
				];
				return $response;
			}
			else
			{
				//response is error string
				$response = [
					'status' => 'FAILED',
					'reason' => $error_tag . 'CreateItem failed on post create step with details: ' . $response
				];
				return $response;
			}
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
			'data' => $item
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

	public static function EditItem($data)
	{
		$error_tag = static::$model_name . ' EditItem - ';

		$my_model = app()->getNamespace() . static::$model_name;

		if (!isset($data['id']))
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'Missing item id field'
			];
			return $response;
		}

		if (!isset($data['columns']))
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'Missing item edit columns field'
			];
			return $response;
		}

		if (count($data['columns']) == 0)
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'No columns to save in edit item id ' . $data['id']
			];
			return $response;
		}

		$table_detail = static::GetCrudTableDetails();
		$table_config = $table_detail->GetConfig();

		$db_ignored = array();
		foreach($table_config['fields'] as $field_name => $field_data)
		{
			if($field_data['db_ignored'] != '')
			{
				$db_ignored[$field_name] = 1;
			}
		}


		try
		{
			$item = $my_model::find($data['id']);
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
				'reason' => $error_tag . 'Could not find database information for item id: ' . $data['id']
			];
			return $response;
		}

		foreach ($data['columns'] as $column_name => $column_value)
		{
			if(isset($db_ignored[$column_name]))
			{
				continue;
			}
			if (!array_key_exists($column_name, $item->getAttributes()))
			{
				$response = [
					'status' => 'FAILED',
					'reason' => $error_tag . 'Column name ' . $column_name . ' does not exist in EditItem'
				];
				return $response;
			}

			$item[$column_name] = $column_value;
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
			'data' => $item
		];

		return $response;
	}
}