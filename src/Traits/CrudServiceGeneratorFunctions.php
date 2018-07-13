<?php

namespace SoftDreams\LaravelVuexCrud\Traits;

use Illuminate\Filesystem\Filesystem;

trait CrudServiceGeneratorFunctions
{

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
	 * The config section that defines paths and namespaces.
	 *
	 * @var string
	 */
	protected $crud_section = 'default';

	/**
	 * The config section that defines paths and namespaces.
	 *
	 * @var string
	 */
	protected $my_class_name = 'example';

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
	 * Compile the api stub.
	 *
	 * @return string
	 */
	protected function compileStub($stub_name , $component , $section)
	{
		$stub = $this->files->get(__DIR__ . '/../stubs/' . $stub_name . '.stub');

		$this->replaceClassName($stub)
			->replaceNamespace($stub , $component , $section)
			->replaceExtra($stub , $component , $section);
		return $stub;
	}

	/**
	 * Other replacements in the stub ... override in parent.
	 *
	 * @param  string $stub
	 * @return $this
	 */
	protected function replaceExtra(&$stub , $component , $section)
	{
		return $this;
	}

	/**
	 * Replace the class name in the stub.
	 *
	 * @param  string $stub
	 * @return $this
	 */
	protected function replaceClassName(&$stub)
	{
		$stub = str_replace('{{class}}', $this->my_class_name, $stub);
		return $this;
	}

	/**
	 * Replace the namespace in the stub.
	 *
	 * @param  string $stub
	 * @return $this
	 */
	protected function replaceNamespace(&$stub , $component , $section)
	{
		$section_data = app()['config']["vuexcrud.sections." . $section];
		$namespace = trim($section_data[$component]);
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
	 * Build the directory for the class if necessary.
	 *
	 * @param  string $path
	 * @return string
	 */
	protected function createDirectory($path)
	{
		$this->files->makeDirectory($path, 0777, true, true);
	}

	/**
	 * Get the path to where we should store the migration.
	 *
	 * @param  string $name
	 * @return string
	 */
	protected function getPath($component , $section)
	{
		$section_data = app()['config']["vuexcrud.sections." . $section];
		$file_path = '/app/' . trim($section_data[$component] , " /\t\n\r\0\x0B") . '/' . $this->my_class_name . '.php';
		return base_path() . $file_path;
	}

	/**
	 * Get the path to where we should store vue components.
	 *
	 * @param  string $name
	 * @return string
	 */
	protected function getVuePath($component , $section)
	{
		$section_data = app()['config']["vuexcrud.sections." . $section];
		$file_path = '/' . trim($section_data[$component] , " /\t\n\r\0\x0B");

		return base_path() . $file_path;
	}

	/**
	 * Get the path to where we should store vue components.
	 *
	 * @param  string $name
	 * @return string
	 */
	protected function getVueTreePath($path , $component , $section)
	{
		$section_data = app()['config']["vuexcrud.sections." . $section . ".vue_tree"];
		$file_path = '/' . trim($section_data[$component] , " /\t\n\r\0\x0B");

		return $path . $file_path;
	}
}
