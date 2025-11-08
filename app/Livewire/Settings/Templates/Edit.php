<?php

namespace App\Livewire\Settings\Templates;

use App\Enums\NotificationChannel;
use App\Models\CommunicationTemplate;
use App\Models\EventType;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Edit extends Component
{
    public CommunicationTemplate $template;

    public $name = '';

    public $type = 'email';

    public $event_type_id = null;

    public $subject = '';

    public $content = '';

    public $is_default = false;

    public $append_unsubscribe_footer = true;

    public $availableParameters = [
        '{name}' => 'Member\'s full name',
        '{event_name}' => 'Event name',
        '{event_date}' => 'Event date',
        '{event_time}' => 'Event time',
        '{position_name}' => 'Member\'s assigned position',
        '{organization_name}' => 'Organization name',
        '{unsubscribe_link}' => 'Unsubscribe link (email only)',
    ];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,sms',
            'event_type_id' => 'nullable|exists:event_types,id',
            'subject' => 'required_if:type,email|nullable|string|max:255',
            'content' => 'required|string',
            'is_default' => 'boolean',
            'append_unsubscribe_footer' => 'boolean',
        ];
    }

    protected $messages = [
        'name.required' => 'Template name is required.',
        'type.required' => 'Template type is required.',
        'subject.required_if' => 'Subject is required for email templates.',
        'content.required' => 'Template content is required.',
    ];

    public function mount(CommunicationTemplate $template)
    {
        // Ensure user has access to this template
        if (! Auth::user()->organizations->contains($template->organization_id)) {
            abort(403, 'Unauthorized access to template.');
        }

        $this->template = $template;
        $this->name = $template->name;
        $this->type = $template->type->value;
        $this->event_type_id = $template->event_type_id;
        $this->subject = $template->subject ?? '';
        $this->content = $template->content;
        $this->is_default = $template->is_default;
        $this->append_unsubscribe_footer = $template->append_unsubscribe_footer;
    }

    #[Computed]
    public function eventTypes()
    {
        return EventType::whereIn('organization_id', Auth::user()->organizations->pluck('id'))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function characterCount(): int
    {
        return mb_strlen($this->content);
    }

    #[Computed]
    public function previewContent(): string
    {
        $sampleData = [
            '{name}' => 'John Doe',
            '{event_name}' => 'Sunday Service',
            '{event_date}' => '2025-11-02',
            '{event_time}' => '10:00 AM',
            '{position_name}' => 'Usher',
            '{organization_name}' => Auth::user()->organizations->first()?->name ?? 'Sample Church',
            '{unsubscribe_link}' => '[Unsubscribe Link]',
        ];

        return str_replace(array_keys($sampleData), array_values($sampleData), $this->content);
    }

    #[Computed]
    public function previewSubject(): string
    {
        $sampleData = [
            '{name}' => 'John Doe',
            '{event_name}' => 'Sunday Service',
            '{event_date}' => '2025-11-02',
            '{event_time}' => '10:00 AM',
            '{position_name}' => 'Usher',
            '{organization_name}' => Auth::user()->organizations->first()?->name ?? 'Sample Church',
        ];

        return str_replace(array_keys($sampleData), array_values($sampleData), $this->subject);
    }

    public function updatedType($value)
    {
        // Reset subject if switching to SMS
        if ($value === 'sms') {
            $this->subject = '';
            $this->append_unsubscribe_footer = false;
        } else {
            $this->append_unsubscribe_footer = true;
        }
    }

    public function save()
    {
        $this->validate();

        $this->template->update([
            'name' => $this->name,
            'type' => NotificationChannel::from($this->type),
            'event_type_id' => $this->event_type_id ?: null,
            'subject' => $this->type === 'email' ? $this->subject : null,
            'content' => $this->content,
            'is_default' => $this->is_default,
            'append_unsubscribe_footer' => $this->type === 'email' ? $this->append_unsubscribe_footer : false,
        ]);

        session()->flash('success', 'Template updated successfully.');

        return redirect()->route('settings.templates.index');
    }

    public function cancel()
    {
        return redirect()->route('settings.templates.index');
    }

    public function render()
    {
        return view('livewire.settings.templates.edit');
    }
}
