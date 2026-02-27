<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ebook extends Model
{
    // ✅ Correct table name
    protected $table = 'ebook';

   protected $fillable = [
'title',
        'file_title',
        'pdf_path',
        'folder_path',
        'page_count',
        'category_id',
        'subcategory_id',
        'related_subcategory_id',
        'uploaded_by',
        'user_id',
        'share_token',
        'share_expires_at',
        'share_enabled',
        'shared_by',
        'max_views',
        'current_views'
];


    public function pages()
    {
        return $this->hasMany(EbookPage::class);
    }

    public function coverPage()
    {
        return $this->hasOne(EbookPage::class)->oldestOfMany('page_no');
    }
    // Upload pannina user
    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uploadedByUser()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Share pannina user
    public function sharedUser()
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function relatedSubcategory()
    {
        return $this->belongsTo(Category::class, 'related_subcategory_id');
    }
}
