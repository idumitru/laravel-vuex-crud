<?php

namespace SoftDreams\LaravelVuexCrud\Generators;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use SoftDreams\LaravelVuexCrud\Traits\CrudServiceGeneratorFunctions;
use SoftDreams\LaravelVuexCrud\Traits\TabIndenter;

class CrudVueCrudPageCommand extends Command
{
	use DetectsApplicationNamespace;
	use CrudServiceGeneratorFunctions;
	use TabIndenter;

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

		$page_file_exists = false;
		$page_file_path = $pages_layout_path . '/' . $this->my_page_name . '.vue';
		if ($this->files->exists($page_file_path)) {
			$page_file_exists = true;
			$this->info('Page ' . $this->my_page_name .  ' already exits! (' . $page_file_path . ')');
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
		if(count($this->table_config['fields']) == 0)
		{
			return $this->error('Unable to generate table configuration from crud service  ' . $this->my_service_name .  ' (' . $table_detail_class . '::GetCrudTableDetails()->GetConfig())');
		}

		if($page_file_exists === false)
		{
			$this->files->put($page_file_path, $this->compileCrudPage(__DIR__ . '/../stubs/vuecrud_crudpage.stub'));
		}

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

		$reload_component = '';
		$reload_var = '';
		$reload_computed = '';
		$reload_watch = '';
		$reload_prepare = '';
		$trigger_component = '';
		$trigger_var = '';
		if($this->table_config['table']['filter_change_update'] === true)
		{
			$reload_component = '                     :reload_monitor.sync="reload_monitor"' . "\n";
			$reload_var = '				reload_monitor: {},' . "\n";

			$reload_prepare = '				this.reload_monitor = {' . "\n";

			foreach($this->table_config['table']['filter_change_update_vars'] as $filter_change_update_var)
			{
				$reload_computed = '			' . $filter_change_update_var['tag'] . ': function () {
				return this.$store.getters[\'' . $filter_change_update_var['getter'] . '\'];
			},' . "\n";
				$reload_watch = '			' . $filter_change_update_var['tag'] . ': function (val) {
				this.reload_monitor[\'' . $filter_change_update_var['tag'] . '\'] = val;
				this.reload_monitor = JSON.parse(JSON.stringify(this.reload_monitor));
			},' . "\n";

				$reload_prepare .= '					' . $filter_change_update_var['tag'] . ': this.' . $filter_change_update_var['tag'] . ',' . "\n";
			}

			$reload_prepare .= '				};' . "\n";
		}
		
		if($this->table_config['table']['has_trigger_filter'] === true)
		{
			$trigger_component = '                     :wait_for_trigger=1
                     :trigger_data="trigger_data"' . "\n";

			$trigger_var = '				trigger_data: [' . "\n";
			foreach($this->table_config['table']['triger_filters'] as $trigger_filter)
			{
				$trigger_var .= '					{
						data_type: \'' . $trigger_filter['type'] . '\',
						compare: ' . $trigger_filter['compare'] . ',
						data_getter: \'' . $trigger_filter['data_getter'] . '\'
					},' . "\n";
			}
			$trigger_var .= '				],' . "\n";
		}
		
		$columns = '';
		$filters = '';
		foreach($this->table_config['fields'] as $field_name => $field_data)
		{
			if($field_data['is_filter'] === true)
			{
				$filters .= '                            {
								column: \'' . $field_name . '\',
								compare_type: \'' . $field_data['filter_compare'] . '\',
								data_getter: \'' . $field_data['filter_source'] . '\'
                            }
';
			}

			if($field_data['primary'] === true || $field_data['hidden'] === true || $field_data['vue_hidden'] === true)
			{
				continue;
			}

			$nice_field_name = ucwords(str_replace('_' , ' ' , $field_name));
			if($field_data['has_relation'] === true)
			{
				$vuex_module = $field_data['relation']['vuex_module'];
				$relation_column = $field_data['relation']['match_column'];
				$relation_value = $field_data['relation']['match_value'];

				$relation_server_filters = '';
				if($field_data['with_filters'] != '')
				{
					$config_filters = false;
					$relation_class = '';

					if(@class_exists($field_data['with_filters']))
					{
						$relation_class = $field_data['with_filters'];
						$config_filters = true;
					}
					else
					{
						$relation_class_namespace = '\\' . app()['config']["vuexcrud.sections." .  $this->crud_section]['crudservice_namespace'] . '\\' . $field_data['with_filters'];
						if(@class_exists($relation_class_namespace))
						{
							$relation_class = $relation_class_namespace;
							$config_filters = true;
						}

					}
					if ($config_filters === true)
					{
						$relation_table_detail = $relation_class::GetCrudTableDetails();
						$relation_config = $relation_table_detail->GetConfig();
						foreach($relation_config['fields'] as $relation_field_name => $relation_field_data)
						{
							if ($relation_field_data['is_filter'] === true)
							{
								$relation_server_filters .= '                            {
								column: \'' . $relation_field_name . '\',
								compare_type: \'' . $relation_field_data['filter_compare'] . '\',
								data_getter: \'' . $relation_field_data['filter_source'] . '\'
                            }
';
							}
						}

						if($relation_server_filters != '')
						{
							$relation_server_filters =
								'                        server_filters: [' . "\n" .
								$relation_server_filters .
								'                        ],' . "\n";
						}

					}
					else
					{
						$this->info("Could not load relation configuration for crud service " . $field_data['with_filters'] . ". Perhaps you need to specify full namespace path");
					}
				}

				$columns .='							{
								grid_column: \'' . $nice_field_name . '\',
								data_column: \'' . $field_name . '\',
								editable: true,
								type: \'dropdown\',
								data_source: {
									type: \'vuex\',
                                    tag: \'' . $field_name . '_' . $vuex_module . '\',
									data_getter: \'' . $vuex_module . '/' . $vuex_module . 'GetItems\',
                                    fetch_action: \'' . $vuex_module . '/' . $vuex_module . 'FetchItems\',
                                    match_field: \'' . $relation_column . '\',
                                    match_value: \'' . $relation_value . '\','.
					$relation_server_filters .'
								}
							},' . "\n";
			}
			else if($field_data['is_dropdown'] === true && count($field_data['dropdown_options']) > 0)
			{
				$field_options = '';
				foreach($field_data['dropdown_options'] as $dropdown_option)
				{
					$field_options .= '										{
											data_value: ' . (is_string($dropdown_option['value'])?"'" . $dropdown_option['value'] .  "'":$dropdown_option['value']) . ',
											label: \'' . $dropdown_option['display'] . '\'
										},' . "\n";
				}
				$columns .='							{
								grid_column: \'' . $nice_field_name . '\',
								data_column: \'' . $field_name . '\',
								editable: true,
								type: \'dropdown\',
								data_source: {
									type: \'provided\',
									options: ['.
					$field_options . '
									]
								}
							},' . "\n";
			}
			else
			{
				$columns .='							{
								grid_column: \'' . $nice_field_name . '\',
								data_column: \'' . $field_name . '\',
								editable: true,
								type: \'editbox\'
							},' . "\n";
			}
		}

		if($filters != '')
		{
			$filters =
				'                        server_filters: [' . "\n" .
				$filters .
				'                        ],' . "\n";
		}

		$stub = str_replace('{{reload_component}}', $reload_component, $stub);
		$stub = str_replace('{{reload_var}}', $reload_var, $stub);
		$stub = str_replace('{{reload_computed}}', $reload_computed, $stub);
		$stub = str_replace('{{reload_watch}}', $reload_watch, $stub);
		$stub = str_replace('{{reload_prepare}}', $reload_prepare, $stub);

		$stub = str_replace('{{trigger_component}}', $trigger_component, $stub);
		$stub = str_replace('{{trigger_var}}', $trigger_var, $stub);

		$stub = str_replace('{{filters}}', $filters, $stub);
		$stub = str_replace('{{columns}}', $columns, $stub);

		return $stub;
	}

	protected function compileCreateItem($stub_src)
	{
		$stub = $this->files->get($stub_src);

		$stubs = [
			'server_filters' => $this->files->get(__DIR__ . '/../stubs/partials/server_filters.stub'),
			'server_filters_item' => $this->files->get(__DIR__ . '/../stubs/partials/server_filters_item.stub'),
			'load_components_item' => $this->files->get(__DIR__ . '/../stubs/partials/load_components_item.stub'),
			'prepare_settings_function' => $this->files->get(__DIR__ . '/../stubs/partials/prepare_settings_function.stub'),
			'prop_variable' => $this->files->get(__DIR__ . '/../stubs/partials/prop_variable.stub'),
			'watch_prop_var' => $this->files->get(__DIR__ . '/../stubs/partials/watch_prop_var.stub'),
			'watch_data_var' => $this->files->get(__DIR__ . '/../stubs/partials/watch_data_var.stub'),
		];

		$uc_item = ucwords($this->my_page_name);
		$lower_item = strtolower($this->my_page_name);

		$inputs = '';
		$prop_variables = [];
		$watch_prop_variables = [];
		$data_variables = [];
		$warnings = '';
		$call_data = '';
		$reset_data = '';
		$enable_load_data = false;
		$load_components_items = [];
		$load_settings_calls = '';
		$load_settings_functions = [];
		$settings_vars = '';
		$destroy_calls = '';
		foreach($this->table_config['fields'] as $field_name => $field_data)
		{
			if($field_data['is_filter'] === false && ($field_data['primary'] === true || $field_data['hidden'] === true || $field_data['hide_for_create'] === true))
			{
				continue;
			}

			$input_item = '';
			$warning_item = '';
			$call_item = '';
			$reset_item = '';

			$uc_field = ucwords($field_name);
			$nice_field_name = ucwords(str_replace('_' , ' ' , $field_name));

			if($field_data['is_filter'] === false)
			{
				if($field_data['has_relation'] === true)
				{
					$enable_load_data = true;
					$vuex_module = $field_data['relation']['vuex_module'];
					$relation_column = $field_data['relation']['match_column'];
					$relation_value = $field_data['relation']['match_value'];
					$nice_relation_name = ucwords(str_replace('_' , ' ' , $vuex_module));
					$singular_relation_name = str_singular($nice_relation_name);

					$server_filters = '';
					$server_filters_items = [];
					if($field_data['with_filters'] != '')
					{
						$config_filters = false;
						$relation_class = '';

						if(@class_exists($field_data['with_filters']))
						{
							$relation_class = $field_data['with_filters'];
							$config_filters = true;
						}
						else
						{
							$relation_class_namespace = '\\' . app()['config']["vuexcrud.sections." .  $this->crud_section]['crudservice_namespace'] . '\\' . $field_data['with_filters'];
							if(@class_exists($relation_class_namespace))
							{
								$relation_class = $relation_class_namespace;
								$config_filters = true;
							}

						}
						if ($config_filters === true)
						{
							$relation_table_detail = $relation_class::GetCrudTableDetails();
							$relation_config = $relation_table_detail->GetConfig();
							foreach($relation_config['fields'] as $relation_field_name => $relation_field_data)
							{
								if ($relation_field_data['is_filter'] === true)
								{
									$new_server_filter_item = $stubs['server_filters_item'];
									$new_server_filter_item = str_replace('{{server_filters_item_column}}', $relation_field_name, $new_server_filter_item);
									$new_server_filter_item = str_replace('{{server_filters_item_compare_type}}', $relation_field_data['filter_compare'], $new_server_filter_item);
									$new_server_filter_item = str_replace('{{server_filters_item_data_getter}}', $relation_field_data['filter_source'], $new_server_filter_item);

									$server_filters_items[] = $new_server_filter_item;
								}
							}
							
							if(count($server_filters_items) > 0)
							{
								$server_filters = str_replace('{{server_filter_items}}', static::tabIndent(implode("\n" , $server_filters_items) , 1), $stubs['server_filters']);
							}

						}
						else
						{
							$this->info("Could not load relation configuration for crud service " . $field_data['with_filters'] . ". Perhaps you need to specify full namespace path");
						}
					}

					if($field_data['field_type'] == 'string' || $field_data['field_type'] == 'number')
					{
						$v_model = 'v-model';
						if($field_data['field_type'] == 'number')
						{
							$v_model = 'v-model.number';
						}
						$input_item ='                <div>
                    <label for="' . $uc_field . '">' . $nice_field_name . '</label>
                    <select
                            id="' . $uc_field . '"
                            class="form-control"
                            ' . $v_model . '="new_' . $field_name . '"
                    >
                        <option value="0">[Select ' . $singular_relation_name . ']</option>
                        <option v-for="item in $store.state.' . $vuex_module . '.' . $vuex_module . '"
                                :value="item.' . $relation_column . '">{{item.' . $relation_value . '}}
                        </option>
                    </select>
                </div>' . "\n";

						$new_load_components_item = $stubs['load_components_item'];
						$new_load_components_item = str_replace('{{load_components_tag}}', $vuex_module, $new_load_components_item);
						$new_load_components_item = str_replace('{{load_components_fetch_action}}', $vuex_module . '/'. $vuex_module, $new_load_components_item);
						$new_load_components_item = str_replace('{{server_filters}}', static::tabIndent($server_filters , 1), $new_load_components_item);
						$load_components_items[] = $new_load_components_item;

						$call_item = '					    ' . $field_name . ': this.new_' . $field_name . ',' . "\n";
					}
					elseif ($field_data['field_type'] == 'smart_input')
					{
						$input_item ='                <div>
                    <label for="' . $uc_field . '">' . $nice_field_name . '</label>
                    <SmartInput
                            id="' . $uc_field . '"
                            info_name="' . $singular_relation_name . '"
                            mode="vuex"
                            :settings="' . $field_name . '_settings"
                            :settings_ready.sync="' . $field_name . '_settings_ready"
                            label="' . $relation_value . '"
                            return_label="' . $relation_column . '"
                            v-model="new_' . $field_name . '"
                    ></SmartInput>
				</div>' . "\n";

						$settings_vars .= '                ' . $field_name . '_settings: {},' . "\n";
						$settings_vars .= '                ' . $field_name . '_settings_ready: false,' ."\n";

						$load_settings_calls .= '            this.prepare_' . $field_name . '_settings();';
						
						$new_prepare_settings_function = $stubs['prepare_settings_function'];
						$new_prepare_settings_function = str_replace('{{prepare_settings_field_name}}', $field_name, $new_prepare_settings_function);
						$new_prepare_settings_function = str_replace('{{prepare_settings_vuex_src}}', $vuex_module . '/' . $vuex_module, $new_prepare_settings_function);
						$new_prepare_settings_function = str_replace('{{prepare_settings_field}}', $relation_value, $new_prepare_settings_function);
						
						$new_load_components_item = $stubs['load_components_item'];
						$new_load_components_item = str_replace('{{load_components_tag}}', $vuex_module, $new_load_components_item);
						$new_load_components_item = str_replace('{{load_components_fetch_action}}', $vuex_module . '/'. $vuex_module, $new_load_components_item);
						$new_load_components_item = str_replace('{{server_filters}}', static::tabIndent($server_filters , 1), $new_load_components_item);

						$new_prepare_settings_function = str_replace('{{prepare_settings_load_components}}', static::tabIndent($new_load_components_item, 3), $new_prepare_settings_function);

						$load_settings_functions[] = $new_prepare_settings_function;
					}
					else
					{
						$input_item ='                <div><label for="' . $uc_field . '">' . $nice_field_name . '</label></div>' . "\n";
					}

					$prop_variable_stub = $stubs['prop_variable'];
					$prop_variable_stub = str_replace('{{prop_variable_field_name}}', $field_name, $prop_variable_stub);
					$prop_variable_stub = str_replace('{{prop_variable_default}}', 0, $prop_variable_stub);

					$prop_variables[] = $prop_variable_stub;

					$data_variables[] = 'new_' . $field_name . ': this.' . $field_name . ',';

					$watch_prop_var_stub = $stubs['watch_prop_var'];
					$watch_prop_var_stub = str_replace('{{watch_prop_field_name}}', $field_name, $watch_prop_var_stub);
					$watch_prop_var_stub = str_replace('{{watch_prop_data_field_name}}', 'new_' . $field_name, $watch_prop_var_stub);

					$watch_prop_variables[] = $watch_prop_var_stub;

					$watch_data_var_stub = $stubs['watch_data_var'];
					$watch_data_var_stub = str_replace('{{watch_data_field_name}}', 'new_' . $field_name, $watch_data_var_stub);
					$watch_data_var_stub = str_replace('{{watch_data_prop_field_name}}', $field_name, $watch_data_var_stub);

					$watch_prop_variables[] = $watch_data_var_stub;

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
				}
				else if($field_data['is_dropdown'] === true)
				{
					$v_model = 'v-model';
					if($field_data['field_type'] == 'number')
					{
						$v_model = 'v-model.number';
					}
					$input_item ='                <div>
                    <label for="new_' . $field_name . '">' . $nice_field_name . '</label>
                    <select
                            id="new_' . $field_name . '"
                            class="form-control"
                            ' . $v_model . '="new_' . $field_name . '"
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


					$input_item .= '                    </select>
                </div>' . "\n";

					$prop_variable_stub = $stubs['prop_variable'];
					$prop_variable_stub = str_replace('{{prop_variable_field_name}}', $field_name, $prop_variable_stub);
					$prop_variable_stub = str_replace('{{prop_variable_default}}', (is_string($first_option['value'])?"'" . $first_option['value'] . "'":$first_option['value']), $prop_variable_stub);

					$prop_variables[] = $prop_variable_stub;

					$data_variables[] = 'new_' . $field_name . ': this.' . $field_name . ',';

					$watch_prop_var_stub = $stubs['watch_prop_var'];
					$watch_prop_var_stub = str_replace('{{watch_prop_field_name}}', $field_name, $watch_prop_var_stub);
					$watch_prop_var_stub = str_replace('{{watch_prop_data_field_name}}', 'new_' . $field_name, $watch_prop_var_stub);

					$watch_prop_variables[] = $watch_prop_var_stub;

					$watch_data_var_stub = $stubs['watch_data_var'];
					$watch_data_var_stub = str_replace('{{watch_data_field_name}}', 'new_' . $field_name, $watch_data_var_stub);
					$watch_data_var_stub = str_replace('{{watch_data_prop_field_name}}', $field_name, $watch_data_var_stub);

					$watch_prop_variables[] = $watch_data_var_stub;

					$reset_item = '						this.new_' . $field_name . ' = ' . $first_option['value'] . ';' . "\n";
					$call_item = '					    ' . $field_name . ': this.new_' . $field_name . ',' . "\n";
				}
				else
				{
					if($field_data['field_type'] == 'string' || $field_data['field_type'] == 'number')
					{
						$input_type = 'text';
						if($field_data['field_type'] == 'number')
						{
							$input_type = 'number';
						}
						$input_item ='                <div>
                    <label for="new_' . $field_name . '">' . $nice_field_name . '</label>
                    <input
                            type="' . $input_type . '"
                            class="form-control"
                            id="new_' . $field_name . '"
                            placeholder="Enter ' . $nice_field_name . '"
                            v-model="new_' . $field_name . '"
                    >
                </div>' . "\n";

						$prop_variable_stub = $stubs['prop_variable'];
						$prop_variable_stub = str_replace('{{prop_variable_field_name}}', $field_name, $prop_variable_stub);
						$prop_variable_stub = str_replace('{{prop_variable_default}}', "''", $prop_variable_stub);

						$prop_variables[] = $prop_variable_stub;

						$data_variables[] = 'new_' . $field_name . ': this.' . $field_name . ',';

						$watch_prop_var_stub = $stubs['watch_prop_var'];
						$watch_prop_var_stub = str_replace('{{watch_prop_field_name}}', $field_name, $watch_prop_var_stub);
						$watch_prop_var_stub = str_replace('{{watch_prop_data_field_name}}', 'new_' . $field_name, $watch_prop_var_stub);

						$watch_prop_variables[] = $watch_prop_var_stub;

						$watch_data_var_stub = $stubs['watch_data_var'];
						$watch_data_var_stub = str_replace('{{watch_data_field_name}}', 'new_' . $field_name, $watch_data_var_stub);
						$watch_data_var_stub = str_replace('{{watch_data_prop_field_name}}', $field_name, $watch_data_var_stub);

						$watch_prop_variables[] = $watch_data_var_stub;

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
						$call_item = '					    ' . $field_name . ': this.new_' . $field_name . ',' . "\n";
					}
					else if($field_data['field_type'] == 'date')
					{
						$input_item ='                <div>
	                <label for="new_' . $field_name . '">' . $nice_field_name . '</label>
	                <date-picker 
	                        id="new_' . $field_name . '"
	                        v-model="new_' . $field_name . '" 
	                        lang="en"
	                        format="YYYY-MM-DD"
	                        confirm
	                ></date-picker>
                </div>' . "\n";

						$prop_variable_stub = $stubs['prop_variable'];
						$prop_variable_stub = str_replace('{{prop_variable_field_name}}', $field_name, $prop_variable_stub);
						$prop_variable_stub = str_replace('{{prop_variable_default}}', 'this.$moment().format(\'YYYY-MM-DD\')', $prop_variable_stub);

						$prop_variables[] = $prop_variable_stub;

						$data_variables[] = 'new_' . $field_name . ': this.' . $field_name . ',';

						$watch_prop_var_stub = $stubs['watch_prop_var'];
						$watch_prop_var_stub = str_replace('{{watch_prop_field_name}}', $field_name, $watch_prop_var_stub);
						$watch_prop_var_stub = str_replace('{{watch_prop_data_field_name}}', 'new_' . $field_name, $watch_prop_var_stub);

						$watch_prop_variables[] = $watch_prop_var_stub;

						$watch_data_var_stub = $stubs['watch_data_var'];
						$watch_data_var_stub = str_replace('{{watch_data_field_name}}', 'new_' . $field_name, $watch_data_var_stub);
						$watch_data_var_stub = str_replace('{{watch_data_prop_field_name}}', $field_name, $watch_data_var_stub);

						$watch_prop_variables[] = $watch_data_var_stub;

						$reset_item = '						this.new_' . $field_name . ' = this.$moment().format(\'YYYY-MM-DD\');' . "\n";
						if($field_data['nullable'] === false)
						{
							$warning_item = '				if (this.new_' . $field_name . ' === \'\')
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
						$call_item = '					    ' . $field_name . ': this.$moment(this.new_' . $field_name . ').format(\'YYYY-MM-DD\'),' . "\n";
					}
					else if($field_data['field_type'] == 'datetime')
					{
						$input_item ='                <div>
                    <label for="new_' . $field_name . '">' . $nice_field_name . '</label>
                    <date-picker 
                            v-model="new_' . $field_name . '" 
                            id="new_' . $field_name . '"
                            type="datetime"
                            lang="en"
                            format="YYYY-MM-DD hh:mm:ss"
                            confirm
                    ></date-picker>
                </div>' . "\n";

						$prop_variable_stub = $stubs['prop_variable'];
						$prop_variable_stub = str_replace('{{prop_variable_field_name}}', $field_name, $prop_variable_stub);
						$prop_variable_stub = str_replace('{{prop_variable_default}}', 'this.$moment().format(\'YYYY-MM-DD hh:mm:ss\')', $prop_variable_stub);

						$prop_variables[] = $prop_variable_stub;

						$data_variables[] = 'new_' . $field_name . ': this.' . $field_name . ',';

						$watch_prop_var_stub = $stubs['watch_prop_var'];
						$watch_prop_var_stub = str_replace('{{watch_prop_field_name}}', $field_name, $watch_prop_var_stub);
						$watch_prop_var_stub = str_replace('{{watch_prop_data_field_name}}', 'new_' . $field_name, $watch_prop_var_stub);

						$watch_prop_variables[] = $watch_prop_var_stub;

						$watch_data_var_stub = $stubs['watch_data_var'];
						$watch_data_var_stub = str_replace('{{watch_data_field_name}}', 'new_' . $field_name, $watch_data_var_stub);
						$watch_data_var_stub = str_replace('{{watch_data_prop_field_name}}', $field_name, $watch_data_var_stub);

						$watch_prop_variables[] = $watch_data_var_stub;

						$reset_item = '						this.new_' . $field_name . ' = this.$moment().format(\'YYYY-MM-DD hh:mm:ss\');' . "\n";
						if($field_data['nullable'] === false)
						{
							$warning_item = '				if (this.new_' . $field_name . ' === \'\')
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
						$call_item = '					    ' . $field_name . ': this.$moment(this.new_' . $field_name . ').format(\'YYYY-MM-DD\'),' . "\n";
					}
					else if($field_data['field_type'] == 'text')
					{
						$input_item ='                <div>
                    <label for="' . $field_name . '">' . $uc_field . '</label>
                    <textarea 
                            class="form-control" 
                            v-model="new_' . $field_name . '"
                            id="new_' . $field_name . '"
                    ></textarea>
                </div>' . "\n";

						$prop_variable_stub = $stubs['prop_variable'];
						$prop_variable_stub = str_replace('{{prop_variable_field_name}}', $field_name, $prop_variable_stub);
						$prop_variable_stub = str_replace('{{prop_variable_default}}', "''", $prop_variable_stub);

						$prop_variables[] = $prop_variable_stub;

						$data_variables[] = 'new_' . $field_name . ': this.' . $field_name . ',';

						$watch_prop_var_stub = $stubs['watch_prop_var'];
						$watch_prop_var_stub = str_replace('{{watch_prop_field_name}}', $field_name, $watch_prop_var_stub);
						$watch_prop_var_stub = str_replace('{{watch_prop_data_field_name}}', 'new_' . $field_name, $watch_prop_var_stub);

						$watch_prop_variables[] = $watch_prop_var_stub;

						$watch_data_var_stub = $stubs['watch_data_var'];
						$watch_data_var_stub = str_replace('{{watch_data_field_name}}', 'new_' . $field_name, $watch_data_var_stub);
						$watch_data_var_stub = str_replace('{{watch_data_prop_field_name}}', $field_name, $watch_data_var_stub);

						$watch_prop_variables[] = $watch_data_var_stub;

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
						$call_item = '					    ' . $field_name . ': this.new_' . $field_name . ',' . "\n";
					}
				}
			}
			else
			{
				$call_item = '					    ' . $field_name . ': this.$store.getters[\'' . $field_data['filter_source'] . '\'],' . "\n";
			}


			$inputs .= $input_item;
			$warnings .= $warning_item;
			$call_data .= $call_item;
			$reset_data .= $reset_item;
		}

		if($enable_load_data === true)
		{
			$stub = str_replace('{{load_data}}', '        	this.load_data();', $stub);
			$data_variables[] = 'loading_data_sources: [],';

			$load_functions_stub = $this->files->get(__DIR__ . '/../stubs/vuecrud_create_load_functions.stub');
			$load_functions_stub = str_replace('{{load_components}}', static::tabIndent(implode("\n" , $load_components_items) , 5), $load_functions_stub);

			$stub = str_replace('{{loading_functions}}', $load_functions_stub, $stub);

			$destroy_calls = '		    this.cancelLoadingSources();' . "\n";
		}
		else
		{
			$stub = str_replace('{{load_data}}', '', $stub);
			$stub = str_replace('{{loading_functions}}', '', $stub);
		}
		$stub = str_replace('{{inputs}}', $inputs, $stub);
		$stub = str_replace('{{prop_variables}}', static::tabIndent(implode("\n", $prop_variables), 3), $stub);
		$stub = str_replace('{{data_variables}}', static::tabIndent(implode("\n", $data_variables), 4), $stub);
		$stub = str_replace('{{watch_variables}}', static::tabIndent(implode("\n", $watch_prop_variables), 3), $stub);
		$stub = str_replace('{{warnings}}', $warnings, $stub);
		$stub = str_replace('{{call_data}}', $call_data, $stub);
		$stub = str_replace('{{reset_data}}', $reset_data, $stub);
		$stub = str_replace('{{uc_item}}', $uc_item, $stub);
		$stub = str_replace('{{lower_item}}', $lower_item, $stub);
		$stub = str_replace('{{module}}', $this->my_module_name, $stub);
		$stub = str_replace('{{page}}', $this->my_page_name, $stub);
		$stub = str_replace('{{settings_calls}}', $load_settings_calls, $stub);
		$stub = str_replace('{{settings_vars}}', $settings_vars, $stub);
		$stub = str_replace('{{settings_functions}}', static::tabIndent(implode("\n" , $load_settings_functions), 3), $stub);
		$stub = str_replace('{{destroy_calls}}', $destroy_calls, $stub);

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