<?php


namespace SoftDreams\LaravelVuexCrud;

use Illuminate\Support\ServiceProvider;

class LaravelVuexCrud extends ServiceProvider
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
		$this->registerLaravelCrudGenerator();
		$this->registerVuexModuleGenerator();
	}
	/**
	 * Register the make:seed generator.
	 */
	private function registerLaravelCrudGenerator()
	{
//		$this->app->singleton('command.laracasts.seed', function ($app) {
//			return $app['Laracasts\Generators\Commands\SeedMakeCommand'];
//		});
//		$this->commands('command.laracasts.seed');
	}

	private function registerVuexModuleGenerator()
	{
//		$this->app->singleton('command.laracasts.seed', function ($app) {
//			return $app['Laracasts\Generators\Commands\SeedMakeCommand'];
//		});
//		$this->commands('command.laracasts.seed');
	}
}