<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({});
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
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h1 class="font-serif text-[25px] leading-snug text-ink">{{ data.title }}</h1>
                        <p class="mt-1 text-sm text-ink-muted">{{ data.authors }} · {{ data.publisher }} · {{ data.published_year }}</p>
                        <p class="mt-3 font-mono text-xs text-ink-muted">Copy {{ data.copy.code }}</p>
                    </div>
                </div>

                <div class="mt-5 rounded-md bg-reception-bg px-3 py-2 text-sm font-medium text-reception capitalize">
                    {{ data.copy.status.replaceAll('_', ' ') }}
                </div>

                <dl class="mt-5 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-ink-subtle">Where it is</dt>
                        <dd class="text-ink">{{ data.copy.location || 'Unknown' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-subtle">Can I take it?</dt>
                        <dd class="text-ink">{{ data.for_loan ? 'Yes' : 'Not for loan' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-subtle">Loan type</dt>
                        <dd class="capitalize text-ink">{{ data.loan_type }}</dd>
                    </div>
                </dl>
            </div>

            <p v-if="data.estimated_available_at && data.available_count === 0" class="mt-3 text-sm text-ink-muted">
                Estimated available around {{ new Date(data.estimated_available_at).toLocaleDateString() }}.
            </p>
        </main>
    </div>
</template>
