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
                <p v-if="!data.external" class="mt-1 text-sm text-ink-muted">
                    {{ t('public.not_in_catalog', { isbn: data.isbn }) }}
                </p>

                <div v-if="data.external" class="mt-3 flex gap-4">
                    <img v-if="data.external.cover" :src="data.external.cover" :alt="data.external.title"
                         class="h-40 w-28 rounded-md border border-rule object-cover" />
                    <div>
                        <h2 class="font-serif text-[20px] leading-snug text-ink">{{ data.external.title }}</h2>
                        <p v-if="data.external.subtitle" class="text-sm italic text-ink-muted">{{ data.external.subtitle }}</p>
                        <p class="mt-1 text-sm text-ink-muted">
                            {{ data.external.authors.join(', ') }}<template v-if="data.external.authors.length"> · </template>{{ data.external.publisher }}<template v-if="data.external.publisher"> · </template>{{ data.external.published_year }}
                        </p>
                        <p v-if="data.external.summary" class="mt-2 line-clamp-3 text-sm text-ink-muted">{{ data.external.summary }}</p>
                    </div>
                </div>

                <p class="mt-3 text-xs text-ink-subtle">{{ t('public.external_hint') }}</p>

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
