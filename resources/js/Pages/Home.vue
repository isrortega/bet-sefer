<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    name: String,
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-[#F5F6F4] p-6">
        <p class="text-[#14211F]">
            <span class="font-serif text-[39px] font-medium">Bet-Sefer</span>
            <span class="ml-2 inline-block h-6 w-px translate-y-1 bg-[#14543F]" aria-hidden="true"></span>
        </p>
        <p class="mt-1 text-[#55625E]">A small library, a public catalogue, and a quiet front desk.</p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3 text-sm">
            <a href="/catalog"
               class="rounded-md bg-[#14543F] px-4 py-2 font-medium text-white outline-none hover:bg-[#0f4433] focus:ring-2 focus:ring-[#A8761C] focus:ring-offset-2">
                Browse the catalogue
            </a>
            <a href="/lookup"
               class="rounded-md border border-[#DFE2DD] bg-white px-4 py-2 font-medium text-[#14211F] outline-none hover:bg-[#F5F6F4] focus:ring-2 focus:ring-[#A8761C]">
                Find a book by ISBN
            </a>
            <a v-if="!user" href="/login"
               class="rounded-md border border-[#DFE2DD] bg-white px-4 py-2 font-medium text-[#14211F] outline-none hover:bg-[#F5F6F4] focus:ring-2 focus:ring-[#A8761C]">
                Sign in
            </a>
            <a v-else href="/account"
               class="rounded-md border border-[#DFE2DD] bg-white px-4 py-2 font-medium text-[#14211F] outline-none hover:bg-[#F5F6F4] focus:ring-2 focus:ring-[#A8761C]">
                My account
            </a>
        </div>
    </div>
</template>
