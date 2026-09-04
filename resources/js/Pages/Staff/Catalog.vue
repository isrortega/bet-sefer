<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '../../i18n';

const { t } = useTrans();
const props = defineProps({ editions: Object, categories: Array });
const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const errors = computed(() => page.props.errors ?? {});
const firstError = computed(() => Object.values(errors.value).flat()[0] ?? null);
const q = new URLSearchParams(window.location.search).get('q') ?? '';
const canDelete = computed(() => page.props.auth?.user?.capabilities?.editions_delete ?? false);
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-5xl items-center justify-between">
                <span class="font-serif text-lg font-medium text-ink">Bet-Sefer · Staff</span>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="/account" class="text-ink-muted hover:text-ink">{{ t('nav.my_account') }}</a>
                    <a href="/staff/catalog/create"
                       class="rounded-md bg-buckram px-3 py-1.5 font-medium text-paper">{{ t('catalog.new_edition') }}</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-[25px] font-medium text-ink">{{ t('catalog.staff_title') }}</h1>
                <form method="get" action="/staff/catalog" class="flex gap-2">
                    <input name="q" :value="q" :placeholder="t('catalog.search_ph')"
                           class="rounded-md border border-rule bg-paper px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brass" />
                    <button class="rounded-md bg-buckram px-3 py-2 text-sm font-medium text-paper">{{ t('catalog.search') }}</button>
                </form>
            </div>

            <div v-if="flash.message || flash.error || firstError" class="mt-4 rounded-md border px-3 py-2 text-sm"
                 :class="(flash.error || firstError) ? 'border-lost-bg bg-lost-bg text-lost' : 'border-available-bg bg-available-bg text-available'">
                {{ flash.error || flash.message || firstError }}
            </div>

            <div class="mt-4 overflow-hidden rounded-[10px] border border-rule bg-paper">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-rule text-ink-muted">
                        <tr>
                            <th class="px-4 py-2 font-medium">{{ t('account.title') }}</th>
                            <th class="px-4 py-2 font-medium">{{ t('admin.name_header') }}</th>
                            <th class="px-4 py-2 font-medium">ISBN</th>
                            <th class="px-4 py-2 font-medium">{{ t('catalog.copies') }}</th>
                            <th class="px-4 py-2 text-right font-medium">{{ t('readers.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in editions.data" :key="e.ulid" class="border-b border-rule last:border-0">
                            <td class="px-4 py-3">
                                <a :href="`/staff/catalog/${e.ulid}/edit`" class="font-serif text-ink hover:text-buckram hover:underline">{{ e.title }}</a>
                            </td>
                            <td class="px-4 py-3 text-xs text-ink-muted">{{ e.authors || '—' }}<template v-if="e.published_year"> · {{ e.published_year }}</template></td>
                            <td class="px-4 py-3 font-mono text-xs text-ink-muted">{{ e.isbn }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-ink">{{ e.copies_count }}</span>
                                <span class="text-ink-subtle"> / <span class="text-available">{{ e.available_count }}</span> {{ t('catalog.available') }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a :href="`/staff/catalog/${e.ulid}/edit`" class="text-sm font-medium text-buckram hover:underline">{{ t('admin.edit') }}</a>
                                <form v-if="canDelete" method="post" :action="`/staff/catalog/${e.ulid}`" class="inline pl-3"
                                      @submit.prevent="confirm(t('catalog.confirm_delete')+'?') && $event.target.submit()">
                                    <input type="hidden" name="_token" :value="page.props.csrf_token" />
                                    <input type="hidden" name="_method" value="DELETE" />
                                    <button class="text-sm font-medium text-lost hover:underline">{{ t('admin.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="editions.links && editions.links.length > 3" class="mt-4 flex gap-2 text-sm">
                <template v-for="link in editions.links" :key="link.label">
                    <span v-if="link.url" class="rounded border border-rule bg-paper px-2 py-1 text-buckram">
                        <a v-html="link.label" :href="link.url"></a>
                    </span>
                    <span v-else class="rounded border border-rule px-2 py-1 text-ink-subtle" v-html="link.label"></span>
                </template>
            </div>
        </main>
    </div>
</template>
