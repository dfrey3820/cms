<?php

namespace Buni\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Page extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'content', 'blocks', 'status', 'seo_title', 'seo_description', 'author_id'];

    protected $casts = [
        'blocks' => 'array',
    ];

    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'author_id');
    }

    public function revisions()
    {
        return $this->hasMany(PageRevision::class);
    }
}