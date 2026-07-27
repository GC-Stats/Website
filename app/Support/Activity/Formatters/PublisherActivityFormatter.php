<?php

namespace App\Support\Activity\Formatters;

class PublisherActivityFormatter extends BaseActivityFormatter
{
    protected array $labels = [
        'name' => 'Name',
        'slug' => 'Slug',
        'socials' => 'Social links',
        'max_permissions' => 'Permission ceiling',
        'permissions' => 'Permissions',
        'publisher_id' => 'Publisher',
        'role' => 'Role',
        'user_id' => 'User',
        'logo_id' => 'Logo',
        'title' => 'Title',
        'lang' => 'Language',
        'status' => 'Status',
        'published_at' => 'Published at',
        'is_featured' => 'Featured',
        'show_on_home' => 'Show on home',
        'excerpt' => 'Excerpt',
        'bio' => 'Bio',
        'news_id' => 'Article',
        'image_id' => 'Image',
        'platform' => 'Platform',
        'url' => 'URL',
        'language_code' => 'Language',
        'is_active' => 'Active',
    ];
}
