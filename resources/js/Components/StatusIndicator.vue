<script setup>
const props = defineProps({
    status: {
        type: String,
        default: 'unknown',
    },
});

const colorMap = {
    online: 'bg-green-500',
    ok: 'bg-green-500',
    healthy: 'bg-green-500',
    offline: 'bg-red-500',
    fault: 'bg-red-500',
    warning: 'bg-yellow-400',
    no_video: 'bg-yellow-400',
    disabled: 'bg-orange-400',
    unknown: 'bg-gray-400',
};

const labelMap = {
    online: 'Online',
    ok: 'OK',
    healthy: 'Healthy',
    offline: 'Offline',
    fault: 'Fault',
    warning: 'Warning',
    no_video: 'No Video',
    disabled: 'Disabled',
    unknown: 'Unknown',
};

const dotClass = colorMap[props.status] ?? 'bg-gray-400';
const label = labelMap[props.status] ?? props.status;
</script>

<template>
    <span class="inline-flex items-center gap-1.5">
        <span
            :class="[dotClass, 'h-2.5 w-2.5 rounded-full shrink-0']"
        ></span>
        <span class="text-sm font-medium capitalize text-gray-700">{{ label }}</span>
    </span>
</template>
