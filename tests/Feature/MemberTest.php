<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_members_get_a_card_number_automatically(): void
    {
        $this->post(route('members.store'), ['name' => 'Б. Номин', 'phone' => '99112233', 'is_active' => true]);
        $this->post(route('members.store'), ['name' => 'Д. Тэмүүлэн', 'member_no' => 'ST-2026-01']);

        [$auto, $custom] = Member::query()->orderBy('id')->get()->all();

        $this->assertSame(sprintf('M-%05d', $auto->id), $auto->member_no);
        $this->assertSame('ST-2026-01', $custom->member_no);
    }

    public function test_member_validation(): void
    {
        Member::factory()->create(['member_no' => 'M-1']);

        $this->post(route('members.store'), ['member_no' => 'M-1', 'email' => 'not-an-email'])
            ->assertSessionHasErrors(['name', 'member_no', 'email']);
    }

    public function test_members_list_counts_open_and_overdue_loans(): void
    {
        $member = Member::factory()->create();
        Loan::factory()->for($member)->create();
        Loan::factory()->overdue()->for($member)->create();
        Loan::factory()->returned()->for($member)->create();

        $this->get(route('members.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('members/Index')
                ->where('members.data.0.open_loans_count', 2)
                ->where('members.data.0.overdue_loans_count', 1));

        $this->get(route('members.index', ['status' => 'overdue']))
            ->assertInertia(fn (Assert $page) => $page->has('members.data', 1));
    }

    public function test_the_member_page_shows_current_loans_and_history(): void
    {
        $member = Member::factory()->create();
        Loan::factory()->for($member)->create();
        Loan::factory()->returned()->count(2)->for($member)->create();

        $this->get(route('members.show', $member))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('members/Show')
                ->has('openLoans', 1)
                ->has('history', 2));
    }

    public function test_a_member_can_be_deactivated(): void
    {
        $member = Member::factory()->create();

        $this->put(route('members.update', $member), ['name' => $member->name, 'is_active' => false])
            ->assertRedirect(route('members.show', $member));

        $this->assertFalse($member->fresh()->is_active);
    }

    public function test_a_member_with_loan_history_cannot_be_deleted(): void
    {
        $withHistory = Loan::factory()->returned()->create()->member;
        $fresh = Member::factory()->create();

        $this->delete(route('members.destroy', $withHistory));
        $this->delete(route('members.destroy', $fresh))->assertRedirect(route('members.index'));

        $this->assertModelExists($withHistory);
        $this->assertModelMissing($fresh);
    }
}
