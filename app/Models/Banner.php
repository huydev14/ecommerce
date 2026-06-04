<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'image_public_id',
        'image_url',
        'link',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['optimized_image_url'];
    protected $hidden = ['image_url'];

    protected function optimizedImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->image_url) {
                    return str_replace('/image/upload/', '/image/upload/q_auto,f_auto/', $this->image_url);
                }
                return null;
            }
        );
    }
}
