<?php

namespace Tests\Feature;

use App\Helpers\NotificationHelper;
use App\Jobs\SendTelegramJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TelegramDeliveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telegram.bot_token', 'test-bot-token');
    }

    public function test_transactional_notification_is_delivered_immediately(): void
    {
        Queue::fake();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $result = NotificationHelper::sendTelegram('123456789', '<b>Pesan tes</b>');

        $this->assertTrue($result);
        Queue::assertNothingPushed();
        Http::assertSent(fn ($request): bool => $request['chat_id'] === '123456789'
            && $request['text'] === '<b>Pesan tes</b>'
            && $request['parse_mode'] === 'HTML'
        );
    }

    public function test_bulk_notification_remains_queued(): void
    {
        Queue::fake();
        Http::fake();

        $result = NotificationHelper::queueTelegram('123456789', 'Pengingat');

        $this->assertTrue($result);
        Queue::assertPushed(SendTelegramJob::class, 1);
        Http::assertNothingSent();
    }

    public function test_api_failure_is_reported_to_the_caller(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 400)]);

        $this->assertFalse(NotificationHelper::sendTelegram('invalid-chat', 'Pesan tes'));
    }

    public function test_dynamic_notification_content_is_safe_for_telegram_html(): void
    {
        $message = NotificationHelper::buildNewSubmissionMessage((object) [
            'id_mutasi' => 'MM<&>',
            'pengusul' => 'Operator & Admin',
            'nama_sekolah_tujuan' => 'SMP <Unggul>',
        ], 'masuk');

        $this->assertStringContainsString('MM&lt;&amp;&gt;', $message);
        $this->assertStringContainsString('Operator &amp; Admin', $message);
        $this->assertStringContainsString('SMP &lt;Unggul&gt;', $message);
    }

    public function test_resubmission_notification_is_distinguished_from_new_submission(): void
    {
        $message = NotificationHelper::buildNewSubmissionMessage((object) [
            'id_mutasi' => 'MM-001',
            'pengusul' => 'operator',
            'nama_sekolah_tujuan' => 'SMP Contoh',
            'revision_count' => 2,
        ], 'masuk');

        $this->assertStringContainsString('PERBAIKAN DIKIRIM ULANG', $message);
        $this->assertStringContainsString('Dikirim ulang setelah perbaikan', $message);
    }
}
