<?php

#[Attribute]
class Route
{
    public function __construct(
        public array|string $method,
        public string $route,
        public ?string $name = null
    ) {
        echo "";
    }
}
