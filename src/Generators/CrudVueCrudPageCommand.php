<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use SoftDreams\LaravelVuexCrud\Traits\CrudServiceGeneratorFunctions;

class CrudVueCrudPageCommand extends Command
{
	use DetectsApplicationNamespace;
	use CrudServiceGeneratorFunctions;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = 'vuexcrud:vue:make:crudpage {app} {layout} {service} {module} {name} {section=default}';

	protected $my_folder_name = 'example';
	protected $my_layout_name = 'example';
	protected $my_service_name = 'example';
	protected $my_module_name = 'example';
	protected $my_page_name = 'example';

	protected $table_config = '';
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
		$this->my_service_name = ucwords(camel_case($this->argument('service')));
		$this->my_module_name = $this->argument('module');
		$this->my_page_name = ucwords(camel_case($this->argument('name')));

		if(strpos($this->my_folder_name , "/") !== FALSE || strpos($this->my_folder_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in app name');
		}
		if(strpos($this->my_layout_name , "/") !== FALSE || strpos($this->my_layout_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in layout name');
		}
		if(strpos($this->my_service_name , "/") !== FALSE || strpos($this->my_service_name , "\\") !== FALSE)
		{
			return $this->error('Subfolders are not supported in service name');
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

		$components_path = $app_path . '/components';
		if (!$this->files->exists($components_path)) {
			return $this->error('Components folder for app ' . $this->my_folder_name .  ' does not exits! (' . $components_path . ')');
		}

		$common_path = $this->getVuePath('vue_common' , $this->crud_section);
		if (!$this->files->exists($common_path)) {
			return $this->error('Vue common folder for app ' . $this->my_folder_name .  ' does not exits! (' . $common_path . ')');
		}

		$section_data = app()['config']["vuexcrud.sections." .  $this->crud_section];
		$table_detail_class = '\\' . $section_data['crudservice_namespace'] . '\\' .$this->my_service_name . "CrudService";

		$table_detail = $table_detail_class::GetCrudTableDetails();

		$this->table_config = $table_detail->GetConfig();
		if(count($this->table_config) == 0)
		{
			return $this->error('Unable to generate table configuration from crud service  ' . $this->my_service_name .  ' (' . $table_detail_class . '::GetCrudTableDetails()->GetConfig())');
		}

		$this->files->put($page_file_path, $this->compileCrudPage(__DIR__ . '/../stubs/vuecrud_crudpage.stub'));

		$components_page_path = $components_path . '/' . $this->my_page_name;
		$this->createDirectory($components_page_path);

		$components_page_create_path = $components_page_path . '/' . $this->my_page_name . 'Create.vue';
		if (!$this->files->exists($components_page_create_path))
		{
			$this->files->put($components_page_create_path , $this->compileCreateItem(__DIR__ . '/../stubs/vuecrud_create.stub'));
		}

		$components_page_listing_path = $components_page_path . '/' . $this->my_page_name . 'Listing.vue';
		if (!$this->files->exists($components_page_listing_path))
		{
			$this->files->put($components_page_listing_path , $this->compileListingItems(__DIR__ . '/../stubs/vuecrud_listing.stub'));
		}

		$common_listing_grid_path = $common_path . '/ListingGrid.vue';
		if (!$this->files->exists($common_listing_grid_path))
		{
			$this->files->put($common_listing_grid_path , $this->files->get(__DIR__ . '/../stubs/vuecrud_listinggrid.stub'));
		}

		$common_smart_input_path = $common_path . '/SmartInput.vue';
		if (!$this->files->exists($common_smart_input_path))
		{
			$this->files->put($common_smart_input_path , $this->files->get(__DIR__ . '/../stubs/vuecrud_smartinput.stub'));
		}

		$common_loading_path = $common_path . '/LoadingAnim.vue';
		if (!$this->files->exists($common_loading_path))
		{
			$this->files->put($common_loading_path, $this->files->get(__DIR__ . '/../stubs/vuecrud_loading.stub'));
		}
		
		//inject route and link
		if(app()['config']["vuexcrud.sections." . $this->crud_section]['inject_vue_nav'] == 1)
		{
			$layout_file_path = $app_path . '/layouts/' . $this->my_layout_name . '.vue';
			$this->injectLayoutLink($layout_file_path);
		}

		if(app()['config']["vuexcrud.sections." . $this->crud_section]['inject_vue_route'] == 1)
		{
			$router_path = $app_path . '/' . $this->my_folder_name . 'routes.js';
			$this->injectRoute($router_path);
		}

		$this->info('Page ' . $this->my_page_name . ' for layout ' . $this->my_layout_name . ' for app ' . $this->my_folder_name . ' created successfully in ' . $page_file_path);
	}

	protected function compileListingItems($stub_src)
	{
		$stub = $this->files->get($stub_src);

		$uc_item = ucwords($this->my_page_name);
		$camel_item = camel_case($this->my_page_name);

		$nice_item_name = ucwords(str_replace('_' , ' ' , $this->my_service_name));
		$singular_item_name = str_singular($nice_item_name);

		$stub = str_replace('{{uc_item}}', $uc_item, $stub);
		$stub = str_replace('{{module}}', $this->my_module_name, $stub);
		$stub = str_replace('{{camel_item}}', $camel_item, $stub);
		$stub = str_replace('{{page}}', $this->my_page_name, $stub);
		$stub = str_replace('{{item}}', $singular_item_name, $stub);

		$columns = '';
		foreach($this->table_config as $field_name => $field_data)
		{
			if($field_data['primary'] === true || $field_data['hidden'] === true)
			{
				continue;
			}

			$nice_field_name = ucwords(str_replace('_' , ' ' , $field_name));
			$columns .='							{
								grid_column: \'' . $nice_field_name . '\',
								data_column: \'' . $field_name . '\',
								editable: true,
								type: \'editbox\'
							},
';
		}

		$stub = str_replace('{{columns}}', $columns, $stub);

		return $stub;
	}

	protected function compileCreateItem($stub_src)
	{
		$stub = $this->files->get($stub_src);

		$uc_item = ucwords($this->my_page_name);
		$lower_item = strtolower($this->my_page_name);

		$inputs = '';
		$variables = '';
		$warnings = '';
		$call_data = '';
		$reset_data = '';
		$enable_load_data = false;
		$load_components = '';
		$load_settings_calls = '';
		$load_settings_functions = '';
		$settings_vars = '';
		foreach($this->table_config as $field_name => $field_data)
		{
			if($field_data['primary'] === true || $field_data['hidden'] === true)
			{
				continue;
			}

			$input_item = '';
			$field_variable = '';
			$warning_item = '';
			$call_item = '';
			$reset_item = '';

			$uc_field = ucwords($field_name);
			$nice_field_name = ucwords(str_replace('_' , ' ' , $field_name));

			if($field_data['has_relation'] === true)
			{
				$enable_load_data = true;
				$vuex_module = $field_data['relation']['vuex_module'];
				$relation_column = $field_data['relation']['match_column'];
				$relation_value = $field_data['relation']['match_value'];
				$nice_relation_name = ucwords(str_replace('_' , ' ' , $vuex_module));
				$singular_relation_name = str_singular($nice_relation_name);

				if($field_data['field_type'] == 'string' || $field_data['field_type'] == 'number')
				{
					$input_item ='                <label for="' . $uc_field . '">' . $nice_field_name . '</label>
                <select
                        id="' . $uc_field . '"
                        class="form-control"
                        v-model="new_' . $field_name . '"
                >
                    <option value="0">[Select ' . $singular_relation_name . ']</option>
                    <option v-for="item in $store.state.' . $vuex_module . '.' . $vuex_module . '"
                            :value="item.' . $relation_column . '">{{item.' . $relation_value . '}}
                    </option>
                </select>' . "\n";
				}
				elseif ($field_data['field_type'] == 'smart_input')
				{
					$input_item ='                <label for="' . $uc_field . '">' . $nice_field_name . '</label>
                <SmartInput
                        info_name="' . $singular_relation_name . '"
                        mode="vuex"
                        :settings="' . $field_name . '_settings"
                        :settings_ready.sync="' . $field_name . '_settings_ready"
                        label="' . $relation_value . '"
                        v-model="new_' . $field_name . '"
                ></SmartInput>' . "\n";

					$settings_vars .= '                ' . $field_name . '_settings: {},';
					$settings_vars .= '                ' . $field_name . '_settings_ready: false,';

					$load_settings_calls .= '            this.prepare_' . $field_name . '_settings();';
					$load_settings_functions .= '            prepare_' . $field_name . '_settings: function()
            {
                this.' . $field_name . '_settings = {
                    vuex_src: \'' . $vuex_module . '/' . $vuex_module . 'GetItems\',
                    data_field: \'' . $relation_value . '\',
                    load_components: [
                        {
                            tag: \'' . $vuex_module . '\',
                            fetch_action: \'' . $vuex_module . '/' . $vuex_module . 'FetchItems\',
                        }
                    ]
                };
                this.' . $field_name . '_settings_ready = true;
            },' . "\n";
				}
				else
				{
					$input_item ='                <label for="' . $uc_field . '">' . $nice_field_name . '</label>' . "\n";
				}

				$field_variable = '				new_' . $field_name . ': 0,' . "\n";
				$reset_item = '						this.new_' . $field_name . ' = 0;' . "\n";
				if($field_data['can_be_0'] === false)
				{
					$warning_item = '				if (this.new_' . $field_name . ' === 0)
				{
					this.$swal({
						type: \'error\',
						title: \'Oops...\',
						text: \'Please select ' . $singular_relation_name . ' field\',
					});

					return;
				}' . "\n";
				}

				$load_components .= '                    {
						tag: \''. $vuex_module . '\',
						fetch_action: \''. $vuex_module . '/'. $vuex_module . 'FetchItems\',
                    },' . "\n";
			}
			else if($field_data['is_dropdown'] === true)
			{
				$input_item ='                <label for="new_' . $field_name . '">' . $nice_field_name . '</label>
                <select
                        id="new_' . $field_name . '"
                        class="form-control"
                        v-model="new_' . $field_name . '"
                >' . "\n";

				$first_option = false;
				foreach ($field_data['dropdown_options'] as $dropdown_option)
				{
					if($first_option === false)
					{
						$first_option = $dropdown_option;
					}
					$input_item .= '                    <option value="' . $dropdown_option['value'] .'">' . $dropdown_option['display'] .'</option>' . "\n";
				}


				$input_item .= '                </select>' . "\n";

				$field_variable = '				new_' . $field_name . ': ' . $first_option['value'] . ',' . "\n";
				$reset_item = '						this.new_' . $field_name . ' = ' . $first_option['value'] . ';' . "\n";
			}
			else
			{
				if($field_data['field_type'] == 'string' || $field_data['field_type'] == 'number')
				{
					$input_item ='                <label for="new_' . $field_name . '">' . $nice_field_name . '</label>
                <input
                        type="text"
                        class="form-control"
                        id="new_' . $field_name . '"
                        placeholder="Enter ' . $nice_field_name . '"
                        v-model="new_' . $field_name . '"
                >' . "\n";

					$field_variable = '				new_' . $field_name . ': \'\',' . "\n";
					$reset_item = '						this.new_' . $field_name . ' = \'\';' . "\n";
					if($field_data['nullable'] === false)
					{
						$warning_item = '				if (this.new_' . $field_name . '.trim() === \'\')
				{
					this.$swal({
						type: \'error\',
						title: \'Oops...\',
						text: \'Please fill in ' . $nice_field_name . ' field\',
					});

					return;
				}' . "\n";
					}
				}
				else if($field_data['field_type'] == 'date')
				{
					$input_item ='                <label for="new_' . $field_name . '">' . $nice_field_name . '</label>
                <date-picker 
                        class="form-control"
                        id="new_' . $field_name . '"
                        v-model="new_' . $field_name . '" 
                        lang="en"
                        format="YYYY-MM-DD"
                        confirm
                ></date-picker>' . "\n";

					$field_variable = '				new_' . $field_name . ': \'\',' . "\n";
					$reset_item = '						this.new_' . $field_name . ' = \'\';' . "\n";
					if($field_data['nullable'] === false)
					{
						$warning_item = '				if (this.new_' . $field_name . '.trim() === \'\')
				{
					this.$swal({
						type: \'error\',
						title: \'Oops...\',
						text: \'Please fill in ' . $nice_field_name . ' field\',
					});

					return;
				}
';
					}
				}
				else if($field_data['field_type'] == 'datetime')
				{
					$input_item ='                <label for="new_' . $field_name . '">' . $nice_field_name . '</label>
                <date-picker 
                        class="form-control"
                        v-model="new_' . $field_name . '" 
                        id="new_' . $field_name . '"
                        type="datetime"
                        lang="en"
                        format="YYYY-MM-DD hh:mm:ss"
                        confirm
                ></date-picker>' . "\n";

					$field_variable = '				new_' . $field_name . ': \'\',' . "\n";
					$reset_item = '						this.new_' . $field_name . ' = \'\';' . "\n";
					if($field_data['nullable'] === false)
					{
						$warning_item = '				if (this.new_' . $field_name . '.trim() === \'\')
				{
					this.$swal({
						type: \'error\',
						title: \'Oops...\',
						text: \'Please fill in ' . $nice_field_name . ' field\',
					});

					return;
				}
';
					}
				}
				else if($field_data['field_type'] == 'text')
				{
					$input_item ='                <label for="' . $field_name . '">' . $uc_field . '</label>
                <textarea 
                        class="form-control" 
                        v-model="new_' . $field_name . '"
                        id="new_' . $field_name . '"
                ></textarea>' . "\n";

					$field_variable = '				new_' . $field_name . ': \'\',' . "\n";
					$reset_item = '						this.new_' . $field_name . ' = \'\';' . "\n";
					if($field_data['nullable'] === false)
					{
						$warning_item = '				if (this.new_' . $field_name . '.trim() === \'\')
				{
					this.$swal({
						type: \'error\',
						title: \'Oops...\',
						text: \'Please fill in ' . $nice_field_name . ' field\',
					});

					return;
				}
';
					}
				}
			}

			$call_item = '					' . $field_name . ': this.new_' . $field_name . ',' . "\n";


			$inputs .= $input_item;
			$variables .= $field_variable;
			$warnings .= $warning_item;
			$call_data .= $call_item;
			$reset_data .= $reset_item;
		}

		if($enable_load_data === true)
		{
			$stub = str_replace('{{load_data}}', '        	this.load_data();', $stub);
			$variables .= '				loading_data_sources: [],' . "\n";

			$load_functions_stub = $this->files->get(__DIR__ . '/../stubs/vuecrud_create_load_functions.stub');
			$load_functions_stub = str_replace('{{load_components}}', $load_components, $load_functions_stub);

			$stub = str_replace('{{loading_functions}}', $load_functions_stub, $stub);
		}
		else
		{
			$stub = str_replace('{{load_data}}', '', $stub);
			$stub = str_replace('{{loading_functions}}', '', $stub);
		}
		$stub = str_replace('{{inputs}}', $inputs, $stub);
		$stub = str_replace('{{variables}}', $variables, $stub);
		$stub = str_replace('{{warnings}}', $warnings, $stub);
		$stub = str_replace('{{call_data}}', $call_data, $stub);
		$stub = str_replace('{{reset_data}}', $reset_data, $stub);
		$stub = str_replace('{{uc_item}}', $uc_item, $stub);
		$stub = str_replace('{{lower_item}}', $lower_item, $stub);
		$stub = str_replace('{{module}}', $this->my_module_name, $stub);
		$stub = str_replace('{{page}}', $this->my_page_name, $stub);
		$stub = str_replace('{{settings_calls}}', $load_settings_calls, $stub);
		$stub = str_replace('{{settings_vars}}', $settings_vars, $stub);
		$stub = str_replace('{{settings_functions}}', $load_settings_functions, $stub);

		return $stub;
	}

	protected function compileCrudPage($stub_src)
	{
		$stub = $this->files->get($stub_src);

		$stub = str_replace('{{page}}', $this->my_page_name, $stub);

		return $stub;
	}

	protected function injectLayoutLink($page_path)
	{
		$layout = $this->files->get($page_path);

		if($layout)
		{
			$file_lines = explode("\n" , str_replace("\r\n" , "\n" , $layout));
			
			if(is_array($file_lines))
			{
				$file_lines_count = count($file_lines);

				$inject_pos = -1;
				$inject_after = '<ul class="nav flex-column">';
				$page_name = strtolower($this->my_page_name);
				$exists_test = 'to="/' . $this->my_folder_name . '/' . $page_name . '"';

				//find inject point
				for ($i = 0; $i < $file_lines_count; $i++)
				{
					$search_pos = strpos(trim($file_lines[$i]), $inject_after);
					if ($search_pos === 0)
					{
						$inject_pos = $i;
						break;
					}
				}

				$add_link = true;
				for ($i = 0; $i < $file_lines_count; $i++)
				{
					$search_pos = strpos(trim($file_lines[$i]), $exists_test);
					if ($search_pos !== false)
					{
						$add_link = false;
						break;
					}
				}

				if($inject_pos != -1 && $add_link === true)
				{
					array_splice( $file_lines, $inject_pos + 1, 0, '                    <li class="nav-item">');
					array_splice( $file_lines, $inject_pos + 2, 0,
						'                        <router-link to="/' . $this->my_folder_name . '/' . $page_name . '" class="nav-link text-white" href="#">' . $this->my_page_name . '</router-link>');
					array_splice( $file_lines, $inject_pos + 3, 0, '                    </li>');
					$this->files->put($page_path , implode("\n" , $file_lines));
				}
			}
		}
	}

	protected function injectRoute($route_path)
	{
		$file_contents = $this->files->get($route_path);

		if($file_contents)
		{
			$file_lines = explode("\n" , str_replace("\r\n" , "\n" , $file_contents));

			if(is_array($file_lines))
			{
				$file_lines_count = count($file_lines);

				$inject_pos = -1;
				$inject_after_first_line = 'component: Vue.component( \'' . $this->my_layout_name . '\', require( \'./layouts/' . $this->my_layout_name . '.vue\' ) )';
				$inject_after_second_line = 'children: [';

				$page_name = strtolower($this->my_page_name);
				$exists_test = 'path: \'' . $page_name . '\',';

				//find inject point
				for ($i = 0; $i < $file_lines_count; $i++)
				{
					$search_pos = strpos(trim($file_lines[$i]), $inject_after_first_line);
					if ($search_pos === 0)
					{
						$inject_pos = $i;
						break;
					}
				}

				$do_inject = false;
				if($inject_pos != -1 && isset($file_lines[$inject_pos + 1]))
				{
					$indent_pos = strpos($file_lines[$inject_pos + 1], $inject_after_second_line);
					if($indent_pos !== false)
					{
						$inject_pos += 1;

						$found = false;
						//see if it's already there
						for ($i = $inject_pos; $i < $file_lines_count; $i++)
						{
							$search_pos = strpos(trim($file_lines[$i]), $exists_test);
							if ($search_pos !== false)
							{
								$found = true;
								break;
							}
							
							$search_pos = strpos(trim($file_lines[$i]), ']');
							if ($search_pos === $indent_pos) //found the closing array for layout children (very rough parsing)
							{
								break;
							}
						}
						
						if($found === false)
						{
							$do_inject = true;
						}
					}
				}

				if($inject_pos != -1 && $do_inject === true)
				{
					array_splice( $file_lines, $inject_pos + 1, 0, '            {');
					array_splice( $file_lines, $inject_pos + 2, 0, '				path: \'' . $page_name . '\',');
					array_splice( $file_lines, $inject_pos + 3, 0,
						'				component: Vue.component( \'' . $this->my_page_name . '\', require( \'./pages/' . $this->my_layout_name . '/' . $this->my_page_name . '.vue\' ) )');
					array_splice( $file_lines, $inject_pos + 4, 0, '            },');
					$this->files->put($route_path , implode("\n" , $file_lines));
				}
			}
		}
	}
}