<?php

namespace Dsc\Cms\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = ['name', 'file_name', 'mime_type', 'path', 'disk', 'file_hash', 'collection', 'size'];

    public function getUrlAttribute()
    {
        return \Storage::disk($this->disk)->url($this->path);
    }
}