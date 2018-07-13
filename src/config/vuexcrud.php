<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Deployment sections
	|--------------------------------------------------------------------------
	|
	| A section describes the locations and namespaces for the generated files
	| inside the main 'app' folder. The 'default structure would be as follows
	|  -app
	|      |- Http
	|      |      |- Controllers - ExampleApiController.php
	|      |- Services
	|                 |- CrudServices - ExampleCrudService.php
	|
	*/

	'sections' => [
		'default' => [
			'controller_folder' => '/Http/Controllers',
			'controller_namespace' => 'App\\Http\\Controllers',
			'crudservice_folder' => '/Services/CrudServices',
			'crudservice_namespace' => 'App\\Services\\CrudServices',
			'vue_root' => '/resources/assets/js/vuexcrud',
			'vue_common' => '/resources/assets/js/vuexcrud/vue_common',
			'vue_tree' => [
				'components' => 'components',
				'controllers' => 'controllers',
				'layouts' => 'layouts',
				'pages' => 'pages',
				'store' => 'store',
			]
		],

		/*
		'admin' => [
			'controller_folder' => '/Http/Controllers/Admin',
			'controller_namespace' => 'App\\Http\\Controllers\\Admin',
			'crudservice_folder' => 'Services/CrudServices/Admin',
			'crudservice_namespace' => 'App\\Http\\CrudServices\\Admin'
		],

		*/
	]
];