<x-mail::message>
# RACODE

A new inquiry was saved in the dashboard.

**Name:** {{ $inquiry->name }}

**Email:** {{ $inquiry->email }}

**Phone:** {{ $inquiry->phone }}

**Project type:** {{ $inquiry->project_type }}

@if ($inquiry->company)
**Company:** {{ $inquiry->company }}
@endif

{{ $inquiry->message }}

Open the admin dashboard to reply. This message does not include internal notes.
</x-mail::message>
