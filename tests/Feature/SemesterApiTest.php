<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SemesterApiTest extends TestCase
{
    public function test_semesters_endpoint_returns_semesters(): void
    {
        // Arrange
        $databaseCount = DB::table('semesters')->count();

        // Act
        $response = $this->getJson('/api/semesters');

        // Assert
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'count',
            'data' => [
                '*' => [
                    'semester_id',
                    'semester_name',
                    'semester_number',
                    'academic_year',
                    'level_id',
                    'level_name',
                    'department_name',
                    'department_code',
                ],
            ],
        ]);

        $response->assertJsonPath('status', true);
        $response->assertJsonPath('count', $databaseCount);
    }

    public function test_semesters_endpoint_can_filter_by_level_id(): void
    {
        // Arrange
        $levelId = 1;

        $expectedCount = DB::table('semesters')
            ->where('level_id', $levelId)
            ->count();

        // Act
        $response = $this->getJson('/api/semesters?level_id=' . $levelId);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('count', $expectedCount);

        $responseData = $response->json('data');

        foreach ($responseData as $semester) {
            $this->assertSame($levelId, $semester['level_id']);
        }
    }
}
