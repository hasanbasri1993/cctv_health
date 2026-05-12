<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Str;

class FrigateConfigExportService
{
    private const DEFAULT_RTSP_PORT = 554;

    private const DEFAULT_DETECT_WIDTH = 1920;

    private const DEFAULT_DETECT_HEIGHT = 1080;

    private const DEFAULT_DETECT_FPS = 5;

    /**
     * Generate a complete Frigate YAML config from all registered devices and their channels.
     */
    public function generate(): string
    {
        $devices = Device::with('channels')->get();

        $go2rtcStreams = [];
        $cameras = [];

        foreach ($devices as $device) {
            $device->makeVisible('password');
            $deviceSlug = $this->slugify($device->name);
            $credentials = "{$device->username}:{$device->password}";
            $rtspBase = "rtsp://{$credentials}@{$device->ip_address}:" . self::DEFAULT_RTSP_PORT;

            foreach ($device->channels as $channel) {
                if ($channel->status === 'disabled') {
                    continue;
                }

                $channelSlug = $this->slugify($channel->name);
                $cameraName = "{$deviceSlug}_{$channelSlug}";

                // Ensure unique camera names by appending channel number if duplicated
                if (isset($go2rtcStreams[$cameraName])) {
                    $cameraName = "{$deviceSlug}_{$channelSlug}_ch{$channel->channel_number}";
                }

                $mainStreamId = $channel->channel_number * 100 + 1;
                $subStreamId = $channel->channel_number * 100 + 2;

                $mainStreamUrl = "{$rtspBase}/Streaming/Channels/{$mainStreamId}";
                $subStreamUrl = "{$rtspBase}/Streaming/Channels/{$subStreamId}";

                // go2rtc streams (main + sub)
                $go2rtcStreams[$cameraName] = [
                    "- {$mainStreamUrl}",
                    "- ffmpeg:{$cameraName}#audio=aac",
                ];
                $go2rtcStreams["{$cameraName}_sub"] = [
                    "- {$subStreamUrl}",
                ];

                // Camera config with main for record + sub for detect
                $cameras[$cameraName] = [
                    'main_path' => "rtsp://127.0.0.1:8554/{$cameraName}",
                    'sub_path' => "rtsp://127.0.0.1:8554/{$cameraName}_sub",
                ];
            }
        }

        return $this->renderYaml($go2rtcStreams, $cameras);
    }

    /**
     * Build the YAML string manually to match Frigate's expected format exactly.
     */
    private function renderYaml(array $go2rtcStreams, array $cameras): string
    {
        $lines = [];

        // mqtt
        $lines[] = 'mqtt:';
        $lines[] = '  enabled: false';
        $lines[] = '';

        // ffmpeg global
        $lines[] = 'ffmpeg:';
        $lines[] = '  hwaccel_args: []';
        $lines[] = '  output_args:';
        $lines[] = '    record: preset-record-generic-audio-aac';
        $lines[] = '';

        // go2rtc
        $lines[] = 'go2rtc:';
        $lines[] = '  streams:';
        foreach ($go2rtcStreams as $name => $streamLines) {
            $lines[] = "    {$name}:";
            foreach ($streamLines as $streamLine) {
                $lines[] = "      {$streamLine}";
            }
        }
        $lines[] = '';

        // cameras
        $lines[] = 'cameras:';
        foreach ($cameras as $name => $config) {
            $lines[] = "  {$name}:";
            $lines[] = '    ffmpeg:';
            $lines[] = '      inputs:';
            $lines[] = "        - path: {$config['main_path']}";
            $lines[] = '          input_args: preset-rtsp-restream';
            $lines[] = '          roles:';
            $lines[] = '            - record';
            $lines[] = '            - audio';
            $lines[] = "        - path: {$config['sub_path']}";
            $lines[] = '          input_args: preset-rtsp-restream';
            $lines[] = '          roles:';
            $lines[] = '            - detect';
            $lines[] = '    detect:';
            $lines[] = '      width: ' . self::DEFAULT_DETECT_WIDTH;
            $lines[] = '      height: ' . self::DEFAULT_DETECT_HEIGHT;
            $lines[] = '      fps: ' . self::DEFAULT_DETECT_FPS;
            $lines[] = '    audio:';
            $lines[] = '      enabled: true';
            $lines[] = '';
        }

        // record
        $lines[] = 'record:';
        $lines[] = '  enabled: true';
        $lines[] = '';

        // version & extras
        $lines[] = 'version: 0.17-0';
        $lines[] = 'semantic_search:';
        $lines[] = '  enabled: false';
        $lines[] = '  model_size: small';
        $lines[] = 'face_recognition:';
        $lines[] = '  enabled: true';
        $lines[] = '  model_size: small';
        $lines[] = 'lpr:';
        $lines[] = '  enabled: false';
        $lines[] = 'classification:';
        $lines[] = '  bird:';
        $lines[] = '    enabled: false';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Convert a name into a YAML-safe slug (lowercase, underscores, no special chars).
     */
    private function slugify(string $name): string
    {
        $slug = Str::ascii($name);
        $slug = preg_replace('/[^a-zA-Z0-9]+/', '_', $slug);
        $slug = trim($slug, '_');
        $slug = strtolower($slug);

        return $slug ?: 'unnamed';
    }
}
