<?php

namespace Tests\Feature;

use App\Mail\MemoApproved;
use App\Mail\MemoSubmitted;
use App\Models\Area;
use App\Models\Branch;
use App\Models\DigitalSignature;
use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MemoEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_a_memo_emails_the_area_manager(): void
    {
        Mail::fake();
        [$kc, $am, $memo] = $this->createMemo('draft');
        DigitalSignature::create([
            'user_id' => $kc->id,
            'signature_image' => 'signatures/kc.png',
        ]);

        $response = $this->actingAs($kc)->post(route('memos.submit', $memo));

        $response->assertRedirect(route('memos.show', $memo));
        Mail::assertSent(MemoSubmitted::class, function (MemoSubmitted $mail) use ($am, $memo) {
            return $mail->hasTo($am->email) && $mail->memo->is($memo);
        });
    }

    public function test_approving_a_memo_emails_the_cabinet_chief(): void
    {
        Mail::fake();
        [$kc, $am, $memo] = $this->createMemo('submitted');
        DigitalSignature::create([
            'user_id' => $am->id,
            'signature_image' => 'signatures/am.png',
        ]);

        $response = $this->actingAs($am)->post(route('approvals.approve', $memo));

        $response->assertRedirect(route('approvals.pending'));
        Mail::assertSent(MemoApproved::class, function (MemoApproved $mail) use ($kc, $memo) {
            return $mail->hasTo($kc->email) && $mail->memo->is($memo);
        });
    }

    public function test_viewing_a_memo_marks_only_the_current_users_notification_as_read(): void
    {
        [$kc, $am, $memo] = $this->createMemo('submitted');
        $kcNotification = Notification::create([
            'user_id' => $kc->id,
            'memo_id' => $memo->id,
            'message' => 'Memo telah disetujui.',
        ]);
        $amNotification = Notification::create([
            'user_id' => $am->id,
            'memo_id' => $memo->id,
            'message' => 'Memo baru menunggu persetujuan.',
        ]);

        $this->actingAs($kc)->get(route('memos.show', $memo));

        $this->assertTrue($kcNotification->fresh()->is_read);
        $this->assertFalse($amNotification->fresh()->is_read);

        $this->actingAs($am)->get(route('approvals.review', $memo));

        $this->assertTrue($amNotification->fresh()->is_read);
    }

    private function createMemo(string $status): array
    {
        $area = Area::create(['name' => 'Area Email Test']);
        $am = User::factory()->create([
            'name' => 'Area Manager Test',
            'role' => 'AM',
            'area_id' => $area->id,
        ]);
        $branch = Branch::create([
            'name' => 'Cabang Email Test',
            'area_id' => $area->id,
        ]);
        $kc = User::factory()->create([
            'name' => 'KC Test',
            'role' => 'KC',
            'branch_id' => $branch->id,
        ]);
        $template = MemoTemplate::create([
            'name' => 'Template Email Test',
            'field_schema' => [],
            'created_by' => $kc->id,
        ]);
        $memo = Memo::create([
            'template_id' => $template->id,
            'title' => 'Memo Email Test',
            'field_values' => [],
            'branch_id' => $branch->id,
            'created_by' => $kc->id,
            'area_manager_id' => $am->id,
            'status' => $status,
        ]);

        return [$kc, $am, $memo];
    }
}