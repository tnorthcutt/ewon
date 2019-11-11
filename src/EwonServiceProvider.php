<?php

namespace tnorthcutt\Ewon;

use Illuminate\Support\ServiceProvider;

class EwonServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application events.
     *
     * @return void
	 */
	public function boot()
	{
        $source = realpath($raw = __DIR__ . '/../config/ewon.php') ?: $raw;

        $this->publishes([
            $source => config_path('ewon.php'),
        ]);

        $this->mergeConfigFrom($source, 'ewon');
	}
	/**
	 * Register the service provider.
     *
     * @return void
	 */
	public function register()
	{
	}
}