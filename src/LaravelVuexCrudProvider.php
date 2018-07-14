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
		]);
	}
	/**
	 * Register the application services.
	 *
	 * @return void
	 */

	public function register()
	{
		$this->registerLaravelCrudServiceGenerators();
		$this->registerVuexModuleGenerator();

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

		$this->app->singleton('command.softdreams.vuexcrud.vuexmodule', function ($app) {
			return $app['SoftDreams\LaravelVuexCrud\Generators\CrudVuexModuleCommand'];
		});
		$this->commands('command.softdreams.vuexcrud.vuexmodule');
	}

	private function registerVuexModuleGenerator()
	{
//		$this->app->singleton('command.laracasts.seed', function ($app) {
//			return $app['Laracasts\Generators\Commands\SeedMakeCommand'];
//		});
//		$this->commands('command.laracasts.seed');
	}
}