<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cctv;
use App\Models\Health;
use Illuminate\Support\Facades\Http;

class GetDataHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-data-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting GetDataHealth command...');
        $this->info('Querying active CCTVs...');
        $cctvs = Cctv::where('status', 1)->get();
        $this->info('Found ' . $cctvs->count() . ' active CCTVs.');
        foreach ($cctvs as $cctv) {
            $this->info("Processing CCTV: {$cctv->ip} (ID: {$cctv->id})");
            // --- HAS_CAMERA LOGIC ---
            $this->info("Querying camera channels for {$cctv->ip} ...");
            $channelsUrl = "http://{$cctv->ip}/ISAPI/System/Video/inputs/channels";
            $camerasActive = 0;
            try {
                $channelsResponse = Http::withDigestAuth($cctv->username, $cctv->password)
                    ->timeout(30)
                    ->get($channelsUrl);
                if ($channelsResponse->ok()) {
                    $this->info('Parsing camera channels XML...');
                    $channelsXml = simplexml_load_string($channelsResponse->body());
                    $camerasEnabled = 0;
                    if ($channelsXml && isset($channelsXml->VideoInputChannel)) {
                        foreach ($channelsXml->VideoInputChannel as $channel) {
                            $enabled = ((string)$channel->videoInputEnabled === 'true');
                            $resDesc = (string)($channel->resDesc ?? '');
                            if ($enabled && stripos($resDesc, 'NO VIDEO') === false) {
                                $camerasActive++;
                            }
                            if ($enabled) {
                                $camerasEnabled++;
                            }
                        }
                    }
                    $this->info("Detected {$camerasEnabled} camera(s) for CCTV {$cctv->ip}.");
                    $cctv->has_camera = $camerasEnabled;
                    $cctv->save();
                } else {
                    $this->error("Failed to get camera channel data from {$cctv->ip}");
                }
            } catch (\Exception $e) {
                $this->error("Error getting camera channels for {$cctv->ip}: " . $e->getMessage());
            }
            // --- END HAS_CAMERA LOGIC ---

            $url = "http://{$cctv->ip}/ISAPI/ContentMgmt/Storage/hdd/1/SMARTTest/status";
            try {
                $this->info("Sending HTTP request to {$url} ...");
                $response = Http::withDigestAuth($cctv->username, $cctv->password)
                    ->timeout(30)
                    ->get($url);
                if ($response->ok()) {
                    $this->info('Received response. Parsing XML...');
                    $xml = simplexml_load_string($response->body());
                    if ($xml) {
                        $this->info('Inserting Health record...');
                        Health::create([
                            'cctv_id' => $cctv->id,
                            'temprature' => (string)($xml->temprature ?? null),
                            'powerOnDay' => (string)($xml->powerOnDay ?? null),
                            'allEvaluaingStatus' => (string)($xml->allEvaluaingStatus ?? null),
                            'active_cameras' => $camerasActive
                        ]);
                        $testResultList = [];
                        if (isset($xml->TestResultList->TestResult)) {
                            $this->info('Parsing TestResultList...');
                            foreach ($xml->TestResultList->TestResult as $result) {
                                $testResultList[] = [
                                    'attributeID' => (string)($result->attributeID ?? null),
                                    'status' => (string)($result->status ?? null),
                                    'flags' => (string)($result->flags ?? null),
                                    'thresholds' => (string)($result->thresholds ?? null),
                                    'value' => (string)($result->value ?? null),
                                    'worst' => (string)($result->worst ?? null),
                                    'rawValue' => (string)($result->rawValue ?? null),
                                ];
                            }
                        }
                        $this->info('Updating test_result_list field for CCTV...');
                        $cctv->test_result_list = $testResultList;
                        $cctv->save();
                        $this->info('Done processing this CCTV.');
                    } else {
                        $this->error('Failed to parse XML response.');
                    }
                } else {
                    $this->error("Failed to get health data from {$cctv->ip}");
                }
            } catch (\Exception $e) {
                $this->error("Error for {$cctv->ip}: " . $e->getMessage());
            }
        }
        $this->info('GetDataHealth command completed.');
    }
}
