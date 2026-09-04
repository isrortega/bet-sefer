<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '../../i18n';

const { t } = useTrans();
const props = defineProps({ suggestions: Object, totals: Object });
const page = usePage();
const flash = computed(() => page.props.flash ?? {});
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-4xl items-center justify-between">
                <span class="font-serif text-lg font-medium text-ink">Bet-Sefer · Staff</span>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="/account" class="text-ink-muted hover:text-ink">{{ t('nav.my_account') }}</a>
                    <a href="/" class="text-ink-muted hover:text-ink">{{ t('nav.catalog') }}</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-[25px] font-medium text-ink">{{ t('demand.title') }}</h1>
                    <p class="mt-1 text-sm text-ink-muted">
                        <span class="font-medium text-reception">{{ totals.pending }}</span> {{ t('demand.stats_pending') }} ·
                        {{ totals.total }} {{ t('demand.stats_total') }}
                    </p>
                </div>
            </div>

            <div v-if="flash.message || flash.error" class="mt-4 rounded-md border px-3 py-2 text-sm"
                 :class="flash.error ? 'border-lost-bg bg-lost-bg text-lost' : 'border-available-bg bg-available-bg text-available'">
                {{ flash.error || flash.message }}
            </div>

            <p v-if="suggestions.data.length === 0" class="mt-6 text-sm text-ink-muted">{{ t('demand.empty') }}</p>

            <div v-else class="mt-4 overflow-hidden rounded-[10px] border border-rule bg-paper">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-rule text-ink-muted">
                        <tr>
                            <th class="px-4 py-2 font-medium">{{ t('demand.book') }}</th>
                            <th class="px-4 py-2 font-medium">{{ t('demand.isbn') }}</th>
                            <th class="px-4 py-2 font-medium">{{ t('demand.when') }}</th>
                            <th class="px-4 py-2 text-right font-medium">{{ t('readers.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in suggestions.data" :key="s.id" class="border-b border-rule last:border-0">
                            <td class="px-4 py-3">
                                <template v-if="s.meta?.title">
                                    <p class="font-serif text-ink">{{ s.meta.title }}</p>
                                    <p class="text-xs text-ink-muted">
                                        {{ (s.meta.authors ?? []).join(', ') }}<template v-if="(s.meta.authors ?? []).length"> · </template>{{ s.meta.publisher }}<template v-if="s.meta.publisher"> · </template>{{ s.meta.published_year }}
                                    </p>
                                </template>
                                <span v-else class="text-xs text-ink-subtle">{{ t('demand.unknown_title') }}</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-ink">{{ s.isbn }}</td>
                            <td class="px-4 py-3 text-ink-muted">{{ new Date(s.created_at).toLocaleDateString() }}</td>
                            <td class="px-4 py-3 text-right">
                                <span v-if="s.resolved_at" class="rounded-md bg-available-bg px-2 py-0.5 text-xs font-medium text-available">
                                    {{ t('demand.handled') }}
                                </span>
                                <form v-else method="post" :action="`/staff/demand/${s.id}/resolve`" class="inline">
                                    <input type="hidden" name="_token" :value="page.props.csrf_token" />
                                    <button class="rounded bg-buckram px-2 py-1 text-xs font-medium text-paper">{{ t('demand.mark_handled') }}</button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="suggestions.links && suggestions.links.length > 3" class="mt-4 flex gap-2 text-sm">
                <template v-for="link in suggestions.links" :key="link.label">
                    <span v-if="link.url" class="rounded border border-rule bg-paper px-2 py-1 text-buckram">
                        <a v-html="link.label" :href="link.url"></a>
                    </span>
                    <span v-else class="rounded border border-rule px-2 py-1 text-ink-subtle" v-html="link.label"></span>
                </template>
            </div>
        </main>
    </div>
</template>
