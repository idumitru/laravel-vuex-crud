<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use SoftDreams\LaravelVuexCrud\Traits\CrudServiceGeneratorFunctions;

class CrudVuePageCommand extends Command
{
	use DetectsApplicationNamespace;
	use CrudServiceGeneratorFunctions;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = 'vuexcrud:vue:make:page {app} {layout} {name} {section=default}';

	protected $my_folder_name = 'example';
	protected $my_layout_name = 'example';
	protected $my_page_name = 'example';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new vue page';

	public function handle()
	{
		$this->my_folder_name = strtolower($this->argument('app'));
		$this->my_layout_name = ucwords(camel_case($this->argument('layout')));
		$this->my_page_name = ucwords(camel_case($this->argument('name')));

		if(strpos($this->my_folder_name , "/") !== FALSE || strpos($this->my_folder_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in app name');
		}
		if(strpos($this->my_layout_name , "/") !== FALSE || strpos($this->my_layout_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in layout name');
		}
		if(strpos($this->my_page_name , "/") !== FALSE || strpos($this->my_page_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in page name');
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

		$pages_path = $app_path . '/pages';
		if (!$this->files->exists($pages_path)) {
			return $this->error('Pages folder for app ' . $this->my_folder_name .  ' does not exits! (' . $pages_path . ')');
		}

		$pages_layout_path = $pages_path . '/' . $this->my_layout_name;
		if (!$this->files->exists($pages_layout_path)) {
			return $this->error('Pages layout folder for app ' . $this->my_folder_name .  ' does not exits! (' . $pages_layout_path . ')');
		}

		$page_file_path = $pages_layout_path . '/' . $this->my_page_name . '.vue';
		if ($this->files->exists($page_file_path)) {
			return $this->error('Page ' . $this->my_page_name .  ' already exits! (' . $page_file_path . ')');
		}

		$this->files->put($page_file_path, $this->files->get(__DIR__ . '/../stubs/vue_component.stub'));

		$this->info('Page ' . $this->my_layout_name . ' for layout ' . $this->my_layout_name . ' for app ' . $this->my_folder_name . ' created successfully in ' . $page_file_path);
	}
}