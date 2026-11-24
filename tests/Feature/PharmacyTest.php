<?php

namespace Tests\Feature;

use App\Models\Billing\PatientCharge;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Pharmacy\Medicine;
use App\Models\Pharmacy\MedicineBatch;
use App\Models\Pharmacy\MedicineStockMovement;
use App\Models\Pharmacy\Supplier;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pharmacy: medicines, stock in (restock), prescriptions + dispensing (stock out), suppliers.
 *
 * Note: there is no standalone "sale" endpoint in routes/web.php; stock leaves the
 * pharmacy only through prescription dispensing, so that is what "sale" covers here.
 */
class PharmacyTest extends TestCase
{
    use DatabaseTransactions;

    // ---------------------------------------------------------------- helpers

    private function uid(): string
    {
        return strtoupper(Str::random(10));
    }

    private function makeMedicine(array $attrs = []): Medicine
    {
        return Medicine::create(array_merge([
            'medicine_name' => 'TestMed ' . $this->uid(),
            'category' => 'Tablet',
            'unit' => 'box',
            'dosage_unit' => 'mg',
            'strength' => '500',
            'unit_price' => 1.00,
            'pieces_per_unit' => 10,
            'selling_price' => 0.25,
            'reorder_level' => 5,
            'is_active' => true,
        ], $attrs));
    }

    private function makeBatch(Medicine $medicine, int $qty, string $expiry): MedicineBatch
    {
        return MedicineBatch::create([
            'medicine_id' => $medicine->medicine_id,
            'batch_number' => 'B-' . $this->uid(),
            'expiry_date' => $expiry,
            'quantity_initial' => $qty,
            'remaining_quantity' => $qty,
            'quantity_remaining' => $qty,
            'purchase_price' => 0.10,
        ]);
    }

    private function stock(Medicine $medicine): int
    {
        return (int) MedicineBatch::where('medicine_id', $medicine->medicine_id)->sum('remaining_quantity');
    }

    private function makeRecord(): MedicalRecord
    {
        $doctor = User::factory()->create();
        $patient = Patient::create([
            'patient_code' => 'PT-' . $this->uid(),
            'full_name' => 'Pharmacy Patient',
            'id_card' => 'T-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(12)),
            'sex' => 'male',
            'date_of_birth' => '1990-01-01',
            'phone' => '012345678',
        ]);

        return MedicalRecord::create([
            'patient_id' => $patient->patient_id,
            'user_id' => $doctor->id,
            'visit_date' => now(),
            'diagnosis' => 'Test',
        ]);
    }

    /** @return array{0: Prescription, 1: PrescriptionItem} */
    private function makePrescription(Medicine $medicine, int $qty): array
    {
        $record = $this->makeRecord();
        $prescription = Prescription::create([
            'record_id' => $record->record_id,
            'prescribed_date' => now(),
            'status' => 'pending',
        ]);
        $item = PrescriptionItem::create([
            'prescription_id' => $prescription->prescription_id,
            'medicine_id' => $medicine->medicine_id,
            'dosage' => '1 tab',
            'frequency' => '3x/day',
            'duration_days' => 5,
            'quantity' => $qty,
        ]);

        return [$prescription, $item];
    }

    private function dispense(Prescription $p, array $items)
    {
        return $this->from('/pharmacy/prescriptions')
            ->post("/pharmacy/prescriptions/{$p->prescription_id}/dispense", ['items' => $items]);
    }

    // ------------------------------------------------------------ pages/auth

    public function test_pharmacy_pages_render_for_pharmacist(): void
    {
        $this->actingAsRole('pharmacist');

        $this->get('/pharmacy')->assertOk();
        $this->get('/pharmacy/prescriptions')->assertOk();
        $this->getJson('/pharmacy/stats')->assertOk()->assertJsonStructure(['totalMedicine', 'stockValue', 'lowStock']);
        $this->getJson('/pharmacy/data?draw=1&start=0&length=10')->assertOk()->assertJsonStructure(['data', 'recordsTotal']);
    }

    public function test_pharmacy_index_renders_for_admin(): void
    {
        $this->actingAsRole('admin');
        $this->get('/pharmacy')->assertOk();
    }

    public function test_cashier_and_nurse_are_forbidden_from_pharmacy(): void
    {
        $this->actingAsRole('cashier');
        $this->get('/pharmacy')->assertForbidden();
        $this->postJson('/pharmacy', [])->assertForbidden();

        $this->actingAsRole('nurse');
        $this->get('/pharmacy/prescriptions')->assertForbidden();
    }

    // -------------------------------------------------------- medicine/stock

    public function test_create_medicine_creates_batch_and_in_movement_in_pieces(): void
    {
        $this->actingAsRole('pharmacist');
        $name = 'Paracetamol ' . $this->uid();
        $batchNo = 'BN-' . $this->uid();

        $this->postJson('/pharmacy', [
            'medicine_name' => $name,
            'category' => 'Tablet',
            'unit' => 'box',
            'dosage_unit' => 'mg',
            'strength' => '500',
            'selling_price' => 0.20,
            'pieces_per_unit' => 10,
            'reorder_level' => 5,
            'quantity_initial' => 12,
            'expiry_date' => now()->addYear()->toDateString(),
            'purchase_price' => 1.50,
            'batch_number' => $batchNo,
        ])->assertOk();

        $medicine = Medicine::where('medicine_name', $name)->firstOrFail();
        $this->assertSame(120, $this->stock($medicine));
        $this->assertEquals(1.50, (float) $medicine->unit_price);

        $batch = MedicineBatch::where('batch_number', $batchNo)->firstOrFail();
        $this->assertSame(120, (int) $batch->quantity_initial);
        $this->assertSame(120, (int) $batch->quantity_remaining, 'legacy quantity_remaining must stay in sync (L7)');

        $this->assertDatabaseHas('medicine_stock_movements', [
            'batch_id' => $batch->batch_id,
            'movement_type' => 'IN',
            'quantity' => 120,
        ]);
    }

    public function test_create_medicine_validation_rejects_bad_input(): void
    {
        $this->actingAsRole('pharmacist');

        $this->postJson('/pharmacy', [
            'medicine_name' => '',
            'selling_price' => -1,
            'pieces_per_unit' => 0,
            'quantity_initial' => -5,
            'expiry_date' => now()->subDay()->toDateString(),
        ])->assertStatus(422)->assertJsonStructure(['errors' => [
            'medicine_name', 'selling_price', 'pieces_per_unit', 'quantity_initial', 'expiry_date', 'batch_number',
        ]]);
    }

    public function test_restock_adds_units_times_pieces_per_unit(): void
    {
        $this->actingAsRole('pharmacist');
        $medicine = $this->makeMedicine(['pieces_per_unit' => 10]);
        $this->makeBatch($medicine, 30, now()->addMonths(6)->toDateString());

        $batchNo = 'RS-' . $this->uid();
        $this->postJson("/pharmacy/{$medicine->medicine_id}/restock", [
            'batch_number' => $batchNo,
            'expiry_date' => now()->addYear()->toDateString(),
            'quantity_initial' => 5,
            'purchase_price' => 1.2,
        ])->assertOk();

        $this->assertSame(80, $this->stock($medicine));
        $batch = MedicineBatch::where('batch_number', $batchNo)->firstOrFail();
        $this->assertSame(50, (int) $batch->remaining_quantity);
        $this->assertSame(50, (int) $batch->quantity_remaining);
        $this->assertDatabaseHas('medicine_stock_movements', [
            'batch_id' => $batch->batch_id, 'movement_type' => 'IN', 'quantity' => 50,
        ]);
    }

    public function test_restock_rejects_duplicate_batch_number_and_zero_quantity(): void
    {
        $this->actingAsRole('pharmacist');
        $medicine = $this->makeMedicine();
        $existing = $this->makeBatch($medicine, 10, now()->addMonths(6)->toDateString());

        $this->postJson("/pharmacy/{$medicine->medicine_id}/restock", [
            'batch_number' => $existing->batch_number,
            'expiry_date' => now()->addYear()->toDateString(),
            'quantity_initial' => 0,
            'purchase_price' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['batch_number', 'quantity_initial']);

        $this->assertSame(10, $this->stock($medicine));
    }

    public function test_update_medicine_and_soft_deactivate(): void
    {
        $this->actingAsRole('pharmacist');
        $medicine = $this->makeMedicine();

        $this->putJson("/pharmacy/{$medicine->medicine_id}", [
            'medicine_name' => 'Renamed ' . $this->uid(),
            'category' => 'Syrup',
            'unit' => 'bottle',
            'dosage_unit' => 'ml',
            'strength' => '100',
            'unit_price' => 2,
            'selling_price' => 3.5,
            'pieces_per_unit' => 1,
        ])->assertOk();
        $this->assertEquals(3.5, (float) $medicine->fresh()->selling_price);

        $this->deleteJson("/pharmacy/{$medicine->medicine_id}")->assertOk();
        $this->assertFalse($medicine->fresh()->is_active);
    }

    // ---------------------------------------------------- prescriptions/dispense

    public function test_store_prescription_creates_items(): void
    {
        $this->actingAsRole('pharmacist');
        $medicine = $this->makeMedicine();
        $record = $this->makeRecord();

        $this->from('/pharmacy/prescriptions')->post('/pharmacy/prescriptions/store', [
            'record_id' => $record->record_id,
            'prescribed_date' => now()->toDateString(),
            'items' => [[
                'medicine_id' => $medicine->medicine_id,
                'dosage' => '1 tab',
                'frequency' => '3x/day',
                'duration_days' => 5,
                'quantity' => 15,
            ]],
        ])->assertRedirect()->assertSessionHas('success');

        $p = Prescription::where('record_id', $record->record_id)->firstOrFail();
        $this->assertSame('pending', $p->status);
        $this->assertSame(15, (int) $p->items()->first()->quantity);
    }

    public function test_dispense_reduces_stock_fefo_and_creates_exact_charge(): void
    {
        $user = $this->actingAsRole('pharmacist');
        $medicine = $this->makeMedicine(['selling_price' => 0.35]);
        $soon = $this->makeBatch($medicine, 10, now()->addMonths(2)->toDateString());
        $later = $this->makeBatch($medicine, 50, now()->addYear()->toDateString());
        [$p, $item] = $this->makePrescription($medicine, 30);

        $this->dispense($p, [$item->prescription_item_id => 30])->assertSessionHas('success');

        $this->assertSame(0, (int) $soon->fresh()->remaining_quantity, 'earliest expiry used first');
        $this->assertSame(30, (int) $later->fresh()->remaining_quantity);
        $this->assertSame(30, (int) $later->fresh()->quantity_remaining);
        $this->assertSame(30, $this->stock($medicine));

        $outs = MedicineStockMovement::whereIn('batch_id', [$soon->batch_id, $later->batch_id])
            ->where('movement_type', 'OUT')->sum('quantity');
        $this->assertSame(30, (int) $outs);

        $p->refresh();
        $this->assertSame('dispensed', $p->status);
        $this->assertSame($user->id, (int) $p->dispensed_by);

        $charges = PatientCharge::where('source_type', 'medicine')
            ->where('medical_record_id', $p->record_id)->get();
        $this->assertCount(1, $charges);
        $this->assertSame(30, $charges[0]->quantity);
        $this->assertEquals(0.35, (float) $charges[0]->unit_price);
        $this->assertEquals(10.50, (float) $charges[0]->total_price);
        $this->assertSame('pending', $charges[0]->status);
    }

    public function test_cannot_dispense_more_than_stock_and_nothing_changes(): void
    {
        $this->actingAsRole('pharmacist');
        $medicine = $this->makeMedicine();
        $batch = $this->makeBatch($medicine, 10, now()->addYear()->toDateString());
        [$p, $item] = $this->makePrescription($medicine, 20);

        $this->dispense($p, [$item->prescription_item_id => 20])->assertSessionHas('error');

        $this->assertSame(10, (int) $batch->fresh()->remaining_quantity, 'stock rolled back');
        $this->assertSame(0, MedicineStockMovement::where('batch_id', $batch->batch_id)->where('movement_type', 'OUT')->count());
        $this->assertSame(0, PatientCharge::where('medical_record_id', $p->record_id)->count());
        $this->assertSame('pending', $p->fresh()->status);
    }

    public function test_expired_batches_are_never_dispensed(): void
    {
        $this->actingAsRole('pharmacist');
        $medicine = $this->makeMedicine();
        $expired = $this->makeBatch($medicine, 100, now()->subDays(3)->toDateString());
        $valid = $this->makeBatch($medicine, 10, now()->addYear()->toDateString());
        [$p, $item] = $this->makePrescription($medicine, 20);

        // Only 10 valid pieces -> must fail even though 100 expired pieces exist.
        $this->dispense($p, [$item->prescription_item_id => 20])->assertSessionHas('error');
        $this->assertSame(100, (int) $expired->fresh()->remaining_quantity);
        $this->assertSame(10, (int) $valid->fresh()->remaining_quantity);

        // 10 is fine and must come from the valid batch only.
        $this->dispense($p, [$item->prescription_item_id => 10])->assertSessionHas('success');
        $this->assertSame(100, (int) $expired->fresh()->remaining_quantity);
        $this->assertSame(0, (int) $valid->fresh()->remaining_quantity);
    }

    public function test_cannot_dispense_more_than_prescribed(): void
    {
        $this->actingAsRole('pharmacist');
        $medicine = $this->makeMedicine();
        $batch = $this->makeBatch($medicine, 100, now()->addYear()->toDateString());
        [$p, $item] = $this->makePrescription($medicine, 5);

        $this->dispense($p, [$item->prescription_item_id => 6])->assertSessionHas('error');
        $this->assertSame(100, (int) $batch->fresh()->remaining_quantity);
    }

    public function test_partial_then_full_dispense_and_no_double_dispense(): void
    {
        $this->actingAsRole('pharmacist');
        $medicine = $this->makeMedicine(['selling_price' => 1.10]);
        $batch = $this->makeBatch($medicine, 100, now()->addYear()->toDateString());
        [$p, $item] = $this->makePrescription($medicine, 15);

        $this->dispense($p, [$item->prescription_item_id => 10])->assertSessionHas('success');
        $this->assertSame('partial', $p->fresh()->status);
        $this->assertSame(90, (int) $batch->fresh()->remaining_quantity);

        // Asking for the full 15 again must fail: only 5 remain to dispense.
        $this->dispense($p, [$item->prescription_item_id => 15])->assertSessionHas('error');
        $this->assertSame(90, (int) $batch->fresh()->remaining_quantity);

        $this->dispense($p, [$item->prescription_item_id => 5])->assertSessionHas('success');
        $this->assertSame('dispensed', $p->fresh()->status);
        $this->assertSame(85, (int) $batch->fresh()->remaining_quantity);

        $this->dispense($p, [$item->prescription_item_id => 1])->assertSessionHas('error');
        $this->assertSame(85, (int) $batch->fresh()->remaining_quantity);

        $total = PatientCharge::where('medical_record_id', $p->record_id)->sum('total_price');
        $this->assertEquals(16.50, round((float) $total, 2));
    }

    public function test_dispense_rejects_item_from_another_prescription_and_bad_qty(): void
    {
        $this->actingAsRole('pharmacist');
        $medicine = $this->makeMedicine();
        $batch = $this->makeBatch($medicine, 100, now()->addYear()->toDateString());
        [$p1] = $this->makePrescription($medicine, 5);
        [, $otherItem] = $this->makePrescription($medicine, 5);

        $this->dispense($p1, [$otherItem->prescription_item_id => 5])->assertSessionHas('error');
        $this->dispense($p1, [$otherItem->prescription_item_id => '-3'])->assertSessionHas('error');
        $this->dispense($p1, [])->assertSessionHas('error');
        $this->assertSame(100, (int) $batch->fresh()->remaining_quantity);
    }

    public function test_dispense_refuses_medicine_without_selling_price(): void
    {
        $this->actingAsRole('pharmacist');
        $medicine = $this->makeMedicine(['selling_price' => 0]);
        $batch = $this->makeBatch($medicine, 100, now()->addYear()->toDateString());
        [$p, $item] = $this->makePrescription($medicine, 5);

        $this->dispense($p, [$item->prescription_item_id => 5])->assertSessionHas('error');
        $this->assertSame(100, (int) $batch->fresh()->remaining_quantity);
    }
}
