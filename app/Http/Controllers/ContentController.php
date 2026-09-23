<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Content;
use Illuminate\Support\Facades\Storage;
use Exception;

class ContentController extends Controller
{
    public function index(Request $request)
    {
        $query = Content::with([
            'contentType:content_type_id,name',
            'courseDoctor.doctor.user:user_id,full_name',
            'courseDoctor.offering.course:course_id,course_name_ar,course_code',
            'courseDoctor.offering.semester:semester_id,name',
        ]);

        // فلترة بحسب الدكتور المسند
        if ($request->filled('doctor_id')) {
            $query->whereHas('courseDoctor', function ($q) use ($request) {
                $q->where('doctor_id', $request->doctor_id);
            });
        }

        // فلترة بحسب المقرر المطروح
        if ($request->filled('offering_id')) {
            $query->whereHas('courseDoctor', function ($q) use ($request) {
                $q->where('offering_id', $request->offering_id);
            });
        }

        // فلترة بحسب الترم الدراسي
        if ($request->filled('semester_id')) {
            $query->whereHas('courseDoctor.offering', function ($q) use ($request) {
                $q->where('semester_id', $request->semester_id);
            });
        }

        // فلترة بحسب نوع المحتوى
        if ($request->filled('content_type_id')) {
            $query->where('content_type_id', $request->content_type_id);
        }

        // فلترة بحسب الإسناد المباشر
        if ($request->filled('course_doctor_id')) {
            $query->where('course_doctor_id', $request->course_doctor_id);
        }

        // بحث بالعنوان
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $contents = $query->latest('content_id')->get();

        return response()->json([
            'status' => true,
            'count'  => $contents->count(),
            'data'   => $contents,
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_doctor_id' => 'required|exists:course_doctors,course_doctor_id',
            'content_type_id'  => 'required|exists:content_types,content_type_id',
            'title'            => 'required|string|max:200',
            'description'      => 'nullable|string',
            'file'             => 'required|file|max:102400', // 100MB كحد أقصى
        ], [
            'course_doctor_id.required' => 'يرجى تحديد المقرر والدكتور المسند.',
            'content_type_id.required'  => 'يرجى اختيار نوع المحتوى.',
            'title.required'             => 'عنوان المحتوى مطلوب.',
            'file.required'              => 'الملف مطلوب.',
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileSize = $file->getSize();

            // حفظ الملف داخل مجلد التخزين العام
            $path = $file->store('course_contents', 'public');

            $content = Content::create([
                'course_doctor_id' => $validated['course_doctor_id'],
                'content_type_id'  => $validated['content_type_id'],
                'uploaded_by'      => auth()->id() ?? 1, // المعرف الافتراضي للمسؤول
                'title'            => $validated['title'],
                'description'      => $validated['description'] ?? null,
                'source_type'      => 'file',
                'file_path'        => $path,
                'file_name'        => $originalName,
                'file_size'        => $fileSize,
                'file_extension'   => strtolower($extension),
                'download_count'   => 0,
                'status'           => 'active',
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'تم رفع المحتوى الأكاديمي بنجاح.',
                'data'    => $content->load('contentType'),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'فشلت عملية رفع الملف: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $content = Content::find($id);

        if (!$content) {
            return response()->json([
                'status'  => false,
                'message' => 'المحتوى الأكاديمي غير موجود.',
            ], 404);
        }

        try {
            if ($content->file_path && Storage::disk('public')->exists($content->file_path)) {
                Storage::disk('public')->delete($content->file_path);
            }

            $content->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم حذف الملف بنجاح.',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'تعذر حذف الملف: ' . $e->getMessage(),
            ], 500);
        }
    }
}
