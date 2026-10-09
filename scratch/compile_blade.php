<?php
require __DIR__ . '/vendor/autoload.php';
\ = require_once __DIR__ . '/bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Console\Kernel::class);
\->bootstrap();
try {
    view('admin.booking-show')->render();
} catch (\Throwable \) {
    echo \->getMessage() . "\n";
    echo \->getFile() . ":" . \->getLine() . "\n";
}
