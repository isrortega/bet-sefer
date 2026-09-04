<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const data = computed(() => page.props);
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-2xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-ink">Bet-Sefer</a>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-4 py-6">
            <form method="get" action="/lookup" class="flex gap-2">
                <input name="isbn" :value="data.isbn ?? ''" inputmode="numeric" placeholder="Type an ISBN, e.g. 9780062316110"
                       class="w-full rounded-md border border-rule bg-paper px-3 py-2 font-mono text-sm outline-none focus:ring-2 focus:ring-brass" />
                <button class="rounded-md bg-buckram px-4 py-2 text-sm font-medium text-paper">Find it</button>
            </form>

            <div v-if="flash.message || flash.error" class="mt-4 rounded-md border px-3 py-2 text-sm"
                 :class="flash.error ? 'border-lost-bg bg-lost-bg text-lost' : 'border-available-bg bg-available-bg text-available'">
                {{ flash.error || flash.message }}
            </div>

            <div v-if="data.lookedUp === true && data.found === null" class="mt-6 rounded-[10px] border border-rule bg-paper p-5">
                <p class="font-medium text-ink">Not available at this library.</p>
                <p class="mt-1 text-sm text-ink-muted">
                    We could not find <span class="font-mono">{{ data.isbn }}</span> in our catalogue.
                    You can suggest we acquire it — no account needed.
                </p>
                <form method="post" action="/lookup/suggest" class="mt-4 flex items-center gap-2">
                    <input type="hidden" name="_token" :value="page.props.csrf_token" />
                    <input type="hidden" name="isbn" :value="data.isbn" />
                    <button class="rounded-md bg-brass px-3 py-2 text-sm font-medium text-paper">Suggest acquisition</button>
                </form>
            </div>

            <div v-else-if="data.lookedUp === null" class="mt-6 text-sm text-ink-muted">
                Scanning the QR on a book also works — it points here.
            </div>
        </main>
    </div>
</template>
