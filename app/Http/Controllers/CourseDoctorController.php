<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CourseDoctor;
use Illuminate\Validation\Rule;
use Exception;

class CourseDoctorController extends Controller
{
   public function index(Request $request)
{
    // جلب العلاقات اللازمة حتى الوصول للقسم مع التأكد من جلب المفاتيح الأساسية والأجنبية
    $query = CourseDoctor::with([
        'doctor.user:user_id,full_name',
        'offering.course:course_id,course_code,course_name_ar',
        'offering.semester:semester_id,level_id,name',
        'offering.semester.level:level_id,department_id,name',
        'offering.semester.level.department:department_id,name',
    ]);

    if ($request->filled('offering_id')) {
        $query->where('offering_id', $request->offering_id);
    }

    $assignments = $query->latest('course_doctor_id')->get();

    // تشكيل الاستجابة وإضافة القسم والمستوى
    $flattened = $assignments->map(function ($item) {
        $semester   = $item->offering->semester ?? null;
        $level      = $semester->level ?? null;
        $department = $level->department ?? null;

        return [
            'course_doctor_id' => $item->course_doctor_id,
            'doctor_name'      => $item->doctor->user->full_name ?? 'غير محدد',
            'course_name'      => $item->offering->course->course_name_ar ?? 'غير محدد',
            'course_code'      => $item->offering->course->course_code ?? null,
            'department_name'  => $department->name ?? 'غير محدد',
            'level_name'       => $level->name ?? 'غير محدد',
            'semester_name'    => $semester->name ?? 'غير محدد',
        ];
    });

    return response()->json([
        'status' => true,
        'count'  => $flattened->count(),
        'data'   => $flattened
    ], 200);
}
    public function store(Request $request)
    {
        $validated = $request->validate([
            'offering_id' => 'required|exists:course_offerings,offering_id',
            'doctor_id'   => [
                'required',
                'exists:doctors,doctor_id',
                // منع تعيين نفس الدكتور أكثر من مرة لنفس المادة في نفس الفصل
                Rule::unique('course_doctors')->where(function ($query) use ($request) {
                    return $query->where('offering_id', $request->offering_id);
                }),
            ],
        ], [
            'doctor_id.unique' => 'هذا الدكتور مسند بالفعل لهذه المادة في هذا الفصل.',
        ]);

        try {
            $courseDoctor = CourseDoctor::create($validated);

            return response()->json([
                'status'  => true,
                'message' => 'تم تعيين الدكتور للمادة بنجاح',
                'data'    => $courseDoctor->load(['doctor.user', 'offering.course'])
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'فشلت عملية تعيين الدكتور للمادة',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, int $id)
    {
        $assignment = CourseDoctor::find($id);

        if (!$assignment) {
            return response()->json([
                'status'  => false,
                'message' => 'سجل إسناد المادة غير موجود.'
            ], 404);
        }

        $validated = $request->validate([
            'offering_id' => 'required|exists:course_offerings,offering_id',
            'doctor_id'   => [
                'required',
                'exists:doctors,doctor_id',
                // منع تكرار نفس الدكتور لنفس الطرح مع استثناء السجل الحالي
                Rule::unique('course_doctors')
                    ->where(function ($query) use ($request) {
                        return $query->where('offering_id', $request->offering_id);
                    })
                    ->ignore($id, 'course_doctor_id'),
            ],
        ], [
            'doctor_id.unique'     => 'هذا الدكتور مسند بالفعل لهذه المادة في هذا الفصل.',
            'offering_id.required' => 'المقرر المطروح مطلوب.',
            'doctor_id.required'   => 'الدكتور مطلوب.',
        ]);

        try {
            $assignment->update($validated);

            return response()->json([
                'status'  => true,
                'message' => 'تم تعديل إسناد المادة للدكتور بنجاح',
                'data'    => $assignment->load(['doctor.user', 'offering.course'])
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'فشلت عملية تعديل الإسناد',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(int $id)
    {
        $assignment = CourseDoctor::find($id);

        if (!$assignment) {
            return response()->json([
                'status'  => false,
                'message' => 'سجل الإسناد غير موجود.'
            ], 404);
        }

        try {
            $assignment->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم إلغاء إسناد المادة للدكتور بنجاح.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'فشلت عملية الإلغاء',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
