<?php

namespace SoftDreams\LaravelVuexCrud;

use App\Http\Controllers\Controller;

class CrudController extends Controller
{
	protected $crud_services = [];

	protected $router = [];


	public function __construct()
	{
		$this->RegisterMethods();
	}

	public function RegisterMethods()
	{
		foreach($this->crud_services as $crud_service)
		{
			if(isset($router[$crud_service::$service_name]))
			{
				throw(mew \Exception('Crud service ' . $crud_service::$service_name . ' already defined'));
			}

			$this->router[$crud_service::$service_name] = [];


			$crud_default_methods = $crud_service::GetDefaultMethods();
			$this->AddRoutes($crud_service::$service_name , $crud_default_methods);

			$crud_extra_methods = $crud_service::GetExtraMethods();
			$this->AddRoutes($crud_service::$service_name , $crud_extra_methods);
		}
	}

	public function AddRoutes($service_name , $methods)
	{
		foreach($methods as $method)
		{
			if(is_array($method))
			{
				$this->router[$service_name][] = [
					'method' => $method['method'],
					'function' => $method['function'],
				];
			}
			else
			{
				$this->router[$service_name][] = [
					'method' => $method,
					'function' => $method,
				];
			}
		}
	}
}