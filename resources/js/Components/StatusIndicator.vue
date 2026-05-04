<script setup>
const props = defineProps({
    status: {
        type: String,
        default: 'unknown',
    },
});

const colorMap = {
    online: 'bg-emerald-500',
    ok: 'bg-emerald-500',
    healthy: 'bg-emerald-500',
    offline: 'bg-red-500',
    fault: 'bg-red-500',
    warning: 'bg-amber-400',
    no_video: 'bg-amber-400',
    disabled: 'bg-orange-400',
    unknown: 'bg-slate-500',
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

const dotClass = colorMap[props.status] ?? 'bg-slate-500';
const label = labelMap[props.status] ?? props.status;
</script>

<template>
    <span class="inline-flex items-center gap-1.5">
        <span :class="[dotClass, 'h-2 w-2 rounded-full shrink-0']"></span>
        <span class="text-[13px] font-medium capitalize text-slate-300">{{ label }}</span>
    </span>
</template>
