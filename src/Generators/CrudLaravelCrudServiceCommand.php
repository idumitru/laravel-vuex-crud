<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use SoftDreams\LaravelVuexCrud\Traits\CrudServiceGeneratorFunctions;
use Illuminate\Support\Facades\DB;

class CrudLaravelCrudServiceCommand extends Command
{
	use DetectsApplicationNamespace;
	use CrudServiceGeneratorFunctions;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = 'vuexcrud:laravel:make:crudservice {api} {model} {name} {section=default}';

	protected $my_api_name = 'example';
	protected $simple_model_name = 'example';
	protected $my_model_name = 'example';

	protected $table_detail = '';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new api crud service class';

	public function handle()
	{
		$this->my_api_name = ucwords(camel_case($this->argument('api'))) . 'ApiController';
		$this->simple_model_name = $this->argument('model');
		$this->my_model_name = app()->getNamespace() . $this->simple_model_name;
		$this->my_class_name = ucwords(camel_case($this->argument('name'))) . 'CrudService';

		if (strpos($this->my_api_name, "/") !== FALSE || strpos($this->my_api_name, "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in api name');
		}
		if (!@class_exists($this->my_model_name))
		{
			return $this->error('Could not find model ' . $this->my_model_name);
		}

		if (strpos($this->my_class_name, "/") !== FALSE || strpos($this->my_class_name, "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in service name');
		}

		$this->crud_section = $this->argument('section');

		if (!app()['config']["vuexcrud.sections." . $this->crud_section])
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
			__DIR__ . '/config/vuexcrud.php', 'vuexcrud'
		);
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function runGenerator()
	{
		$folder_component = 'crudservice_folder';
		$namespace_component = 'crudservice_namespace';
		$stub_name = 'crudservice';

		if ($this->files->exists($path = $this->getPath($folder_component, $this->crud_section)))
		{
			return $this->error($path . ' already exists!');
		}

		$section_data = app()['config']["vuexcrud.sections." . $this->crud_section];
		$filter_column = trim($section_data['filter_column']);
		$filter_vuex_source = trim($section_data['filter_vuex_source']);
		$filter_vuex_compare = trim($section_data['filter_vuex_compare']);

		$model_item = new $this->my_model_name();

		$table = $model_item->getTable();
		$tableDescription = DB::select('DESCRIBE ' . $table);

		//$this->info('Schema for Model: ' . $this->my_model_name);
		//$this->comment('Table: ' . $table);

		$rows = [];
		if (count($tableDescription) > 0)
		{
			foreach ($tableDescription as $field)
			{
				$rowData = [];
				foreach ($field as $field_name => $value)
				{
					$rowData[$field_name] = $value;
				}
				$rows[] = $rowData;
			}
//			$this->table([
//				'Field',
//				'Type',
//				'Null',
//				'Key',
//				'Default',
//				'Extra'
//			], $rows);
		}

		$output = '';
		$indent = '        ';
		foreach ($rows as $field_data)
		{
			$primary = '';
			$hide = '';
			$type = '->type_string()';
			$nullable = '';
			$unique = '';
			$default = '';
			$filter = '';

			$field_type = strtolower($field_data['Type']);
			$key_type = strtolower($field_data['Key']);

			if($filter_column != '' && $field_data['Field'] == $filter_column)
			{
				$filter = '->filter( "' . $filter_vuex_compare . '", "' . $filter_vuex_source . '")';
				$hide = '->hide()';
				$type = '';
			}
			else if (
				$field_data['Field'] == 'created_at' ||
				$field_data['Field'] == 'updated_at'
			)
			{
				$hide = '->hide()';
				$type = '';
			}
			else if(strpos($key_type , 'pri') !== false)
			{
				$primary = '->primary()';
				$type = '';
			}
			else
			{
				$number_types = array('int' , 'integer' , 'smallint' , 'tinyint' , 'mediumint' , 'bigint');
				$string_types = array('char' , 'varchar');
				$text_types = array('text' , 'smalltext' , 'largetext');

				$found_type = 0;
				if($found_type == 0)
				{
					foreach ($number_types as $number_type)
					{
						if (strpos($field_type, $number_type) !== false)
						{
							$type = '->type_number()';
							$found_type = 1;
							break;
						}
					}
				}
				if($found_type == 0)
				{
					foreach($string_types as $string_type)
					{
						if(strpos($field_type , $string_type) !== false)
						{
							$type = '->type_string()';
							$found_type = 1;
							break;
						}
					}
				}
				if($found_type == 0)
				{
					foreach($text_types as $text_type)
					{
						if(strpos($field_type , $text_type) !== false)
						{
							$type = '->type_text()';
							$found_type = 1;
							break;
						}
					}
				}
				if($found_type == 0)
				{
					if(strpos($field_type , 'datetime') !== false)
					{
						$type = '->type_datetime()';
						$found_type = 1;
					}
					else if(strpos($field_type , 'date') !== false)
					{
						$type = '->type_date()';
						$found_type = 1;
					}
					else if(strpos($field_type , 'timestamp') !== false)
					{
						$type = '->type_timestamp()';
						$found_type = 1;
					}
				}

				$nullable_type = strtolower($field_data['Null']);
				if(strpos($nullable_type , 'yes') !== false)
				{
					$nullable = '->nullable()';
				}

				if(strpos($key_type , 'uni') !== false)
				{
					$unique = '->unique()';
				}

				$default_value = $field_data['Default'];
				if($default_value != '')
				{
					$default = '->set_default("' . $default_value . '")';
				}
			}

			$output .= $indent . '$table->field("' . $field_data['Field'] . '")' . $primary . $filter . $hide . $type . $nullable . $unique . $default . ";\n";
		}

		$this->table_detail = $output;

		$this->makeDirectory($path);
		$this->files->put($path, $this->compileStub($stub_name, $namespace_component, $this->crud_section));

		$api_path = $this->getAppPath('controller_folder', $this->crud_section);
		$api_file_path = $api_path . '/' . $this->my_api_name . '.php';

		if ($this->files->exists($api_file_path))
		{
			$index_contents = $this->files->get($api_file_path);

			if ($index_contents)
			{
				$import_pos = -1;
				$import_end_pos = -1;

				$import_marker = '/* -- api inject module -- do not modify this comment */';
				$import_end_marker = '/* -- api end inject module -- do not modify this comment */';

				$index_lines = explode("\n", str_replace("\r\n", "\n", $index_contents));

				if (is_array($index_lines))
				{
					$index_lines_count = count($index_lines);

					//import_marker pos
					for ($i = 0; $i < $index_lines_count; $i++)
					{
						$search_pos = strpos(trim($index_lines[$i]), $import_marker);
						if ($search_pos === 0)
						{
							$import_pos = $i;
							break;
						}
					}

					//import_end_marker pos
					for ($i = 0; $i < $index_lines_count; $i++)
					{
						$search_pos = strpos(trim($index_lines[$i]), $import_end_marker);
						if ($search_pos === 0)
						{
							$import_end_pos = $i;
							break;
						}
					}

					$section_data = app()['config']["vuexcrud.sections." . $this->crud_section];
					$import_text = '        \\' . $section_data['crudservice_namespace'] . '\\' . $this->my_class_name . "::class,";

					$add_import = true;
					if ($import_pos != -1 && $import_end_pos != -1)
					{
						//search if already exists
						for ($i = $import_pos + 1; $i < $import_end_pos; $i++)
						{
							$search_pos = strpos(trim($index_lines[$i]), trim($import_text));
							if ($search_pos === 0)
							{
								$add_import = false;
							}
						}
					}

					$has_changes = false;
					if ($import_pos != -1 && $add_import === true)
					{
						array_splice($index_lines, $import_pos + 1, 0, $import_text);
						$has_changes = true;
					}

					if ($has_changes)
					{
						$this->files->put($api_file_path, implode("\n", $index_lines));
					}
				}
			}
		}

		$this->info('Crud service created successfully.');
		$this->composer->dumpAutoloads();
	}

	/**
	 * Other replacements in the stub
	 *
	 * @param  string $stub
	 * @return $this
	 */
	protected function replaceExtra(&$stub, $component, $section)
	{
		$table_detail_function_stub = $this->files->get(__DIR__ . '/../stubs/function_crud_table_detail.stub');
		$table_detail_function_stub = str_replace('{{table}}', $this->table_detail, $table_detail_function_stub);

		$stub = str_replace('{{crud_table}}', $table_detail_function_stub, $stub);
		$stub = str_replace('{{modelname}}', $this->simple_model_name, $stub);
		$stub = str_replace('{{crudname}}', $this->my_class_name, $stub);
		return $this;
	}
}