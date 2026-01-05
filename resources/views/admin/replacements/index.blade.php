@extends('admin.layouts.app')

@section('content')
<h2>Replacements History</h2>

<div class="card shadow-sm mt-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Original Driver</th>
                    <th>Replacement Driver</th>
                    <th>Reason</th>
                    <th>Assigned By</th>
                    <th>Assigned At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($replacements as $replacement)
                <tr>
                    <td>{{ $replacement->dailyDutyLog->duty_date->format('d M Y') }}</td>
                    <td>{{ $replacement->originalDriver->name }}</td>
                    <td>{{ $replacement->replacementDriver->name }}</td>
                    <td>{{ $replacement->reason }}</td>
                    <td>{{ $replacement->assigner->name }}</td>
                    <td>{{ $replacement->assigned_at->format('d M Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4">No replacements found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
     <div class="card-footer bg-white">
        {{ $replacements->links() }}
    </div>
</div>
@endsection
