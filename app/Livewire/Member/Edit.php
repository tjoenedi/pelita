<?php

namespace App\Livewire\Member;

use App\Models\Member;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public Member $member;

    // Form fields
    public $first_name;

    public $last_name;

    public $phone;

    public $address;

    public $city;

    public $state_province;

    public $zip;

    public $country;

    public $birth_date;

    public $birth_place;

    public $gender;

    public $baptism_date;

    public $marital_status;

    public $email;

    public $profile_picture;

    public $new_profile_picture;

    protected function rules()
    {
        return [
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
            'email' => 'required|email|max:255|unique:members,email,'.$this->member->id,
            'new_profile_picture' => 'nullable|image|max:2048',
        ];
    }

    public function mount(Member $member)
    {
        $this->member = $member;
        $this->fill([
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'phone' => $member->phone,
            'address' => $member->address,
            'city' => $member->city,
            'state_province' => $member->state_province,
            'zip' => $member->zip,
            'country' => $member->country,
            'birth_date' => $member->birth_date?->format('Y-m-d'),
            'birth_place' => $member->birth_place,
            'gender' => $member->gender,
            'baptism_date' => $member->baptism_date?->format('Y-m-d'),
            'marital_status' => $member->marital_status,
            'email' => $member->email,
            'profile_picture' => $member->profile_picture,
        ]);
    }

    public function save()
    {
        $this->validate();

        // Handle file upload
        if ($this->new_profile_picture) {
            $path = $this->new_profile_picture->store('profile-pictures', 'public');
            $this->profile_picture = $path;
        }

        $this->member->update([
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
            'profile_picture' => $this->profile_picture,
        ]);

        session()->flash('success', 'Member updated successfully.');

        return redirect()->route('members.index');
    }

    public function cancel()
    {
        return redirect()->route('members.index');
    }

    public function render()
    {
        return view('livewire.member.edit');
    }
}
