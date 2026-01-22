<?php
namespace Buni\Cms\SampleTheme\Dsccore\Controllers;

use Illuminate\Http\Request;

class ThemeController
{
    public function index(Request $request)
    {
        return response('DSC Core theme admin page', 200);
    }
}
