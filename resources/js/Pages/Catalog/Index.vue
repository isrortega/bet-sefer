<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '../../i18n';

const { t } = useTrans();
const props = defineProps({ q: String, items: Object });
const page = usePage();
const flash = computed(() => page.props.flash ?? {});
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-3xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-ink">Bet-Sefer</a>
                <nav class="flex items-center gap-4 text-sm">
                    <a v-if="!page.props.auth?.user" href="/login" class="text-ink-muted hover:text-ink">{{ t('auth.sign_in') }}</a>
                    <a v-else href="/account" class="text-ink-muted hover:text-ink">{{ t('nav.my_account') }}</a>
                    <a href="/lookup" class="text-ink-muted hover:text-ink">{{ t('nav.by_isbn') }}</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8">
            <h1 class="text-[25px] font-medium text-ink">{{ t('catalog.title') }}</h1>

            <form method="get" action="/catalog" class="mt-4 flex gap-2">
                <input name="q" :value="q" :placeholder="t('catalog.search_ph')"
                       class="w-full rounded-md border border-rule bg-paper px-3 py-2 text-[16px] outline-none focus:ring-2 focus:ring-brass" />
                <button class="rounded-md bg-buckram px-4 py-2 text-sm font-medium text-paper">{{ t('catalog.search') }}</button>
            </form>

            <div v-if="flash.error" class="mt-4 rounded-md border border-lost-bg bg-lost-bg px-3 py-2 text-sm text-lost">
                {{ flash.error }}
            </div>

            <ul class="mt-6 divide-y divide-rule rounded-[10px] border border-rule bg-paper">
                <li v-for="item in items.data" :key="item.isbn ?? item.title" class="flex items-center justify-between gap-4 p-4">
                    <div class="min-w-0">
                        <a :href="'/lookup/' + item.isbn" class="font-serif text-ink hover:text-buckram hover:underline">{{ item.title }}</a>
                        <p class="truncate text-sm text-ink-muted">{{ item.authors }} · {{ item.published_year }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p v-if="item.available > 0" class="rounded-md bg-available-bg px-2 py-0.5 text-xs font-medium text-available">
                            {{ t('catalog.available', { n: item.available }) }}
                        </p>
                        <p v-else class="text-xs text-ink-subtle">{{ t('catalog.on_loan') }}</p>
                    </div>
                </li>
            </ul>

            <p v-if="q && items.data.length === 0" class="mt-6 text-sm text-ink-muted">
                {{ t('catalog.no_results', { q }) }}
            </p>
        </main>
    </div>
</template>
