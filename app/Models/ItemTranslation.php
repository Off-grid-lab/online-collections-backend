<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'title',
        'description',
        'description_source',
        'description_source_link',
        'work_type',
        'object_type',
        'work_level',
        'topic',
        'subject',
        'measurement',
        'dating',
        'medium',
        'technique',
        'inscription',
        'place',
        'state_edition',
        'gallery',
        'relationship_type',
        'related_work',
    ];

    protected $casts = [
        'additionals' => 'json',
    ];
}
