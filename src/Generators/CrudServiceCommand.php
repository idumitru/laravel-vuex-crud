<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use SoftDreams\LaravelVuexCrud\Traits\CrudServiceGeneratorFunctions;

class CrudServiceCommand extends Command
{
	use DetectsApplicationNamespace;
	use CrudServiceGeneratorFunctions;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'vuexcrud:make:service';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new crud service class';

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
	 * Register bindings in the container.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(
			__DIR__.'/path/to/config/courier.php', 'courier'
		);
	}
	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function makeApi()
	{
		$this->my_class_name = ucwords(camel_case($this->argument('name'))) . 'CrudService';

		$folder_component		= 'crudservice_folder';
		$namespace_component	= 'crudservice_namespace';
		$section				= 'default';
		$stub_name				= 'crudservice';

		if ($this->files->exists($path = $this->getPath($folder_component , $section))) {
			return $this->error($path . ' already exists!');
		}
		$this->makeDirectory($path);
		$this->files->put($path, $this->compileStub($stub_name , $namespace_component , $section));
		$this->info('Crud service created successfully.');
		$this->composer->dumpAutoloads();
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['name', InputArgument::REQUIRED, 'Name of the crud service class to create.'],
		];
	}
}