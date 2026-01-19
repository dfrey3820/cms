<?php

namespace Buni\Cms\Contracts;

interface PluginInterface
{
    public function register();

    public function boot();

    public function enable();

    public function disable();

    public function getName(): string;

    public function getVersion(): string;

    public function getDescription(): string;
}