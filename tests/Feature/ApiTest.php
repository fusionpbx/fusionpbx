<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_successful_response(): void
    {
        $response = $this->get('/api/health');
        $response->assertStatus(200);
    }

    public function test_can_list_users(): void
    {
        // Create test user
        $user = \App\Models\User::factory()->create();
        
        $response = $this->actingAs($user)->get('/api/users');
        $response->assertStatus(200);
    }

    public function test_can_list_extensions(): void
    {
        $user = \App\Models\User::factory()->create();
        
        $response = $this->actingAs($user)->get('/api/extensions');
        $response->assertStatus(200);
    }
}
