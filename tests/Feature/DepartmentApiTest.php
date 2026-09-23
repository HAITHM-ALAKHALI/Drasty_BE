<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class DepartmentApiTest extends TestCase
{
    public function test_departments_endpoint_returns_departments(): void
    {
        // Arrange
        // لا نضيف بيانات؛ نستخدم البيانات الموجودة في قاعدة MySQL.

        // Act
        $response = $this->getJson('/api/departments');

        // Assert
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'count',
            'data' => [
                '*' => [
                    'department_id',
                    'name',
                    'code',
                ],
            ],
        ]);

        $response->assertJsonPath('status', true);
    }

    public function test_departments_endpoint_contains_computer_science_department(): void
    {
        // Arrange
        $expectedDepartmentId = 1;
        $expectedName = 'computer science';
        $expectedCode = 'CS';

        // Act
        $response = $this->getJson('/api/departments');

        // Assert
        $response->assertStatus(200);

        $response->assertJsonFragment([
            'department_id' => $expectedDepartmentId,
            'name' => $expectedName,
            'code' => $expectedCode,
        ]);
    }

    public function test_departments_count_matches_database_count(): void
    {
        // Arrange
        $databaseCount = DB::table('departments')->count();

        // Act
        $response = $this->getJson('/api/departments');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('count', $databaseCount);
    }
}
