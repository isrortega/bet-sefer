<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
                <h1 class="font-serif text-[25px] leading-snug text-[#14211F]">{{ data.title }}</h1>
                <p class="mt-1 text-sm text-[#55625E]">
                    {{ data.authors }} · {{ data.publisher }} · {{ data.published_year }}
                </p>
                <p v-if="data.subtitle" class="mt-1 text-sm italic text-[#55625E]">{{ data.subtitle }}</p>

                <p class="mt-4 font-mono text-xs text-[#7C8783]">ISBN {{ data.isbn }}</p>

                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-md bg-[#E6F1EB] px-3 py-2">
                        <p class="text-[25px] font-medium text-[#1E6B4F]">{{ data.available_count }}</p>
                        <p class="text-xs text-[#55625E]">available</p>
                    </div>
                    <div class="rounded-md bg-[#F5F6F4] px-3 py-2">
                        <p class="text-[25px] font-medium text-[#14211F]">{{ data.copies_count }}</p>
                        <p class="text-xs text-[#55625E]">copies</p>
                    </div>
                    <div class="rounded-md bg-[#F5F6F4] px-3 py-2">
                        <p class="text-[25px] font-medium text-[#14211F]">{{ data.borrowed_last_year }}</p>
                        <p class="text-xs text-[#55625E]">loans / year</p>
                    </div>
                    <div class="rounded-md bg-[#F5F6F4] px-3 py-2">
                        <p class="text-[25px] font-medium capitalize text-[#14211F]">{{ data.loan_type }}</p>
                        <p class="text-xs text-[#55625E]">loan type</p>
                    </div>
                </div>

                <p v-if="data.estimated_available_at && data.available_count === 0" class="mt-4 text-sm text-[#55625E]">
                    No copy available right now. Estimated around
                    <span class="font-medium text-[#14211F]">{{ new Date(data.estimated_available_at).toLocaleDateString() }}</span>.
                </p>

                <p v-if="data.summary" class="mt-5 text-[#14211F]">{{ data.summary }}</p>

                <p v-if="data.tags.length" class="mt-4 text-xs text-[#55625E]">
                    <span v-for="tag in data.tags" :key="tag" class="mr-2 rounded bg-[#F5F6F4] px-2 py-0.5">#{{ tag }}</span>
                </p>
            </div>
        </main>
    </div>
</template>
