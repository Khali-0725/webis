<?php

namespace Tests\Unit\Models;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_payment_soft_deletes_it(): void
    {
        $payment = Payment::factory()->create();

        $payment->delete();

        $this->assertSoftDeleted($payment);
        $this->assertNull(Payment::find($payment->id));
        $this->assertNotNull(Payment::withTrashed()->find($payment->id));
    }

    public function test_a_soft_deleted_payment_can_be_restored(): void
    {
        $payment = Payment::factory()->create();
        $payment->delete();

        $payment->restore();

        $this->assertNotSoftDeleted($payment);
        $this->assertNotNull(Payment::find($payment->id));
    }
}
