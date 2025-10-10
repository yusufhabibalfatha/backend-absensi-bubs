<?php

use MyPlugin\Controller\ApiController;

add_action('rest_api_init', function () {
    $controller = new ApiController();
    $controller->register_routes();
});
