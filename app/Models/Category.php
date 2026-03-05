<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'image',
        'parent_id',
        'is_deleted'
    ];

    protected static function booted()
    {
        if (!Schema::hasColumn('categories', 'is_deleted')) {
            return;
        }

        static::addGlobalScope('not_deleted', function ($query) {
            $query->where('is_deleted', 0);
        });
    }

    // Parent category
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Sub categories
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Ebooks
    public function ebooks()
    {
        return $this->hasMany(Ebook::class);
    }
}
