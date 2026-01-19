<?php

namespace Dsc\Cms\Services;

use Illuminate\Support\Collection;

class PageBuilder
{
    protected $blocks = [];

    public function registerBlock($name, $class)
    {
        $this->blocks[$name] = $class;
    }

    public function getBlocks()
    {
        return $this->blocks;
    }

    public function renderBlock($name, $data = [])
    {
        if (!isset($this->blocks[$name])) return '';

        $block = new $this->blocks[$name]();
        return $block->render($data);
    }

    public function renderPage($blocks)
    {
        $output = '';

        foreach ($blocks as $block) {
            $output .= $this->renderBlock($block['type'], $block['data']);
        }

        return $output;
    }
}