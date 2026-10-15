<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_mark_their_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $user->id,
            'message' => 'Ada pembaruan.',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('notifications.read', $notification));

        $response->assertOk()
            ->assertJson(['success' => true]);
        $this->assertTrue($notification->fresh()->is_read);
    }
}
