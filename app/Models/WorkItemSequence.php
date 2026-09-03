<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['year', 'next_sequence'])]
class WorkItemSequence extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'year';

    public $incrementing = false;

    protected $keyType = 'int';
}
