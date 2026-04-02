<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\StudySession;
use App\Models\User;
use App\Services\AiService;
use App\Services\ContentProcessor;
use Exception;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class Phase10ExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_session_page_loads_successfully(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('study_sessions.create'));

        $response->assertOk();
    }

    public function test_text_session_store_queues_background_generation(): void
    {
        $user = User::factory()->create();

        $text = str_repeat('Cell biology fundamentals. ', 30);
        Bus::fake();

        $contentProcessor = Mockery::mock(ContentProcessor::class);
        $contentProcessor->shouldReceive('processAndExtract')
            ->once()
            ->with('text', Mockery::type('string'), null)
            ->andReturn($text);
        $this->instance(ContentProcessor::class, $contentProcessor);

        $response = $this->actingAs($user)->post(route('study_sessions.store'), [
            'title' => 'Text Session',
            'input_source_type' => 'text',
            'input_text' => $text,
        ]);

        $session = StudySession::where('title', 'Text Session')->firstOrFail();

        $response->assertRedirect(route('study_sessions.show', $session->id));
        $this->assertDatabaseHas('study_sessions', [
            'id' => $session->id,
            'status' => 'processing',
            'input_source_type' => 'text',
        ]);
        $this->assertDatabaseHas('session_input_sources', [
            'study_session_id' => $session->id,
            'source_type' => 'text',
            'extraction_status' => 'success',
        ]);

        Bus::assertBatched(function (PendingBatch $batch): bool {
            return count($batch->jobs) === 3;
        });
    }

    public function test_pdf_session_store_works_for_readable_pdf(): void
    {
        $user = User::factory()->create();
        $pdf = UploadedFile::fake()->create('lecture-notes.pdf', 100, 'application/pdf');
        $extracted = str_repeat('Readable extracted PDF text. ', 20);
        Bus::fake();

        $contentProcessor = Mockery::mock(ContentProcessor::class);
        $contentProcessor->shouldReceive('processAndExtract')
            ->once()
            ->with('pdf', null, Mockery::type(UploadedFile::class))
            ->andReturn($extracted);
        $this->instance(ContentProcessor::class, $contentProcessor);

        $response = $this->actingAs($user)->post(route('study_sessions.store'), [
            'title' => 'PDF Session',
            'input_source_type' => 'pdf',
            'pdf_file' => $pdf,
        ]);

        $session = StudySession::where('title', 'PDF Session')->firstOrFail();

        $response->assertRedirect(route('study_sessions.show', $session->id));
        $this->assertDatabaseHas('study_sessions', [
            'id' => $session->id,
            'input_source_type' => 'pdf',
            'status' => 'processing',
        ]);
        $this->assertDatabaseHas('session_input_sources', [
            'study_session_id' => $session->id,
            'source_type' => 'pdf',
            'extraction_status' => 'success',
        ]);

        Bus::assertBatched(function (PendingBatch $batch): bool {
            return count($batch->jobs) === 3;
        });
    }

    public function test_pdf_session_fails_gracefully_for_scanned_image_only_pdf(): void
    {
        $user = User::factory()->create();
        $pdf = UploadedFile::fake()->create('scanned.pdf', 100, 'application/pdf');

        $contentProcessor = Mockery::mock(ContentProcessor::class);
        $contentProcessor->shouldReceive('processAndExtract')
            ->once()
            ->andThrow(new Exception('PDF extraction returned insufficient readable text. Please upload a text-based PDF.'));
        $this->instance(ContentProcessor::class, $contentProcessor);

        $response = $this->actingAs($user)
            ->from(route('study_sessions.create'))
            ->post(route('study_sessions.store'), [
                'title' => 'Scanned PDF',
                'input_source_type' => 'pdf',
                'pdf_file' => $pdf,
            ]);

        $response->assertRedirect(route('study_sessions.create'));
        $response->assertSessionHas('error', 'PDF extraction returned insufficient readable text. Please upload a text-based PDF.');
        $this->assertDatabaseMissing('study_sessions', ['title' => 'Scanned PDF']);
    }

    public function test_long_input_queues_background_batch_generation(): void
    {
        $user = User::factory()->create();
        $longText = str_repeat('Long content for chunking integration. ', 600);
        Bus::fake();

        $contentProcessor = Mockery::mock(ContentProcessor::class);
        $contentProcessor->shouldReceive('processAndExtract')
            ->once()
            ->andReturn($longText);
        $this->instance(ContentProcessor::class, $contentProcessor);

        $response = $this->actingAs($user)->post(route('study_sessions.store'), [
            'title' => 'Chunked Session',
            'input_source_type' => 'text',
            'input_text' => $longText,
        ]);

        $session = StudySession::where('title', 'Chunked Session')->firstOrFail();
        $response->assertRedirect(route('study_sessions.show', $session->id));
        $this->assertDatabaseHas('study_sessions', ['id' => $session->id, 'status' => 'processing']);

        Bus::assertBatched(function (PendingBatch $batch): bool {
            return count($batch->jobs) === 3;
        });
    }

    public function test_empty_chat_history_returns_success_with_empty_messages(): void
    {
        $user = User::factory()->create();
        $session = StudySession::create([
            'user_id' => $user->id,
            'title' => 'No Chat Yet',
            'input_text' => 'Some session content that is definitely more than 50 chars for validity.',
            'status' => 'completed',
            'input_source_type' => 'text',
            'extracted_text' => 'Some session content that is definitely more than 50 chars for validity.',
        ]);

        $response = $this->actingAs($user)->get(route('chat.history', $session->id));

        $response->assertOk()->assertJson([
            'success' => true,
            'messages' => [],
        ]);
    }

    public function test_non_empty_chat_history_returns_messages(): void
    {
        $user = User::factory()->create();
        $session = StudySession::create([
            'user_id' => $user->id,
            'title' => 'Chat Session',
            'input_text' => 'Content for chat history verification with enough characters.',
            'status' => 'completed',
            'input_source_type' => 'text',
            'extracted_text' => 'Content for chat history verification with enough characters.',
        ]);

        $thread = ChatThread::create([
            'study_session_id' => $session->id,
            'title' => 'Main Tutor Chat',
        ]);

        ChatMessage::create([
            'chat_thread_id' => $thread->id,
            'role' => 'user',
            'content' => 'Hello tutor',
        ]);
        ChatMessage::create([
            'chat_thread_id' => $thread->id,
            'role' => 'assistant',
            'content' => 'Hello student',
        ]);

        $response = $this->actingAs($user)->get(route('chat.history', $session->id));

        $response->assertOk()->assertJsonFragment(['content' => 'Hello tutor']);
        $response->assertOk()->assertJsonFragment(['content' => 'Hello student']);
    }

    public function test_chat_refusal_behavior_for_out_of_context_question(): void
    {
        $user = User::factory()->create();
        $session = StudySession::create([
            'user_id' => $user->id,
            'title' => 'Biology Session',
            'input_text' => 'Photosynthesis occurs in chloroplasts and converts light energy into chemical energy.',
            'status' => 'completed',
            'input_source_type' => 'text',
            'extracted_text' => 'Photosynthesis occurs in chloroplasts and converts light energy into chemical energy.',
        ]);

        $ai = Mockery::mock(AiService::class);
        $ai->shouldReceive('generateChatResponse')
            ->once()
            ->withArgs(function ($prompt) {
                return str_contains($prompt, 'SESSION_CONTEXT')
                    && str_contains($prompt, "I don't have enough context from this study session to answer that.")
                    && str_contains($prompt, 'Who won the world cup in 2010?');
            })
            ->andReturn("I don't have enough context from this study session to answer that.");
        $this->instance(AiService::class, $ai);

        $response = $this->actingAs($user)->postJson(route('chat.send', $session->id), [
            'message' => 'Who won the world cup in 2010?',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message.content', "I don't have enough context from this study session to answer that.");

        $this->assertDatabaseHas('chat_messages', [
            'role' => 'user',
            'content' => 'Who won the world cup in 2010?',
        ]);
        $this->assertDatabaseHas('chat_messages', [
            'role' => 'assistant',
            'content' => "I don't have enough context from this study session to answer that.",
        ]);

        $this->assertNotNull(ChatMessage::query()->where('role', 'user')->where('content', 'Who won the world cup in 2010?')->value('tokens_used'));
        $this->assertNotNull(ChatMessage::query()->where('role', 'assistant')->where('content', "I don't have enough context from this study session to answer that.")->value('tokens_used'));
    }
}
