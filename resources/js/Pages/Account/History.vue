<script setup>
const props = defineProps({
    loans: Object,
});
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-4xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-ink">Bet-Sefer</a>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="/account" class="text-ink-muted hover:text-ink">Dashboard</a>
                    <a href="/account/history" class="font-medium text-buckram">History</a>
                    <a href="/account/card" class="text-ink-muted hover:text-ink">Card</a>
                    <a href="/" class="text-ink-muted hover:text-ink">Browse</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            <h1 class="text-[25px] font-medium text-ink">Loan history</h1>

            <p v-if="loans.data.length === 0" class="mt-4 text-sm text-ink-muted">Nothing returned yet.</p>

            <div v-else class="mt-4 overflow-hidden rounded-[10px] border border-rule bg-paper">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-rule text-ink-muted">
                        <tr>
                            <th class="px-4 py-2 font-medium">Title</th>
                            <th class="px-4 py-2 font-medium">Checked out</th>
                            <th class="px-4 py-2 font-medium">Returned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="loan in loans.data" :key="loan.code" class="border-b border-rule last:border-0">
                            <td class="px-4 py-2 font-serif text-ink">{{ loan.title }}</td>
                            <td class="px-4 py-2 text-ink-muted">{{ new Date(loan.checked_out_at).toLocaleDateString() }}</td>
                            <td class="px-4 py-2 text-ink-muted">{{ new Date(loan.returned_at).toLocaleDateString() }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</template>
