<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
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
	protected $name = 'vuexcrud:make:api';

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
		$this->makeApi();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function makeApi()
	{
		$this->my_class_name = ucwords(camel_case($this->argument('name'))) . 'ApiController';

		$folder_component		= 'controller_folder';
		$namespace_component	= 'controller_namespace';
		$section				= 'default';
		$stub_name				= 'crudapi';

		if ($this->files->exists($path = $this->getPath($folder_component , $section))) {
			return $this->error($path . ' already exists!');
		}
		$this->makeDirectory($path);
		$this->files->put($path, $this->compileStub($stub_name , $namespace_component , $section));
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
		$base_controller_namespace = getAppNamespace() . 'Http\\Controllers';

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

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['name', InputArgument::REQUIRED, 'Name of the api class to create.'],
		];
	}
}