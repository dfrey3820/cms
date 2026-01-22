<?php
use PHPUnit\Framework\TestCase;

final class UserControllerTest extends TestCase
{
    public function test_class_and_methods_exist()
    {
        $class = 'Buni\\Cms\\Controllers\\Admin\\UserController';
        $this->assertTrue(class_exists($class), "Class $class should exist");

        $methods = ['index','create','store','edit','update','destroy','sendReset'];
        foreach ($methods as $m) {
            $this->assertTrue(method_exists($class, $m), "Method $m should exist on $class");
        }
    }
}
