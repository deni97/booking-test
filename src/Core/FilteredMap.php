<?php

namespace Reservations\Core;

class FilteredMap 
{
    private $map;

    public function __construct(array $baseMap) 
    {
        $this->map = $baseMap;
    }

    public function has(string $name): bool
    {
        return isset($this->map[$name]);
    }

    public function get(string $name) 
    {
        return $this->map[$name] ?? null;
    }

    public function getInt(string $name)
    {
        return (int) $this->get($name);
    }

    public function getFloat(string $name)
    {
        return (float) $this->get($name);
    }

    public function getDate(string $name)
    {
        return date_create_from_format('Y-n-j', $this->get($name));
    }

    public function getString(string $name, bool $filter = true) 
    {
        $value = (string) $this->get($name);
        return $filter ? addslashes($value) : $value;
    }
}