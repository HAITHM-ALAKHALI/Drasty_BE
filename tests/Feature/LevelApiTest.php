<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LevelApiTest extends TestCase
{
    public function test_levels_endpoint_returns_levels(): void
    {
        // Arrange
        $databaseCount = DB::table('levels')->count();

        // Act
        $response = $this->getJson('/api/levels');

        // Assert
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'count',
            'data' => [
                '*' => [
                    'level_id',
                    'level_name',
                    'level_number',
                    'department_id',
                    'department_name',
                    'department_code',
                ],
            ],
        ]);

        $response->assertJsonPath('status', true);
        $response->assertJsonPath('count', $databaseCount);
    }
}
