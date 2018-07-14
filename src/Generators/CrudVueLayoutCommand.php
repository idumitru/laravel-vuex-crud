<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use SoftDreams\LaravelVuexCrud\Traits\CrudServiceGeneratorFunctions;

class CrudVueLayoutCommand extends Command
{
	use DetectsApplicationNamespace;
	use CrudServiceGeneratorFunctions;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = 'vuexcrud:vue:make:layout {app} {name} {section=default}';

	protected $my_folder_name = 'example';
	protected $my_layout_name = 'example';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new vue layout';

	public function handle()
	{
		$this->my_folder_name = strtolower($this->argument('app'));
		$this->my_layout_name = ucwords(camel_case($this->argument('name')));

		if(strpos($this->my_folder_name , "/") !== FALSE || strpos($this->my_folder_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in app name');
		}
		if(strpos($this->my_layout_name , "/") !== FALSE || strpos($this->my_layout_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in layout name');
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
		$app_path = $this->getVuePath('vue_root' , $this->crud_section) . '/' . $this->my_folder_name;
		if (!$this->files->exists($app_path)) {
			return $this->error('Path for app ' . $this->my_folder_name .  ' does not exits! (' . $app_path . ')');
		}

		$layouts_path = $app_path . '/layouts';
		if (!$this->files->exists($layouts_path)) {
			return $this->error('Layouts folder for app ' . $this->my_folder_name .  ' does not exits! (' . $layouts_path . ')');
		}

		$pages_path = $app_path . '/pages';
		if (!$this->files->exists($pages_path)) {
			return $this->error('Pages folder for app ' . $this->my_folder_name .  ' does not exits! (' . $pages_path . ')');
		}

		$layout_file_path = $layouts_path . '/' . $this->my_layout_name . '.vue';
		if ($this->files->exists($layout_file_path)) {
			return $this->error('Layout ' . $this->my_layout_name .  ' already exits! (' . $layout_file_path . ')');
		}

		$this->files->put($layout_file_path, $this->files->get(__DIR__ . '/../stubs/vue_layout.stub'));

		$this->createDirectory($pages_path . '/' . $this->my_layout_name);

		$this->info('App layout ' . $this->my_layout_name . ' for app ' . $this->my_folder_name . ' created successfully in ' . $layout_file_path);
	}
}