<?php

namespace Tests\Feature;

use App\Events\MessagePosted;
use App\Jobs\ProcessMessage;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuestbookTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_guestbook_page_loads(): void
    {
        $this->get('/')->assertStatus(200)->assertSee('Velq Guestbook');
    }

    public function test_posting_a_message_persists_broadcasts_and_queues(): void
    {
        Event::fake([MessagePosted::class]);
        Bus::fake([ProcessMessage::class]);

        $this->post('/messages', [
            'author' => 'Ada',
            'body' => 'hello from the test',
            '_auth' => '0',
        ])->assertRedirect('/');

        $message = Message::first();
        $this->assertNotNull($message);
        $this->assertSame('Ada', $message->author);
        // ULID primary key (26 chars).
        $this->assertSame(26, strlen($message->id));

        Event::assertDispatched(MessagePosted::class);
        Bus::assertDispatched(ProcessMessage::class);
    }

    public function test_posting_succeeds_even_when_the_live_broadcast_fails(): void
    {
        // The broadcast runs synchronously (ShouldBroadcastNow). If the edge is
        // unreachable (e.g. before PUSHER_HOST is injected) the post must still
        // succeed: message saved + queue job dispatched, no 500.
        Bus::fake([ProcessMessage::class]);
        config(['broadcasting.default' => 'pusher']);
        config(['broadcasting.connections.pusher.key' => 'invalid']);
        config(['broadcasting.connections.pusher.secret' => 'invalid']);
        config(['broadcasting.connections.pusher.app_id' => 'invalid']);

        $this->post('/messages', [
            'author' => 'Ada',
            'body' => 'broadcast may fail',
            '_auth' => '0',
        ])->assertRedirect('/');

        $this->assertNotNull(Message::first());
        Bus::assertDispatched(ProcessMessage::class);
    }

    public function test_signup_creates_a_user_with_a_ulid_and_sends_welcome_mail(): void
    {
        Mail::fake();

        $this->post('/auth/register', [
            'name' => 'Grace',
            'email' => 'grace@example.com',
            'password' => 'password123',
        ])->assertRedirect('/');

        $user = User::first();
        $this->assertNotNull($user);
        $this->assertSame(26, strlen($user->id));
        $this->assertAuthenticatedAs($user);

        Mail::assertSent(\App\Mail\WelcomeMail::class);
    }

    public function test_the_process_message_job_marks_a_message_scanned(): void
    {
        $message = Message::create([
            'author' => 'Bo',
            'body' => 'scan me',
        ]);

        (new ProcessMessage($message->id))->handle();

        $this->assertTrue($message->fresh()->attachment_scanned);
    }
}
