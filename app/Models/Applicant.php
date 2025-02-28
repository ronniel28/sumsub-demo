<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;


use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_user_id', // Add this line
        'applicant_id', // Add this line
        'websdk_url', // Add this line
    ];
}
