<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Client;
use App\Models\InventoryItem;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

DB::beginTransaction();

$admin = User::firstOrCreate(['email' => 'admin@test.com'], ['name' => 'Admin', 'password' => bcrypt('password'), 'role' => 'admin']);
$client = Client::firstOrCreate(['email' => 'client@test.com'], ['first_name' => 'Client', 'last_name' => 'Test', 'phone' => '1234']);

$inventoryItem = InventoryItem::create([
    'name' => 'White Roses Test ' . rand(),
    'current_stock' => 10,
    'unit_cost' => 1.50,
    'unit' => 'stem',
]);

$booking = Booking::create([
    'client_id' => $client->id,
    'event_type' => 'wedding',
    'event_date' => now()->addDays(20)->toDateString(),
    'venue' => 'The Garden Hall',
    'status' => 'payment_submitted',
    'total_quoted' => 15000,
]);

$booking->inventoryItems()->attach($inventoryItem->id, [
    'quantity' => 4,
    'quoted_unit_price' => 100,
    'procurement_status' => 'pending',
]);

$payment = Payment::create([
    'booking_id' => $booking->id,
    'amount' => 15000,
    'payment_type' => 'gcash',
    'reference_number' => 'REF-001',
    'status' => 'pending',
]);

Auth::login($admin);

$request = Request::create('/test', 'POST', ['amount_received' => 15000]);

$controller = app(\App\Http\Controllers\Admin\BookingController::class);
try {
    $response = $controller->verifyPayment($request, $payment);
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Session success: " . session('success') . "\n";
    echo "Session error: " . session('error') . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

$inventoryItem->refresh();
echo "Reserved stock: " . $inventoryItem->reserved_stock . "\n";

$txs = \App\Models\InventoryTransaction::where('booking_id', $booking->id)->get();
echo "Transactions count: " . $txs->count() . "\n";

DB::rollBack();
