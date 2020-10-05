<?php

namespace LaravelCode\EventSourcing\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ModelFinder
{
    private static array $cache = [];

    public static function extract(string $controller, string $namespace)
    {
        if (! preg_match('/(.*)\\\(.*)(Controller)$/i', $controller, $matches)) {
            Log::info('Unable to extract model from controller '.$controller);

            return;
        }

        if (isset(static::$cache[$controller])) {
            return static::$cache[$controller];
        }

        $model = $namespace.'\\'.Str::singular($matches[2]);
        if (class_exists($model)) {
            static::$cache[$controller] = $model;

            return $model;
        }
    }
}
