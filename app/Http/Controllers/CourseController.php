<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Validation\Rule;
use Exception;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::orderBy('course_name_ar', 'asc')->get();

        return response()->json([
            'status' => true,
            'count'  => $courses->count(),
            'data'   => $courses
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_code'    => 'nullable|string|max:50|unique:courses,course_code',
            'course_name_ar' => 'required|string|max:150',
            'course_name_en' => 'required|string|max:150',
            'credit_hours'   => 'nullable|integer|min:1|max:10',
        ], [
            'course_code.unique'       => 'رمز المادة مسجل مسبقاً',
            'course_code.required'     => 'يرجى إدخال رمز المادة',
            'course_name_ar.required'  => 'اسم المادة بالعربي مطلوب',
            'course_name_en.required'  => 'اسم المادة بالإنجليزي مطلوب',
            'credit_hours.required'    => 'عدد الساعات مطلوب',
        ]);

        try {
            $course = Course::create($validated);

            return response()->json([
                'status'  => true,
                'message' => 'تمت إضافة المادة بنجاح',
                'data'    => $course
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'فشل في إضافة المادة',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // تعديل بيانات مادة دراسية (Update)
    // تعديل بيانات المادة (الاسم، الكود، الوصف)
public function update(Request $request, int $id)
{
    $course = Course::find($id);

    if (!$course) {
        return response()->json([
            'status'  => false,
            'message' => 'المادة الدراسية المطلوبة غير موجودة.'
        ], 404);
    }

    try {
        $validated = $request->validate([
            'course_code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('courses', 'course_code')->ignore($id, 'course_id'),
            ],
            'course_name_ar' => 'sometimes|required|string|max:150',
            'course_name_en' => 'sometimes|required|string|max:150',
            'credit_hours'   => 'sometimes|required|integer|min:1|max:10',
        ], [
            'course_code.unique'      => 'رمز المادة مسجل لمادة أخرى مسبقاً.',
            'course_code.required'    => 'رمز المادة مطلوب.',
            'course_name_ar.required' => 'اسم المادة بالعربي مطلوب.',
            'course_name_en.required' => 'اسم المادة بالإنجليزي مطلوب.',
            'credit_hours.required'   => 'عدد الساعات مطلوب.',
        ]);

        $course->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث بيانات المادة الدراسية بنجاح.',
            'data'    => $course
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status'  => false,
            'message' => 'فشل التحقق من البيانات المدخلة',
            'errors'  => $e->errors()
        ], 422);
    } catch (Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => 'حدث خطأ أثناء تعديل بيانات المادة.',
            'error'   => $e->getMessage()
        ], 500);
    }
}

    public function destroy(int $id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json([
                'status'  => false,
                'message' => 'المادة الدراسية المطلوبة غير موجودة.'
            ], 404);
        }

        try {
            // فحص التكامل المرجعي: منع حذف المادة إذا تم طرحها مسبقاً في أي فصل دراسي
            if (method_exists($course, 'courseOfferings') && $course->courseOfferings()->exists()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'لا يمكن حذف المادة الدراسية نظراً لوجود فصول دراسية ومقررات مطروحة مرتبطة بها.'
                ], 409);
            }

            $course->delete();

            return response()->json([
                'status'  => true,
                'data'    => [
                    'course_id'   => $course->course_id,
                    'course_name' => $course->course_name_ar ?? $course->name,
                ],
                'message' => 'تم حذف المادة الدراسية بنجاح.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'تعذر حذف المادة نظراً لارتباطها ببيانات أكاديمية أخرى.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
