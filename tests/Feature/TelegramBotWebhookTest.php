<?php

namespace Tests\Feature;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\FeedbackRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);
        
        // Ensure cache is clear before each test
        Cache::flush();
    }

    public function test_webhook_returns_400_for_empty_payload()
    {
        $response = $this->postJson('/api/telegram/webhook', []);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Empty payload']);
    }

    public function test_webhook_processes_start_command_successfully()
    {
        $payload = [
            'update_id' => 123456,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'username' => 'johndoe',
                    'language_code' => 'en',
                ],
                'chat' => [
                    'id' => 987654321,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'username' => 'johndoe',
                    'type' => 'private',
                ],
                'date' => 1620000000,
                'text' => '/start',
            ]
        ];

        $response = $this->postJson('/api/telegram/webhook', $payload);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Webhook processed successfully']);

        // Verify the user was created/updated in the repository
        $userRepo = app(UserRepositoryInterface::class);
        $user = $userRepo->findByTelegramId('987654321');
        $this->assertNotNull($user);
        $this->assertEquals('John', $user['firstName']);
        $this->assertEquals('Doe', $user['lastName']);
        $this->assertEquals('johndoe', $user['username']);
    }

    public function test_webhook_handles_category_inline_callback()
    {
        $payload = [
            'update_id' => 123457,
            'callback_query' => [
                'id' => '1234567890',
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'username' => 'johndoe',
                ],
                'message' => [
                    'message_id' => 2,
                    'chat' => [
                        'id' => 987654321,
                        'type' => 'private',
                    ],
                    'date' => 1620000100,
                    'text' => 'Please select the category for your feedback:',
                ],
                'data' => 'cat:Bug',
            ]
        ];

        $response = $this->postJson('/api/telegram/webhook', $payload);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Webhook processed successfully']);

        // Verify user state in Cache was set to awaiting content for the "Bug" category
        $userStateKey = "telegram_state_987654321";
        $this->assertEquals('awaiting_content:Bug', Cache::get($userStateKey));
    }

    public function test_webhook_submits_feedback_successfully_when_awaiting_content()
    {
        // First register the user
        $userRepo = app(UserRepositoryInterface::class);
        $user = $userRepo->createOrUpdateTelegramUser([
            'telegramId' => '987654321',
            'chatId' => '987654321',
            'username' => 'johndoe',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'language' => 'en',
        ]);

        // Place the user state in cache
        Cache::put('telegram_state_987654321', 'awaiting_content:Bug', 3600);

        $payload = [
            'update_id' => 123458,
            'message' => [
                'message_id' => 3,
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'username' => 'johndoe',
                ],
                'chat' => [
                    'id' => 987654321,
                    'type' => 'private',
                ],
                'date' => 1620000200,
                'text' => 'The app crashes when I click the export button!',
            ]
        ];

        $response = $this->postJson('/api/telegram/webhook', $payload);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Webhook processed successfully']);

        // Verify state is cleared
        $this->assertNull(Cache::get('telegram_state_987654321'));

        // Verify feedback was persisted
        $feedbackRepo = app(FeedbackRepositoryInterface::class);
        $allFeedback = $feedbackRepo->getAll([]);
        
        $this->assertNotEmpty($allFeedback);
        $submittedFeedback = collect($allFeedback)->first(function ($fb) {
            return $fb['telegramId'] === '987654321';
        });

        $this->assertNotNull($submittedFeedback);
        $this->assertEquals('Bug', $submittedFeedback['category']);
        $this->assertEquals('The app crashes when I click the export button!', $submittedFeedback['message']);
        $this->assertEquals('New', $submittedFeedback['status']);
        $this->assertEquals('Medium', $submittedFeedback['priority']);
    }
}
