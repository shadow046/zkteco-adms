@extends('zkteco-adms::layout')

@section('content')
    <div class="header">
        <div>
            <div class="eyebrow">Package UI</div>
            <h1>Sequence Audit</h1>
            <div class="meta">Per-device at per-day audit ito ng `seqno` para mabilis makita ang gaps, duplicates, at suspicious starts. Useful ito kapag gusto mong ma-spot agad ang tampering o payload ordering issues.</div>
        </div>
    </div>

    <form class="panel" method="GET" action="{{ route('zkteco-adms.ui.sequence-audit') }}" style="margin-bottom:18px;">
        <div class="stack" style="grid-template-columns:1.2fr 1fr 1fr .8fr auto; display:grid;">
            <label>
                Serial Number
                <input type="text" name="serialno" value="{{ $filterSerialno }}" placeholder="3647184760209">
            </label>
            <label>
                Start Date
                <input type="date" name="start" value="{{ $filterStart }}">
            </label>
            <label>
                End Date
                <input type="date" name="end" value="{{ $filterEnd }}">
            </label>
            <label>
                Rows Per Page
                <select name="per_page">
                    @foreach ([50, 100, 250, 500] as $option)
                        <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
            <div style="display:flex; align-items:end;">
                <button type="submit">Run Audit</button>
            </div>
        </div>
    </form>

    <div class="table-panel">
        <div class="table-head">
            <div>
                <strong>Device/Date Sequence Groups</strong>
                <div class="count">Showing {{ $auditRows->count() }} of {{ $auditRows->total() }} grouped rows for the selected range.</div>
            </div>
            <div class="count">Page {{ $auditRows->currentPage() }} of {{ $auditRows->lastPage() }}</div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Serial</th>
                        <th>Date</th>
                        <th>Rows</th>
                        <th>Min Seq</th>
                        <th>Max Seq</th>
                        <th>Distinct Seq</th>
                        <th>Duplicates</th>
                        <th>Gaps</th>
                        <th>Flags</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditRows as $row)
                        <tr>
                            <td>{{ $row->serialno ?: '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->txndate)->format('M. d, Y') }}</td>
                            <td>{{ number_format((int) $row->row_count) }}</td>
                            <td>{{ $row->min_seqno }}</td>
                            <td>{{ $row->max_seqno }}</td>
                            <td>{{ number_format((int) $row->distinct_seqno_count) }} / {{ number_format((int) $row->expected_distinct_count) }}</td>
                            <td>{{ number_format((int) $row->duplicate_count) }}</td>
                            <td>{{ number_format((int) $row->gap_count) }}</td>
                            <td>
                                @if ($row->flag_labels !== [])
                                    @foreach ($row->flag_labels as $flag)
                                        <span class="badge" style="margin-right:6px; margin-bottom:6px; {{ $row->has_issue ? 'background: rgba(248,113,113,.12); color:#fecaca; border-color: rgba(248,113,113,.18);' : '' }}">{{ $flag }}</span>
                                    @endforeach
                                @else
                                    <span class="badge">Clean</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9">No sequence groups found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">
            {{ $auditRows->links() }}
        </div>
    </div>
@endsection
