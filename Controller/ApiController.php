<?php

namespace MyPlugin\Controller;

use WP_REST_Server;
use WP_REST_Request;
use MyPlugin\Model\PostModel;
use MyPlugin\View\JsonView;

class ApiController
{
    public function register_routes()
    {
        register_rest_route('myplugin/v1', '/posts', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_posts'],
        ]);
    }

    public function get_posts(WP_REST_Request $request)
    {
        $model = new PostModel();
        $posts = $model->get_all_posts();

        return JsonView::render($posts);
    }
}
