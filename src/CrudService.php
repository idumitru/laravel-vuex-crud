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

	protected static $default_methods = [
		'Create',
		'Fetch',
		'Delete',
		'Edit'
	];

	protected static $extra_methods = [];

	public static function GetDefaultMethods()
	{
		return self::$default_methods;
	}

	public static function GetExtraMethods()
	{
		return self::$extra_methods;
	}

	public static function HandleRequest($service, $method, $data)
	{
		if (!in_array($method, self::$accepted_methods))
		{
			$response = [
				'status' => 'FAILED',
				'reason' => 'Bad method'
			];
			return $response;
		}
	}

	public static function Fetch($model, $fields, $create_info, $fetch_data, $error_tag)
	{
		if (!isset($fetch_data['index_start']))
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'Missing index start field'
			];
			return $response;
		}

		if (!isset($fetch_data['items_count']))
		{
			$response = [
				'status' => 'FAILED',
				'reason' => $error_tag . 'Missing items count field'
			];
			return $response;
		}

		try
		{
			$items = $model::all($fields)->toArray();
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

	public static function Create($model, $fields, $create_info, $create_data, $error_tag)
	{
		if (isset($create_info['required']))
		{
			foreach ($create_info['required'] as $required_field)
			{
				if (!isset($create_data[$required_field]))
				{
					$response = [
						'status' => 'FAILED',
						'reason' => $error_tag . 'Missing ' . $required_field . ' field'
					];
					return $response;
				}
			}
		}

		if (isset($create_info['not_empty']))
		{
			foreach ($create_info['not_empty'] as $required_field)
			{
				if (trim($create_data[$required_field]) == '')
				{
					$response = [
						'status' => 'FAILED',
						'reason' => $error_tag . $required_field . ' should not be empty'
					];
					return $response;
				}
			}
		}

		if (isset($create_info['trim_fields']))
		{
			foreach ($create_info['trim_fields'] as $trim_field)
			{
				$create_data[$trim_field] = trim($create_data[$trim_field]);
			}
		}

		if (!empty($create_info['where']))
		{
			$where_clause = [];
			foreach ($create_info['where'] as $where_rule)
			{
				if (count($where_rule) == 1)
				{
					$where_clause[] = [$where_rule[0], $create_data[$where_rule[0]]];
				}
				else if (count($where_rule) == 2)
				{
					$where_clause[] = [$where_rule[0], $where_rule[1], $create_data[$where_rule[0]]];
				}
				else if (count($where_rule) == 3)
				{
					$where_clause[] = [$where_rule[0], $where_rule[1], $where_rule[2]];
				}
			}

			if (count($where_clause) > 0)
			{
				try
				{
					$exists = $model::where($where_clause)->count();
				} catch (\Exception $e)
				{
					$response = [
						'status' => 'FAILED',
						'reason' => $error_tag . 'Error retrieving database data in CreateItem: ' . $e->getMessage()
					];
					return $response;
				}

				if ($exists > 0)
				{
					$response = [
						'status' => 'FAILED',
						'reason' => $error_tag . 'Item is a duplicate'
					];
					return $response;
				}
			}
		}

		try
		{
			$item = new $model;
			foreach ($create_data as $field_name => $field_value)
			{
				if (isset($create_info['ignore_columns']) && count($create_info['ignore_columns']) > 0 && in_array($field_name, $create_info['ignore_columns']))
				{
					continue;
				}
				$item->$field_name = $field_value;
			}
			if (isset($create_info['special_fields']))
			{
				foreach ($create_info['special_fields'] as $field_name => $field_value)
				{
					$item->$field_name = $field_value;
				}
			}

			if (isset($create_info['additional_transactions']) && count($create_info['additional_transactions']) > 0)
			{
				\DB::beginTransaction();
			}
			$item->save();

			if (isset($create_info['additional_transactions']) && count($create_info['additional_transactions']) > 0)
			{
				$success = TRUE;
				$transaction_result = '';
				foreach ($create_info['additional_transactions'] as $transaction_method)
				{
					$transaction_result = self::$transaction_method($create_data, $item->id);
					if ($transaction_result === FALSE)
					{
						$success = FALSE;
						break;
					}
				}

				if ($success === TRUE)
				{
					\DB::commit();
				}
				else
				{
					\DB::rollBack();
					throw new \Exception("Something went wrong (" . $transaction_result . ")");
				}
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
			'data' => $item->toArray()
		];
		return $response;
	}

	public static function Delete($model, $fields, $create_info, $delete_data, $error_tag)
	{
		try
		{
			$model::destroy($delete_data);
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

	public static function Edit($model, $fields, $create_info, $edit_data, $error_tag)
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