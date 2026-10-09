<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;

$item = InventoryItem::first();
if (!$item) {
    echo "No item\n";
    exit;
}

echo "Current Item ID: " . $item->id . "\n";

// Create a dummy transaction
$tx = InventoryTransaction::create([
    'inventory_item_id' => $item->id,
    'booking_id' => 1,
    'quantity_change' => -4.0,
    'transaction_type' => 'booking_lock',
    'reason' => 'test',
    'performed_by' => 1,
]);

echo "Created tx ID: " . $tx->id . " with change: " . $tx->quantity_change . "\n";

$item = $item->fresh();

$reserved = $item->reserved_stock;
echo "Reserved stock: " . $reserved . "\n";

$tx->delete();
echo "Cleanup done\n";
