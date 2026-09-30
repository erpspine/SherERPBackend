<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentAttachment extends Model
{
    protected $fillable = ['incident_report_id', 'stage', 'name', 'path', 'mime', 'size', 'uploaded_by'];

    protected $hidden = ['path'];
}
