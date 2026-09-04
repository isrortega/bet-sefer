<script setup>
const props = defineProps({
    member: Object,
});
</script>

<template>
    <div class="min-h-screen bg-[#F5F6F4]">
        <header class="border-b border-[#DFE2DD] bg-white px-4 py-3">
            <div class="mx-auto flex max-w-4xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-[#14211F]">Bet-Sefer</a>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="/account" class="text-[#55625E] hover:text-[#14211F]">Dashboard</a>
                    <a href="/account/history" class="text-[#55625E] hover:text-[#14211F]">History</a>
                    <a href="/account/card" class="font-medium text-[#14543F]">Card</a>
                    <a href="/" class="text-[#55625E] hover:text-[#14211F]">Browse</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            <div class="mx-auto max-w-xs rounded-[10px] border border-[#DFE2DD] bg-white p-6 text-center">
                <p class="font-serif text-[20px] text-[#14211F]">{{ member.name }}</p>
                <div class="mt-4 flex justify-center" v-html="member.qr"></div>
                <p class="mt-4 font-mono text-sm tracking-[0.2em] text-[#14211F]">{{ member.code }}</p>
                <p class="mt-1 text-xs text-[#55625E]">Show this code at the front desk</p>
                <p class="mt-4 text-[10px] text-[#7C8783]">Print only from your own profile.</p>
            </div>
        </main>
    </div>
</template>
