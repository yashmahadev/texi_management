<!DOCTYPE html>
<html>
<head>
    <title>Bill Processing Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; word-wrap: break-word; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .footer { margin-top: 40px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .grand-total { font-weight: bold; background: #f9f9f9; }
        .badge { font-size: 9px; padding: 2px 4px; border: 1px solid #ccc; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="header">
        @if($company['logo'])
            @php
                $logoPath = storage_path('app/public/' . $company['logo']);
                if (!file_exists($logoPath)) {
                    $logoPath = public_path('storage/' . $company['logo']);
                }
            @endphp
            @if(file_exists($logoPath))
                <img src="{{ $logoPath }}" style="max-height: 70px; margin-bottom: 10px;">
            @endif
        @endif
        <h2 style="margin: 0; padding: 0;">{{ $company['name'] }}</h2>
        @if($company['address'])
            <p style="margin: 5px 0; font-size: 9px;">{{ $company['address'] }}</p>
        @endif
        @if($company['mobile'] || $company['email'])
            <p style="margin: 2px 0; font-size: 9px;">
                {{ $company['mobile'] ? 'Mobile: ' . $company['mobile'] : '' }}
                {{ $company['mobile'] && $company['email'] ? ' | ' : '' }}
                {{ $company['email'] ? 'Email: ' . $company['email'] : '' }}
            </p>
        @endif
        @if(!empty($company['gst']))
            <p style="margin: 2px 0; font-size: 9px;">GSTIN: {{ $company['gst'] }}</p>
        @endif
        <h3 style="margin: 15px 0 5px 0; padding: 0; text-decoration: underline;">Bill Processing Report</h3>
        <p style="margin: 0; font-size: 10px;">Period: {{ \Carbon\Carbon::parse($startDate)->toAppDate() }} to {{ \Carbon\Carbon::parse($endDate)->toAppDate() }}</p>
    </div>

    @php
        $grandTotalKm = 0;
        $totalRecords = 0;
    @endphp

    <table>
        <thead>
            <tr>
                <th width="12%">Date</th>
                <th width="15%">Vehicle #</th>
                <th width="20%">Driver</th>
                <th width="23%">Dept / Trip Details</th>
                <th width="18%">Time (S-E)</th>
                <th width="12%" class="text-right">Total KM</th>
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
                            {{ \Carbon\Carbon::parse($date)->toAppDate() }}
                        </td>
                    @endif
                    <td>{{ $log->monthlyDuty->vehicle->vehicle_number }}</td>
                    <td>{{ $log->monthlyDuty->primaryDriver->name }}</td>
                    <td>{{ $log->monthlyDuty->department_name }}</td>
                    <td class="text-center">{{ $log->start_time ?? '-' }} to {{ $log->end_time ?? '-' }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ $log->total_km ?? '0' }}</td>
                </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 30px;">No records found for this period.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td colspan="5" class="text-right" style="padding: 8px;">TOTAL KM FOR THE PERIOD:</td>
                <td class="text-right" style="padding: 8px;">{{ number_format($grandTotalKm, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <table style="border: none; margin-top: 40px;">
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
        <p style="text-align: center; font-size: 9px; margin-top: 20px; color: #555; border-top: 1px solid #ddd; padding-top: 10px;">
            This is a system-generated document and does not require a physical signature.
        </p>
        <p style="text-align: center; font-size: 8px; margin-top: 5px; color: #888;">
            Generated via Fleet Management System on {{ now()->toAppDateTime() }}
        </p>
    </div>
</body>
</html>
