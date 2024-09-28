<?php

namespace ObvioBySage\Telemetry\Tests;

use Exception;
use Orchestra\Testbench\TestCase as BaseTestCase;
use ReflectionClass;
use ReflectionException;

abstract class TestCase extends BaseTestCase
{
    protected function callMethod($obj, string $method, array $params = [])
    {
        try {
            $className = get_class($obj);
            $reflection = new ReflectionClass($className);
        } catch (ReflectionException $e) {
            throw new Exception($e->getMessage());
        }

        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $params);
    }

    protected function getProperty($obj, $propertyName)
    {
        try {
            $className = get_class($obj);
            $reflection = new ReflectionClass($className);
        } catch (ReflectionException $e) {
            throw new Exception($e->getMessage());
        }

        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($obj);
    }

    protected function setProperty($obj, $propertyNames, $value = null)
    {
        try {
            $reflection = new ReflectionClass($obj);
        } catch (ReflectionException $e) {
            throw new Exception($e->getMessage());
        }

        if (is_array($propertyNames) === false) {
            $property = $reflection->getProperty($propertyNames);
            $property->setAccessible(true);
            $property->setValue($obj, $value);

            return;
        }

        foreach ($propertyNames as $propertyName => $value) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue($obj, $value);
        }
    }
}
