<?php

namespace AGdev\Sage\ACFBlocks;

use Roots\Acorn\ServiceProvider;

class ACFBlocksServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('acfblocks', ACFBlocks::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Check whether WordPress and ACF are available; bail if not.
        if (! function_exists('acf_register_block_type') ||
            ! function_exists('add_filter') ||
            ! function_exists('add_action') ){
            return;
        }

        $this->bindSetupAction();
        $this->bindFilters();
    }

    public function bindFilters()
    {
        $acfblocks = $this->app['acfblocks'];

        // Add the default blocks location, 'views/blocks', via filter
        add_filter('sage-acf-gutenberg-blocks-templates', [$this->app['acfblocks'], 'blockDirectories']);
    }

    public function bindSetupAction()
    {           
        // Create blocks based on templates found in Sage's "views/blocks" directory        
        add_action('acf/init', [ $this->app['acfblocks'], 'createBlocks' ]);
    }
}
