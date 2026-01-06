<!DOCTYPE html>
<html>
<head>
    <title>Bill Processing Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .header { text-align: center; margin-bottom: 25px; }
        .footer { margin-top: 40px; }
        .summary { margin-top: 20px; float: right; width: 250px; }
        .date-header { background-color: #e9ecef; font-weight: bold; padding: 8px; border: 1px solid #000; }
        .grand-total { font-weight: bold; background: #eee; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Government Vehicle Log Book</h2>
        <h3>Consolidated Bill Processing Report</h3>
        <p>Period: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
    </div>

    @php 
        $grandTotalKm = 0;
        $totalRecords = 0;
    @endphp

    <table>
        <thead>
            <tr>
                <th width="80">Date</th>
                <th width="100">Vehicle</th>
                <th width="100">Driver</th>
                <th>Dept / Description</th>
                <th width="70">Times</th>
                <th width="60">KM Start</th>
                <th width="60">KM End</th>
                <th width="60">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $date => $dayLogs)
                @foreach($dayLogs as $log)
                @php 
                    $grandTotalKm += $log->total_km;
                    $totalRecords++;
                @endphp
                <tr>
                    @if($loop->first)
                        <td rowspan="{{ count($dayLogs) }}" style="vertical-align: top; font-weight: bold;">
                            {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                        </td>
                    @endif
                    <td>{{ $log->monthlyDuty->vehicle->vehicle_number }}</td>
                    <td>{{ $log->monthlyDuty->primaryDriver->name }}</td>
                    <td>{{ $log->monthlyDuty->department_name }}</td>
                    <td>{{ $log->start_time }} - {{ $log->end_time }}</td>
                    <td>{{ $log->start_km }}</td>
                    <td>{{ $log->end_km }}</td>
                    <td style="font-weight: bold;">{{ $log->total_km }}</td>
                </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px;">No records found for this period.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td colspan="7" style="text-align: right;">Total Records: {{ $totalRecords }} | GRAND TOTAL KM</td>
                <td>{{ number_format($grandTotalKm, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="clear: both;"></div>

    <div class="footer">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="border: none; text-align: center; height: 80px; vertical-align: bottom;">
                    _____________________<br>
                    Verified by (Operator)
                </td>
                <td style="border: none; text-align: center; height: 80px; vertical-align: bottom;">
                    _____________________<br>
                    Authorized Signature
                </td>
            </tr>
        </table>
        <p style="text-align: center; font-size: 9px; margin-top: 30px; color: #777;">
            Generated via Fleet Management System on {{ date('d/m/Y H:i') }}
        </p>
    </div>
</body>
</html>
