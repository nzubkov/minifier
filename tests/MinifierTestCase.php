<?php
if (!isset($argv)) {
    $argv = ['minify.php', '--help'];
}
ob_start();
require_once __DIR__ . '/../minify.php';
ob_end_clean();

trait MinifierTestCase
{
    protected function getMethod($name)
    {
        $class = new ReflectionClass('FileMinifier');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
    
    protected function getProperty($name)
    {
        $class = new ReflectionClass('FileMinifier');
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        return $property;
    }
}
