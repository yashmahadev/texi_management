<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Bill Processing Report</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

@page { size: A4 portrait; margin: 10mm 10mm 10mm 10mm; }

html, body { width: 100%; }

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 8pt;
    line-height: 1.25;
    color: #000;
    background: #fff;
    zoom: {{ $scale }};
}

/* HEADER */
.header-outer { border-bottom: 1.5pt solid #000; padding-bottom: 4pt; margin-bottom: 5pt; }
.header-inner  { width: 100%; border-collapse: collapse; }
.header-inner td { border: none; vertical-align: middle; padding: 0; }
.col-logo   { width: 50pt; }
.col-logo img { max-height: 36pt; max-width: 48pt; }
.col-spacer { width: 50pt; }
.col-company { text-align: center; padding: 0 4pt; }
.col-company h1 { font-size: 11pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4pt; margin-bottom: 1pt; }
.col-company .co-sub { font-size: 6.5pt; line-height: 1.3; }
.title-bar { text-align: center; margin-top: 3pt; padding-top: 3pt; border-top: 0.5pt solid #000; }
.title-bar h2 { font-size: 9pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6pt; }
.title-bar p  { font-size: 7pt; margin-top: 1pt; }

/* SECTION LABEL */
.slabel { font-size: 6pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4pt; border-bottom: 0.5pt solid #000; padding-bottom: 1pt; margin-bottom: 2pt; }

/* DATA TABLE */
.tbl-wrap { margin-bottom: 4pt; }
.dtbl { width: 100%; border-collapse: collapse; table-layout: fixed; }
.dtbl thead tr { background: #000; color: #fff; }
.dtbl th { padding: 3pt 2pt; text-align: center; font-size: 7pt; font-weight: bold; border: 0.5pt solid #000; white-space: nowrap; }
.dtbl tbody td { padding: 2.5pt 2pt; border: 0.5pt solid #aaa; font-size: 7.5pt; vertical-align: middle; }
.dtbl tbody tr:nth-child(even) td { background: #f2f2f2; }
.dtbl tbody tr:nth-child(odd)  td { background: #fff; }
.dtbl tfoot td { padding: 3pt 2pt; border: 0.75pt solid #000; font-size: 7.5pt; font-weight: bold; background: #000; color: #fff; }

/* BOTTOM */
.bot { width: 100%; border-collapse: collapse; margin-top: 4pt; }
.bot > tbody > tr > td { border: none; vertical-align: top; padding: 0; }
.bleft  { width: 50%; padding-right: 6pt; }
.bright { width: 50%; }

.tot-tbl { width: 100%; border-collapse: collapse; }
.tot-tbl th { background: #000; color: #fff; padding: 2.5pt 5pt; font-size: 6.5pt; text-transform: uppercase; letter-spacing: 0.2pt; text-align: left; border: 0.5pt solid #000; }
.tot-tbl td { padding: 2.5pt 5pt; border: 0.5pt solid #000; font-size: 7.5pt; }
.tot-tbl td.lbl { background: #f2f2f2; font-weight: bold; font-size: 7pt; text-align: left; width: 65%; }
.tot-tbl td.val { text-align: right; font-weight: bold; background: #fff; }

.sig-tbl { width: 100%; border-collapse: collapse; }
.sig-tbl td { border: none; padding: 0 3pt; vertical-align: top; }
.sig-box { border: 0.5pt solid #000; padding: 20pt 6pt 4pt 6pt; text-align: center; font-size: 7pt; font-weight: bold; }
.sig-box span { font-size: 6.5pt; font-weight: normal; display: block; margin-top: 1pt; }

/* FOOTER */
.foot { margin-top: 4pt; border-top: 0.5pt solid #000; padding-top: 2pt; text-align: center; }
.foot p { font-size: 6pt; line-height: 1.3; }

.tr { text-align: right !important; }
.tc { text-align: center !important; }
.tl { text-align: left !important; }
</style>
</head>
<body>

<div class="header-outer">
    <table class="header-inner">
        <tr>
            <td class="col-logo">
                @if($company['logo'])
                    @php
                        $lp = storage_path('app/public/'.$company['logo']);
                        if (!file_exists($lp)) $lp = public_path('storage/'.$company['logo']);
                    @endphp
                    @if(file_exists($lp))<img src="{{ $lp }}" alt="Logo">@endif
                @endif
            </td>
            <td class="col-company">
                <h1>{{ $company['name'] }}</h1>
                <div class="co-sub">
                    @if($company['address']){{ $company['address'] }}<br>@endif
                    @if($company['mobile'])Mob: {{ $company['mobile'] }}@endif
                    @if($company['mobile'] && $company['email']) &bull; @endif
                    @if($company['email']){{ $company['email'] }}@endif
                    @if(!empty($company['gst']))<br>GSTIN: {{ $company['gst'] }}@endif
                </div>
            </td>
            <td class="col-spacer"></td>
        </tr>
    </table>
    <div class="title-bar">
        <h2>Bill Processing Report</h2>
        <p>Period: {{ \Carbon\Carbon::parse($startDate)->toAppDate() }} &mdash; {{ \Carbon\Carbon::parse($endDate)->toAppDate() }}</p>
    </div>
</div>

@php $grandTotalKm = 0; @endphp

<div class="tbl-wrap">
    <div class="slabel">Duty Log Entries</div>
    <table class="dtbl">
        <thead>
            <tr>
                <th width="12%">Date</th>
                <th width="15%">Vehicle No.</th>
                <th width="20%">Driver</th>
                <th width="23%">Department</th>
                <th width="16%">Start &mdash; End Time</th>
                <th width="7%">Trips</th>
                <th width="7%">KM</th>
            </tr>
        </thead>
        <tbody>
            @forelse($aggregatedRows as $row)
                @php $grandTotalKm += $row->total_km ?? 0; @endphp
                <tr>
                    <td class="tc" style="font-weight:bold;">
                        {{ $row->duty_date->format('d M') }}
                        <span style="font-size:6pt;font-weight:normal;display:block;">{{ $row->duty_date->format('D') }}</span>
                    </td>
                    <td class="tc">{{ $row->monthlyDuty?->vehicle?->vehicle_number ?? '—' }}</td>
                    <td>{{ $row->monthlyDuty?->primaryDriver?->name ?? '—' }}</td>
                    <td>{{ $row->monthlyDuty?->department_name ?? '—' }}</td>
                    <td class="tc">{{ $row->start_time ?? '—' }} &mdash; {{ $row->end_time ?? '—' }}</td>
                    <td class="tc">{{ $row->trip_count }}</td>
                    <td class="tr" style="font-weight:bold;padding-right:3pt;">{{ $row->total_km ?? '0' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="tc" style="padding:10pt;font-style:italic;">No records found.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="tr">Total Entries: {{ $aggregatedRows->count() }}</td>
                <td class="tr">Grand Total KM:</td>
                <td class="tr" style="padding-right:3pt;">{{ number_format($grandTotalKm, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<table class="bot">
    <tbody><tr>
        <td class="bleft">
            <div class="slabel">Summary</div>
            <table class="tot-tbl">
                <thead><tr><th colspan="2">Report Totals</th></tr></thead>
                <tbody>
                    <tr><td class="lbl">Total Duty Days</td><td class="val">{{ $aggregatedRows->count() }}</td></tr>
                    <tr><td class="lbl">Total KM Covered</td><td class="val">{{ number_format($grandTotalKm, 2) }} km</td></tr>
                    <tr><td class="lbl">Period</td><td class="val" style="font-size:6.5pt;">{{ \Carbon\Carbon::parse($startDate)->toAppDate() }} &mdash; {{ \Carbon\Carbon::parse($endDate)->toAppDate() }}</td></tr>
                </tbody>
            </table>
        </td>
        <td class="bright">
            <div class="slabel">Authorisation</div>
            <table class="sig-tbl">
                <tr>
                    <td><div class="sig-box">&nbsp;<span>Verified by (Operator)</span></div></td>
                    <td><div class="sig-box">&nbsp;<span>Authorized Signature</span></div></td>
                </tr>
            </table>
        </td>
    </tr></tbody>
</table>

<div class="foot">
    <p>System-generated document &bull; Fleet Management System &bull; {{ now()->toAppDateTime() }}</p>
</div>

</body>
</html>
