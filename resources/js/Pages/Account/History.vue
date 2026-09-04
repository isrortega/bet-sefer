<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '../../i18n';

const { t } = useTrans();
const props = defineProps({
    loans: Object,
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-4xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-ink">Bet-Sefer</a>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="/account" class="text-ink-muted hover:text-ink">{{ t('nav.dashboard') }}</a>
                    <a href="/account/history" class="font-medium text-buckram">{{ t('nav.history') }}</a>
                    <a href="/account/card" class="text-ink-muted hover:text-ink">{{ t('nav.card') }}</a>
                    <a href="/" class="text-ink-muted hover:text-ink">{{ t('nav.catalog') }}</a>
                    <form method="post" action="/logout" class="inline">
                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                        <button type="submit" class="text-ink-muted hover:text-ink">{{ t('nav.sign_out') }}</button>
                    </form>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            <div v-if="flash.message || flash.error" class="mb-4 rounded-md border px-3 py-2 text-sm"
                 :class="flash.error ? 'border-lost-bg bg-lost-bg text-lost' : 'border-available-bg bg-available-bg text-available'">
                {{ flash.error || flash.message }}
            </div>

            <h1 class="text-[25px] font-medium text-ink">{{ t('account.loan_history') }}</h1>

            <p v-if="loans.data.length === 0" class="mt-4 text-sm text-ink-muted">{{ t('account.nothing_returned') }}</p>

            <div v-else class="mt-4 overflow-hidden rounded-[10px] border border-rule bg-paper">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-rule text-ink-muted">
                        <tr>
                            <th class="px-4 py-2 font-medium">{{ t('account.title') }}</th>
                            <th class="px-4 py-2 font-medium">{{ t('account.checked_out') }}</th>
                            <th class="px-4 py-2 font-medium">{{ t('account.returned') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="loan in loans.data" :key="loan.code" class="border-b border-rule last:border-0">
                            <td class="px-4 py-2 font-serif text-ink">{{ loan.title }}</td>
                            <td class="px-4 py-2 text-ink-muted">{{ new Date(loan.checked_out_at).toLocaleDateString() }}</td>
                            <td class="px-4 py-2 text-ink-muted">{{ new Date(loan.returned_at).toLocaleDateString() }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</template>
