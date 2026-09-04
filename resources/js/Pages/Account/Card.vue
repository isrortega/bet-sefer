<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '../../i18n';

const { t } = useTrans();
const props = defineProps({
    member: Object,
});

const page = usePage();
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-4xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-ink">Bet-Sefer</a>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="/account" class="text-ink-muted hover:text-ink">{{ t('nav.dashboard') }}</a>
                    <a href="/account/history" class="text-ink-muted hover:text-ink">{{ t('nav.history') }}</a>
                    <a href="/account/card" class="font-medium text-buckram">{{ t('nav.card') }}</a>
                    <a href="/" class="text-ink-muted hover:text-ink">{{ t('nav.catalog') }}</a>
                    <form method="post" action="/logout" class="inline">
                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                        <button type="submit" class="text-ink-muted hover:text-ink">{{ t('nav.sign_out') }}</button>
                    </form>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            <div class="mx-auto max-w-xs rounded-[10px] border border-rule bg-paper p-6 text-center">
                <p class="font-serif text-[20px] text-ink">{{ member.name }}</p>
                <div class="mt-4 flex justify-center" v-html="member.qr"></div>
                <p class="mt-4 font-mono text-sm tracking-[0.2em] text-ink">{{ member.code }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ t('account.show_code') }}</p>
                <p class="mt-4 text-[10px] text-ink-subtle">{{ t('account.print_only') }}</p>
            </div>
        </main>
    </div>
</template>
