<!DOCTYPE html>
<html>
<head>
    <title>Monthly Duty Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f0f0f0; }
        .header { text-align: center; margin-bottom: 30px; }
        .footer { margin-top: 50px; }
        .summary { margin-top: 20px; float: right; width: 300px; }
    </style>
</head>
<body>
    <div class="header">
        @if($company['logo'])
            {{-- <img src="{{ public_path('storage/' . $company['logo']) }}" style="max-height: 80px; margin-bottom: 10px;"> --}}
            <img src="{{ storage_path('app/public/duty_photos/'.$company['logo']) }}" style="max-height: 80px; margin-bottom: 10px;">
        @endif
        <h2 style="margin: 0; padding: 0;">{{ $company['name'] }}</h2>
        @if($company['address'])
            <p style="margin: 5px 0; font-size: 10px;">{{ $company['address'] }}</p>
        @endif
        @if($company['mobile'] || $company['email'])
            <p style="margin: 2px 0; font-size: 10px;">
                {{ $company['mobile'] ? 'Mobile: ' . $company['mobile'] : '' }}
                {{ $company['mobile'] && $company['email'] ? ' | ' : '' }}
                {{ $company['email'] ? 'Email: ' . $company['email'] : '' }}
            </p>
        @endif
        @if(!empty($company['gst']))
            <p style="margin: 2px 0; font-size: 10px;">GSTIN: {{ $company['gst'] }}</p>
        @endif
        <h3 style="margin: 15px 0 5px 0; padding: 0; text-decoration: underline;">Monthly Duty Report - {{ $monthlyDuty->start_date->toAppDate() }}</h3>
    </div>

    <div>
        <p><strong>Department:</strong> {{ $monthlyDuty->department_name }}</p>
        <p><strong>Officer:</strong> {{ $monthlyDuty->officer_name }}</p>
        <p><strong>Vehicle No:</strong> {{ $monthlyDuty->vehicle->vehicle_number }} ({{ ucfirst($monthlyDuty->vehicle->vehicle_type) }})</p>
        <p><strong>Primary Driver:</strong> {{ $monthlyDuty->primaryDriver->name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Start KM</th>
                <th>End KM</th>
                <th>Total KM</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthlyDuty->dailyLogs as $log)
            <tr>
                <td>{{ $log->duty_date->toAppDate() }}</td>
                <td>{{ $log->start_time }}</td>
                <td>{{ $log->end_time }}</td>
                <td>{{ $log->start_km }}</td>
                <td>{{ $log->end_km }}</td>
                <td>{{ $log->total_km }}</td>
                <td>{{ ucfirst($log->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <table style="width: 100%">
            <tr>
                <th>Total KM Run</th>
                <td>{{ $totalKm }}</td>
            </tr>
            <tr>
                <th>Total Duties</th>
                <td>{{ $totalDuties }}</td>
            </tr>
            <tr>
                <th>Completed</th>
                <td>{{ $completedDuties }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="border: none; text-align: center; height: 100px; vertical-align: bottom;">
                    _____________________<br>
                    Signature of Officer
                </td>
                <td style="border: none; text-align: center; height: 100px; vertical-align: bottom;">
                    _____________________<br>
                    Signature of Driver
                </td>
            </tr>
        </table>
        <p style="text-align: center; font-size: 10px; margin-top: 20px; color: #555; border-top: 1px solid #ddd; padding-top: 10px;">
            This is a system-generated document and does not require a physical signature.
        </p>
        <p style="text-align: center; font-size: 9px; margin-top: 5px; color: #888;">
            Generated via Fleet Management System on {{ now()->toAppDateTime() }}
        </p>
    </div>
</body>
</html>
