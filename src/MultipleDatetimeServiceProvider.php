<?php

namespace JoseChan\Admin\Extensions\MultipleDatetime;

use Encore\Admin\Admin;
use Encore\Admin\Form;
use Illuminate\Support\ServiceProvider;

class MultipleDatetimeServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(MultipleDatetimeExtension $extension)
    {
        if (! MultipleDatetimeExtension::boot()) {
            return ;
        }

        if ($views = $extension->views()) {
            $this->loadViewsFrom($views, 'multiple-datetime');
        }

        if ($this->app->runningInConsole() && $assets = $extension->assets()) {
            $this->publishes(
                [$assets => public_path('vendor/jose-chan/multiple-datetime')],
                'multiple-datetime'
            );
        }

        Admin::booting(function (){
            Form::extend('multipleDatetime', MultipleDatetime::class);

            if ($alias = MultipleDatetimeExtension::config('alias')) {
                Form::alias('multipleDatetime', $alias);
            }
        });
    }
}