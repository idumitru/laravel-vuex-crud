<?php

namespace SoftDreams\LaravelVuexCrud;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;

class CrudController extends Controller
{
	protected static $crud_services = [];

	protected static $web_route_path = "";
	protected static $web_route_name = "";

	protected static $router = [];

	public static function HandleRequest()
	{
		static::RegisterMethods();

		$request_units = Input::get('units' , []);
		if(!is_array($request_units) || count($request_units) == 0)
		{
			$apicall_response = array(
				'status' => 'FAILED',
				'reason' => 'Bad request units data'
			);
			return \Response::json($apicall_response);
		}

		$response_contents = [];

		foreach($request_units as $request_unit)
		{
			$found_service = 0;
			foreach(static::$router as $service => $service_data)
			{
				if($service == $request_unit['service'])
				{
					$found_service = 1;

					$found_route = 0;
					foreach($service_data['routes'] as $route)
					{
						if($route['method'] == $request_unit['method'])
						{
							$found_route = 1;
							if(!method_exists($service_data['class'] , $route['function']))
							{
								$apicall_response = array(
									'status' => 'FAILED',
									'reason' => 'Unknown service resolver ' . $service . '::' . $route['function']
								);
								return \Response::json($apicall_response);
							}

							$action_response = $service_data['class']::HandleRequest($route['function'] , $request_unit['data']);

							$response_contents[] = [
								'unit' => $request_unit['unit'],
								'unit_response' => $action_response
							];

							break;
						}
					}

					if($found_route == 0)
					{
						$apicall_response = array(
							'status' => 'FAILED',
							'reason' => 'Unknown method'
						);
						return \Response::json($apicall_response);
					}

					break;
				}
			}

			if($found_service == 0)
			{
				$apicall_response = array(
					'status' => 'FAILED',
					'reason' => 'Unknown service'
				);
				return \Response::json($apicall_response);
			}
		}

		$apicall_response = array(
			'status' => 'OK',
			'response_data' => $response_contents
		);

		return \Response::json($apicall_response);
	}

	public static function RegisterMethods()
	{
		foreach(static::$crud_services as $crud_service)
		{
			if(isset(static::$router[$crud_service::$service_name]))
			{
				throw(mew \Exception('Crud service ' . $crud_service::$service_name . ' already defined'));
			}
			static::$router[$crud_service::$service_name] = [
				'class' => $crud_service,
				'routes' => []
			];

			$crud_extra_methods = $crud_service::GetExtraMethods();
			static::AddRoutes($crud_service::$service_name , $crud_extra_methods);

			$crud_default_methods = $crud_service::GetDefaultMethods();
			static::AddRoutes($crud_service::$service_name , $crud_default_methods);
		}
	}

	public static function AddRoutes($service_name , $methods)
	{
		foreach($methods as $method)
		{
			if(is_array($method))
			{
				if(isset($method['method']))
				{
					static::$router[$service_name]['routes'][] = [
						'method' => $method['method'],
						'function' => $method['function'],
					];
				}
				else
				{
					static::$router[$service_name]['routes'][] = [
						'method' => $method[0],
						'function' => $method[1],
					];
				}
			}
			else
			{
				static::$router[$service_name]['routes'][] = [
					'method' => $method,
					'function' => $method,
				];
			}
		}
	}

	public static function GetWebRoute()
	{
		$web_path = trim(static::$web_route_path);
		if($web_path != '')
		{
			$route_data = array(
				'path' => $web_path,
				'method' => 'post',
				'class' => get_called_class(),
				'route_name' => trim(static::$web_route_name),
				'function' => 'HandleRequest',
			);

			return $route_data;
		}

		return false;
	}
}