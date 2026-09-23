<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContentTypeApiTest extends TestCase
{
    /**
     * الحصول على توكن JWT لمستخدم موجود في قاعدة البيانات
     */
    protected function getAuthHeaders(): array
    {
        // استخدام أول مستخدم موجود في قاعدة بيانات MySQL
        $user = User::first();

        // إذا لم يكن هناك مستخدم، ننشئ مستخدماً تجريبياً أو نستخدم auth('api')->tokenById
        $token = auth('api')->login($user);

        return [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ];
    }

    public function test_content_types_endpoint_returns_content_types(): void
    {
        // Arrange
        $databaseCount = DB::table('content_types')->count();
        $headers = $this->getAuthHeaders();

        // Act
        $response = $this->withHeaders($headers)->getJson('/api/content-types');

        // Assert
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'status',
            'count',
            'data' => [
                '*' => [
                    'content_type_id',
                    'name',
                    'description',
                    'created_at',
                ],
            ],
        ]);

        $response->assertJsonPath('status', true);
        $response->assertJsonPath('count', $databaseCount);
    }

    public function test_content_types_endpoint_contains_lecture_type(): void
    {
        // Arrange
        $expectedContentType = [
            'content_type_id' => 1,
            'name' => 'محاضرة',
            'description' => 'ملفات المحاضرات',
        ];
        $headers = $this->getAuthHeaders();

        // Act
        $response = $this->withHeaders($headers)->getJson('/api/content-types');

        // Assert
        $response->assertStatus(200);

        $response->assertJsonFragment($expectedContentType);
    }
}
