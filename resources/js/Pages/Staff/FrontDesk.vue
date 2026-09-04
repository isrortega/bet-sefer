<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '../../i18n';

const { t } = useTrans();
const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const errors = computed(() => page.props.errors ?? {});
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-3xl items-center justify-between">
                <span class="font-serif text-lg font-medium text-ink">{{ t('desk.title') }}</span>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="/account" class="text-ink-muted hover:text-ink">{{ t('nav.my_account') }}</a>
                    <a href="/" class="text-ink-muted hover:text-ink">{{ t('nav.catalog') }}</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8">
            <div v-if="flash.message || flash.error" class="mb-4 rounded-md border px-3 py-2 text-sm"
                 :class="flash.error ? 'border-lost-bg bg-lost-bg text-lost' : 'border-available-bg bg-available-bg text-available'">
                {{ flash.error || flash.message }}
            </div>

            <section class="rounded-[10px] border border-rule bg-paper p-5">
                <h1 class="text-[20px] font-medium text-ink">{{ t('desk.checkout') }}</h1>
                <form method="post" action="/staff/desk/checkout" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <input type="hidden" name="_token" :value="page.props.csrf_token" />
                    <div>
                        <label class="text-sm text-ink" for="co-member">{{ t('desk.reader') }}</label>
                        <input id="co-member" name="member" required :placeholder="t('desk.member_hint')"
                               class="mt-1 w-full rounded-md border border-rule px-3 py-2 font-mono text-sm outline-none focus:ring-2 focus:ring-brass" />
                    </div>
                    <div>
                        <label class="text-sm text-ink" for="co-code">{{ t('desk.copy') }}</label>
                        <input id="co-code" name="code" required placeholder="BS-XXXX"
                               class="mt-1 w-full rounded-md border border-rule px-3 py-2 font-mono text-sm uppercase outline-none focus:ring-2 focus:ring-brass" />
                    </div>
                    <div>
                        <label class="text-sm text-ink" for="co-hours">{{ t('desk.hours_optional') }}</label>
                        <input id="co-hours" name="hours" type="number" min="1" max="720" :placeholder="t('desk.hours_default')"
                               class="mt-1 w-full rounded-md border border-rule px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brass" />
                    </div>
                    <button type="submit" class="rounded-md bg-buckram px-3 py-2 text-sm font-medium text-paper">{{ t('desk.checkout') }}</button>
                    <p v-if="errors.code" class="text-sm text-lost sm:col-span-2">{{ errors.code[0] }}</p>
                </form>
            </section>

            <section class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-[10px] border border-rule bg-paper p-5">
                    <h2 class="text-[16px] font-medium text-ink">{{ t('desk.checkin') }}</h2>
                    <form method="post" action="/staff/desk/checkin" class="mt-2 flex gap-2">
                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                        <input name="code" required placeholder="BS-XXXX"
                               class="w-full rounded-md border border-rule px-3 py-2 font-mono text-sm uppercase outline-none focus:ring-2 focus:ring-brass" />
                        <button class="rounded-md bg-buckram px-3 py-2 text-sm text-paper">{{ t('desk.checkin') }}</button>
                    </form>
                </div>
                <div class="rounded-[10px] border border-rule bg-paper p-5">
                    <h2 class="text-[16px] font-medium text-ink">{{ t('desk.renew') }}</h2>
                    <form method="post" action="/staff/desk/renew" class="mt-2 flex gap-2">
                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                        <input name="code" required placeholder="LN-XXXX"
                               class="w-full rounded-md border border-rule px-3 py-2 font-mono text-sm uppercase outline-none focus:ring-2 focus:ring-brass" />
                        <button class="rounded-md bg-buckram px-3 py-2 text-sm text-paper">{{ t('desk.renew') }}</button>
                    </form>
                </div>
            </section>
        </main>
    </div>
</template>
