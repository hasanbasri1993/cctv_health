<?php

namespace App\Contracts;

use App\DTOs\ChannelStatusResponse;
use App\DTOs\ConnectionTestResult;
use App\DTOs\DeviceHealthResponse;
use App\DTOs\StorageStatusResponse;
use App\Models\Device;

interface HikvisionISAPIServiceInterface
{
    public function getChannelStatus(Device $device): ChannelStatusResponse;

    public function getStorageStatus(Device $device): StorageStatusResponse;

    public function getDeviceHealth(Device $device): DeviceHealthResponse;

    public function testConnection(Device $device): ConnectionTestResult;
}
