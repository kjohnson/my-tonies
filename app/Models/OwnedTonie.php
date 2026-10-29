<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A Content Tonie in our Toniecloud household.
 */
#[Fillable(['cloud_id', 'sales_id', 'title', 'series', 'image', 'household'])]
class OwnedTonie extends Model
{
    //
}
