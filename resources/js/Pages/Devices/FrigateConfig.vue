<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    yaml: { type: String, default: '' },
});

const copied = ref(false);
const showMasked = ref(true);

const displayYaml = computed(() => {
    if (!showMasked.value) return props.yaml;
    // Mask passwords in RTSP URLs: rtsp://user:PASSWORD@...
    return props.yaml.replace(
        /rtsp:\/\/([^:]+):([^@]+)@/g,
        'rtsp://$1:********@',
    );
});

const lineCount = computed(() => {
    return props.yaml.split('\n').length;
});

const cameraCount = computed(() => {
    const matches = props.yaml.match(/^  [a-z0-9_]+:\s*$/gm);
    // cameras section entries — filter out subsections like ffmpeg:, detect:, audio: etc
    let count = 0;
    let inCameras = false;
    for (const line of props.yaml.split('\n')) {
        if (/^cameras:/.test(line)) {
            inCameras = true;
            continue;
        }
        if (inCameras && /^[a-z]/.test(line)) {
            inCameras = false;
        }
        if (inCameras && /^  [a-z0-9_]+:\s*$/.test(line)) {
            count++;
        }
    }
    return count;
});

const streamCount = computed(() => {
    let count = 0;
    let inGo2rtcStreams = false;
    for (const line of props.yaml.split('\n')) {
        if (/^go2rtc:/.test(line)) {
            // Found go2rtc
            continue;
        }
        if (/^  streams:/.test(line)) {
            inGo2rtcStreams = true;
            continue;
        }
        if (inGo2rtcStreams && /^[a-z]/.test(line)) {
            inGo2rtcStreams = false;
        }
        if (inGo2rtcStreams && /^    [a-zA-Z0-9_]+:\s*$/.test(line)) {
            count++;
        }
    }
    return count;
});

function highlightYaml(text) {
    return text.split('\n').map(line => {
        // Empty line
        if (line.trim() === '') return '';

        // Comment lines
        if (/^\s*#/.test(line)) {
            return `<span class="yaml-comment">${escapeHtml(line)}</span>`;
        }

        // Key: value lines
        const match = line.match(/^(\s*)(- )?([a-zA-Z_][a-zA-Z0-9_]*)(:\s*)(.*)?$/);
        if (match) {
            const [, indent, dash, key, colon, value] = match;
            let result = escapeHtml(indent);
            if (dash) result += `<span class="yaml-dash">- </span>`;
            result += `<span class="yaml-key">${escapeHtml(key)}</span>`;
            result += `<span class="yaml-colon">${escapeHtml(colon)}</span>`;
            if (value !== undefined && value !== '') {
                result += highlightValue(value);
            }
            return result;
        }

        // List items  (- value)
        const listMatch = line.match(/^(\s*)(- )(.*)/);
        if (listMatch) {
            const [, indent, dash, value] = listMatch;
            return `${escapeHtml(indent)}<span class="yaml-dash">- </span>${highlightValue(value)}`;
        }

        return escapeHtml(line);
    }).join('\n');
}

function highlightValue(value) {
    if (value === 'true' || value === 'false') {
        return `<span class="yaml-bool">${value}</span>`;
    }
    if (/^\d+$/.test(value)) {
        return `<span class="yaml-number">${value}</span>`;
    }
    if (value === '[]') {
        return `<span class="yaml-bracket">${value}</span>`;
    }
    if (/^rtsp:\/\//.test(value)) {
        return `<span class="yaml-url">${escapeHtml(value)}</span>`;
    }
    if (/^preset-/.test(value) || /^ffmpeg:/.test(value)) {
        return `<span class="yaml-preset">${escapeHtml(value)}</span>`;
    }
    return `<span class="yaml-string">${escapeHtml(value)}</span>`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function copyToClipboard() {
    try {
        await navigator.clipboard.writeText(props.yaml);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2500);
    } catch (e) {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = props.yaml;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2500);
    }
}
</script>

<template>
    <Head title="Frigate Config Export" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        href="/devices"
                        class="inline-flex items-center gap-1 text-[13px] text-slate-500 hover:text-slate-300 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Devices
                    </Link>
                    <span class="text-slate-700">/</span>
                    <div>
                        <h2 class="text-[17px] font-semibold text-slate-100">Frigate Config</h2>
                        <p class="text-[13px] text-slate-500">Preview & export YAML configuration</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="copyToClipboard"
                        class="inline-flex items-center gap-1.5 rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] font-medium text-slate-300 shadow-sm transition-all hover:bg-slate-700/60 hover:text-slate-100"
                    >
                        <svg v-if="!copied" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <svg v-else class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ copied ? 'Copied!' : 'Copy' }}
                    </button>
                    <a
                        :href="route('devices.frigate-config.download')"
                        class="inline-flex items-center gap-1.5 rounded-md bg-cyan-600 px-3 py-2 text-[13px] font-medium text-white shadow-sm transition-colors hover:bg-cyan-500"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download YAML
                    </a>
                </div>
            </div>
        </template>

        <!-- Stats row -->
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-4">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Cameras</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-cyan-400">{{ cameraCount }}</p>
            </div>
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-4">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Streams</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-violet-400">{{ streamCount }}</p>
            </div>
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-4">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Lines</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-300">{{ lineCount }}</p>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="mt-3 flex items-center justify-between rounded-t-lg border border-b-0 border-white/[0.06] bg-slate-900/80 px-4 py-2.5">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <div class="h-3 w-3 rounded-full bg-red-500/80"></div>
                    <div class="h-3 w-3 rounded-full bg-amber-500/80"></div>
                    <div class="h-3 w-3 rounded-full bg-emerald-500/80"></div>
                </div>
                <span class="text-[12px] font-medium text-slate-500">frigate-config.yml</span>
            </div>
            <div class="flex items-center gap-3">
                <label class="flex cursor-pointer items-center gap-2 text-[12px] text-slate-400 hover:text-slate-300 transition-colors select-none">
                    <div class="relative">
                        <input
                            type="checkbox"
                            v-model="showMasked"
                            class="sr-only peer"
                        />
                        <div class="h-5 w-9 rounded-full bg-slate-700 peer-checked:bg-cyan-600/60 transition-colors"></div>
                        <div class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-slate-300 transition-transform peer-checked:translate-x-4 peer-checked:bg-white"></div>
                    </div>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="showMasked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path v-if="!showMasked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    {{ showMasked ? 'Passwords hidden' : 'Passwords visible' }}
                </label>
            </div>
        </div>

        <!-- YAML Preview -->
        <div class="rounded-b-lg border border-white/[0.06] bg-[#0d1117] shadow-[0_4px_24px_rgba(0,0,0,0.5)] overflow-hidden">
            <div class="overflow-x-auto">
                <div class="flex min-w-0">
                    <!-- Line numbers -->
                    <div class="flex-none select-none border-r border-white/[0.04] bg-[#0d1117] px-3 py-4 text-right">
                        <pre class="text-[12px] leading-[1.7] font-mono text-slate-600"><template v-for="(_, i) in displayYaml.split('\n')" :key="i">{{ i + 1 }}
</template></pre>
                    </div>
                    <!-- Code content -->
                    <div class="flex-1 min-w-0 overflow-x-auto px-4 py-4">
                        <pre class="text-[12px] leading-[1.7] font-mono yaml-code" v-html="highlightYaml(displayYaml)"></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="!yaml" class="mt-6 rounded-lg border border-white/[0.06] bg-slate-900/60 py-16 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-3 text-[14px] font-medium text-slate-400">No configuration to preview</p>
            <p class="mt-1 text-[13px] text-slate-500">Add devices and poll their channels first.</p>
            <Link
                href="/devices"
                class="mt-4 inline-flex items-center gap-1.5 text-[13px] font-medium text-cyan-400 hover:text-cyan-300"
            >
                Go to Devices →
            </Link>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.yaml-code :deep(.yaml-key) {
    color: #7ee787;
}
.yaml-code :deep(.yaml-colon) {
    color: #8b949e;
}
.yaml-code :deep(.yaml-string) {
    color: #a5d6ff;
}
.yaml-code :deep(.yaml-bool) {
    color: #ff7b72;
}
.yaml-code :deep(.yaml-number) {
    color: #d2a8ff;
}
.yaml-code :deep(.yaml-bracket) {
    color: #8b949e;
}
.yaml-code :deep(.yaml-dash) {
    color: #8b949e;
}
.yaml-code :deep(.yaml-comment) {
    color: #484f58;
    font-style: italic;
}
.yaml-code :deep(.yaml-url) {
    color: #f0883e;
}
.yaml-code :deep(.yaml-preset) {
    color: #79c0ff;
}
</style>
