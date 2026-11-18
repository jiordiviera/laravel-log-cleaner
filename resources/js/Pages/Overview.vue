<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Layout from '../Components/Layout.vue';

const props = defineProps({
    directory: { type: String, required: true },
    report: { type: Object, default: null },
    health: { type: Object, required: true },
    schedule: { type: Object, required: true },
    scannedAt: { type: String, required: true },
    cached: { type: Boolean, default: false },
});

const LEVELS = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR', 'WARNING', 'NOTICE', 'INFO', 'DEBUG'];
const SEVERE = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR'];
const BAR = 24;

const number = (value) => Number(value ?? 0).toLocaleString('en-US');
const bytes = (value) => {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let size = Math.max(Number(value ?? 0), 0);
    let unit = 0;
    while (size >= 1024 && unit < units.length - 1) {
        size /= 1024;
        unit += 1;
    }
    return `${unit === 0 ? size : size.toFixed(2)} ${units[unit]}`;
};
const severe = (levels) => SEVERE.reduce((sum, level) => sum + (levels?.[level] ?? 0), 0);

const totals = computed(() => props.report?.totals ?? null);
const files = computed(() => [...(props.report?.files ?? [])].sort((a, b) => b.size_bytes - a.size_bytes));
const ratio = computed(() => (props.health.limitBytes && totals.value ? totals.value.size_bytes / props.health.limitBytes : null));

const levelRows = computed(() => {
    if (!totals.value || totals.value.records === 0) return [];
    return LEVELS.filter((level) => (totals.value.levels[level] ?? 0) > 0).map((level) => {
        const count = totals.value.levels[level];
        const share = count / totals.value.records;
        const filled = Math.max(1, Math.round(share * BAR));
        return {
            level: level.toLowerCase(),
            severe: SEVERE.includes(level),
            filled: '█'.repeat(filled),
            empty: '░'.repeat(BAR - filled),
            count: number(count).padStart(10),
            share: `${(share * 100).toFixed(1)}%`.padStart(6),
        };
    });
});
const absentLevels = computed(() => (totals.value ? LEVELS.filter((level) => !(totals.value.levels[level] > 0)).map((l) => l.toLowerCase()) : []));

const scheduleOptions = computed(() => Object.entries(props.schedule.options)
    .filter(([, value]) => value !== false && value !== null)
    .map(([key, value]) => (value === true ? `--${key}` : `--${key}=${value}`))
    .join(' '));

const refresh = () => router.reload({ data: { refresh: 1 }, preserveScroll: true });
</script>

<template>
    <Head title="Overview" />
    <Layout active="overview">
        <main class="lc-main">
            <template v-if="report">
                <section v-if="health.exceeded" class="alert" aria-label="Health">
                    <span class="err" style="font-weight: 700; font-size: 15px">● over limit</span>
                    <span style="flex: 1 1 360px; font-size: 14px">
                        {{ bytes(totals.size_bytes) }} used / {{ bytes(health.limitBytes) }} allowed
                        <span class="mut">(×{{ ratio.toFixed(1) }}) · health.max_size_mb</span>
                    </span>
                </section>
                <section v-else-if="health.limitBytes" class="alert alert--ok" aria-label="Health">
                    <span class="ok" style="font-weight: 700; font-size: 15px">● under limit</span>
                    <span style="font-size: 14px">{{ bytes(totals.size_bytes) }} used / {{ bytes(health.limitBytes) }} allowed</span>
                </section>

                <section class="stats" aria-label="Totals">
                    <div class="box stat">
                        <span class="mut" style="font-size: 13px">size</span>
                        <span class="stat__value" :class="{ err: health.exceeded }">{{ bytes(totals.size_bytes) }}</span>
                        <span class="mut" style="font-size: 12px">{{ totals.files }} {{ totals.files === 1 ? 'file' : 'files' }}</span>
                    </div>
                    <div class="box stat">
                        <span class="mut" style="font-size: 13px">records</span>
                        <span class="stat__value">{{ number(totals.records) }}</span>
                        <span class="mut" style="font-size: 12px">{{ totals.oldest ?? '-' }} → {{ totals.newest ?? '-' }}</span>
                    </div>
                    <div class="box stat">
                        <span class="mut" style="font-size: 13px">errors+</span>
                        <span class="stat__value" :class="{ err: severe(totals.levels) > 0 }">{{ number(severe(totals.levels)) }}</span>
                        <span class="mut" style="font-size: 12px">emergency → error</span>
                    </div>
                    <div class="box stat">
                        <span class="mut" style="font-size: 13px">warnings</span>
                        <span class="stat__value">{{ number(totals.levels.WARNING) }}</span>
                        <span class="mut" style="font-size: 12px">warning</span>
                    </div>
                </section>

                <section class="box" aria-labelledby="lc-files" style="padding-bottom: 8px">
                    <h2 id="lc-files" class="ttl">files</h2>
                    <div class="scroll-x" style="margin-top: -6px">
                        <table class="tbl">
                            <thead>
                                <tr>
                                    <th>file</th>
                                    <th class="num">size</th>
                                    <th class="num">records</th>
                                    <th class="num">errors+</th>
                                    <th class="num">warnings</th>
                                    <th>oldest</th>
                                    <th>newest</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="file in files" :key="file.file">
                                    <td style="font-weight: 700">{{ file.file }}</td>
                                    <td class="num">{{ bytes(file.size_bytes) }}</td>
                                    <td class="num">{{ number(file.records) }}</td>
                                    <td class="num" :class="{ err: severe(file.levels) > 0 }">{{ number(severe(file.levels)) }}</td>
                                    <td class="num">{{ number(file.levels.WARNING) }}</td>
                                    <td>{{ file.oldest ?? '-' }}</td>
                                    <td>{{ file.newest ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="grid2">
                    <div class="box" style="padding: 0 20px 18px">
                        <h2 class="ttl" style="left: -8px">by level</h2>
                        <pre v-if="levelRows.length" class="pre"><template v-for="row in levelRows" :key="row.level"><span :class="{ err: row.severe }">{{ row.level.padEnd(9) }}{{ row.filled }}</span><span class="mut">{{ row.empty }}</span>{{ row.count }}{{ row.share }}
</template></pre>
                        <p v-else class="mut" style="margin: 0; font-size: 14px">no records with a known level</p>
                        <p v-if="absentLevels.length && levelRows.length" class="mut" style="margin: 6px 0 0; font-size: 12px">no {{ absentLevels.join(', ') }} records</p>
                    </div>
                    <div class="box" style="padding: 0 20px 18px">
                        <h2 class="ttl" style="left: -8px">status</h2>
                        <pre class="pre">schedule ........... <span :class="schedule.enabled ? 'ok' : ''">{{ schedule.enabled ? `on · ${schedule.cron}` : 'off' }}</span>
health limit ....... {{ health.limitBytes ? bytes(health.limitBytes) : 'not set' }}
backups+archives ... <span :class="{ mut: report.archives.count === 0 }">{{ report.archives.count === 0 ? 'none' : `${report.archives.count} · ${bytes(report.archives.size_bytes)}` }}</span>
scanned ............ {{ scannedAt }}{{ cached ? ' (cached)' : '' }}</pre>
                        <p v-if="schedule.enabled" class="mut" style="margin: 6px 0 0; font-size: 12px">runs log:clear {{ scheduleOptions }}</p>
                        <p v-else class="mut" style="margin: 6px 0 0; font-size: 12px">enable with LOG_CLEANER_SCHEDULE_ENABLED=true</p>
                    </div>
                </section>
            </template>

            <section v-else class="box" style="padding: 28px 24px; font-size: 14px; line-height: 1.8; overflow-wrap: anywhere">
                <span class="mut">$ ls {{ directory }}/*.log</span><br />
                no log files yet. they show up here as soon as your app writes one.
            </section>
        </main>

        <template #keys>
            <button type="button" class="kbtn" @click="refresh">refresh</button>
        </template>
    </Layout>
</template>
