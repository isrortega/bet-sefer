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
    <div class="min-h-screen bg-[#F5F6F4]">
        <header class="border-b border-[#DFE2DD] bg-white px-4 py-3">
            <div class="mx-auto flex max-w-4xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-[#14211F]">Bet-Sefer</a>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="/account" class="font-medium text-[#14543F]">Dashboard</a>
                    <a href="/account/history" class="text-[#55625E] hover:text-[#14211F]">History</a>
                    <a href="/account/card" class="text-[#55625E] hover:text-[#14211F]">Card</a>
                    <a href="/" class="text-[#55625E] hover:text-[#14211F]">Browse</a>
                    <form method="post" action="/logout" class="inline">
                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                        <button type="submit" class="text-[#55625E] hover:text-[#14211F]">Sign out</button>
                    </form>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            <div v-if="flash.message || flash.error" class="mb-6 rounded-md border px-3 py-2 text-sm"
                 :class="flash.error ? 'border-[#F8E7EA] bg-[#F8E7EA] text-[#8A2B3B]' : 'border-[#E6F1EB] bg-[#E6F1EB] text-[#1E6B4F]'">
                {{ flash.error || flash.message }}
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-[25px] font-medium text-[#14211F]">Hello, {{ member.name.split(' ')[0] }}</h1>
                    <p class="mt-1 text-sm text-[#55625E]">
                        Member code
                        <span class="font-mono text-[#14211F]">{{ member.code }}</span>
                        · status <span class="capitalize">{{ member.status.replaceAll('_', ' ') }}</span>
                    </p>
                </div>
            </div>

            <section class="mt-8">
                <h2 class="text-[20px] font-medium text-[#14211F]">Current loans</h2>

                <p v-if="loans.length === 0" class="mt-3 text-sm text-[#55625E]">
                    No books out right now.
                </p>

                <div v-else class="mt-3 overflow-hidden rounded-[10px] border border-[#DFE2DD] bg-white">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#DFE2DD] text-[#55625E]">
                            <tr>
                                <th class="px-4 py-2 font-medium">Title</th>
                                <th class="px-4 py-2 font-medium">Copy</th>
                                <th class="px-4 py-2 font-medium">Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="loan in loans" :key="loan.code" class="border-b border-[#DFE2DD] last:border-0">
                                <td class="px-4 py-2 font-serif text-[#14211F]">{{ loan.title }}</td>
                                <td class="px-4 py-2 font-mono text-xs text-[#55625E]">{{ loan.copy_code }}</td>
                                <td class="px-4 py-2">
                                    <span v-if="loan.overdue" class="rounded-md bg-[#F8E7EA] px-2 py-0.5 text-xs font-medium text-[#8A2B3B]">Overdue · {{ new Date(loan.due_at).toLocaleDateString() }}</span>
                                    <span v-else class="text-[#14211F]">{{ new Date(loan.due_at).toLocaleDateString() }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</template>
