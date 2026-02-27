<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EbookPage extends Model
{
    protected $fillable = [
        'ebook_id',
        'page_no',
        'image_path',
        'orientation',
        'width',
        'height'
    ];

    public function ebook()
    {
        return $this->belongsTo(Ebook::class);
    }
}
