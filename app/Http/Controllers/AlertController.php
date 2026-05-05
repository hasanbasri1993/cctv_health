<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Device;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlertController extends Controller
{
    public function __construct(private AlertService $alertService)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'severity', 'device_id']);
        $alerts = $this->alertService->getAlerts($filters);
        
        $devices = Device::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Alerts/Index', [
            'alerts' => $alerts,
            'devices' => $devices,
            'filters' => $request->only(['status', 'severity', 'device_id']),
        ]);
    }

    public function acknowledge(Request $request, Alert $alert)
    {
        $success = $this->alertService->acknowledgeAlert($alert, $request->user());

        if (!$success) {
            return back()->with('error', 'Alert is not active or already acknowledged.');
        }

        return back()->with('success', 'Alert acknowledged.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['status', 'severity']);

        return $this->alertService->exportAlerts($filters);
    }
}
