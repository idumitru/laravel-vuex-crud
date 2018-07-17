<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use SoftDreams\LaravelVuexCrud\Traits\CrudServiceGeneratorFunctions;

class CrudLaravelApiCommand extends Command
{
	use DetectsApplicationNamespace;
	use CrudServiceGeneratorFunctions;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = 'vuexcrud:laravel:make:api {name} {section=default}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new crud api controller class';

	public function handle()
	{
		$this->my_class_name = ucwords(camel_case($this->argument('name'))) . 'ApiController';

		if(strpos($this->my_class_name , "/") !== FALSE || strpos($this->my_class_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in api name');
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
		$folder_component		= 'controller_folder';
		$namespace_component	= 'controller_namespace';
		$stub_name				= 'crudapi';

		if ($this->files->exists($path = $this->getPath($folder_component , $this->crud_section))) {
			return $this->error($path . ' already exists!');
		}
		$this->makeDirectory($path);
		$this->files->put($path, $this->compileStub($stub_name , $namespace_component , $this->crud_section));

		$provider_file_path = base_path() . '/config/vuexcrud.php';

		$can_publish = true;
		if (!$this->files->exists($provider_file_path)) {
			$this->error('VuexCrud configuration has not been published. For api auto-import of routes please publish with: ');
			$this->info('php artisan vendor:publish --provider "SoftDreams\LaravelVuexCrud\LaravelVuexCrudProvider"');
			$can_publish = false;
		}

		if($can_publish === true && $this->files->exists($provider_file_path))
		{
			$file_contents = $this->files->get($provider_file_path);

			if($file_contents)
			{
				$import_pos = -1;
				$import_end_pos = -1;

				$import_marker = '/* -- api inject module -- do not modify this comment */';
				$import_end_marker = '/* -- api end inject module -- do not modify this comment */';

				$file_lines = explode("\n" , str_replace("\r\n" , "\n" , $file_contents));

				if(is_array($file_lines))
				{
					$file_lines_count = count($file_lines);

					//import_marker pos
					for($i = 0 ; $i < $file_lines_count ; $i++)
					{
						$search_pos = strpos(trim($file_lines[$i]) , $import_marker);
						if( $search_pos === 0)
						{
							$import_pos = $i;
							break;
						}
					}

					//import_end_marker pos
					for($i = 0 ; $i < $file_lines_count ; $i++)
					{
						$search_pos = strpos(trim($file_lines[$i]) , $import_end_marker);
						if( $search_pos === 0)
						{
							$import_end_pos = $i;
							break;
						}
					}

					$section_data = app()['config']["vuexcrud.sections." .  $this->crud_section];
					$import_text = '        \\' . $section_data['controller_namespace'] . '\\' .$this->my_class_name . "::class,";

					$add_import = true;
					if($import_pos != -1 && $import_end_pos != -1)
					{
						//search if already exists
						for($i = $import_pos + 1 ; $i < $import_end_pos ; $i++)
						{
							$search_pos = strpos(trim($file_lines[$i]) , trim($import_text));
							if( $search_pos === 0)
							{
								$add_import = false;
							}
						}
					}

					$has_changes = false;
					if($import_pos != -1 && $add_import === true)
					{
						array_splice( $file_lines, $import_pos + 1, 0, $import_text);
						$has_changes = true;
					}

					if($has_changes)
					{
						$this->files->put($provider_file_path , implode("\n" , $file_lines));
					}
				}
			}
		}

		if($can_publish === true && app()['config']["vuexcrud.sections." . $this->crud_section]['inject_routes'] == 1)
		{
			$web_routes_file_path = base_path() . '/routes/web.php';
			if($this->files->exists($web_routes_file_path))
			{
				$file_contents = $this->files->get($web_routes_file_path);

				if($file_contents)
				{
					$import_pos = -1;

					$file_lines = explode("\n" , str_replace("\r\n" , "\n" , $file_contents));

					if(is_array($file_lines))
					{
						$file_lines_count = count($file_lines);

						//find 'use' lines and insert after
						for($i = 0 ; $i < $file_lines_count ; $i++)
						{
							$search_pos = strpos(trim($file_lines[$i]) , 'use ');
							if( $search_pos !== false)
							{
								$import_pos = $i + 1;
							}
						}

						if($import_pos == -1)
						{
							$import_pos = 0;
						}

						$section_data = app()['config']["vuexcrud.sections." .  $this->crud_section];
						$import_text = 'use SoftDreams\\LaravelVuexCrud\\LaravelVuexCrudProvider;';

						$add_import = true;
						for($i = 0 ; $i < $file_lines_count ; $i++)
						{
							$search_pos = strpos(trim($file_lines[$i]) , $import_text);
							if( $search_pos !== false)
							{
								$add_import = false;
							}
						}

						$has_changes = false;
						if($add_import === true)
						{
							array_splice( $file_lines, $import_pos, 0, $import_text);
							array_splice( $file_lines, $import_pos + 1, 0, '');
							array_splice( $file_lines, $import_pos + 2, 0, "LaravelVuexCrudProvider::routes();");
							$this->files->put($web_routes_file_path , implode("\n" , $file_lines));
						}
					}
				}
			}
		}

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
		$stub = str_replace('{{api_path}}', ucwords(camel_case($this->argument('name'))) . 'Api', $stub);
		return $this;
	}
}