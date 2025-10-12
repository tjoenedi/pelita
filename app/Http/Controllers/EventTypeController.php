<?php

namespace App\Http\Controllers;

use App\Models\EventType;

class EventTypeController extends Controller
{
    public function index()
    {
        return view('event-types.index');
    }

    public function create()
    {
        return view('event-types.create');
    }

    public function edit(EventType $eventType)
    {
        return view('event-types.edit', compact('eventType'));
    }
}