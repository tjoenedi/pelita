<?php

namespace App\Livewire\Member;

use App\Models\Member;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    // Form fields
    public $first_name = '';

    public $last_name = '';

    public $phone = '';

    public $address = '';

    public $city = '';

    public $state_province = '';

    public $zip = '';

    public $country = '';

    public $birth_date = '';

    public $birth_place = '';

    public $gender = '';

    public $baptism_date = '';

    public $marital_status = '';

    public $email = '';

    public $profile_picture;

    protected $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string|max:500',
        'city' => 'nullable|string|max:255',
        'state_province' => 'nullable|string|max:255',
        'zip' => 'nullable|string|max:20',
        'country' => 'nullable|string|max:255',
        'birth_date' => 'nullable|date',
        'birth_place' => 'nullable|string|max:255',
        'gender' => 'nullable|in:M,F',
        'baptism_date' => 'nullable|date',
        'marital_status' => 'nullable|in:Single,Married,Divorced,Widowed',
        'email' => 'required|email|max:255|unique:members,email',
        'profile_picture' => 'nullable|image|max:2048',
    ];

    public function save()
    {
        $this->validate();

        $data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state_province' => $this->state_province,
            'zip' => $this->zip,
            'country' => $this->country,
            'birth_date' => $this->birth_date,
            'birth_place' => $this->birth_place,
            'gender' => $this->gender,
            'baptism_date' => $this->baptism_date,
            'marital_status' => $this->marital_status,
            'email' => $this->email,
        ];

        // Handle file upload
        if ($this->profile_picture) {
            $path = $this->profile_picture->store('profile-pictures', 'public');
            $data['profile_picture'] = $path;
        }

        // Add organization_id from user's first organization
        $data['organization_id'] = Auth::user()->organizations->first()->id ?? null;

        $member = Member::create($data);

        session()->flash('success', 'Member created successfully.');

        return redirect()->route('members.index');
    }

    public function cancel()
    {
        return redirect()->route('members.index');
    }

    public function render()
    {
        return view('livewire.member.create');
    }
}
