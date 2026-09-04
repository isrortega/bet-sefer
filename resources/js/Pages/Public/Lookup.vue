<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '../../i18n';

const { t } = useTrans();
const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const data = computed(() => page.props);
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-2xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-ink">Bet-Sefer</a>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-4 py-6">
            <form method="get" action="/lookup" class="flex gap-2">
                <input name="isbn" :value="data.isbn ?? ''" inputmode="numeric" :placeholder="t('public.isbn_ph')"
                       class="w-full rounded-md border border-rule bg-paper px-3 py-2 font-mono text-sm outline-none focus:ring-2 focus:ring-brass" />
                <button class="rounded-md bg-buckram px-4 py-2 text-sm font-medium text-paper">{{ t('public.find') }}</button>
            </form>

            <div v-if="flash.message || flash.error" class="mt-4 rounded-md border px-3 py-2 text-sm"
                 :class="flash.error ? 'border-lost-bg bg-lost-bg text-lost' : 'border-available-bg bg-available-bg text-available'">
                {{ flash.error || flash.message }}
            </div>

            <div v-if="data.lookedUp === true && data.found === null" class="mt-6 rounded-[10px] border border-rule bg-paper p-5">
                <p class="font-medium text-ink">{{ t('public.not_available') }}</p>
                <p class="mt-1 text-sm text-ink-muted">
                    {{ t('public.not_in_catalog', { isbn: data.isbn }) }}
                </p>
                <form method="post" action="/lookup/suggest" class="mt-4 flex items-center gap-2">
                    <input type="hidden" name="_token" :value="page.props.csrf_token" />
                    <input type="hidden" name="isbn" :value="data.isbn" />
                    <button class="rounded-md bg-brass px-3 py-2 text-sm font-medium text-paper">{{ t('public.suggest') }}</button>
                </form>
            </div>

            <div v-else-if="data.lookedUp === null" class="mt-6 text-sm text-ink-muted">
                {{ t('public.lookup_intro') }}
            </div>
        </main>
    </div>
</template>
