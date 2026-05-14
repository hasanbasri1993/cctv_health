<?php

namespace App\Http\Controllers;

use App\Contracts\HikvisionISAPIServiceInterface;
use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Models\Device;
use App\Services\DeviceService;
use App\Services\FrigateConfigExportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeviceController extends Controller
{
    public function __construct(private DeviceService $deviceService)
    {
    }

    public function index(): Response
    {
        $devices = $this->deviceService->getDevicesWithStats();

        return Inertia::render('Devices/Index', [
            'devices' => $devices,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Devices/Create');
    }

    public function store(StoreDeviceRequest $request)
    {
        $device = $this->deviceService->createDevice($request->validated());

        return redirect()->route('devices.show', $device)->with('success', 'Device added successfully.');
    }

    public function show(Device $device): Response
    {
        $perPage = in_array((int) request('per_page'), [5, 10, 15, 20]) ? (int) request('per_page') : 20;

        $details = $this->deviceService->getDeviceDetails($device, $perPage);
        $details['device'] = $details['device']->makeVisible(['username', 'password']);

        return Inertia::render('Devices/Show', $details);
    }

    public function edit(Device $device): Response
    {
        return Inertia::render('Devices/Edit', [
            'device' => $device->makeVisible('password'),
        ]);
    }

    public function update(UpdateDeviceRequest $request, Device $device)
    {
        $this->deviceService->updateDevice($device, $request->validated());

        return redirect()->route('devices.show', $device)->with('success', 'Device updated successfully.');
    }

    public function destroy(Device $device)
    {
        $this->deviceService->deleteDevice($device);

        return redirect()->route('devices.index')->with('success', 'Device deleted.');
    }

    public function testConnection(Device $device, HikvisionISAPIServiceInterface $isapi)
    {
        $result = $isapi->testConnection($device);

        return response()->json([
            'success' => $result->success,
            'response_time_ms' => $result->responseTimeMs,
            'device_info' => $result->deviceInfo,
            'error' => $result->error,
        ]);
    }

    public function healthHistory(Device $device)
    {
        $logs = $this->deviceService->getHealthHistory($device);

        return response()->json($logs);
    }

    public function frigateConfig(FrigateConfigExportService $exporter): Response
    {
        $yaml = $exporter->generate();

        return Inertia::render('Devices/FrigateConfig', [
            'yaml' => $yaml,
        ]);
    }

    public function downloadFrigateConfig(FrigateConfigExportService $exporter)
    {
        $yaml = $exporter->generate();

        return response($yaml)
            ->header('Content-Type', 'text/yaml')
            ->header('Content-Disposition', 'attachment; filename="frigate-config.yml"');
    }
}
