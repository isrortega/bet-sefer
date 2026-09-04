<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ queue: Array });
const page = usePage();
const flash = computed(() => page.props.flash ?? {});
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-2xl items-center justify-between">
                <span class="font-serif text-lg font-medium text-ink">Bet-Sefer · Shelving</span>
                <a href="/" class="text-sm text-ink-muted hover:text-ink">Browse</a>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-4 py-6">
            <div v-if="flash.message || flash.error" class="mb-4 rounded-md border px-3 py-2 text-sm"
                 :class="flash.error ? 'border-lost-bg bg-lost-bg text-lost' : 'border-available-bg bg-available-bg text-available'">
                {{ flash.error || flash.message }}
            </div>

            <h1 class="text-[20px] font-medium text-ink">To shelve</h1>
            <p v-if="queue.length === 0" class="mt-2 text-sm text-ink-muted">Nothing in the queue. Nice work.</p>

            <ul class="mt-4 space-y-3">
                <li v-for="copy in queue" :key="copy.code"
                    class="flex items-center justify-between rounded-[10px] border border-rule bg-paper p-4">
                    <div>
                        <p class="font-mono text-sm text-ink-muted">{{ copy.code }}</p>
                        <p class="font-serif text-ink">{{ copy.title }}</p>
                        <p class="mt-1 text-sm font-medium text-buckram">{{ copy.destination || 'No destination' }}</p>
                    </div>
                    <form method="post" action="/staff/shelving/advance">
                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                        <input type="hidden" name="code" :value="copy.code" />
                        <button type="submit"
                                class="min-h-12 rounded-md bg-buckram px-4 py-2 text-sm font-medium text-paper">
                            {{ copy.status === 'at_reception' ? 'Pick up' : 'Shelve' }}
                        </button>
                    </form>
                </li>
            </ul>
        </main>
    </div>
</template>
