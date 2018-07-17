<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use SoftDreams\LaravelVuexCrud\Traits\CrudServiceGeneratorFunctions;

class CrudLaravelServiceInjectCommand extends Command
{
	use DetectsApplicationNamespace;
	use CrudServiceGeneratorFunctions;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = 'vuexcrud:laravel:inject:service {api} {name} {section=default}';

	protected $my_api_name = 'example';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new crud service class';

	public function handle()
	{
		$this->my_api_name = ucwords(camel_case($this->argument('api')));
		$this->my_api_name = str_replace('ApiController' , '' , $this->my_api_name);
		$this->my_api_name .= 'ApiController';

		$this->my_class_name = ucwords(camel_case($this->argument('name'))) . 'CrudService';
		$this->my_class_name = str_replace('CrudService' , '' , $this->my_class_name);
		$this->my_class_name .= 'CrudService';

		if(strpos($this->my_api_name , "/") !== FALSE || strpos($this->my_api_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in api name');
		}
		if(strpos($this->my_class_name , "/") !== FALSE || strpos($this->my_class_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in service name');
		}


		$this->crud_section = $this->argument('section');

		if(!app()['config']["vuexcrud.sections." . $this->crud_section])
		{
			return $this->error('Configuration section "' . $this->crud_section . '" does not exists!');
		}

		$this->runGenerator();
	}
	/**
	 * Register bindings in the container.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(
			__DIR__.'/config/vuexcrud.php', 'vuexcrud'
		);
	}
	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function runGenerator()
	{
		$service_file_path = $this->getPath('crudservice_folder' , $this->crud_section);
		if (!$this->files->exists($service_file_path)) {
			return $this->error('Service file for ' . $this->my_class_name . ' does not exists!' . $service_file_path);
		}

		$api_path = $this->getAppPath('controller_folder' , $this->crud_section);
		$api_file_path = $api_path . '/' . $this->my_api_name . '.php';

		if (!$this->files->exists($api_file_path)) {
			return $this->error('Api file for ' . $this->my_api_name . ' does not exists!' . $api_file_path);
		}

		$index_contents = $this->files->get($api_file_path);

		if($index_contents)
		{
			$import_pos = -1;
			$import_end_pos = -1;

			$import_marker = '/* -- api inject module -- do not modify this comment */';
			$import_end_marker = '/* -- api end inject module -- do not modify this comment */';

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

				$section_data = app()['config']["vuexcrud.sections." .  $this->crud_section];
				$import_text = '        \\' . $section_data['crudservice_namespace'] . '\\' .$this->my_class_name . "::class,";

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

				$has_changes = false;
				if($import_pos != -1 && $add_import === true)
				{
					array_splice( $index_lines, $import_pos + 1, 0, $import_text);
					$has_changes = true;
				}

				if($has_changes)
				{
					$this->files->put($api_file_path , implode("\n" , $index_lines));
				}
			}
		}

		$this->info('Crud service injected successfully.');
	}
}