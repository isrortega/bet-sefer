<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({});
const page = usePage();
const data = computed(() => page.props);
</script>

<template>
    <div class="min-h-screen bg-[#F5F6F4]">
        <header class="border-b border-[#DFE2DD] bg-white px-4 py-3">
            <div class="mx-auto flex max-w-2xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-[#14211F]">Bet-Sefer</a>
                <a href="/lookup" class="text-sm text-[#55625E] hover:text-[#14211F]">Search a book</a>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-4 py-6">
            <div class="rounded-[10px] border border-[#DFE2DD] bg-white p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h1 class="font-serif text-[25px] leading-snug text-[#14211F]">{{ data.title }}</h1>
                        <p class="mt-1 text-sm text-[#55625E]">{{ data.authors }} · {{ data.publisher }} · {{ data.published_year }}</p>
                        <p class="mt-3 font-mono text-xs text-[#55625E]">Copy {{ data.copy.code }}</p>
                    </div>
                </div>

                <div class="mt-5 rounded-md bg-[#FAF0DC] px-3 py-2 text-sm font-medium text-[#8A6212] capitalize">
                    {{ data.copy.status.replaceAll('_', ' ') }}
                </div>

                <dl class="mt-5 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-[#7C8783]">Where it is</dt>
                        <dd class="text-[#14211F]">{{ data.copy.location || 'Unknown' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[#7C8783]">Can I take it?</dt>
                        <dd class="text-[#14211F]">{{ data.for_loan ? 'Yes' : 'Not for loan' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[#7C8783]">Loan type</dt>
                        <dd class="capitalize text-[#14211F]">{{ data.loan_type }}</dd>
                    </div>
                </dl>
            </div>

            <p v-if="data.estimated_available_at && data.available_count === 0" class="mt-3 text-sm text-[#55625E]">
                Estimated available around {{ new Date(data.estimated_available_at).toLocaleDateString() }}.
            </p>
        </main>
    </div>
</template>
