<?php
namespace Tests\Feature;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\Booking;
use App\Models\BookingLocation;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\ProviderSkill;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class DatabasePhase2Test extends TestCase
{
    use RefreshDatabase;
    public function test_user_factory_creates_all_roles(): void
    {
        $c = User::factory()->client()->create();
        $p = User::factory()->provider()->create();
        $a = User::factory()->admin()->create();
        $this->assertEquals(UserRole::Client, $c->role);
        $this->assertEquals(UserRole::Provider, $p->role);
        $this->assertEquals(UserRole::Admin, $a->role);
    }
    public function test_barangay_factory(): void
    {
        $b = Barangay::factory()->create(['name' => 'Test Brgy']);
        $this->assertDatabaseHas('barangays', ['name' => 'Test Brgy']);
    }
    public function test_provider_profile_factory(): void
    {
        $p = ProviderProfile::factory()->create();
        $this->assertDatabaseHas('provider_profiles', ['id' => $p->id]);
        $this->assertEquals(VerificationStatus::Pending, $p->verification_status);
    }
    public function test_provider_profile_verified_state(): void
    {
        $p = ProviderProfile::factory()->verified()->create();
        $this->assertEquals(VerificationStatus::Approved, $p->verification_status);
    }
    public function test_service_and_category_factory(): void
    {
        $cat = ServiceCategory::factory()->create();
        $svc = Service::factory()->create(['service_category_id' => $cat->id]);
        $this->assertEquals($cat->id, $svc->category->id);
    }
    public function test_booking_factory_all_states(): void
    {
        $p = Booking::factory()->create();
        $a = Booking::factory()->accepted()->create();
        $c = Booking::factory()->completed()->create();
        $x = Booking::factory()->cancelled()->create();
        $this->assertEquals(BookingStatus::Pending, $p->status);
        $this->assertEquals(BookingStatus::Accepted, $a->status);
        $this->assertEquals(BookingStatus::Completed, $c->status);
        $this->assertEquals(BookingStatus::Cancelled, $x->status);
    }
    public function test_review_factory(): void
    {
        $r = Review::factory()->create();
        $this->assertDatabaseHas('reviews', ['id' => $r->id]);
        $this->assertGreaterThanOrEqual(1, $r->rating);
        $this->assertLessThanOrEqual(5, $r->rating);
    }
    public function test_conversation_and_message_factory(): void
    {
        $c = Conversation::factory()->create();
        $m = Message::factory()->create(['conversation_id' => $c->id]);
        $this->assertDatabaseHas('conversations', ['id' => $c->id]);
        $this->assertDatabaseHas('messages', ['id' => $m->id]);
    }
    public function test_user_has_provider_profile(): void
    {
        $u = User::factory()->provider()->create();
        ProviderProfile::factory()->create(['user_id' => $u->id]);
        $this->assertNotNull($u->providerProfile);
    }
    public function test_provider_profile_has_skills(): void
    {
        $p = ProviderProfile::factory()->create();
        ProviderSkill::create(['provider_profile_id' => $p->id, 'skill' => 'Welding']);
        $this->assertCount(1, $p->skills);
    }
    public function test_booking_relationships(): void
    {
        $client = User::factory()->client()->create();
        $profile = ProviderProfile::factory()->create();
        $svc = Service::factory()->create(['provider_profile_id' => $profile->id]);
        $b = Booking::factory()->create(['client_id' => $client->id, 'provider_profile_id' => $profile->id, 'service_id' => $svc->id]);
        $this->assertEquals($client->id, $b->client->id);
        $this->assertEquals($profile->id, $b->providerProfile->id);
        $this->assertEquals($svc->id, $b->service->id);
    }
    public function test_booking_has_location(): void
    {
        $b = Booking::factory()->create();
        $brgy = Barangay::factory()->create();
        BookingLocation::create(['booking_id'=>$b->id,'barangay_id'=>$brgy->id,'address_line'=>'123 St.','latitude'=>14.34,'longitude'=>120.85]);
        $this->assertNotNull($b->location);
    }
    public function test_conversation_participant_check(): void
    {
        $c = Conversation::factory()->create();
        $this->assertTrue($c->hasParticipant($c->client_id));
        $this->assertTrue($c->hasParticipant($c->provider_user_id));
        $this->assertFalse($c->hasParticipant(99999));
    }
    public function test_unique_booking_review_constraint(): void
    {
        $b = Booking::factory()->completed()->create();
        Review::factory()->create(['booking_id'=>$b->id,'client_id'=>$b->client_id,'provider_profile_id'=>$b->provider_profile_id]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        Review::factory()->create(['booking_id'=>$b->id,'client_id'=>$b->client_id,'provider_profile_id'=>$b->provider_profile_id]);
    }
    public function test_unique_provider_skill_constraint(): void
    {
        $p = ProviderProfile::factory()->create();
        ProviderSkill::create(['provider_profile_id'=>$p->id,'skill'=>'Plumbing']);
        $this->expectException(\Illuminate\Database\QueryException::class);
        ProviderSkill::create(['provider_profile_id'=>$p->id,'skill'=>'Plumbing']);
    }
    public function test_barangay_active_scope(): void
    {
        Barangay::factory()->create(['is_active'=>true]);
        Barangay::factory()->create(['is_active'=>false]);
        $this->assertEquals(1, Barangay::active()->count());
    }
    public function test_provider_verified_scope(): void
    {
        ProviderProfile::factory()->verified()->create();
        ProviderProfile::factory()->create();
        $this->assertEquals(1, ProviderProfile::verified()->count());
    }
    public function test_service_published_scope(): void
    {
        Service::factory()->published()->create();
        Service::factory()->create();
        $this->assertEquals(1, Service::published()->count());
    }
    public function test_booking_slot_blocking_scope(): void
    {
        Booking::factory()->create(['status'=>BookingStatus::Pending]);
        Booking::factory()->completed()->create();
        Booking::factory()->cancelled()->create();
        $this->assertEquals(1, Booking::slotBlocking()->count());
    }
    public function test_booking_status_transitions(): void
    {
        $this->assertTrue(BookingStatus::Pending->canTransitionTo(BookingStatus::Accepted));
        $this->assertFalse(BookingStatus::Pending->canTransitionTo(BookingStatus::Completed));
        $this->assertTrue(BookingStatus::Rejected->isTerminal());
        $this->assertTrue(BookingStatus::Cancelled->isTerminal());
    }
    public function test_system_setting_helper(): void
    {
        SystemSetting::create(['key'=>'test.k','value'=>42,'group'=>'test']);
        $this->assertEquals(42, SystemSetting::getValue('test.k'));
        $this->assertNull(SystemSetting::getValue('nope'));
    }
}
