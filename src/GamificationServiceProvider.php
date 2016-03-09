<?php

namespace Pbmedia\Gamification;

use Illuminate\Support\ServiceProvider;

class GamificationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (!class_exists('CreateGamificationPointsTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__ . '/../resources/migrations/create_gamification_points_table.php.stub' => database_path('migrations/' . $timestamp . '_create_gamification_points_table.php'),
            ], 'migrations');
        }
    }

    public function register()
    {

    }
}
