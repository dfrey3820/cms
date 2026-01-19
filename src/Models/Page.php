<?php

namespace Dsc\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Page extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'content', 'blocks', 'status', 'seo_title', 'seo_description'];

    protected $casts = [
        'blocks' => 'array',
    ];

    public function revisions()
    {
        return $this->hasMany(PageRevision::class);
    }
}