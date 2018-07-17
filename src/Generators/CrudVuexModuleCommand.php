<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use SoftDreams\LaravelVuexCrud\Traits\CrudServiceGeneratorFunctions;

class CrudVuexModuleCommand extends Command
{
	use DetectsApplicationNamespace;
	use CrudServiceGeneratorFunctions;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = 'vuexcrud:vuex:make:module {app} {name} {section=default}';

	protected $my_folder_name = 'example';
	protected $my_module_name = 'example';
	protected $camel_module_name = 'example';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new vue layout';

	public function handle()
	{
		$this->my_folder_name = strtolower($this->argument('app'));
		$this->my_module_name = strtolower($this->argument('name'));
		$this->camel_module_name = ucwords(camel_case($this->argument('name')));

		if(strpos($this->my_folder_name , "/") !== FALSE || strpos($this->my_folder_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in app name');
		}
		if(strpos($this->my_module_name , "/") !== FALSE || strpos($this->my_module_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in vuex module name');
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

		$store_path = $app_path . '/store';
		if (!$this->files->exists($store_path)) {
			return $this->error('Store folder for app ' . $this->my_folder_name .  ' does not exits! (' . $store_path . ')');
		}

		$vuex_module_path = $store_path . '/modules';
		if (!$this->files->exists($vuex_module_path)) {
			return $this->error('Vuex modules folder for app ' . $this->my_folder_name .  ' does not exits! (' . $vuex_module_path . ')');
		}

		$module_js_path = $vuex_module_path . '/' . $this->my_module_name . '.js';
		if ($this->files->exists($module_js_path)) {
			return $this->error('Vuex module ' . $this->my_module_name . ' for app ' . $this->my_folder_name .  ' already exits! (' . $module_js_path . ')');
		}

		$this->files->put($module_js_path, $this->compileModuleJs());

		$vuex_index_path = $store_path . '/index.js';
		if($this->files->exists($vuex_index_path))
		{
			$index_contents = $this->files->get($vuex_index_path);

			if($index_contents)
			{
				$import_pos = -1;
				$import_end_pos = -1;
				$module_pos = -1;
				$module_end_pos = -1;

				$import_marker = '/* -- vuexcrud inject imports -- do not modify this comment */';
				$import_end_marker = '/* -- vuexcrud end inject imports -- do not modify this comment */';
				$module_marker = '/* -- vuexcrud inject module -- do not modify this comment */';
				$module_end_marker = '/* -- vuexcrud end inject module -- do not modify this comment */';

				$index_lines = explode("\n" , str_replace("\r\n" , "\n" , $index_contents));

				if(is_array($index_lines))
				{
					$index_lines_count = count($index_lines);

					//import_marker pos
					for($i = 0 ; $i < $index_lines_count ; $i++)
					{
						$search_pos = strpos(trim($index_lines[$i]) , $import_marker);
						if( $search_pos === 0)
						{
							$import_pos = $i;
							break;
						}
					}

					//import_end_marker pos
					for($i = 0 ; $i < $index_lines_count ; $i++)
					{
						$search_pos = strpos(trim($index_lines[$i]) , $import_end_marker);
						if( $search_pos === 0)
						{
							$import_end_pos = $i;
							break;
						}
					}

					//module_pos pos
					for($i = 0 ; $i < $index_lines_count ; $i++)
					{
						$search_pos = strpos(trim($index_lines[$i]) , $module_marker);
						if( $search_pos === 0)
						{
							$module_pos = $i;
						}
					}

					//module_end_pos pos
					for($i = 0 ; $i < $index_lines_count ; $i++)
					{
						$search_pos = strpos(trim($index_lines[$i]) , $module_end_marker);
						if( $search_pos === 0)
						{
							$module_end_pos = $i;
						}
					}

					$import_text = "import " .  $this->my_module_name . " from './modules/" .  $this->my_module_name . "'";
					$module_text = '        ' .  $this->my_module_name . ',';

					$add_import = true;
					if($import_pos != -1 && $import_end_pos != -1)
					{
						//search if already exists
						for($i = $import_pos + 1 ; $i < $import_end_pos ; $i++)
						{
							$search_pos = strpos(trim($index_lines[$i]) , trim($import_text));
							if( $search_pos === 0)
							{
								$add_import = false;
							}
						}
					}

					$add_module = true;
					if($module_pos != -1 && $module_end_pos != -1)
					{
						//search if already exists
						for($i = $module_pos + 1 ; $i < $module_end_pos ; $i++)
						{
							$search_pos = strpos(trim($index_lines[$i]) , trim($module_text));
							if( $search_pos === 0)
							{
								$add_module = false;
							}
						}
					}

					$has_changes = false;
					if($import_pos != -1 && $add_import === true)
					{
						array_splice( $index_lines, $import_pos + 1, 0, $import_text);
						$module_pos++;
						$has_changes = true;
					}

					if($module_pos != -1 && $add_module === true)
					{
						array_splice( $index_lines, $module_pos + 1, 0, $module_text);
						$has_changes = true;
					}

					if($has_changes)
					{
						$this->files->put($vuex_index_path , implode("\n" , $index_lines));
					}
				}
			}
		}

		$this->info('Module ' . $this->my_module_name . ' for app ' . $this->my_folder_name . ' created successfully in ' . $module_js_path);
	}

	protected function compileModuleJs()
	{
		$stub = $this->files->get(__DIR__ . '/../stubs/modulejs.stub');

		$stub = str_replace('{{var_name}}', $this->my_module_name, $stub);
		$stub = str_replace('{{api_route}}', ucwords(camel_case($this->my_folder_name)) . 'Api', $stub);
		$stub = str_replace('{{service_name}}', $this->camel_module_name . "CrudService", $stub);

		return $stub;
	}
}