<?php

namespace App\Services;

use App\Models\Device;

class OpenApiExportService
{
    public function generate(): string
    {
        $version = $this->appVersion();
        $servers = $this->buildServers();

        return <<<YAML
openapi: 3.0.3
info:
  title: CCTV Monitor
  description: CCTV device monitoring and management API
  version: {$version}

servers:
{$servers}
security: []

tags:
  - name: Dashboard
    description: Overview statistics
  - name: Devices
    description: Device CRUD and monitoring
  - name: Alerts
    description: Alert management
  - name: Configuration
    description: System configuration (admin only)
  - name: Export
    description: Configuration file exports

paths:
  /dashboard:
    get:
      tags: [Dashboard]
      summary: Dashboard overview
      responses:
        '200':
          description: Dashboard statistics and device summary

  /devices:
    get:
      tags: [Devices]
      summary: List all devices
      responses:
        '200':
          description: Devices list with status stats
    post:
      tags: [Devices]
      summary: Create device
      requestBody:
        required: true
        content:
          application/json:
            schema:
              \$ref: '#/components/schemas/StoreDeviceRequest'
      responses:
        '302':
          description: Redirect to device detail
        '422':
          description: Validation error

  /devices/{device}:
    get:
      tags: [Devices]
      summary: Show device detail
      parameters:
        - \$ref: '#/components/parameters/DeviceId'
      responses:
        '200':
          description: Device with channels, storage, and health logs
        '404':
          description: Device not found
    patch:
      tags: [Devices]
      summary: Update device
      parameters:
        - \$ref: '#/components/parameters/DeviceId'
      requestBody:
        required: true
        content:
          application/json:
            schema:
              \$ref: '#/components/schemas/UpdateDeviceRequest'
      responses:
        '302':
          description: Redirect to device detail
        '422':
          description: Validation error
    delete:
      tags: [Devices]
      summary: Delete device
      parameters:
        - \$ref: '#/components/parameters/DeviceId'
      responses:
        '302':
          description: Redirect to devices list

  /devices/{device}/test:
    post:
      tags: [Devices]
      summary: Test device connectivity
      parameters:
        - \$ref: '#/components/parameters/DeviceId'
      responses:
        '200':
          description: Connection test result
          content:
            application/json:
              schema:
                \$ref: '#/components/schemas/TestConnectionResponse'

  /devices/{device}/health-history:
    get:
      tags: [Devices]
      summary: Device health history logs
      parameters:
        - \$ref: '#/components/parameters/DeviceId'
      responses:
        '200':
          description: Array of health log entries
          content:
            application/json:
              schema:
                type: array
                items:
                  \$ref: '#/components/schemas/HealthLog'

  /devices/export/frigate-config:
    get:
      tags: [Export]
      summary: Preview Frigate YAML configuration
      responses:
        '200':
          description: Frigate config preview page

  /devices/export/frigate-config/download:
    get:
      tags: [Export]
      summary: Download Frigate YAML configuration file
      responses:
        '200':
          description: YAML file
          content:
            text/yaml:
              schema:
                type: string

  /export/openapi:
    get:
      tags: [Export]
      summary: Preview OpenAPI specification
      responses:
        '200':
          description: OpenAPI spec preview page

  /export/openapi/download:
    get:
      tags: [Export]
      summary: Download OpenAPI specification YAML file
      responses:
        '200':
          description: OpenAPI YAML file
          content:
            text/yaml:
              schema:
                type: string

  /alerts:
    get:
      tags: [Alerts]
      summary: List alerts
      parameters:
        - name: status
          in: query
          schema:
            type: string
            enum: [active, acknowledged, resolved]
        - name: severity
          in: query
          schema:
            type: string
            enum: [info, warning, critical]
        - name: page
          in: query
          schema:
            type: integer
            minimum: 1
      responses:
        '200':
          description: Paginated alert list

  /alerts/{alert}/acknowledge:
    post:
      tags: [Alerts]
      summary: Acknowledge an alert
      parameters:
        - name: alert
          in: path
          required: true
          schema:
            type: integer
      responses:
        '302':
          description: Redirect back
        '404':
          description: Alert not found

  /alerts/export:
    get:
      tags: [Alerts]
      summary: Export alerts as CSV
      parameters:
        - name: status
          in: query
          schema:
            type: string
        - name: severity
          in: query
          schema:
            type: string
      responses:
        '200':
          description: CSV file download
          content:
            text/csv:
              schema:
                type: string

  /configuration:
    get:
      tags: [Configuration]
      summary: Get system configuration
      responses:
        '200':
          description: Current configuration values
        '403':
          description: Admin only
    post:
      tags: [Configuration]
      summary: Update system configuration
      requestBody:
        required: true
        content:
          application/json:
            schema:
              \$ref: '#/components/schemas/UpdateConfigurationRequest'
      responses:
        '302':
          description: Redirect back
        '403':
          description: Admin only
        '422':
          description: Validation error

  /configuration/test-telegram:
    post:
      tags: [Configuration]
      summary: Send test Telegram notification
      responses:
        '200':
          description: Test dispatch result
        '403':
          description: Admin only

  /configuration/test-email:
    post:
      tags: [Configuration]
      summary: Send test email notification
      responses:
        '200':
          description: Test dispatch result
        '403':
          description: Admin only

components:
  parameters:
    DeviceId:
      name: device
      in: path
      required: true
      schema:
        type: integer
      description: Device ID

  schemas:
    StoreDeviceRequest:
      type: object
      required: [name, ip_address, port, username, password]
      properties:
        name:
          type: string
          maxLength: 255
          example: Kamera Depan
        ip_address:
          type: string
          format: ipv4
          example: 192.168.1.100
        port:
          type: integer
          minimum: 1
          maximum: 65535
          example: 80
        username:
          type: string
          maxLength: 255
          example: admin
        password:
          type: string
          maxLength: 255
          example: password123
        model:
          type: string
          maxLength: 255
          nullable: true
          example: DS-2CD2143G2-I

    UpdateDeviceRequest:
      type: object
      required: [name, ip_address, port, username]
      properties:
        name:
          type: string
          maxLength: 255
        ip_address:
          type: string
          format: ipv4
        port:
          type: integer
          minimum: 1
          maximum: 65535
        username:
          type: string
          maxLength: 255
        password:
          type: string
          maxLength: 255
          description: Leave empty to keep existing password
        model:
          type: string
          maxLength: 255
          nullable: true

    UpdateConfigurationRequest:
      type: object
      required:
        - polling_channel_interval
        - polling_storage_interval
        - polling_device_interval
        - notification_reminder_interval
      properties:
        polling_channel_interval:
          type: integer
          minimum: 1
          maximum: 60
          description: Channel status poll interval in minutes
        polling_storage_interval:
          type: integer
          minimum: 1
          maximum: 60
          description: Storage status poll interval in minutes
        polling_device_interval:
          type: integer
          minimum: 1
          maximum: 60
          description: Device health poll interval in minutes
        notification_reminder_interval:
          type: integer
          minimum: 5
          maximum: 1440
          description: Repeat notification interval in minutes
        telegram_bot_token:
          type: string
          nullable: true
        telegram_chat_ids:
          type: string
          nullable: true
          description: Comma-separated chat IDs
        telegram_message_thread_id:
          type: string
          nullable: true
        mail_from_address:
          type: string
          format: email
          nullable: true
        alert_email_recipients:
          type: string
          nullable: true
          description: Comma-separated email addresses

    TestConnectionResponse:
      type: object
      properties:
        success:
          type: boolean
        response_time_ms:
          type: integer
          nullable: true
        device_info:
          type: object
          nullable: true
        error:
          type: string
          nullable: true

    HealthLog:
      type: object
      properties:
        id:
          type: integer
        device_id:
          type: integer
        status:
          type: string
          enum: [online, offline, unknown]
        response_time_ms:
          type: integer
          nullable: true
        temperature:
          type: number
          nullable: true
        created_at:
          type: string
          format: date-time
YAML;
    }

    private function buildServers(): string
    {
        $devices = Device::select('name', 'ip_address', 'port')->orderBy('name')->get();

        if ($devices->isEmpty()) {
            return "  - url: http://localhost\n    description: local\n";
        }

        return $devices->map(function (Device $device) {
            $port = (int) $device->port;
            $url = $port === 80 || $port === 0
                ? "http://{$device->ip_address}"
                : "http://{$device->ip_address}:{$port}";

            $name = str_replace('"', "'", $device->name);

            return "  - url: {$url}\n    description: \"{$name}\"";
        })->implode("\n") . "\n";
    }

    private function appVersion(): string
    {
        $path = base_path('app/VERSION');
        if (! file_exists($path)) {
            return '1.0.0';
        }
        $line = explode("\n", file_get_contents($path))[0];
        preg_match('/^(v[\d.]+(?:-[a-z]+)?)/', $line, $m);

        return $m[1] ?? '1.0.0';
    }
}
