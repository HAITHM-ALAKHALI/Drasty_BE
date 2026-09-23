<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseApiTest extends TestCase
{
    /**
     * استخراج ترويسات التوثيق القياسية والـ JWT Token من قاعدة البيانات
     */
    protected function getAuthHeaders(): array
    {
        $user = User::first();

        $token = auth('api')->login($user);

        return [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ];
    }

    public function test_courses_endpoint_returns_courses(): void
    {
        // Arrange
        $databaseCount = DB::table('courses')->count();
        $headers = $this->getAuthHeaders();

        // Act
        $response = $this->withHeaders($headers)->getJson('/api/courses');

        // Assert
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'count',
            'data' => [
                '*' => [
                    'course_id',
                    'course_code',
                    'course_name_ar',
                    'course_name_en',
                    'description',
                    'credit_hours',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

        $response->assertJsonPath('status', true);
        $response->assertJsonPath('count', $databaseCount);
    }

    public function test_courses_endpoint_returns_courses_ordered_by_arabic_name(): void
    {
        // Arrange
        $expectedNames = DB::table('courses')
            ->orderBy('course_name_ar', 'asc')
            ->pluck('course_name_ar')
            ->values()
            ->toArray();
        $headers = $this->getAuthHeaders();

        // Act
        $response = $this->withHeaders($headers)->getJson('/api/courses');

        // Assert
        $response->assertStatus(200);

        $actualNames = collect($response->json('data'))
            ->pluck('course_name_ar')
            ->values()
            ->toArray();

        $this->assertSame($expectedNames, $actualNames);
    }

    public function test_course_creation_rejects_missing_required_data(): void
    {
        // Arrange
        $coursesCountBeforeRequest = DB::table('courses')->count();
        $headers = $this->getAuthHeaders();
        $invalidData = [];

        // Act
        $response = $this->withHeaders($headers)->postJson('/api/courses', $invalidData);

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'course_name_ar',
            'course_name_en',
        ]);

        $coursesCountAfterRequest = DB::table('courses')->count();

        $this->assertSame(
            $coursesCountBeforeRequest,
            $coursesCountAfterRequest
        );
    }

    public function test_course_creation_rejects_duplicate_course_code(): void
    {
        // Arrange
        $existingCourseCode = DB::table('courses')
            ->whereNotNull('course_code')
            ->value('course_code');

        $this->assertNotNull($existingCourseCode);

        $coursesCountBeforeRequest = DB::table('courses')->count();
        $headers = $this->getAuthHeaders();

        $duplicateData = [
            'course_code' => $existingCourseCode,
            'course_name_ar' => 'مادة اختبار الرمز المكرر',
            'course_name_en' => 'Duplicate Code Test Course',
            'credit_hours' => 3,
        ];

        // Act
        $response = $this->withHeaders($headers)->postJson('/api/courses', $duplicateData);

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'course_code',
        ]);

        $coursesCountAfterRequest = DB::table('courses')->count();

        $this->assertSame(
            $coursesCountBeforeRequest,
            $coursesCountAfterRequest
        );
    }

    public function test_course_can_be_created_successfully(): void
    {
        // Arrange
        $courseCode = 'TEST-' . now()->format('YmdHisv');
        $headers = $this->getAuthHeaders();

        $courseData = [
            'course_code' => $courseCode,
            'course_name_ar' => 'مادة اختبار مؤقتة',
            'course_name_en' => 'Temporary Test Course',
            'credit_hours' => 3,
        ];

        $createdCourseId = null;

        try {
            // Act
            $response = $this->withHeaders($headers)->postJson('/api/courses', $courseData);

            // Assert
            $response->assertStatus(201);

            $response->assertJsonPath('status', true);
            $response->assertJsonPath('data.course_code', $courseCode);
            $response->assertJsonPath(
                'data.course_name_ar',
                $courseData['course_name_ar']
            );
            $response->assertJsonPath(
                'data.course_name_en',
                $courseData['course_name_en']
            );

            $createdCourseId = $response->json('data.course_id');

            $this->assertNotNull($createdCourseId);

            $this->assertDatabaseHas('courses', [
                'course_id' => $createdCourseId,
                'course_code' => $courseCode,
                'course_name_ar' => $courseData['course_name_ar'],
                'course_name_en' => $courseData['course_name_en'],
                'credit_hours' => 3,
            ]);
        } finally {
            // Cleanup
            if ($createdCourseId !== null) {
                DB::table('courses')
                    ->where('course_id', $createdCourseId)
                    ->delete();
            }
        }
    }

    public function test_updating_nonexistent_course_returns_not_found(): void
    {
        // Arrange
        $nonexistentCourseId = ((int) DB::table('courses')->max('course_id')) + 1000;
        $headers = $this->getAuthHeaders();

        $updateData = [
            'course_name_ar' => 'اسم اختبار غير موجود',
        ];

        // Act
        $response = $this->withHeaders($headers)->putJson(
            '/api/courses/' . $nonexistentCourseId,
            $updateData
        );

        // Assert
        $response->assertStatus(404);

        $response->assertJson([
            'status' => false,
            'message' => 'المادة الدراسية المطلوبة غير موجودة.',
        ]);
    }

    public function test_existing_course_can_be_updated_successfully(): void
    {
        // Arrange
        $courseCode = 'UPDATE-TEST-' . now()->format('YmdHisv');
        $headers = $this->getAuthHeaders();

        $createData = [
            'course_code' => $courseCode,
            'course_name_ar' => 'مادة قبل التعديل',
            'course_name_en' => 'Course Before Update',
            'credit_hours' => 3,
        ];

        $createdCourseId = null;

        try {
            $createResponse = $this->withHeaders($headers)->postJson('/api/courses', $createData);

            $createResponse->assertStatus(201);

            $createdCourseId = $createResponse->json('data.course_id');

            $this->assertNotNull($createdCourseId);

            $updateData = [
                'course_name_ar' => 'مادة بعد التعديل',
            ];

            // Act
            $response = $this->withHeaders($headers)->putJson(
                '/api/courses/' . $createdCourseId,
                $updateData
            );

            // Assert
            $response->assertStatus(200);

            $response->assertJsonPath('status', true);
            $response->assertJsonPath(
                'data.course_name_ar',
                $updateData['course_name_ar']
            );

            $this->assertDatabaseHas('courses', [
                'course_id' => $createdCourseId,
                'course_name_ar' => $updateData['course_name_ar'],
            ]);
        } finally {
            // Cleanup
            if ($createdCourseId !== null) {
                DB::table('courses')
                    ->where('course_id', $createdCourseId)
                    ->delete();
            }
        }
    }

    public function test_course_with_offering_cannot_be_deleted(): void
    {
        // Arrange
        $courseId = DB::table('course_offerings')
            ->value('course_id');

        $this->assertNotNull($courseId);
        $headers = $this->getAuthHeaders();

        // Act
        $response = $this->withHeaders($headers)->deleteJson('/api/courses/' . $courseId);

        // Assert
        $response->assertStatus(409);

        $response->assertJson([
            'status' => false,
            'message' => 'لا يمكن حذف المادة الدراسية نظراً لوجود فصول دراسية ومقررات مطروحة مرتبطة بها.',
        ]);

        $this->assertDatabaseHas('courses', [
            'course_id' => $courseId,
        ]);
    }

    public function test_unoffered_course_can_be_deleted_successfully(): void
    {
        // Arrange
        $courseCode = 'DELETE-TEST-' . now()->format('YmdHisv');
        $headers = $this->getAuthHeaders();

        $courseData = [
            'course_code' => $courseCode,
            'course_name_ar' => 'مادة اختبار الحذف',
            'course_name_en' => 'Course Deletion Test',
            'credit_hours' => 3,
        ];

        $createdCourseId = null;

        try {
            $createResponse = $this->withHeaders($headers)->postJson('/api/courses', $courseData);

            $createResponse->assertStatus(201);

            $createdCourseId = $createResponse->json('data.course_id');

            $this->assertNotNull($createdCourseId);

            $this->assertDatabaseHas('courses', [
                'course_id' => $createdCourseId,
            ]);

            // Act
            $response = $this->withHeaders($headers)->deleteJson(
                '/api/courses/' . $createdCourseId
            );

            // Assert
            $response->assertStatus(200);

            $response->assertJson([
                'status' => true,
                'message' => 'تم حذف المادة الدراسية بنجاح.',
            ]);

            $response->assertJsonPath(
                'data.course_id',
                $createdCourseId
            );

            $this->assertDatabaseMissing('courses', [
                'course_id' => $createdCourseId,
            ]);

            $createdCourseId = null;
        } finally {
            // Cleanup احتياطي إذا فشل الحذف عبر API
            if ($createdCourseId !== null) {
                DB::table('courses')
                    ->where('course_id', $createdCourseId)
                    ->delete();
            }
        }
    }
}
