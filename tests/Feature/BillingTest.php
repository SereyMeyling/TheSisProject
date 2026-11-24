<?php

namespace Tests\Feature;

use App\Models\Billing\PatientCharge;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Patient;
use App\Services\Billing\KhqrVerifier;
use App\Services\Billing\PatientChargeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Never talk to the real Bakong API from tests.
        config(['services.bakong.token' => null]);
    }

    // ---------------------------------------------------------------- helpers

    private function makePatient(): Patient
    {
        return Patient::create([
            'patient_code' => 'PT-' . strtoupper(Str::random(10)),
            'full_name' => 'Billing Patient',
            'id_card' => 'T-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(12)),
            'sex' => 'female',
            'date_of_birth' => '1985-05-05',
            'phone' => '099888777',
        ]);
    }

    private function charge(Patient $patient, float $price, int $qty = 1, string $type = 'medicine'): PatientCharge
    {
        return app(PatientChargeService::class)->create([
            'patient_id' => $patient->patient_id,
            'source_type' => $type,
            'source_key' => 'test:' . Str::uuid(),
            'description' => 'Test charge',
            'quantity' => $qty,
            'unit_price' => $price,
        ]);
    }

    /** Create an invoice through the endpoint (manual rows), return the model. */
    private function createInvoice(array $items, array $extra = []): Invoice
    {
        $res = $this->postJson('/billing/store', array_merge([
            'patient_name' => 'Walk-in',
            'patient_phone' => '012000000',
            'items' => $items,
        ], $extra));
        $this->assertSame(201, $res->status(), (string) $res->getContent());
        $res->assertJsonPath('success', true);

        return Invoice::with('items')->findOrFail($res->json('data.id'));
    }

    private function pay(Invoice $invoice, $amount, string $method = 'cash', ?string $ref = null)
    {
        return $this->postJson("/billing/{$invoice->id}/pay", array_filter([
            'payment_method' => $method,
            'amount' => $amount,
            'transaction_ref' => $ref,
        ], fn ($v) => $v !== null));
    }

    // ------------------------------------------------------------ pages/auth

    public function test_billing_index_renders_for_cashier_and_admin(): void
    {
        $this->actingAsRole('cashier');
        $this->get('/billing')->assertOk();
        $this->getJson('/billing', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonStructure(['html', 'totalInvoices', 'totalUnpaid']);

        $this->actingAsRole('admin');
        $this->get('/billing')->assertOk();
    }

    public function test_disallowed_roles_get_403_on_billing(): void
    {
        foreach (['pharmacist', 'doctor', 'nurse'] as $role) {
            $this->actingAsRole($role);
            $this->get('/billing')->assertForbidden();
            $this->postJson('/billing/store', ['patient_name' => 'x', 'items' => []])->assertForbidden();
        }
    }

    // --------------------------------------------------------- create invoice

    public function test_create_invoice_with_manual_items_totals_are_exact(): void
    {
        $this->actingAsRole('cashier');

        $invoice = $this->createInvoice([
            ['item_type' => 'service', 'description' => 'Consultation', 'qty' => 2, 'unit_price' => 10.55],
            ['item_type' => 'medicine', 'description' => 'Pills', 'qty' => 3, 'unit_price' => 0.10],
            ['item_type' => 'lab_test', 'description' => 'CBC', 'qty' => 1, 'unit_price' => 7],
        ]);

        $this->assertEquals(28.40, $invoice->total_amount);
        $this->assertEquals(28.40, $invoice->balance);
        $this->assertEquals(0.0, $invoice->paid_amount);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertNull($invoice->admission_id);
        $this->assertCount(3, $invoice->items);
        $this->assertEquals([21.10, 0.30, 7.00], $invoice->items->sortBy('id')->pluck('subtotal')->map(fn ($v) => (float) $v)->values()->all());
        $this->assertMatchesRegularExpression('/^INV-\d{8}-\d{3,}$/', $invoice->invoice_number);
    }

    public function test_create_invoice_validation(): void
    {
        $this->actingAsRole('cashier');

        $this->postJson('/billing/store', ['patient_name' => ''])
            ->assertStatus(422)->assertJsonValidationErrors(['patient_name', 'items']);

        $this->postJson('/billing/store', [
            'patient_name' => 'X',
            'items' => [['item_type' => 'bogus', 'description' => 'd', 'qty' => 0, 'unit_price' => -1]],
        ])->assertStatus(422)->assertJsonValidationErrors(['items.0.item_type', 'items.0.qty', 'items.0.unit_price']);
    }

    public function test_invoice_from_charges_uses_db_prices_and_links_charges(): void
    {
        $this->actingAsRole('cashier');
        $patient = $this->makePatient();
        $c1 = $this->charge($patient, 0.35, 30);  // 10.50
        $c2 = $this->charge($patient, 4.20, 1, 'lab'); // 4.20

        $this->getJson("/billing/pending-charges/{$patient->patient_id}")
            ->assertOk()->assertJsonPath('total', 14.7)->assertJsonCount(2, 'items');

        $invoice = $this->createInvoice(
            [['item_type' => 'other', 'description' => 'Admin fee', 'qty' => 1, 'unit_price' => 1]],
            [
                'patient_id' => $patient->patient_id,
                'patient_name' => $patient->full_name,
                'charge_ids' => [$c1->id, $c2->id],
            ]
        );

        $this->assertEquals(15.70, $invoice->total_amount);
        $this->assertEquals(15.70, $invoice->balance);
        $this->assertSame(['lab_test', 'medicine', 'other'], $invoice->items->pluck('item_type')->sort()->values()->all());

        foreach ([$c1, $c2] as $c) {
            $c->refresh();
            $this->assertSame('invoiced', $c->status);
            $this->assertSame($invoice->id, (int) $c->invoice_id);
        }

        // The same charges cannot be billed twice.
        $this->postJson('/billing/store', [
            'patient_id' => $patient->patient_id,
            'patient_name' => $patient->full_name,
            'charge_ids' => [$c1->id],
        ])->assertStatus(422);
    }

    public function test_cannot_bill_another_patients_charge(): void
    {
        $this->actingAsRole('cashier');
        $a = $this->makePatient();
        $b = $this->makePatient();
        $charge = $this->charge($b, 5);

        $this->postJson('/billing/store', [
            'patient_id' => $a->patient_id,
            'patient_name' => $a->full_name,
            'charge_ids' => [$charge->id],
        ])->assertStatus(422);

        $this->assertSame('pending', $charge->fresh()->status);
    }

    // ---------------------------------------------------------------- payments

    public function test_cash_partial_then_full_payment(): void
    {
        $user = $this->actingAsRole('cashier');
        $invoice = $this->createInvoice([['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 100]]);

        $this->pay($invoice, 40)->assertOk()->assertJsonPath('success', true);
        $invoice->refresh();
        $this->assertSame('partial', $invoice->status);
        $this->assertEquals(40.0, $invoice->paid_amount);
        $this->assertEquals(60.0, $invoice->balance);

        $this->pay($invoice, 60)->assertOk();
        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertEquals(100.0, $invoice->paid_amount);
        $this->assertEquals(0.0, $invoice->balance);

        $payments = InvoicePayment::where('invoice_id', $invoice->id)->get();
        $this->assertCount(2, $payments);
        $this->assertEquals(100.0, $payments->sum('amount'));
        $this->assertTrue($payments->every(fn ($p) => $p->payment_method === 'cash' && (int) $p->processed_by === $user->id));
    }

    public function test_float_rounding_does_not_leave_invoice_partial(): void
    {
        $this->actingAsRole('cashier');
        $invoice = $this->createInvoice([['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 0.80]]);

        $this->pay($invoice, 0.10)->assertOk();
        $this->pay($invoice, 0.70)->assertOk();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertEquals(0.0, $invoice->balance);
        $this->assertEquals(0.80, $invoice->paid_amount);
    }

    public function test_overpayment_and_invalid_payment_rejected(): void
    {
        $this->actingAsRole('cashier');
        $invoice = $this->createInvoice([['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 10]]);

        $this->pay($invoice, 10.01)->assertStatus(422);
        $this->pay($invoice, 0)->assertStatus(422);
        $this->pay($invoice, 5, 'card')->assertStatus(422);

        $this->pay($invoice, 10)->assertOk();
        $this->pay($invoice, 0.01)->assertStatus(422);

        $this->assertSame(1, InvoicePayment::where('invoice_id', $invoice->id)->count());
        $this->assertEquals(0.0, $invoice->fresh()->balance);
    }

    // -------------------------------------------------------------------- KHQR

    public function test_khqr_md5_not_generated_for_invoice_is_rejected(): void
    {
        $this->actingAsRole('cashier');
        $invoice = $this->createInvoice([['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 10]]);

        $this->pay($invoice, 10, 'khqr', md5('random'))->assertStatus(422);
        $this->assertSame('unpaid', $invoice->fresh()->status);
    }

    public function test_khqr_wrong_amount_or_unconfirmed_is_rejected(): void
    {
        $this->actingAsRole('cashier');
        $invoice = $this->createInvoice([['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 10]]);
        $md5 = md5('qr-' . Str::uuid());
        app(KhqrVerifier::class)->remember($md5, $invoice->id, 10.00, 'USD');

        // Amount differs from the QR.
        $this->pay($invoice, 5, 'khqr', $md5)->assertStatus(422);
        // No Bakong token configured -> not confirmed, no network call.
        $this->pay($invoice, 10, 'khqr', $md5)->assertStatus(422);

        $this->assertSame(0, InvoicePayment::where('invoice_id', $invoice->id)->count());
    }

    public function test_khqr_confirmed_payment_is_recorded_once(): void
    {
        $this->actingAsRole('cashier');
        $invoice = $this->createInvoice([['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 10]]);
        $md5 = md5('qr-' . Str::uuid());

        $this->partialMock(KhqrVerifier::class, function ($mock) {
            $mock->shouldReceive('isPaid')->andReturn(true);
        });
        app(KhqrVerifier::class)->remember($md5, $invoice->id, 4.00, 'USD');

        $this->pay($invoice, 4, 'khqr', $md5)->assertOk();
        $invoice->refresh();
        $this->assertSame('partial', $invoice->status);
        $this->assertEquals(6.0, $invoice->balance);

        // Reusing the same md5 must not record a second payment.
        $this->pay($invoice, 4, 'khqr', $md5)->assertStatus(422);
        $this->assertSame(1, InvoicePayment::where('invoice_id', $invoice->id)->count());
    }

    public function test_khqr_md5_from_another_invoice_is_rejected(): void
    {
        $this->actingAsRole('cashier');
        $a = $this->createInvoice([['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 10]]);
        $b = $this->createInvoice([['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 10]]);
        $md5 = md5('qr-' . Str::uuid());

        $this->partialMock(KhqrVerifier::class, function ($mock) {
            $mock->shouldReceive('isPaid')->andReturn(true);
        });
        app(KhqrVerifier::class)->remember($md5, $a->id, 10.00, 'USD');

        $this->pay($b, 10, 'khqr', $md5)->assertStatus(422);
        $this->assertSame('unpaid', $b->fresh()->status);
    }

    public function test_check_payment_status_does_not_500(): void
    {
        $this->actingAsRole('cashier');
        $this->getJson('/billing/payment/check-status/not-an-md5')->assertStatus(422)->assertJsonPath('paid', false);
        // No token configured -> 503, never a live call.
        $this->getJson('/billing/payment/check-status/' . md5('x'))->assertStatus(503)->assertJsonPath('paid', false);
    }

    // ------------------------------------------------------------- update/edit

    public function test_update_manual_invoice_recomputes_totals_with_store_item_types(): void
    {
        $this->actingAsRole('admin');
        $invoice = $this->createInvoice([['item_type' => 'medicine', 'description' => 'Pills', 'qty' => 1, 'unit_price' => 5]]);

        $this->getJson("/billing/{$invoice->id}/edit")->assertOk()->assertJsonPath('success', true);

        $this->putJson("/billing/{$invoice->id}", [
            'patient_name' => 'Renamed',
            'patient_phone' => '011111111',
            'items' => [
                ['item_type' => 'medicine', 'description' => 'Pills', 'qty' => 4, 'unit_price' => 2.25],
                ['item_type' => 'service', 'description' => 'Dressing', 'qty' => 1, 'unit_price' => 3.33],
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $invoice = $invoice->fresh('items');
        $this->assertSame('Renamed', $invoice->patient_name);
        $this->assertEquals(12.33, $invoice->total_amount);
        $this->assertEquals(12.33, $invoice->balance);
        $this->assertCount(2, $invoice->items);
        $this->assertEquals(12.33, round((float) $invoice->items->sum('subtotal'), 2));
    }

    public function test_update_validation_rules(): void
    {
        $this->actingAsRole('admin');
        $invoice = $this->createInvoice([['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 5]]);

        $this->putJson("/billing/{$invoice->id}", ['patient_name' => 'X'])
            ->assertStatus(422)->assertJsonValidationErrors(['items']);

        $this->putJson("/billing/{$invoice->id}", [
            'patient_name' => '',
            'items' => [['item_type' => 'nope', 'description' => '', 'qty' => 0, 'unit_price' => -2]],
        ])->assertStatus(422)->assertJsonValidationErrors([
            'patient_name', 'items.0.item_type', 'items.0.description', 'items.0.qty', 'items.0.unit_price',
        ]);

        $this->assertEquals(5.0, $invoice->fresh()->total_amount);
    }

    public function test_cannot_edit_invoice_after_payment(): void
    {
        $this->actingAsRole('admin');
        $invoice = $this->createInvoice([['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 50]]);
        $this->pay($invoice, 20)->assertOk();

        $this->getJson("/billing/{$invoice->id}/edit")->assertStatus(422);
        $this->putJson("/billing/{$invoice->id}", [
            'patient_name' => 'X',
            'items' => [['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors(['status']);

        $invoice->refresh();
        $this->assertEquals(50.0, $invoice->total_amount);
        $this->assertEquals(30.0, $invoice->balance);
        $this->assertEquals(20.0, $invoice->paid_amount);
    }

    public function test_charge_backed_invoice_update_keeps_items_and_total(): void
    {
        $this->actingAsRole('admin');
        $patient = $this->makePatient();
        $charge = $this->charge($patient, 12.5, 2); // 25.00

        $invoice = $this->createInvoice([], [
            'patient_id' => $patient->patient_id,
            'patient_name' => $patient->full_name,
            'charge_ids' => [$charge->id],
            'items' => null,
        ]);
        $this->assertEquals(25.0, $invoice->total_amount);

        // Only descriptive fields are accepted; no items needed (H12).
        $this->putJson("/billing/{$invoice->id}", [
            'patient_name' => 'New Name',
            'patient_phone' => '010101010',
            'items' => [['item_type' => 'service', 'description' => 'Hack', 'qty' => 1, 'unit_price' => 0.01]],
        ])->assertOk();

        $invoice = $invoice->fresh('items');
        $this->assertSame('New Name', $invoice->patient_name);
        $this->assertEquals(25.0, $invoice->total_amount);
        $this->assertCount(1, $invoice->items);
    }

    public function test_cashier_without_edit_permission_cannot_edit(): void
    {
        $cashier = $this->actingAsRole('cashier');
        $invoice = $this->createInvoice([['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 5]]);

        $expected = $cashier->checkPermissionTo('edit-invoices') ? 200 : 403;
        $this->getJson("/billing/{$invoice->id}/edit")->assertStatus($expected);
    }

    // -------------------------------------------------------------------- cancel

    public function test_cancel_marks_invoice_cancelled_and_releases_charges(): void
    {
        $this->actingAsRole('admin');
        $patient = $this->makePatient();
        $charge = $this->charge($patient, 90);

        $invoice = $this->createInvoice([], [
            'patient_id' => $patient->patient_id,
            'patient_name' => $patient->full_name,
            'charge_ids' => [$charge->id],
            'items' => null,
        ]);

        $this->postJson("/billing/{$invoice->id}/cancel", ['cancel_reason' => 'Wrong patient selected'])
            ->assertOk()->assertJsonPath('success', true);

        $invoice->refresh();
        $this->assertSame('cancelled', $invoice->status);
        $this->assertNotNull($invoice->cancelled_at);
        $this->assertSame('Wrong patient selected', $invoice->cancel_reason);

        $charge->refresh();
        $this->assertSame('pending', $charge->status);
        $this->assertNull($charge->invoice_id);

        // Cancelled invoice cannot be paid; outstanding totals exclude it.
        $this->pay($invoice, 1)->assertStatus(422);

        // Re-bill on a new invoice: patient owes 90, not 180.
        $new = $this->createInvoice([], [
            'patient_id' => $patient->patient_id,
            'patient_name' => $patient->full_name,
            'charge_ids' => [$charge->id],
            'items' => null,
        ]);
        $this->assertEquals(90.0, $new->total_amount);
        $owed = Invoice::where('patient_id', $patient->patient_id)->where('status', '!=', 'cancelled')->sum('balance');
        $this->assertEquals(90.0, (float) $owed);
    }

    public function test_cannot_cancel_paid_invoice_or_without_reason(): void
    {
        $this->actingAsRole('admin');
        $invoice = $this->createInvoice([['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 5]]);

        $this->postJson("/billing/{$invoice->id}/cancel", ['cancel_reason' => ''])->assertStatus(422);

        $this->pay($invoice, 1)->assertOk();
        $this->postJson("/billing/{$invoice->id}/cancel", ['cancel_reason' => 'Some reason'])->assertStatus(422);
        $this->assertSame('partial', $invoice->fresh()->status);
    }

    public function test_show_returns_receipt_json(): void
    {
        $this->actingAsRole('cashier');
        $invoice = $this->createInvoice([['item_type' => 'service', 'description' => 'S', 'qty' => 1, 'unit_price' => 5]]);

        $this->getJson("/billing/{$invoice->id}")->assertOk()
            ->assertJsonPath('data.id', $invoice->id)
            ->assertJsonStructure(['html']);
    }
}
