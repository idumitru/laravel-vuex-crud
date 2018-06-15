<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class CrudApiCommand extends Command
{
	use DetectsApplicationNamespace;

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
	 * The filesystem instance.
	 *
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * @var Composer
	 */
	private $composer;

	/**
	 * Create a new command instance.
	 *
	 * @param Filesystem $files
	 * @param Composer $composer
	 */
	public function __construct(Filesystem $files)
	{
		parent::__construct();
		$this->files = $files;
		$this->composer = app()['composer'];
	}

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
		$name = $this->argument('name');
		if ($this->files->exists($path = $this->getPath($name))) {
			return $this->error($this->type . ' already exists!');
		}
		$this->makeDirectory($path);
		$this->files->put($path, $this->compileApiStub());
		$this->info('Api handler created successfully.');
		$this->composer->dumpAutoloads();
	}

	/**
	 * Compile the api stub.
	 *
	 * @return string
	 */
	protected function compileApiStub()
	{
		$stub = $this->files->get(__DIR__ . '/../stubs/crudapi.stub');

		$this->replaceClassName($stub)
			->replaceNamespace($stub);
		return $stub;
	}

	/**
	 * Replace the class name in the stub.
	 *
	 * @param  string $stub
	 * @return $this
	 */
	protected function replaceClassName(&$stub)
	{
		$className = ucwords(camel_case($this->argument('name'))) . 'ApiController';
		$stub = str_replace('{{class}}', $className, $stub);
		return $this;
	}

	/**
	 * Replace the namespace in the stub.
	 *
	 * @param  string $stub
	 * @return $this
	 */
	protected function replaceNamespace(&$stub)
	{
		$section_data = $this->app['config']["vuexcrud.sections.default"];
		$namespace = trim($section_data['controller_namespace']);
		$stub = str_replace('{{namespace}}', $namespace, $stub);
		return $this;
	}

	/**
	 * Build the directory for the class if necessary.
	 *
	 * @param  string $path
	 * @return string
	 */
	protected function makeDirectory($path)
	{
		if (!$this->files->isDirectory(dirname($path))) {
			$this->files->makeDirectory(dirname($path), 0777, true, true);
		}
	}

	/**
	 * Get the path to where we should store the migration.
	 *
	 * @param  string $name
	 * @return string
	 */
	protected function getPath($name)
	{
		$section_data = $this->app['config']["vuexcrud.sections.default"];
		$controller_path = '/app/' . trim($section_data['controller_folder'] , " /\t\n\r\0\x0B") . '/' . $name . 'ApiController.php';
		return base_path() . $controller_path;
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