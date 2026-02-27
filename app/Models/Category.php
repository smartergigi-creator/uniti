<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'image',
        'parent_id'
    ];

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
