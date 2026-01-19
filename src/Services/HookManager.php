<?php

namespace Buni\Cms\Services;

use Closure;

class HookManager
{
    protected $hooks = [];

    public function addAction($hook, Closure $callback, $priority = 10)
    {
        $this->hooks[$hook][$priority][] = $callback;
    }

    public function doAction($hook, ...$args)
    {
        if (!isset($this->hooks[$hook])) return;

        ksort($this->hooks[$hook]);

        foreach ($this->hooks[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }

    public function addFilter($hook, Closure $callback, $priority = 10)
    {
        $this->hooks[$hook][$priority][] = $callback;
    }

    public function applyFilters($hook, $value, ...$args)
    {
        if (!isset($this->hooks[$hook])) return $value;

        ksort($this->hooks[$hook]);

        foreach ($this->hooks[$hook] as $callbacks) {
            foreach ($callbacks as $callback) {
                $args[0] = $value;
                $value = call_user_func_array($callback, $args);
            }
        }

        return $value;
    }
}