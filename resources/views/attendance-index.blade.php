@extends('zkteco-adms::layout')

@section('content')
    <div class="header">
        <div>
            <div class="eyebrow">Package UI</div>
            <h1>Attendance Explorer</h1>
            <div class="meta">Package-owned attendance page ito para sa mabilis na testing ng raw attendance feed sa host app.</div>
        </div>
    </div>

    <form class="panel" method="GET" action="{{ route('zkteco-adms.ui.attendance') }}" style="margin-bottom:18px;">
        <div class="stack" style="grid-template-columns:repeat(4,minmax(0,1fr)); display:grid;">
            <label>
                Employee No
                <input type="text" name="empno" value="{{ $filterEmpno }}" placeholder="143107">
            </label>
            <label>
                Start Date/Time
                <input type="datetime-local" name="start" value="{{ $filterStart }}">
            </label>
            <label>
                End Date/Time
                <input type="datetime-local" name="end" value="{{ $filterEnd }}">
            </label>
            <label>
                Rows Per Page
                <select name="per_page">
                    @foreach ([50, 100, 250, 500] as $option)
                        <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div style="margin-top:14px;">
            <button type="submit">Run Attendance Query</button>
        </div>
    </form>

    <div class="table-panel">
        <div class="table-head">
            <div>
                <strong>Attendance Rows</strong>
                <div class="count">Showing {{ $attendanceRows->count() }} of {{ $attendanceRows->total() }} rows for the selected range.</div>
            </div>
            <div class="count">Page {{ $attendanceRows->currentPage() }} of {{ $attendanceRows->lastPage() }}</div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Empno</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Punch</th>
                        <th>Status</th>
                        <th>Serial</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendanceRows as $row)
                        <tr>
                            <td>{{ $row->empno }}</td>
                            <td>{{ $row->txndate }}</td>
                            <td>{{ $row->txntime }}</td>
                            <td><span class="badge">{{ $row->punch ?? '-' }}</span></td>
                            <td>{{ $row->status ?? '-' }}</td>
                            <td>{{ $row->serialno ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No attendance rows found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">
            {{ $attendanceRows->links() }}
        </div>
    </div>
@endsection
