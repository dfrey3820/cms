<?php

namespace Dsc\Cms\Models;

use Illuminate\Database\Eloquent\Model;

class PageRevision extends Model
{
    protected $fillable = ['page_id', 'content', 'blocks', 'user_id'];

    protected $casts = [
        'blocks' => 'array',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}