<?php

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
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
