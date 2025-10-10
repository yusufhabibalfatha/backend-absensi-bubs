<?php

namespace MyPlugin\Model;

class PostModel
{
    public function get_all_posts()
    {
        $query = new \WP_Query([
            'post_type'      => 'post',
            'posts_per_page' => 5,
        ]);

        $posts = [];

        foreach ($query->posts as $post) {
            $posts[] = [
                'id'    => $post->ID,
                'title' => get_the_title($post),
                'link'  => get_permalink($post),
            ];
        }

        return $posts;
    }
}
