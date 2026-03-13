<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EbookIssueReport extends Model
{
    protected $fillable = [
        'ebook_id',
        'reported_by',
        'recipient_id',
        'page',
        'description',
    ];

    public function ebook()
    {
        return $this->belongsTo(Ebook::class, 'ebook_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
