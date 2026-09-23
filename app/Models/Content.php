<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $table = 'contents';
    protected $primaryKey = 'content_id';

    protected $fillable = [
        'course_doctor_id',
        'content_type_id',
        'uploaded_by',
        'title',
        'description',
        'source_type',
        'file_path',
        'file_name',
        'file_size',
        'file_extension',
        'video_url',
        'download_count',
        'status',
    ];

    public function courseDoctor()
    {
        return $this->belongsTo(CourseDoctor::class, 'course_doctor_id', 'course_doctor_id');
    }

    public function contentType()
    {
        return $this->belongsTo(ContentType::class, 'content_type_id', 'content_type_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'user_id');
    }
}
