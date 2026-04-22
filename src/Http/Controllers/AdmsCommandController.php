<?php

namespace Shadow046\ZktecoAdms\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Shadow046\ZktecoAdms\Services\AdmsCommandService;

class AdmsCommandController extends Controller
{
    public function __construct(private readonly AdmsCommandService $commands)
    {
    }

    public function queueAttlogQuery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sn' => ['required', 'string', 'max:50'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        $command = $this->commands->queueAttlogQuery(
            $validated['sn'],
            Carbon::parse($validated['start']),
            Carbon::parse($validated['end'])
        );

        return response()->json([
            'ok' => true,
            'message' => "Queued device command #{$command['id']}",
            'command' => $command,
        ]);
    }

    public function queueFingertmpQuery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sn' => ['required', 'string', 'max:50'],
            'pin' => ['required', 'string', 'max:50'],
            'fid' => ['required', 'integer', 'min:0', 'max:9'],
        ]);

        $command = $this->commands->queueFingertmpQuery(
            $validated['sn'],
            $validated['pin'],
            (string) $validated['fid']
        );

        return response()->json([
            'ok' => true,
            'message' => "Queued device command #{$command['id']}",
            'command' => $command,
        ]);
    }

    public function queueUserUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sn' => ['required', 'string', 'max:50'],
            'pin' => ['required', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:100'],
            'pri' => ['nullable', 'integer', 'min:0', 'max:14'],
            'passwd' => ['nullable', 'string', 'max:50'],
            'card' => ['nullable', 'string', 'max:50'],
            'grp' => ['nullable', 'string', 'max:50'],
            'tz' => ['nullable', 'string', 'max:100'],
        ]);

        $command = $this->commands->queueUserUpdate(
            $validated['sn'],
            $validated['pin'],
            [
                'name' => $validated['name'] ?? $validated['pin'],
                'pri' => (string) ($validated['pri'] ?? 0),
                'passwd' => $validated['passwd'] ?? '',
                'card' => $validated['card'] ?? '',
                'grp' => $validated['grp'] ?? '',
                'tz' => $validated['tz'] ?? '',
            ]
        );

        return response()->json([
            'ok' => true,
            'message' => "Queued device command #{$command['id']}",
            'command' => $command,
        ]);
    }

    public function queueFingertmpUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sn' => ['required', 'string', 'max:50'],
            'pin' => ['required', 'string', 'max:50'],
            'fid' => ['required', 'integer', 'min:0', 'max:9'],
            'template' => ['required', 'string', 'min:10'],
            'valid' => ['nullable', 'integer', 'min:0', 'max:1'],
        ]);

        $command = $this->commands->queueFingertmpUpdate(
            $validated['sn'],
            $validated['pin'],
            (string) $validated['fid'],
            trim($validated['template']),
            (string) ($validated['valid'] ?? 1)
        );

        return response()->json([
            'ok' => true,
            'message' => "Queued device command #{$command['id']}",
            'command' => $command,
        ]);
    }
}
