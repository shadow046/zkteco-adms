@extends('zkteco-adms::layout')

@section('content')
    <div class="header">
        <div>
            <div class="eyebrow">Package UI</div>
            <h1>Daily Logs</h1>
            <div class="meta">Package-owned DTR view ito para makita agad ang pairing result at ma-trigger ang manual re-pairing kapag kailangan.</div>
        </div>
    </div>

    <form class="panel" method="GET" action="{{ route('zkteco-adms.ui.daily-logs') }}" style="margin-bottom:18px;">
        @csrf
        <div class="stack" style="grid-template-columns:1.2fr 1fr 1fr .8fr auto; display:grid;">
            <label>
                Employee No
                <input type="text" name="empno" value="{{ $filterEmpno }}" placeholder="143107">
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
            <div style="display:flex; align-items:end; gap:10px; flex-wrap:wrap;">
                <button type="submit">Search</button>
                <button type="submit" formaction="{{ route('zkteco-adms.ui.daily-logs.run-pairing') }}" formmethod="POST">Run Pairing</button>
            </div>
        </div>
    </form>

    <div class="table-panel">
        <div class="table-head">
            <div>
                <strong>Daily DTR Rows</strong>
                <div class="count">Showing {{ $dtrRows->count() }} of {{ $dtrRows->total() }} rows for the selected range.</div>
            </div>
            <div class="count">Page {{ $dtrRows->currentPage() }} of {{ $dtrRows->lastPage() }}</div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Empno</th>
                        <th>Shift</th>
                        <th>In</th>
                        <th>Break Out</th>
                        <th>Break In</th>
                        <th>Out</th>
                        <th>ND O</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dtrRows as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->txndate)->format('M. d, Y') }} <span class="badge">{{ $row->day_name }}</span></td>
                            <td>{{ $row->empno }}</td>
                            <td>{{ $row->shift ?: '-' }}</td>
                            <td>{{ $row->in ? \Carbon\Carbon::parse($row->in)->format('h:i a') : '-' }}</td>
                            <td>{{ $row->break_out ? \Carbon\Carbon::parse($row->break_out)->format('h:i a') : '-' }}</td>
                            <td>{{ $row->break_in ? \Carbon\Carbon::parse($row->break_in)->format('h:i a') : '-' }}</td>
                            <td>{{ $row->out ? \Carbon\Carbon::parse($row->out)->format('h:i a') : '-' }}</td>
                            <td>{{ $row->nextday_out ?? 'N' }}</td>
                            <td><a class="button" href="{{ $row->logs_url }}" style="min-height:38px;">Logs</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9">No DTR rows found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">
            {{ $dtrRows->links() }}
        </div>
    </div>
@endsection
