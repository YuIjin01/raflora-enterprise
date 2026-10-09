<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class ClientRecordController extends Controller
{
    /**
     * Display a listing of client records.
     */
    public function index()
    {
        $clients = User::where('role', 'client')
            ->withCount('bookings')
            ->with(['bookings' => function ($query) {
                $query->latest();
            }])
            ->get();

        return view('admin.client-records', compact('clients'));
    }
}
