<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use SoftDreams\LaravelVuexCrud\Traits\CrudServiceGeneratorFunctions;

class CrudApiCommand extends Command
{
	use DetectsApplicationNamespace;
	use CrudServiceGeneratorFunctions;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'vuexcrud:make:api {name} {section=default}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new crud api controller class';

	/**
	 * Alias for the fire method.
	 *
	 * In Laravel 5.5 the fire() method has been renamed to handle().
	 * This alias provides support for both Laravel 5.4 and 5.5.
	 */
	public function handle()
	{
		$this->my_class_name = ucwords(camel_case($this->argument('name'))) . 'CrudService';
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
		$folder_component		= 'controller_folder';
		$namespace_component	= 'controller_namespace';
		$stub_name				= 'crudapi';

		if ($this->files->exists($path = $this->getPath($folder_component , $this->crud_section))) {
			return $this->error($path . ' already exists!');
		}
		$this->makeDirectory($path);
		$this->files->put($path, $this->compileStub($stub_name , $namespace_component , $this->crud_section));
		$this->info('Api handler created successfully.');
		$this->composer->dumpAutoloads();
	}

	/**
	 * Other replacements in the stub
	 *
	 * @param  string $stub
	 * @return $this
	 */
	protected function replaceExtra(&$stub , $component , $section)
	{
		$section_data = app()['config']["vuexcrud.sections." . $section];
		$namespace = trim($section_data[$component]);
		$base_controller_namespace = $this->getAppNamespace() . 'Http\\Controllers';

		if($namespace == $base_controller_namespace)
		{
			$stub = str_replace('{{name_space_controller}}', '', $stub);
		}
		else
		{
			$stub = str_replace('{{name_space_controller}}', "\n" . $base_controller_namespace . '\\Controller;', $stub);
		}

		return $this;
	}
}