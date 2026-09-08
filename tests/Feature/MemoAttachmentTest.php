<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Branch;
use App\Models\Memo;
use App\Models\MemoAttachment;
use App\Models\MemoTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemoAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_attachment_cannot_be_uploaded_after_a_memo_is_submitted(): void
    {
        Storage::fake('public');
        [$user, $memo] = $this->createMemo('submitted');

        $response = $this->actingAs($user)->post(
            route('memos.attachments.upload', $memo),
            ['file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf')]
        );

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseCount('memo_attachments', 0);
    }

    public function test_attachment_must_belong_to_the_memo_in_the_route_before_it_can_be_deleted(): void
    {
        Storage::fake('public');
        [$user, $memo] = $this->createMemo();
        [, $otherMemo] = $this->createMemoForUser($user);
        $attachment = MemoAttachment::create([
            'memo_id' => $otherMemo->id,
            'file_path' => 'attachments/' . $otherMemo->id . '/proposal.pdf',
            'original_name' => 'proposal.pdf',
        ]);
        Storage::disk('public')->put($attachment->file_path, 'attachment contents');

        $response = $this->actingAs($user)->delete(route('memos.attachments.delete', [
            'memo' => $memo,
            'attachment' => $attachment,
        ]));

        $response->assertNotFound();
        $this->assertDatabaseHas('memo_attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertExists($attachment->file_path);
    }

    private function createMemo(string $status = 'draft'): array
    {
        $area = Area::create(['name' => 'Area Test']);
        $branch = Branch::create(['name' => 'Cabang Test', 'area_id' => $area->id]);
        $user = User::factory()->create(['role' => 'KC', 'branch_id' => $branch->id]);

        return $this->createMemoForUser($user, $status);
    }

    private function createMemoForUser(User $user, string $status = 'draft'): array
    {
        $template = MemoTemplate::create([
            'name' => 'Template Test ' . MemoTemplate::count(),
            'field_schema' => [],
            'created_by' => $user->id,
        ]);

        $memo = Memo::create([
            'template_id' => $template->id,
            'title' => 'Memo Test ' . Memo::count(),
            'field_values' => [],
            'branch_id' => $user->branch_id,
            'created_by' => $user->id,
            'status' => $status,
        ]);

        return [$user, $memo];
    }
}
