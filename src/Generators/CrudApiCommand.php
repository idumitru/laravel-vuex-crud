<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;

class CrudServiceCommand extends Command
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
	protected $description = 'Create a new crud api class';

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
		$className = ucwords(camel_case($this->argument('name')));
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
		$stub = str_replace('{{namespace}}', $this->getAppNamespace(), $stub);
		return $this;
	}

	/**
	 * Get the path to where we should store the migration.
	 *
	 * @param  string $name
	 * @return string
	 */
	protected function getPath($name)
	{
		return base_path() . '/app/Controllers/' . $name . 'Api.php';
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
	}}