<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'text',
    ];
}
