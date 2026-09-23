<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CourseOffering;
use Illuminate\Validation\Rule;
use Exception;

class CourseOfferingController extends Controller
{
    public function index(Request $request)
    {
        $query = CourseOffering::with([
            'course:course_id,course_code,course_name_ar',
            'semester:semester_id,name',
            'courseDoctors.doctor.user:user_id,full_name'
        ]);

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }

        $offerings = $query->latest('offering_id')->get();

        // تحويل البيانات إلى شكل مسطح ومباشر للواجهة
        $cleanedData = $offerings->map(function ($offering) {
            return [
                'offering_id'   => $offering->offering_id,
                'course_code'   => $offering->course->course_code ?? null,
                'course_name'   => $offering->course->course_name_ar ?? null,
                'semester_name' => $offering->semester->name ?? null,
                'doctors'       => $offering->courseDoctors->map(function ($cd) {
                    return [
                        'course_doctor_id' => $cd->course_doctor_id,
                        'doctor_name'      => $cd->doctor->user->full_name ?? 'غير محدد',
                    ];
                }),
            ];
        });

        return response()->json([
            'status' => true,
            'count'  => $cleanedData->count(),
            'data'   => $cleanedData
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id'   => 'required|exists:courses,course_id',
            'semester_id' => [
                'required',
                'exists:semesters,semester_id',
                // منع تكرار طرح نفس المادة في نفس الفصل الدراسي
                Rule::unique('course_offerings')->where(function ($query) use ($request) {
                    return $query->where('course_id', $request->course_id);
                }),
            ],
        ], [
            'semester_id.unique' => 'هذه المادة مضافة بالفعل لهذا الفصل الدراسي.',
        ]);

        try {
            $offering = CourseOffering::create($validated);

            return response()->json([
                'status'  => true,
                'message' => 'تم طرح المادة في الفصل الدراسي بنجاح',
                'data'    => $offering->load('course')
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'فشلت عملية إضافة المادة للفصل',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

public function update(Request $request, int $id)
    {
        $offering = CourseOffering::find($id);

        if (!$offering) {
            return response()->json([
                'status'  => false,
                'message' => 'سجل طرح المادة المطلوب غير موجود.'
            ], 404);
        }

        $validated = $request->validate([
            'course_id'   => 'required|integer|exists:courses,course_id',
            'semester_id' => [
                'required',
                'integer',
                'exists:semesters,semester_id',
                // منع التكرار مع استثناء السجل الحالي نفسه
                Rule::unique('course_offerings')->where(function ($query) use ($request) {
                    return $query->where('course_id', $request->course_id);
                })->ignore($id, 'offering_id'),
            ],
        ], [
            'course_id.required'   => 'المادة الدراسية مطلوبة.',
            'course_id.exists'     => 'المادة المختارة غير صالحة.',
            'semester_id.required' => 'الفصل الدراسي مطلوب.',
            'semester_id.exists'   => 'الفصل الدراسي المختار غير صالح.',
            'semester_id.unique'   => 'هذه المادة مطروحة مسبقاً في هذا الفصل الدراسي.',
        ]);

        try {
            $offering->update($validated);

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث طرح المقرر بنجاح.',
                'data'    => $offering->load('course')
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'فشلت عملية تحديث الطرح: ' . $e->getMessage(),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(int $id)
    {
        $offering = CourseOffering::find($id);

        if (!$offering) {
            return response()->json([
                'status'  => false,
                'message' => 'سجل طرح المادة المطلوب غير موجود.'
            ], 404);
        }

        try {
            // فحص التكامل المرجعي: منع الحذف إذا كان هناك دكاترة معينون لتدريس هذا الطرح
            if (method_exists($offering, 'courseDoctors') && $offering->courseDoctors()->exists()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'لا يمكن حذف طرح المادة نظراً لتعيين دكاترة ومحاضرين مسجلين لتدريسها.'
                ], 409);
            }

            $offering->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم إلغاء طرح المادة بنجاح.',
                'data'    => [
                    'offering_id' => $offering->getKey()
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'تعذر حذف السجل نظراً لارتباطه ببيانات أكاديمية أخرى.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
