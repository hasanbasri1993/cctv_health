<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestEmailRequest;
use App\Http\Requests\TestTelegramRequest;
use App\Http\Requests\UpdateConfigurationRequest;
use App\Models\SystemConfiguration;
use App\Services\ConfigurationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationController extends Controller
{
    public function __construct(private ConfigurationService $configService)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Configuration/Index', [
            'config' => $this->configService->getConfigData(),
        ]);
    }

    public function update(UpdateConfigurationRequest $request)
    {
        $this->configService->updateConfiguration($request->validated());

        return back()->with('success', 'Configuration saved.');
    }

    public function testTelegram(TestTelegramRequest $request)
    {
        $result = $this->configService->testTelegram($request->validated());

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    public function testEmail(TestEmailRequest $request)
    {
        $result = $this->configService->testEmail($request->validated());

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}
