<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseDoctorApiTest extends TestCase
{
    public function test_course_doctors_endpoint_returns_assignments(): void
    {
        // Arrange
        $databaseCount = DB::table('course_doctors')->count();

        // Act
        $response = $this->getJson('/api/course-doctors');

        // Assert
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'count',
            'data' => [
                '*' => [
                    'course_doctor_id',
                    'doctor_name',
                    'course_name',
                    'course_code',
                    'department_name',
                    'level_name',
                    'semester_name',
                ],
            ],
        ]);

        $response->assertJsonPath('status', true);
        $response->assertJsonPath('count', $databaseCount);
    }
    public function test_each_course_doctor_assignment_exists_in_database(): void
{
    // Arrange
    $response = $this->getJson('/api/course-doctors');

    // Act
    $assignments = $response->json('data');

    // Assert
    $response->assertStatus(200);

    foreach ($assignments as $assignment) {
        $this->assertDatabaseHas('course_doctors', [
            'course_doctor_id' => $assignment['course_doctor_id'],
        ]);
    }
}

}

