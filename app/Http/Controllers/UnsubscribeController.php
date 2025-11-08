<?php

namespace App\Http\Controllers;

use App\Enums\NotificationChannel;
use App\Models\EventType;
use App\Models\MemberCommunicationPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UnsubscribeController extends Controller
{
    public function show(string $token)
    {
        $preference = MemberCommunicationPreference::where('unsubscribe_token', $token)->firstOrFail();

        $member = $preference->member;
        $organization = $preference->organization;

        $eventTypes = EventType::where('organization_id', $organization->id)->get();

        $allPreferences = MemberCommunicationPreference::where('member_id', $member->id)
            ->where('organization_id', $organization->id)
            ->get()
            ->groupBy(function ($pref) {
                return $pref->event_type_id ?? 'global';
            });

        return view('unsubscribe.show', [
            'token' => $token,
            'member' => $member,
            'organization' => $organization,
            'eventTypes' => $eventTypes,
            'preferences' => $allPreferences,
        ]);
    }

    public function update(Request $request, string $token)
    {
        $preference = MemberCommunicationPreference::where('unsubscribe_token', $token)->firstOrFail();

        $member = $preference->member;
        $organization = $preference->organization;

        $subscriptions = $request->input('subscriptions', []);

        $eventTypes = EventType::where('organization_id', $organization->id)->get();
        $channels = [NotificationChannel::Email, NotificationChannel::SMS];

        foreach ($eventTypes as $eventType) {
            foreach ($channels as $channel) {
                $key = "event_{$eventType->id}_{$channel->value}";
                $isSubscribed = isset($subscriptions[$key]);

                MemberCommunicationPreference::updateOrCreate(
                    [
                        'member_id' => $member->id,
                        'organization_id' => $organization->id,
                        'event_type_id' => $eventType->id,
                        'channel' => $channel,
                    ],
                    [
                        'is_subscribed' => $isSubscribed,
                        'unsubscribed_at' => $isSubscribed ? null : now(),
                        'unsubscribe_token' => $isSubscribed ? Str::random(64) : (MemberCommunicationPreference::where('member_id', $member->id)
                            ->where('organization_id', $organization->id)
                            ->where('event_type_id', $eventType->id)
                            ->where('channel', $channel)
                            ->first()?->unsubscribe_token ?? Str::random(64)),
                    ]
                );
            }
        }

        foreach ($channels as $channel) {
            $key = "global_{$channel->value}";
            $isSubscribed = isset($subscriptions[$key]);

            MemberCommunicationPreference::updateOrCreate(
                [
                    'member_id' => $member->id,
                    'organization_id' => $organization->id,
                    'event_type_id' => null,
                    'channel' => $channel,
                ],
                [
                    'is_subscribed' => $isSubscribed,
                    'unsubscribed_at' => $isSubscribed ? null : now(),
                    'unsubscribe_token' => $isSubscribed ? Str::random(64) : (MemberCommunicationPreference::where('member_id', $member->id)
                        ->where('organization_id', $organization->id)
                        ->whereNull('event_type_id')
                        ->where('channel', $channel)
                        ->first()?->unsubscribe_token ?? Str::random(64)),
                ]
            );
        }

        return redirect()->route('unsubscribe.show', $token)
            ->with('success', 'Your communication preferences have been updated.');
    }
}
