# Frigate Export Settings Modal

**Date:** 2026-05-28
**Page:** `/devices/export/frigate-config`

## Overview

Add an Export Settings modal to the Frigate Config page that lets users choose which devices to include in the YAML export and which stream type (main / sub / both) each device uses for its camera roles.

## Data Flow

1. Controller always passes a `devices` prop (all devices: `{id, name}`) alongside `yaml`.
2. On first load, no query params are sent — service generates YAML for all devices using both streams (existing behavior).
3. User clicks **Settings** button in the page header. Modal opens.
4. User adjusts per-device checkboxes and stream selectors, then clicks **Apply**.
5. Vue calls `router.get('/devices/export/frigate-config', { device_ids: [...], stream_types: { [id]: 'main'|'sub'|'both' } })`.
6. Controller reads `device_ids` and `stream_types` from request, passes as `$options` to `FrigateConfigExportService::generate($options)`.
7. Service returns filtered, role-adjusted YAML. Inertia re-renders the page with the new `yaml` prop.
8. Modal closes. Preview updates. Download/Copy use the updated YAML.

Settings are ephemeral (Vue state only, not persisted to DB).

## Modal UI

Triggered by a **⚙ Settings** button in the page header (next to Copy / Download YAML).

If any devices are excluded, the button shows a badge: `Settings (2 hidden)`.

**Modal layout:**

```
┌─────────────────────────────────────────┐
│  Export Settings                    [×] │
├─────────────────────────────────────────┤
│  [Select all]  [Deselect all]           │
│                                         │
│  [✓] Device Name          [ Both  ▾ ]  │
│  [✓] Camera Lobby         [ Main  ▾ ]  │
│  [ ] Camera Parking       [ Sub   ▾ ]  │
│      (stream selector disabled if       │
│       device unchecked)                 │
├─────────────────────────────────────────┤
│  [ Cancel ]               [ Apply ]     │
└─────────────────────────────────────────┘
```

- **Cancel:** closes modal, reverts local state to last-applied values.
- **Apply:** fires `router.get()` with current selections, closes modal.

## Backend — FrigateConfigExportService

```php
public function generate(array $options = []): string
// $options keys:
//   'device_ids'   => int[]   — devices to include; absent/null = all
//   'stream_types' => array   — [device_id => 'main'|'sub'|'both']
//                               absent device_id defaults to 'both'
```

### Stream role mapping

| Stream type | go2rtc entries       | Camera inputs                                      |
|-------------|----------------------|----------------------------------------------------|
| `both`      | `name` + `name_sub`  | main → record+audio; sub → detect (current behavior) |
| `main`      | `name` only          | main → record + audio + detect (single input)      |
| `sub`       | `name` only (sub URL)| sub → record + audio + detect (single input)       |

### Controller changes

Both `frigateConfig()` and `downloadFrigateConfig()` read `device_ids` and `stream_types` from the request and forward to `generate()`.

`frigateConfig()` also always passes `devices` (all Device records: id + name) as an Inertia prop.

## Frontend — Vue state

| Ref               | Type                         | Purpose                                  |
|-------------------|------------------------------|------------------------------------------|
| `showModal`       | `bool`                       | Modal visibility                         |
| `pendingDeviceIds`| `Set<number>`                | Checkbox state while modal is open       |
| `pendingStreams`   | `Record<number, string>`     | Stream selector state while modal is open|
| `appliedDeviceIds`| `Set<number>`                | Last-applied device selection            |
| `appliedStreams`   | `Record<number, string>`     | Last-applied stream types                |

On open: copy `applied*` → `pending*`.
On Cancel: discard `pending*`.
On Apply: copy `pending*` → `applied*`, fire router.get().

## Files Affected

| File | Change |
|------|--------|
| `app/Services/FrigateConfigExportService.php` | Add `$options` param; filter devices; per-device stream role logic |
| `app/Http/Controllers/DeviceController.php` | Pass `devices` prop; read `device_ids`+`stream_types` from request |
| `resources/js/Pages/Devices/FrigateConfig.vue` | Settings button, modal component, Vue state, router.get() call |
