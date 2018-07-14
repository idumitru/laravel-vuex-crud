<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use SoftDreams\LaravelVuexCrud\Traits\CrudServiceGeneratorFunctions;

class CrudVueAppCommand extends Command
{
	use DetectsApplicationNamespace;
	use CrudServiceGeneratorFunctions;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = 'vuexcrud:vue:make:app {name} {section=default}';

	protected $my_folder_name = 'example';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new vue folder structure';

	public function handle()
	{
		$this->my_folder_name = strtolower($this->argument('name'));

		if(strpos($this->my_folder_name , "/") !== FALSE || strpos($this->my_folder_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in app name');
		}

		$this->crud_section = $this->argument('section');

		if(!app()['config']["vuexcrud.sections." . $this->crud_section])
		{
			return $this->error('Configuration section "' . $this->crud_section . '" does not exists!');
		}

		$this->runGenerator();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function runGenerator()
	{
		$vue_root_folder		= 'vue_root';

		if ($this->files->exists($path = $this->getVuePath('vue_root' , $this->crud_section) . '/' . $this->my_folder_name)) {
			return $this->error($path . ' already exists!');
		}

		$this->createDirectory($path);

		if (!$this->files->exists($common_path = $this->getVuePath('vue_common' , $this->crud_section))) {
			$this->createDirectory($common_path);
		}

		$this->createDirectory($this->getVueTreePath($path ,'components' , $this->crud_section));
		$this->createDirectory($this->getVueTreePath($path ,'controllers' , $this->crud_section));
		$this->createDirectory($this->getVueTreePath($path ,'layouts' , $this->crud_section));
		$this->createDirectory($this->getVueTreePath($path ,'pages' , $this->crud_section));

		$vuex_store_path = $this->getVueTreePath($path ,'store' , $this->crud_section);
		$this->createDirectory($vuex_store_path);

		$bootstrap_js_path = $path . '/' . 'bootstrap.js';
		$this->files->put($bootstrap_js_path, $this->files->get(__DIR__ . '/../stubs/bootstrapjs.stub'));

		$routes_js_path = $path . '/' . $this->my_folder_name . 'routes.js';
		$this->files->put($routes_js_path, $this->compileRoutesJs());

		$app_js_path = $path . '/' . $this->my_folder_name . 'app.js';
		$this->files->put($app_js_path, $this->compileAppJs());

		$app_router_path = $path . '/' . $this->my_folder_name . 'router.vue';
		$this->files->put($app_router_path, $this->files->get(__DIR__ . '/../stubs/vue_base_router.stub'));

		$vuex_index_path = $vuex_store_path . '/index.js';
		$this->files->put($vuex_index_path, $this->files->get(__DIR__ . '/../stubs/vuex_index.stub'));

		$vuex_helpers_path = $vuex_store_path . '/helpers';
		$this->createDirectory($vuex_helpers_path);

		$vuex_crudjs_path = $vuex_helpers_path . '/crud.js';
		$this->files->put($vuex_crudjs_path, $this->files->get(__DIR__ . '/../stubs/vuex_crudjs.stub'));

		$vuex_modules_path = $vuex_store_path . '/modules';
		$this->createDirectory($vuex_modules_path);

		$vuex_apicomm_path = $vuex_modules_path . '/apicomm.js';
		$this->files->put($vuex_apicomm_path, $this->files->get(__DIR__ . '/../stubs/vuex_apicomm.stub'));

		$this->info('Vue tree for ' . $this->my_folder_name . ' created successfully in ' . $path);
	}

	protected function compileRoutesJs()
	{
		$stub = $this->files->get(__DIR__ . '/../stubs/routesjs.stub');

		$stub = str_replace('{{routes_name}}', $this->my_folder_name . "routes", $stub);
		$stub = str_replace('{{base_path}}', $this->my_folder_name, $stub);

		return $stub;
	}

	protected function compileAppJs()
	{
		$stub = $this->files->get(__DIR__ . '/../stubs/appjs.stub');

		$stub = str_replace('{{routes_name}}', $this->my_folder_name . "routes", $stub);
		$stub = str_replace('{{app_router}}', $this->my_folder_name . "router", $stub);
		$stub = str_replace('{{api_route}}', ucwords(camel_case($this->my_folder_name)) . 'Api', $stub);

		return $stub;
	}

}