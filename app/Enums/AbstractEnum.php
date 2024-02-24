<?php
declare(strict_types=1);

namespace App\Enums;

use ReflectionClass;

abstract class AbstractEnum
{
    private $values;
    private $names;

    final public static function isValid($value): bool
    {
        $instance = static::getInstance();
        return array_key_exists($value, $instance->names);
    }

    public static function getName($value): ?string
    {
        $instance = static::getInstance();
        return array_key_exists($value, $instance->names) ? $instance->names[$value] : null;
    }

    public static function getValue(string $name)
    {
        $instance = static::getInstance();
        return array_key_exists($name, $instance->values) ? $instance->values[$name] : null;
    }

    public static function getValues(): array
    {
        $instance = static::getInstance();
        return array_values($instance->values);
    }

    protected static function getInstance(): self
    {
        static $instance = null;

        if (null === $instance) {
            $instance = new static();
            $reflectionClass = new ReflectionClass(get_class($instance));
            $instance->values = $reflectionClass->getConstants();
            $instance->names = array_flip($instance->values);
        }

        return $instance;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }
}
