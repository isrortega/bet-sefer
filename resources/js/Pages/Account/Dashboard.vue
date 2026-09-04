<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    member: Object,
    loans: Array,
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-4xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-ink">Bet-Sefer</a>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="/account" class="font-medium text-buckram">Dashboard</a>
                    <a href="/account/history" class="text-ink-muted hover:text-ink">History</a>
                    <a href="/account/card" class="text-ink-muted hover:text-ink">Card</a>
                    <a href="/" class="text-ink-muted hover:text-ink">Browse</a>
                    <form method="post" action="/logout" class="inline">
                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                        <button type="submit" class="text-ink-muted hover:text-ink">Sign out</button>
                    </form>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            <div v-if="flash.message || flash.error" class="mb-6 rounded-md border px-3 py-2 text-sm"
                 :class="flash.error ? 'border-lost-bg bg-lost-bg text-lost' : 'border-available-bg bg-available-bg text-available'">
                {{ flash.error || flash.message }}
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-[25px] font-medium text-ink">Hello, {{ member.name.split(' ')[0] }}</h1>
                    <p class="mt-1 text-sm text-ink-muted">
                        Member code
                        <span class="font-mono text-ink">{{ member.code }}</span>
                        · status <span class="capitalize">{{ member.status.replaceAll('_', ' ') }}</span>
                    </p>
                </div>
            </div>

            <section class="mt-8">
                <h2 class="text-[20px] font-medium text-ink">Current loans</h2>

                <p v-if="loans.length === 0" class="mt-3 text-sm text-ink-muted">
                    No books out right now.
                </p>

                <div v-else class="mt-3 overflow-hidden rounded-[10px] border border-rule bg-paper">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-rule text-ink-muted">
                            <tr>
                                <th class="px-4 py-2 font-medium">Title</th>
                                <th class="px-4 py-2 font-medium">Copy</th>
                                <th class="px-4 py-2 font-medium">Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="loan in loans" :key="loan.code" class="border-b border-rule last:border-0">
                                <td class="px-4 py-2 font-serif text-ink">{{ loan.title }}</td>
                                <td class="px-4 py-2 font-mono text-xs text-ink-muted">{{ loan.copy_code }}</td>
                                <td class="px-4 py-2">
                                    <span v-if="loan.overdue" class="rounded-md bg-lost-bg px-2 py-0.5 text-xs font-medium text-lost">Overdue · {{ new Date(loan.due_at).toLocaleDateString() }}</span>
                                    <span v-else class="text-ink">{{ new Date(loan.due_at).toLocaleDateString() }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</template>
