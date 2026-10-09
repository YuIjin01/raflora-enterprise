<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\InventoryItem;

class AdminPackageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_negative_price_rejected()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => 'Test Package',
            'category' => 'Test',
            'price' => -100,
        ]);
        $response->assertSessionHasErrors('price');
        $this->assertDatabaseCount('packages', 0);
    }

    public function test_zero_price_behavior_verified()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => 'Test Package',
            'category' => 'Test',
            'price' => 0,
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('packages', ['price' => 0]);
    }

    public function test_positive_decimal_accepted()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => 'Test Package',
            'category' => 'Test',
            'price' => 99.99,
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('packages', ['price' => 99.99]);
    }

    public function test_missing_category_rejected()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => 'Test Package',
            'price' => 100,
        ]);
        $response->assertSessionHasErrors('category');
    }

    public function test_missing_package_name_rejected()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'category' => 'Test',
            'price' => 100,
        ]);
        $response->assertSessionHasErrors('title');
    }

    public function test_generated_automatically_unique_and_readable()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => '12 Premium Red Roses Square Box',
            'category' => 'Test',
            'price' => 100,
            'package_code' => 'HACKER-CODE' // attempt to override
        ]);
        $response->assertSessionHasNoErrors();
        
        $package = Package::first();
        $this->assertNotNull($package->package_code);
        $this->assertNotEquals('HACKER-CODE', $package->package_code);
        $this->assertEquals('12PRRSB', $package->package_code);
        
        // Test uniqueness
        $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => '12 Premium Red Roses Square Box',
            'category' => 'Test',
            'price' => 100,
        ]);
        
        $packages = Package::all();
        $this->assertCount(2, $packages);
        $this->assertEquals('12PRRSB', $packages[0]->package_code);
        $this->assertEquals('12PRRSB1', $packages[1]->package_code);
    }

    public function test_package_name_edit_does_not_regenerate_code()
    {
        $package = Package::create(['title' => 'Old Title', 'category' => 'Cat', 'price' => 100]);
        $originalCode = $package->package_code;

        $response = $this->actingAs($this->admin)->put(route('admin.packages.update', $package), [
            'title' => 'New Title',
            'category' => 'Cat',
            'price' => 100,
        ]);

        $package->refresh();
        $this->assertEquals('New Title', $package->title);
        $this->assertEquals($originalCode, $package->package_code);
    }

    public function test_bom_mapping_stored_and_rejected()
    {
        $item = InventoryItem::create(['name' => 'Test Item', 'category' => 'Cat', 'unit' => 'pcs']);
        $stemItem = InventoryItem::create(['name' => 'Rose', 'category' => 'Flowers', 'unit' => 'stems']);
        $liquidItem = InventoryItem::create(['name' => 'Water', 'category' => 'Materials', 'unit' => 'liters']);

        // Valid integer quantity for pcs
        $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => 'Test BOM',
            'category' => 'Cat',
            'price' => 100,
            'inventory_items' => [$item->id => 5, $stemItem->id => 1]
        ]);
        $package = Package::where('title', 'Test BOM')->first();
        $this->assertEquals(5, $package->inventoryItems()->where('inventory_item_id', $item->id)->first()->pivot->quantity);
        $this->assertEquals(1, $package->inventoryItems()->where('inventory_item_id', $stemItem->id)->first()->pivot->quantity);

        // 0.56 stems rejected
        $response = $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => 'Test BOM Dec',
            'category' => 'Cat',
            'price' => 100,
            'inventory_items' => [$stemItem->id => 0.56]
        ]);
        $response->assertSessionHasErrors('inventory_items.'.$stemItem->id);

        // Decimal quantity allowed for genuinely measurable units (not in integer units list)
        $responseDec = $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => 'Test BOM Measurable',
            'category' => 'Cat',
            'price' => 100,
            'inventory_items' => [$liquidItem->id => 1.5]
        ]);
        $responseDec->assertSessionHasNoErrors();
        $packageDec = Package::where('title', 'Test BOM Measurable')->first();
        $this->assertEquals(1.5, $packageDec->inventoryItems()->first()->pivot->quantity);

        // Negative quantity rejected
        $responseNeg = $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => 'Test BOM Neg',
            'category' => 'Cat',
            'price' => 100,
            'inventory_items' => [$item->id => -5]
        ]);
        $responseNeg->assertSessionHasErrors('inventory_items.'.$item->id);

        // Zero rejected
        $responseZero = $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => 'Test BOM Zero Qty',
            'category' => 'Cat',
            'price' => 100,
            'inventory_items' => [$item->id => 0]
        ]);
        $responseZero->assertSessionHasErrors('inventory_items.'.$item->id);

        // Blank quantity rejected
        $responseBlank = $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => 'Test BOM Blank Qty',
            'category' => 'Cat',
            'price' => 100,
            'inventory_items' => [$item->id => null]
        ]);
        // numeric rule handles this
        $responseBlank->assertSessionHasErrors('inventory_items.'.$item->id);

        // Invalid inventory item ID rejected
        $responseInvalid = $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => 'Test BOM Invalid ID',
            'category' => 'Cat',
            'price' => 100,
            'inventory_items' => [99999 => 1]
        ]);
        $responseInvalid->assertSessionHasErrors('inventory_items.99999');

        // Unselected is not synced
        $this->actingAs($this->admin)->post(route('admin.packages.store'), [
            'title' => 'Test BOM Empty',
            'category' => 'Cat',
            'price' => 100,
            'inventory_items' => []
        ]);
        $packageEmpty = Package::where('title', 'Test BOM Empty')->first();
        $this->assertCount(0, $packageEmpty->inventoryItems);
    }

    public function test_update_no_change_and_real_change()
    {
        $package = Package::create([
            'title' => 'Title',
            'category' => 'Cat',
            'price' => 100,
            'description' => 'Desc',
            'is_active' => true,
        ]);

        // No change
        $response = $this->actingAs($this->admin)->put(route('admin.packages.update', $package), [
            'title' => 'Title',
            'category' => 'Cat',
            'price' => 100,
            'description' => 'Desc',
            'is_active' => true,
        ]);
        
        $response->assertSessionHas('info', 'No changes were made to this package.');
        $response->assertSessionMissing('success');

        // Real change
        $response = $this->actingAs($this->admin)->put(route('admin.packages.update', $package), [
            'title' => 'New Title',
            'category' => 'Cat',
            'price' => 100,
            'description' => 'Desc',
            'is_active' => true,
        ]);
        
        $response->assertSessionHas('success', 'Package updated successfully.');
        $response->assertSessionMissing('info');
    }
}
