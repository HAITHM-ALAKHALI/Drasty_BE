<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DoctorApiTest extends TestCase
{
    public function test_doctors_endpoint_returns_doctors(): void
    {
        // Arrange
        $databaseCount = DB::table('doctors')->count();

        // Act
        $response = $this->getJson('/api/doctors');

        // Assert
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'count',
            'data' => [
                '*' => [
                    'doctor_id',
                    'user_id',
                    'user' => [
                        'user_id',
                        'full_name',
                        'email',
                    ],
                ],
            ],
        ]);

        $response->assertJsonPath('status', true);
        $response->assertJsonPath('count', $databaseCount);
    }

    public function test_each_doctor_is_linked_to_an_existing_user(): void
{
    // Arrange
    $response = $this->getJson('/api/doctors');

    // Act
    $doctors = $response->json('data');

    // Assert
    $response->assertStatus(200);

    foreach ($doctors as $doctor) {
        $userId = $doctor['user_id'];

        $this->assertDatabaseHas('users', [
            'user_id' => $userId,
        ]);

        $this->assertNotNull($doctor['user']);

        $this->assertSame(
            $userId,
            $doctor['user']['user_id']
        );
        }
    }

}
