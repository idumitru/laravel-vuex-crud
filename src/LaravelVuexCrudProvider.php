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
		//
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
	}

	/**
	 * Register the make:seed generator.
	 */
	private function registerLaravelCrudServiceGenerators()
	{
		$this->app->singleton('command.softdreams.vuexcrud.service', function ($app) {
			return $app['SoftDreams\LaravelVuexCrud\Generators\CrudServiceCommand'];
		});
		$this->commands('command.softdreams.vuexcrud.service');

		$this->app->singleton('command.softdreams.vuexcrud.api', function ($app) {
			return $app['SoftDreams\LaravelVuexCrud\Generators\CrudApiCommand'];
		});
		$this->commands('command.softdreams.vuexcrud.api');
	}

	private function registerVuexModuleGenerator()
	{
//		$this->app->singleton('command.laracasts.seed', function ($app) {
//			return $app['Laracasts\Generators\Commands\SeedMakeCommand'];
//		});
//		$this->commands('command.laracasts.seed');
	}
}