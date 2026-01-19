<?php

namespace Buni\Cms\Contracts;

interface ThemeInterface
{
    public function register();

    public function boot();

    public function getName(): string;

    public function getVersion(): string;

    public function getDescription(): string;

    public function getLayout(): string;
}