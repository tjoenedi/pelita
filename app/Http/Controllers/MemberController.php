<?php

namespace App\Http\Controllers;

use App\Models\Member;

class MemberController extends Controller
{
    public function index()
    {
        return view('members.index');
    }

    public function create()
    {
        return view('members.create');
    }

    public function edit(Member $member)
    {
        return view('members.edit', compact('member'));
    }
}
