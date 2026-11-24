<?php

namespace Tests\Feature;

use App\Models\Pharmacy\Supplier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use DatabaseTransactions;

    private function uid(): string
    {
        return strtoupper(Str::random(10));
    }

    public function test_supplier_create_list_update(): void
    {
        $this->actingAsRole('pharmacist');
        $name = 'Supplier ' . $this->uid();

        $res = $this->postJson('/pharmacy/suppliers', ['name' => $name, 'phone' => '011222333'])->assertOk();
        $id = $res->json('supplier.supplier_id');
        $this->assertNotNull($id);

        $this->getJson('/pharmacy/suppliers/list')->assertOk()->assertJsonFragment(['name' => $name]);

        $this->putJson("/pharmacy/suppliers/{$id}", ['name' => $name . ' X', 'address' => 'PP'])
            ->assertOk()->assertJsonPath('data.name', $name . ' X');
        $this->assertSame('PP', Supplier::find($id)->address);

        $this->postJson('/pharmacy/suppliers', ['name' => ''])->assertStatus(422);
        $this->putJson('/pharmacy/suppliers/999999999', ['name' => 'x'])->assertNotFound();
    }

    public function test_supplier_routes_forbidden_for_cashier(): void
    {
        $this->actingAsRole('cashier');
        $this->postJson('/pharmacy/suppliers', ['name' => 'Nope'])->assertForbidden();
    }
}
