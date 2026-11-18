<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import GithubLogo from './GithubLogo.vue';
import LaravelLogo from './LaravelLogo.vue';

const props = defineProps({
    active: { type: String, required: true },
});

const page = usePage();
const lc = computed(() => page.props.logCleaner);

const THEMES = [
    { value: 'paper', label: 'Paper (light)' },
    { value: 'console', label: 'Console (dark)' },
];
const FONTS = [
    { value: 'jetbrains', label: 'JetBrains Mono', family: "'JetBrains Mono', monospace" },
    { value: 'plex', label: 'IBM Plex Mono', family: "'IBM Plex Mono', monospace" },
    { value: 'inter', label: 'Inter', family: 'Inter, system-ui, sans-serif' },
    { value: 'app', label: 'App font (inherit)', family: 'inherit' },
];

const read = (key, fallback, allowed) => {
    try {
        const value = window.localStorage.getItem(`log-cleaner.${key}`);
        return allowed.includes(value) ? value : fallback;
    } catch {
        return fallback;
    }
};
const write = (key, value) => {
    try {
        window.localStorage.setItem(`log-cleaner.${key}`, value);
    } catch {
        // Storage can be unavailable (private mode): the choice just isn't remembered.
    }
};

const theme = ref(read('theme', lc.value.theme, THEMES.map((t) => t.value)));
const font = ref(read('font', lc.value.font, FONTS.map((f) => f.value)));
watch(theme, (value) => write('theme', value));
watch(font, (value) => write('font', value));

const settingsOpen = ref(false);
const settings = ref(null);
const closeOnOutside = (event) => {
    if (settingsOpen.value && settings.value && !settings.value.contains(event.target)) {
        settingsOpen.value = false;
    }
};
const closeOnEscape = (event) => {
    if (event.key === 'Escape') settingsOpen.value = false;
};
onMounted(() => {
    document.addEventListener('click', closeOnOutside);
    document.addEventListener('keydown', closeOnEscape);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', closeOnOutside);
    document.removeEventListener('keydown', closeOnEscape);
});

const base = computed(() => page.url.split('?')[0].replace(/\/(logs|cleanup|archives)$/, '').replace(/\/$/, ''));
const tabs = computed(() => [
    { key: 'overview', n: 1, label: 'overview', href: base.value || '/' },
    { key: 'logs', n: 2, label: 'logs', href: null },
    { key: 'cleanup', n: 3, label: 'cleanup', href: null },
    { key: 'archives', n: 4, label: 'archives', href: null },
]);
</script>

<template>
    <div class="lc" :data-theme="theme" :data-font="font">
        <header class="lc-hdr">
            <div class="lc-prompt">
                <LaravelLogo />
                <span>
                    <span class="ok" style="font-weight: 700">{{ lc.environment }}</span><span class="mut">:</span><span style="font-weight: 700">~/storage/logs</span><span class="mut"> $ </span>log-cleaner
                </span>
                <a v-if="lc.backUrl" :href="lc.backUrl" class="mut" style="margin-left: 8px; font-size: 13px">← back to app</a>
            </div>
            <div ref="settings" style="display: flex; align-items: center; gap: 8px; position: relative">
                <button type="button" class="kbtn" :aria-expanded="settingsOpen" @click="settingsOpen = !settingsOpen">display</button>
                <a class="kbtn" :href="lc.repository" target="_blank" rel="noopener"><GithubLogo />★ star on github</a>
                <div v-if="settingsOpen" class="pop" role="group" aria-label="Display settings">
                    <fieldset>
                        <legend>theme</legend>
                        <label v-for="t in THEMES" :key="t.value" class="opt">
                            <input v-model="theme" type="radio" name="lc-theme" :value="t.value" />{{ t.label }}
                        </label>
                    </fieldset>
                    <fieldset>
                        <legend>font</legend>
                        <label v-for="f in FONTS" :key="f.value" class="opt">
                            <input v-model="font" type="radio" name="lc-font" :value="f.value" />
                            <span :style="{ fontFamily: f.family }">{{ f.label }}</span>
                        </label>
                    </fieldset>
                    <span class="mut" style="font-size: 12px; line-height: 1.5">Saved in this browser. Defaults: dashboard.theme and dashboard.font in config/log-cleaner.php.</span>
                </div>
            </div>
        </header>

        <nav class="lc-tabs" aria-label="Dashboard">
            <template v-for="tab in tabs" :key="tab.key">
                <Link v-if="tab.href" :href="tab.href" :aria-current="props.active === tab.key ? 'page' : undefined"><b>{{ tab.n }}</b>{{ tab.label }}</Link>
                <span v-else class="soon" :title="`${tab.label}: coming in v2.5`"><b>{{ tab.n }}</b>{{ tab.label }}</span>
            </template>
        </nav>

        <slot />

        <footer class="lc-ftr">
            <div class="lc-keys mut"><slot name="keys" /></div>
            <span class="mut">laravel-log-cleaner v{{ lc.version }} · <a :href="lc.repository" target="_blank" rel="noopener" style="color: var(--ink)">★ star it on github</a></span>
        </footer>
    </div>
</template>
