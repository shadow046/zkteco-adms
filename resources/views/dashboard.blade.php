@extends('zkteco-adms::layout')

@section('content')
    <div class="header">
        <div>
            <div class="eyebrow">Package UI</div>
            <h1>ADMS Dashboard</h1>
            <div class="meta">Built-in package frontend ito para may testing surface agad ang host app. Dito mo makikita ang recent attendance, device state, at queued commands.</div>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-label">Attendance Rows</div>
            <div class="card-value">{{ number_format($summary['attendance_count'] ?? 0) }}</div>
        </div>
        <div class="card">
            <div class="card-label">Queued Commands</div>
            <div class="card-value">{{ number_format($summary['command_count'] ?? 0) }}</div>
        </div>
        <div class="card">
            <div class="card-label">Tracked Devices</div>
            <div class="card-value">{{ number_format($summary['device_count'] ?? 0) }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="panel">
            <h2>ATTLOG Query</h2>
            <div class="meta" style="margin-top:0; margin-bottom:14px;">Mag-queue ng ADMS ATTLOG command para sa isang device.</div>
            <form class="stack" method="POST" action="{{ route('zkteco-adms.ui.attlog-query') }}">
                @csrf
                <label>
                    Serial Number
                    <input type="text" name="sn" placeholder="3647184760209" value="{{ old('sn') }}">
                </label>
                <label>
                    Start Date/Time
                    <input type="datetime-local" name="start" value="{{ old('start', now()->startOfDay()->format('Y-m-d\TH:i')) }}">
                </label>
                <label>
                    End Date/Time
                    <input type="datetime-local" name="end" value="{{ old('end', now()->endOfDay()->format('Y-m-d\TH:i')) }}">
                </label>
                <button type="submit">Queue ATTLOG Query</button>
            </form>
        </div>

        <div class="stack">
            <div class="table-panel">
                <div class="table-head">
                    <div>
                        <strong>Recent Commands</strong>
                        <div class="count">Latest queued ADMS commands</div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>SN</th>
                                <th>Command</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentCommands as $command)
                                <tr>
                                    <td>{{ $command->id }}</td>
                                    <td>{{ $command->serial_number }}</td>
                                    <td>{{ $command->command_text }}</td>
                                    <td><span class="badge">{{ $command->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4">No commands yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="table-panel">
                <div class="table-head">
                    <div>
                        <strong>Tracked Devices</strong>
                        <div class="count">Latest known device state rows</div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>SN</th>
                                <th>PushVer</th>
                                <th>Language</th>
                                <th>Last Txn</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($deviceStates as $state)
                                <tr>
                                    <td>{{ $state->serial_number }}</td>
                                    <td>{{ $state->pushver ?: '-' }}</td>
                                    <td>{{ $state->language ?: '-' }}</td>
                                    <td>{{ $state->lasttxndatetime ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4">No device state rows yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="table-panel" style="margin-top:18px;">
        <div class="table-head">
            <div>
                <strong>Recent Attendance</strong>
                <div class="count">Latest rows from the configured attendance table</div>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Empno</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Punch</th>
                        <th>Serial</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentAttendance as $row)
                        <tr>
                            <td>{{ $row->empno ?? '-' }}</td>
                            <td>{{ $row->txndate ?? '-' }}</td>
                            <td>{{ $row->txntime ?? '-' }}</td>
                            <td>{{ $row->punch ?? '-' }}</td>
                            <td>{{ $row->serialno ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No attendance rows yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
