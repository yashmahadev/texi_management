<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Monthly Duty Report</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

@page { size: A4 portrait; margin: 4mm; }

html, body { width: 95%; margin: auto; }

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 6pt;
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

/* META */
.meta-wrap { margin-bottom: 4pt; }
.meta-tbl  { width: 100%; border-collapse: collapse; }
.meta-tbl td { padding: 3pt 5pt; font-size: 7.5pt; border: 0.5pt solid #000; width: 25%; vertical-align: top; }
.mk { font-size: 5.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt; display: block; margin-bottom: 1pt; color: #444; }
.mv { font-size: 8pt; font-weight: bold; display: block; }

/* DATA TABLE */
.tbl-wrap { margin-bottom: 4pt; }
.dtbl { width: 100%; border-collapse: collapse; table-layout: fixed; }
.dtbl thead tr { background: #000; color: #fff; }
.dtbl th { padding: 3pt 2pt; text-align: center; font-size: 7pt; font-weight: bold; border: 0.5pt solid #000; white-space: nowrap; }
.dtbl tbody td { padding: 2.5pt 2pt; border: 0.5pt solid #aaa; text-align: center; font-size: 7.5pt; vertical-align: middle; }
.dtbl tbody tr:nth-child(even) td { background: #f2f2f2; }
.dtbl tbody tr:nth-child(odd)  td { background: #fff; }
.dtbl tfoot td { padding: 3pt 2pt; border: 0.75pt solid #000; font-size: 7.5pt; font-weight: bold; background: #e0e0e0; }
.status-completed { font-weight: bold; }
.status-pending   { font-style: italic; }
.status-missing   { font-weight: bold; text-decoration: underline; }

/* BOTTOM */
.bot { width: 100%; border-collapse: collapse; margin-top: 4pt; }
.bot > tbody > tr > td { border: none; vertical-align: top; padding: 0; }
.bleft  { width: 52%; padding-right: 6pt; }
.bright { width: 48%; }

.sum-tbl { width: 100%; border-collapse: collapse; }
.sum-tbl th { background: #000; color: #fff; padding: 2.5pt 5pt; font-size: 6.5pt; text-transform: uppercase; letter-spacing: 0.2pt; text-align: left; border: 0.5pt solid #000; }
.sum-tbl td { padding: 2.5pt 5pt; border: 0.5pt solid #000; font-size: 7.5pt; }
.sum-tbl td.lbl { background: #f2f2f2; font-weight: bold; font-size: 7pt; text-align: left; width: 65%; }
.sum-tbl td.val { text-align: right; font-weight: bold; background: #fff; }

.sig-tbl { width: 100%; border-collapse: collapse; }
.sig-tbl td { border: none; padding: 0 3pt; vertical-align: top; width: 50%; }
.sig-box { border: 0.5pt solid #000; text-align: center; }
.sig-box-top { background: #000; color: #fff; font-size: 6.5pt; font-weight: bold; padding: 2.5pt 4pt; }
.sig-box-mid { min-height: 38pt; padding: 4pt; font-size: 6pt; }
.sig-box-mid .sig-for { font-style: italic; margin-bottom: 2pt; }
.sig-box-mid img { max-height: 22pt; max-width: 65pt; display: block; margin: 0 auto 2pt; }
.sig-box-mid .co-name { font-size: 6pt; font-weight: bold; }
.sig-box-bottom { border-top: 0.5pt solid #000; font-size: 6.5pt; font-weight: bold; padding: 3pt 4pt 4pt; line-height: 1.3; }

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
        <h2>Monthly Duty Report</h2>
        <p>Period: {{ $monthlyDuty->start_date->toAppDate() }} &mdash; {{ $monthlyDuty->end_date->toAppDate() }}</p>
    </div>
</div>

<div class="meta-wrap">
    <div class="slabel">Duty Details</div>
    <table class="meta-tbl">
        <tr>
            <td><span class="mk">Department</span><span class="mv">{{ $monthlyDuty->department_name }}</span></td>
            <td><span class="mk">Officer</span><span class="mv">{{ $monthlyDuty->officer_name }}</span></td>
            <td><span class="mk">Vehicle No.</span><span class="mv">{{ $monthlyDuty->vehicle->vehicle_number }} ({{ ucfirst($monthlyDuty->vehicle->vehicle_type) }})</span></td>
            <td><span class="mk">Primary Driver</span><span class="mv">{{ $monthlyDuty->primaryDriver->name }}</span></td>
        </tr>
    </table>
</div>

<div class="tbl-wrap">
    <div class="slabel">Daily Duty Log</div>
    <table class="dtbl">
        <thead>
            <tr>
                <th width="15%">Date</th>
                <th width="11%">Start Time</th>
                <th width="11%">End Time</th>
                <th width="13%">Start KM</th>
                <th width="13%">End KM</th>
                <th width="11%">Total KM</th>
                <th width="8%">Trips</th>
                <th width="18%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($aggregatedRows as $row)
            <tr>
                <td>{{ $row->duty_date->toAppDate() }}</td>
                <td>{{ $row->start_time ?? '—' }}</td>
                <td>{{ $row->end_time ?? '—' }}</td>
                <td>{{ $row->start_km ?? '—' }}</td>
                <td>{{ $row->end_km ?? '—' }}</td>
                <td><strong>{{ $row->total_km ?? '0' }}</strong></td>
                <td>{{ $row->trip_count }}</td>
                <td>
                    @php $s = strtolower($row->status ?? ''); @endphp
                    <span class="status-{{ in_array($s,['completed','pending','missing']) ? $s : '' }}">{{ ucfirst($row->status ?? 'N/A') }}</span>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="tc" style="padding:10pt;font-style:italic;">No duty logs found.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="tr">Total KM for the Period:</td>
                <td colspan="3" class="tc"><strong>{{ number_format($totalKm, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<table class="bot">
    <tbody><tr>
        <td class="bleft">
            <div class="slabel">Summary</div>
            <table class="sum-tbl">
                <thead><tr><th colspan="2">Duty Statistics</th></tr></thead>
                <tbody>
                    <tr><td class="lbl">Total KM Run</td><td class="val">{{ number_format($totalKm, 2) }} km</td></tr>
                    <tr><td class="lbl">Total Duty Days</td><td class="val">{{ $totalDuties }}</td></tr>
                    <tr><td class="lbl">Completed Days</td><td class="val">{{ $completedDuties }}</td></tr>
                    <tr><td class="lbl">Pending / Other</td><td class="val">{{ $totalDuties - $completedDuties }}</td></tr>
                </tbody>
            </table>
        </td>
        <td class="bright">
            <div class="slabel">Authorisation</div>
            <table class="sig-tbl">
                <tr>
                    <td>
                        <div class="sig-box">
                            <div class="sig-box-top">Verified from Logbook</div>
                            <div class="sig-box-mid">
                                <span class="co-name">{{ $company['name'] }}</span>
                            </div>
                            <div class="sig-box-bottom">Authorised Signature</div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-box">
                            <div class="sig-box-top">Signature of User</div>
                            <div class="sig-box-mid">&nbsp;</div>
                            <div class="sig-box-bottom">Authorised Signature</div>
                        </div>
                    </td>
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
