<script setup>
const props = defineProps({
    member: Object,
});
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-4xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-ink">Bet-Sefer</a>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="/account" class="text-ink-muted hover:text-ink">Dashboard</a>
                    <a href="/account/history" class="text-ink-muted hover:text-ink">History</a>
                    <a href="/account/card" class="font-medium text-buckram">Card</a>
                    <a href="/" class="text-ink-muted hover:text-ink">Browse</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            <div class="mx-auto max-w-xs rounded-[10px] border border-rule bg-paper p-6 text-center">
                <p class="font-serif text-[20px] text-ink">{{ member.name }}</p>
                <div class="mt-4 flex justify-center" v-html="member.qr"></div>
                <p class="mt-4 font-mono text-sm tracking-[0.2em] text-ink">{{ member.code }}</p>
                <p class="mt-1 text-xs text-ink-muted">Show this code at the front desk</p>
                <p class="mt-4 text-[10px] text-ink-subtle">Print only from your own profile.</p>
            </div>
        </main>
    </div>
</template>
