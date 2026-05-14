<?php

namespace App\Http\Controllers;

use App\Services\OpenApiExportService;
use Inertia\Inertia;
use Inertia\Response;

class OpenApiController extends Controller
{
    public function preview(OpenApiExportService $exporter): Response
    {
        return Inertia::render('OpenApi/Index', [
            'yaml' => $exporter->generate(),
        ]);
    }

    public function download(OpenApiExportService $exporter)
    {
        return response($exporter->generate())
            ->header('Content-Type', 'text/yaml')
            ->header('Content-Disposition', 'attachment; filename="openapi.yaml"');
    }
}
