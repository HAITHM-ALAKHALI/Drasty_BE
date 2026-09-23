<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseOfferingApiTest extends TestCase
{
    public function test_course_offerings_endpoint_returns_course_offerings(): void
    {
        // Arrange
        $databaseCount = DB::table('course_offerings')->count();

        // Act
        $response = $this->getJson('/api/course-offerings');

        // Assert
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'count',
            'data' => [
                '*' => [
                    'offering_id',
                    'course_code',
                    'course_name',
                    'semester_name',
                    'doctors' => [
                        '*' => [
                            'course_doctor_id',
                            'doctor_name',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertJsonPath('status', true);
        $response->assertJsonPath('count', $databaseCount);
    }
    public function test_course_offerings_endpoint_can_filter_by_semester_id(): void
{
    // Arrange
    $semesterId = 1;

    $expectedCount = DB::table('course_offerings')
        ->where('semester_id', $semesterId)
        ->count();

    // Act
    $response = $this->getJson('/api/course-offerings?semester_id=' . $semesterId);

    // Assert
    $response->assertStatus(200);
    $response->assertJsonPath('status', true);
    $response->assertJsonPath('count', $expectedCount);

    $offeringIds = collect($response->json('data'))
        ->pluck('offering_id')
        ->toArray();

    foreach ($offeringIds as $offeringId) {
        $this->assertDatabaseHas('course_offerings', [
            'offering_id' => $offeringId,
            'semester_id' => $semesterId,
        ]);
    }
}

}


