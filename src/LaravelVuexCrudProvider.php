<?php


namespace SoftDreams\LaravelVuexCrud;

use Illuminate\Support\ServiceProvider;

class LaravelVuexCrudProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/config/vuexcrud.php' => config_path('vuexcrud.php'),
		], 'config');
	}
	/**
	 * Register the application services.
	 *
	 * @return void
	 */

	public function register()
	{
		$this->registerLaravelCrudServiceGenerators();

		$this->mergeConfigFrom(
			__DIR__.'/config/vuexcrud.php', 'vuexcrud'
		);
	}

	/**
	 * Register the make:seed generator.
	 */
	private function registerLaravelCrudServiceGenerators()
	{
		$this->app->singleton('command.softdreams.vuexcrud.service', function ($app) {
			return $app['SoftDreams\LaravelVuexCrud\Generators\CrudLaravelServiceCommand'];
		});
		$this->commands('command.softdreams.vuexcrud.service');

		$this->app->singleton('command.softdreams.vuexcrud.crudservice', function ($app) {
			return $app['SoftDreams\LaravelVuexCrud\Generators\CrudLaravelCrudServiceCommand'];
		});
		$this->commands('command.softdreams.vuexcrud.crudservice');

		$this->app->singleton('command.softdreams.vuexcrud.service.inject', function ($app) {
			return $app['SoftDreams\LaravelVuexCrud\Generators\CrudLaravelServiceInjectCommand'];
		});
		$this->commands('command.softdreams.vuexcrud.service.inject');

		$this->app->singleton('command.softdreams.vuexcrud.api', function ($app) {
			return $app['SoftDreams\LaravelVuexCrud\Generators\CrudLaravelApiCommand'];
		});
		$this->commands('command.softdreams.vuexcrud.api');

		$this->app->singleton('command.softdreams.vuexcrud.vueapp', function ($app) {
			return $app['SoftDreams\LaravelVuexCrud\Generators\CrudVueAppCommand'];
		});
		$this->commands('command.softdreams.vuexcrud.vueapp');

		$this->app->singleton('command.softdreams.vuexcrud.vuelayout', function ($app) {
			return $app['SoftDreams\LaravelVuexCrud\Generators\CrudVueLayoutCommand'];
		});
		$this->commands('command.softdreams.vuexcrud.vuelayout');

		$this->app->singleton('command.softdreams.vuexcrud.vuepage', function ($app) {
			return $app['SoftDreams\LaravelVuexCrud\Generators\CrudVuePageCommand'];
		});
		$this->commands('command.softdreams.vuexcrud.vuepage');

		$this->app->singleton('command.softdreams.vuexcrud.vuecrudpage', function ($app) {
			return $app['SoftDreams\LaravelVuexCrud\Generators\CrudVueCrudPageCommand'];
		});
		$this->commands('command.softdreams.vuexcrud.vuecrudpage');

		$this->app->singleton('command.softdreams.vuexcrud.vuexmodule', function ($app) {
			return $app['SoftDreams\LaravelVuexCrud\Generators\CrudVuexModuleCommand'];
		});
		$this->commands('command.softdreams.vuexcrud.vuexmodule');
	}

	public static function routes()
	{
		if(isset(app()['config']["vuexcrud.api_end_points"]))
		{
			$api_end_points = app()['config']["vuexcrud.api_end_points"];
			
			foreach($api_end_points as $api_end_point)
			{
				if(!@class_exists($api_end_point))
				{
					continue;
				}

				$route_data = $api_end_point::GetWebRoute();

				if($route_data !== false)
				{
					switch($route_data['method'])
					{
						case 'get':
							\Route::get('/' . $route_data['path'] . '/{catchall?}', "\\" . $route_data['class'] . '@' . $route_data['function'])->name($route_data['route_name'])->where('catchall', '(.*)');
							break;
						case 'post':
							\Route::post('/' . $route_data['path'] . '/{catchall?}', "\\" . $route_data['class'] . '@' . $route_data['function'])->name($route_data['route_name'])->where('catchall', '(.*)');
							break;
						case 'delete':
							\Route::delete('/' . $route_data['path'] . '/{catchall?}', "\\" . $route_data['class'] . '@' . $route_data['function'])->name($route_data['route_name'])->where('catchall', '(.*)');
							break;
						case 'put':
							\Route::put('/' . $route_data['path'] . '/{catchall?}', "\\" . $route_data['class'] . '@' . $route_data['function'])->name($route_data['route_name'])->where('catchall', '(.*)');
							break;
						default:
							\Route::get('/' . $route_data['path'] . '/{catchall?}', "\\" . $route_data['class'] . '@' . $route_data['function'])->name($route_data['route_name'])->where('catchall', '(.*)');
					}
				}
			}
		}
	}
}