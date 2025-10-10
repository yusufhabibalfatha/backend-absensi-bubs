<?php

namespace MyPlugin\View;

class JsonView
{
    public static function render($data)
    {
        return rest_ensure_response($data);
    }
}
