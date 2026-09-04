<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const data = computed(() => page.props);
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-2xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-ink">Bet-Sefer</a>
                <a href="/lookup" class="text-sm text-ink-muted hover:text-ink">Search a book</a>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-4 py-6">
            <div class="rounded-[10px] border border-rule bg-paper p-5">
                <h1 class="font-serif text-[25px] leading-snug text-ink">{{ data.title }}</h1>
                <p class="mt-1 text-sm text-ink-muted">
                    {{ data.authors }} · {{ data.publisher }} · {{ data.published_year }}
                </p>
                <p v-if="data.subtitle" class="mt-1 text-sm italic text-ink-muted">{{ data.subtitle }}</p>

                <p class="mt-4 font-mono text-xs text-ink-subtle">ISBN {{ data.isbn }}</p>

                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-md bg-available-bg px-3 py-2">
                        <p class="text-[25px] font-medium text-available">{{ data.available_count }}</p>
                        <p class="text-xs text-ink-muted">available</p>
                    </div>
                    <div class="rounded-md bg-shelf px-3 py-2">
                        <p class="text-[25px] font-medium text-ink">{{ data.copies_count }}</p>
                        <p class="text-xs text-ink-muted">copies</p>
                    </div>
                    <div class="rounded-md bg-shelf px-3 py-2">
                        <p class="text-[25px] font-medium text-ink">{{ data.borrowed_last_year }}</p>
                        <p class="text-xs text-ink-muted">loans / year</p>
                    </div>
                    <div class="rounded-md bg-shelf px-3 py-2">
                        <p class="text-[25px] font-medium capitalize text-ink">{{ data.loan_type }}</p>
                        <p class="text-xs text-ink-muted">loan type</p>
                    </div>
                </div>

                <p v-if="data.estimated_available_at && data.available_count === 0" class="mt-4 text-sm text-ink-muted">
                    No copy available right now. Estimated around
                    <span class="font-medium text-ink">{{ new Date(data.estimated_available_at).toLocaleDateString() }}</span>.
                </p>

                <p v-if="data.summary" class="mt-5 text-ink">{{ data.summary }}</p>

                <p v-if="data.tags.length" class="mt-4 text-xs text-ink-muted">
                    <span v-for="tag in data.tags" :key="tag" class="mr-2 rounded bg-shelf px-2 py-0.5">#{{ tag }}</span>
                </p>
            </div>
        </main>
    </div>
</template>
