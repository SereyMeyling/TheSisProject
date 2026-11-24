<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * NotificationController: list, unread count, mark one / all as read.
 */
class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    private function notify(User $user, array $data = [], bool $read = false): string
    {
        $id = (string) Str::uuid();
        $user->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\TestNotification',
            'data' => array_merge(['message' => 'Hello'], $data),
            'read_at' => $read ? now() : null,
        ]);

        return $id;
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
        $this->get('/notifications/unread-count')->assertRedirect('/login');
    }

    public function test_user_can_list_notifications(): void
    {
        $user = $this->actingAsRole('nurse');
        $this->notify($user, ['message' => 'Lab result ready']);

        $this->get('/notifications')->assertOk()->assertViewIs('notifications.index');
    }

    public function test_unread_count(): void
    {
        $user = $this->actingAsRole('nurse');
        $this->notify($user);
        $this->notify($user);
        $this->notify($user, [], true);

        $this->getJson('/notifications/unread-count')->assertOk()->assertExactJson(['count' => 2]);
    }

    public function test_mark_as_read_redirects_to_stored_url(): void
    {
        $user = $this->actingAsRole('nurse');
        $id = $this->notify($user, ['url' => '/patients']);

        $this->post("/notifications/{$id}/read")->assertRedirect('/patients');

        $this->assertNotNull($user->notifications()->find($id)->read_at);
    }

    public function test_mark_as_read_without_url_goes_back(): void
    {
        $user = $this->actingAsRole('nurse');
        $id = $this->notify($user);

        $this->from('/notifications')->post("/notifications/{$id}/read")->assertRedirect('/notifications');
    }

    public function test_cannot_mark_another_users_notification(): void
    {
        $other = User::factory()->create();
        $id = $this->notify($other);

        $this->actingAsRole('nurse');
        $this->post("/notifications/{$id}/read")->assertNotFound();

        $this->assertNull($other->notifications()->find($id)->read_at);
    }

    public function test_mark_all_as_read_only_affects_current_user(): void
    {
        $other = User::factory()->create();
        $otherId = $this->notify($other);

        $user = $this->actingAsRole('cashier');
        $this->notify($user);
        $this->notify($user);

        $this->from('/notifications')->post('/notifications/read-all')->assertRedirect('/notifications');

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertNull($other->notifications()->find($otherId)->read_at);
    }
}
