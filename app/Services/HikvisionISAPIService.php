<?php

namespace App\Services;

use App\Contracts\HikvisionISAPIServiceInterface;
use App\DTOs\ChannelStatusResponse;
use App\DTOs\ConnectionTestResult;
use App\DTOs\DeviceHealthResponse;
use App\DTOs\StorageStatusResponse;
use App\Models\Device;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HikvisionISAPIService implements HikvisionISAPIServiceInterface
{
    private const CONNECT_TIMEOUT = 5;
    private const REQUEST_TIMEOUT = 10;
    private const RETRY_TIMES = 3;
    private const RETRY_SLEEP_MS = 500;

    public function getChannelStatus(Device $device): ChannelStatusResponse
    {
        try {
            $response = $this->makeRequest($device, '/ISAPI/System/Video/inputs/channels');

            if (! $response->successful()) {
                return ChannelStatusResponse::failure("HTTP {$response->status()}");
            }

            $channels = $this->parseChannelStatus($response->body());

            return ChannelStatusResponse::success($channels);
        } catch (ConnectionException $e) {
            Log::warning('ISAPI channel status connection failed', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return ChannelStatusResponse::failure('Connection timeout: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('ISAPI channel status error', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return ChannelStatusResponse::failure($e->getMessage());
        }
    }

    public function getStorageStatus(Device $device): StorageStatusResponse
    {
        try {
            $response = $this->makeRequest($device, '/ISAPI/ContentMgmt/Storage');

            if (! $response->successful()) {
                return StorageStatusResponse::failure("HTTP {$response->status()}");
            }

            $storages = $this->parseStorageStatus($response->body());

            foreach ($storages as &$storage) {
                $smart = $this->fetchSMARTData($device, $storage['storage_id']);
                $storage['health_status'] = $smart['health_status'];
                $storage['temperature'] = $smart['temperature'];
            }

            return StorageStatusResponse::success($storages);
        } catch (ConnectionException $e) {
            Log::warning('ISAPI storage status connection failed', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return StorageStatusResponse::failure('Connection timeout: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('ISAPI storage status error', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return StorageStatusResponse::failure($e->getMessage());
        }
    }

    public function getDeviceHealth(Device $device): DeviceHealthResponse
    {
        try {
            $start = microtime(true);
            $response = $this->makeRequest($device, '/ISAPI/System/deviceInfo');
            $responseTimeMs = (int) ((microtime(true) - $start) * 1000);

            if (! $response->successful()) {
                return DeviceHealthResponse::failure("HTTP {$response->status()}");
            }

            $info = $this->parseDeviceInfo($response->body());

            return DeviceHealthResponse::success(
                'online',
                $responseTimeMs,
                $info['firmwareVersion'] ?? null,
                $info['model'] ?? null,
            );
        } catch (ConnectionException $e) {
            Log::warning('ISAPI device health connection failed', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return DeviceHealthResponse::failure('Connection timeout: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('ISAPI device health error', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return DeviceHealthResponse::failure($e->getMessage());
        }
    }

    public function testConnection(Device $device): ConnectionTestResult
    {
        $url = "http://{$device->ip_address}:{$device->port}/ISAPI/System/deviceInfo";

        Log::info('ISAPI testConnection start', [
            'device_id' => $device->id,
            'device_name' => $device->name,
            'url' => $url,
            'username' => $device->username,
        ]);

        try {
            $start = microtime(true);
            $response = $this->makeRequest($device, '/ISAPI/System/deviceInfo');
            $responseTimeMs = (int) ((microtime(true) - $start) * 1000);

            Log::info('ISAPI testConnection response', [
                'device_id' => $device->id,
                'url' => $url,
                'http_status' => $response->status(),
                'response_time_ms' => $responseTimeMs,
                'body_preview' => substr($response->body(), 0, 300),
            ]);

            if ($response->status() === 401) {
                Log::warning('ISAPI testConnection auth failed', [
                    'device_id' => $device->id,
                    'url' => $url,
                ]);
                return ConnectionTestResult::failure('Authentication failed. Check username and password.');
            }

            if (! $response->successful()) {
                Log::warning('ISAPI testConnection non-2xx response', [
                    'device_id' => $device->id,
                    'url' => $url,
                    'http_status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ConnectionTestResult::failure("HTTP {$response->status()}: " . substr($response->body(), 0, 200));
            }

            $info = $this->parseDeviceInfo($response->body());
            $deviceInfo = isset($info['model']) ? "{$info['model']} (FW: {$info['firmwareVersion']})" : null;

            Log::info('ISAPI testConnection success', [
                'device_id' => $device->id,
                'response_time_ms' => $responseTimeMs,
                'device_info' => $deviceInfo,
            ]);

            return ConnectionTestResult::success($responseTimeMs, $deviceInfo);
        } catch (ConnectionException $e) {
            Log::error('ISAPI testConnection connection error', [
                'device_id' => $device->id,
                'url' => $url,
                'error_class' => get_class($e),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ConnectionTestResult::failure('Cannot connect to device: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('ISAPI testConnection unexpected error', [
                'device_id' => $device->id,
                'url' => $url,
                'error_class' => get_class($e),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ConnectionTestResult::failure($e->getMessage());
        }
    }

    private function makeRequest(Device $device, string $path): Response
    {
        $baseUrl = "http://{$device->ip_address}:{$device->port}";

        Log::debug('ISAPI makeRequest', [
            'device_id' => $device->id,
            'url' => "{$baseUrl}{$path}",
            'connect_timeout' => self::CONNECT_TIMEOUT,
            'request_timeout' => self::REQUEST_TIMEOUT,
        ]);

        return Http::withDigestAuth($device->username, $device->password)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->timeout(self::REQUEST_TIMEOUT)
            ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, function (\Exception $e) {
                Log::warning('ISAPI request retry', [
                    'error' => $e->getMessage(),
                ]);
                return $e instanceof ConnectionException;
            }, throw: false)
            ->accept('application/xml')
            ->get("{$baseUrl}{$path}");
    }

    private function parseChannelStatus(string $xml): array
    {
        $channels = [];
        $ns = 'http://www.hikvision.com/ver20/XMLSchema';

        try {
            $doc = new \SimpleXMLElement($xml);

            foreach ($doc->children($ns)->VideoInputChannel as $channel) {
                $ch = $channel->children($ns);
                $enabled = strtolower((string) ($ch->videoInputEnabled ?? 'false')) === 'true';
                $resDesc = strtoupper((string) ($ch->resDesc ?? ''));
                $noVideo = str_contains($resDesc, 'NO VIDEO');

                $channels[] = [
                    'channel_number' => (int) $ch->id,
                    'name' => (string) ($ch->name ?? ''),
                    'status' => ($enabled && ! $noVideo) ? 'ok' : 'no_video',
                    'signal_quality' => null,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to parse channel status XML', ['error' => $e->getMessage()]);
        }

        return $channels;
    }

    private function parseStorageStatus(string $xml): array
    {
        $storages = [];

        try {
            $doc = new \SimpleXMLElement($xml);

            foreach ($doc->hddList->hdd ?? [] as $hdd) {
                $capacity = (int) ($hdd->capacity ?? 0);
                $freeSpace = (int) ($hdd->freeSpace ?? 0);

                $storages[] = [
                    'storage_id' => (int) $hdd->id,
                    'name' => (string) ($hdd->hddName ?? "HDD {$hdd->id}"),
                    'type' => 'HDD',
                    'capacity' => $capacity * 1024 * 1024,
                    'used_space' => ($capacity - $freeSpace) * 1024 * 1024,
                    'health_status' => $this->mapStorageHealth((string) ($hdd->status ?? 'unknown')),
                    'temperature' => null,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to parse storage status XML', ['error' => $e->getMessage()]);
        }

        return $storages;
    }

    private function parseDeviceInfo(string $xml): array
    {
        $info = [];

        try {
            $doc = new \SimpleXMLElement($xml);
            $info['model'] = (string) ($doc->model ?? '');
            $info['firmwareVersion'] = (string) ($doc->firmwareVersion ?? '');
            $info['serialNumber'] = (string) ($doc->serialNumber ?? '');
        } catch (\Exception $e) {
            Log::warning('Failed to parse device info XML', ['error' => $e->getMessage()]);
        }

        return $info;
    }

    private function mapChannelStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'CAPTURING' => 'ok',
            'NOTCAPTURING' => 'no_video',
            'IDLE' => 'idle',
            default => 'unknown',
        };
    }

    private function fetchSMARTData(Device $device, int $hddId): array
    {
        try {
            $response = $this->makeRequest($device, "/ISAPI/ContentMgmt/Storage/hdd/{$hddId}/SMARTTest/status");

            if (! $response->successful()) {
                return ['health_status' => 'unknown', 'temperature' => null];
            }

            return $this->parseSMARTStatus($response->body());
        } catch (\Exception $e) {
            Log::warning('ISAPI SMART status fetch failed', [
                'device_id' => $device->id,
                'hdd_id' => $hddId,
                'error' => $e->getMessage(),
            ]);

            return ['health_status' => 'unknown', 'temperature' => null];
        }
    }

    private function parseSMARTStatus(string $xml): array
    {
        try {
            $doc = new \SimpleXMLElement($xml);
            $ns = 'http://www.hikvision.com/ver20/XMLSchema';

            $children = $doc->children($ns);
            $diskStatus = (string) ($children->diskStatus ?? $doc->diskStatus ?? '');
            $tempRaw = (string) ($children->temperature ?? $doc->temperature ?? '');

            return [
                'health_status' => $this->mapStorageHealth($diskStatus ?: 'unknown'),
                'temperature' => $tempRaw !== '' ? (int) $tempRaw : null,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to parse SMART status XML', ['error' => $e->getMessage()]);

            return ['health_status' => 'unknown', 'temperature' => null];
        }
    }

    private function mapStorageHealth(string $status): string
    {
        return match (strtoupper($status)) {
            'OK', 'NORMAL' => 'healthy',
            'UNFORMATTED' => 'unformatted',
            'FORMATTING' => 'formatting',
            'NOTEXIST', 'NONE' => 'empty',
            'FULL' => 'full',
            'ERROR', 'FAULT' => 'fault',
            default => 'unknown',
        };
    }
}
