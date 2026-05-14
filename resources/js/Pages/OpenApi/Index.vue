<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    yaml: { type: String, default: '' },
});

const copied = ref(false);

const pathCount = computed(() => {
    const matches = props.yaml.match(/^  \/[^\s]+:/gm);
    return matches ? matches.length : 0;
});

const schemaCount = computed(() => {
    const matches = props.yaml.match(/^    [A-Z][a-zA-Z]+:\s*$/gm);
    return matches ? matches.length : 0;
});

const serverCount = computed(() => {
    const matches = props.yaml.match(/^  - url:/gm);
    return matches ? matches.length : 0;
});

const lineCount = computed(() => props.yaml.split('\n').length);

function highlightYaml(text) {
    return text.split('\n').map(line => {
        if (line.trim() === '') return '';
        if (/^\s*#/.test(line)) {
            return `<span class="yaml-comment">${escapeHtml(line)}</span>`;
        }
        const keyVal = line.match(/^(\s*)(- )?([a-zA-Z_$][a-zA-Z0-9_$]*)(:\s*)(.*)?$/);
        if (keyVal) {
            const [, indent, dash, key, colon, value] = keyVal;
            let result = escapeHtml(indent);
            if (dash) result += `<span class="yaml-dash">- </span>`;
            result += `<span class="yaml-key">${escapeHtml(key)}</span>`;
            result += `<span class="yaml-colon">${escapeHtml(colon)}</span>`;
            if (value !== undefined && value !== '') result += highlightValue(value);
            return result;
        }
        const listItem = line.match(/^(\s*)(- )(.*)/);
        if (listItem) {
            const [, indent, dash, value] = listItem;
            return `${escapeHtml(indent)}<span class="yaml-dash">- </span>${highlightValue(value)}`;
        }
        return escapeHtml(line);
    }).join('\n');
}

function highlightValue(value) {
    if (value === 'true' || value === 'false') return `<span class="yaml-bool">${value}</span>`;
    if (/^\d+$/.test(value)) return `<span class="yaml-number">${value}</span>`;
    if (value === '[]') return `<span class="yaml-bracket">${value}</span>`;
    if (/^\$ref:/.test(value)) return `<span class="yaml-ref">${escapeHtml(value)}</span>`;
    if (/^https?:\/\//.test(value)) return `<span class="yaml-url">${escapeHtml(value)}</span>`;
    if (/^\//.test(value)) return `<span class="yaml-path">${escapeHtml(value)}</span>`;
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
    } catch {
        const ta = document.createElement('textarea');
        ta.value = props.yaml;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2500);
    }
}
</script>

<template>
    <Head title="OpenAPI Spec" />

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
                        <h2 class="text-[17px] font-semibold text-slate-100">OpenAPI Spec</h2>
                        <p class="text-[13px] text-slate-500">Preview &amp; export OpenAPI 3.0 specification</p>
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
                        :href="route('export.openapi.download')"
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

        <!-- Stats -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-4">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Paths</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-cyan-400">{{ pathCount }}</p>
            </div>
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-4">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Schemas</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-violet-400">{{ schemaCount }}</p>
            </div>
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-4">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Servers</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-400">{{ serverCount }}</p>
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
                <span class="text-[12px] font-medium text-slate-500">openapi.yaml</span>
            </div>
            <span class="text-[11px] font-medium uppercase tracking-wider text-slate-600">OpenAPI 3.0.3</span>
        </div>

        <!-- YAML Preview -->
        <div class="rounded-b-lg border border-white/[0.06] bg-[#0d1117] shadow-[0_4px_24px_rgba(0,0,0,0.5)] overflow-hidden">
            <div class="overflow-x-auto">
                <div class="flex min-w-0">
                    <div class="flex-none select-none border-r border-white/[0.04] bg-[#0d1117] px-3 py-4 text-right">
                        <pre class="text-[12px] leading-[1.7] font-mono text-slate-600"><template v-for="(_, i) in yaml.split('\n')" :key="i">{{ i + 1 }}
</template></pre>
                    </div>
                    <div class="flex-1 min-w-0 overflow-x-auto px-4 py-4">
                        <pre class="text-[12px] leading-[1.7] font-mono yaml-code" v-html="highlightYaml(yaml)"></pre>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.yaml-code :deep(.yaml-key) { color: #7ee787; }
.yaml-code :deep(.yaml-colon) { color: #8b949e; }
.yaml-code :deep(.yaml-string) { color: #a5d6ff; }
.yaml-code :deep(.yaml-bool) { color: #ff7b72; }
.yaml-code :deep(.yaml-number) { color: #d2a8ff; }
.yaml-code :deep(.yaml-bracket) { color: #8b949e; }
.yaml-code :deep(.yaml-dash) { color: #8b949e; }
.yaml-code :deep(.yaml-comment) { color: #484f58; font-style: italic; }
.yaml-code :deep(.yaml-url) { color: #f0883e; }
.yaml-code :deep(.yaml-path) { color: #ffa657; }
.yaml-code :deep(.yaml-ref) { color: #79c0ff; }
</style>
