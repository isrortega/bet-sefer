<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ q: String, items: Object });
const page = usePage();
const flash = computed(() => page.props.flash ?? {});
</script>

<template>
    <div class="min-h-screen bg-[#F5F6F4]">
        <header class="border-b border-[#DFE2DD] bg-white px-4 py-3">
            <div class="mx-auto flex max-w-3xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-[#14211F]">Bet-Sefer</a>
                <nav class="flex items-center gap-4 text-sm">
                    <a v-if="!page.props.auth?.user" href="/login" class="text-[#55625E] hover:text-[#14211F]">Sign in</a>
                    <a v-else href="/account" class="text-[#55625E] hover:text-[#14211F]">My account</a>
                    <a href="/lookup" class="text-[#55625E] hover:text-[#14211F]">By ISBN</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8">
            <h1 class="text-[25px] font-medium text-[#14211F]">Catalogue</h1>

            <form method="get" action="/catalog" class="mt-4 flex gap-2">
                <input name="q" :value="q" placeholder="Search by title, author or tag"
                       class="w-full rounded-md border border-[#DFE2DD] bg-white px-3 py-2 text-[16px] outline-none focus:ring-2 focus:ring-[#A8761C]" />
                <button class="rounded-md bg-[#14543F] px-4 py-2 text-sm font-medium text-white">Search</button>
            </form>

            <div v-if="flash.error" class="mt-4 rounded-md border border-[#F8E7EA] bg-[#F8E7EA] px-3 py-2 text-sm text-[#8A2B3B]">
                {{ flash.error }}
            </div>

            <ul class="mt-6 divide-y divide-[#DFE2DD] rounded-[10px] border border-[#DFE2DD] bg-white">
                <li v-for="item in items.data" :key="item.isbn ?? item.title" class="flex items-center justify-between gap-4 p-4">
                    <div class="min-w-0">
                        <a :href="'/lookup/' + item.isbn" class="font-serif text-[#14211F] hover:text-[#14543F] hover:underline">{{ item.title }}</a>
                        <p class="truncate text-sm text-[#55625E]">{{ item.authors }} · {{ item.published_year }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p v-if="item.available > 0" class="rounded-md bg-[#E6F1EB] px-2 py-0.5 text-xs font-medium text-[#1E6B4F]">{{ item.available }} available</p>
                        <p v-else class="text-xs text-[#7C8783]">On loan</p>
                    </div>
                </li>
            </ul>

            <p v-if="q && items.data.length === 0" class="mt-6 text-sm text-[#55625E]">
                Nothing matched “{{ q }}”. Try a different spelling, or look it up by ISBN.
            </p>
        </main>
    </div>
</template>
