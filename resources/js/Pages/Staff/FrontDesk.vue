<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const errors = computed(() => page.props.errors ?? {});
</script>

<template>
    <div class="min-h-screen bg-[#F5F6F4]">
        <header class="border-b border-[#DFE2DD] bg-white px-4 py-3">
            <div class="mx-auto flex max-w-3xl items-center justify-between">
                <span class="font-serif text-lg font-medium text-[#14211F]">Bet-Sefer · Front desk</span>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="/account" class="text-[#55625E] hover:text-[#14211F]">My account</a>
                    <a href="/" class="text-[#55625E] hover:text-[#14211F]">Browse</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8">
            <div v-if="flash.message || flash.error" class="mb-4 rounded-md border px-3 py-2 text-sm"
                 :class="flash.error ? 'border-[#F8E7EA] bg-[#F8E7EA] text-[#8A2B3B]' : 'border-[#E6F1EB] bg-[#E6F1EB] text-[#1E6B4F]'">
                {{ flash.error || flash.message }}
            </div>

            <section class="rounded-[10px] border border-[#DFE2DD] bg-white p-5">
                <h1 class="text-[20px] font-medium text-[#14211F]">Check out</h1>
                <form method="post" action="/staff/desk/checkout" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <input type="hidden" name="_token" :value="page.props.csrf_token" />
                    <div>
                        <label class="text-sm text-[#14211F]" for="co-member">Reader</label>
                        <input id="co-member" name="member" required placeholder="Member code or email"
                               class="mt-1 w-full rounded-md border border-[#DFE2DD] px-3 py-2 font-mono text-sm outline-none focus:ring-2 focus:ring-[#A8761C]" />
                    </div>
                    <div>
                        <label class="text-sm text-[#14211F]" for="co-code">Copy</label>
                        <input id="co-code" name="code" required placeholder="BS-XXXX"
                               class="mt-1 w-full rounded-md border border-[#DFE2DD] px-3 py-2 font-mono text-sm uppercase outline-none focus:ring-2 focus:ring-[#A8761C]" />
                    </div>
                    <div>
                        <label class="text-sm text-[#14211F]" for="co-hours">Hours (optional)</label>
                        <input id="co-hours" name="hours" type="number" min="1" max="720" placeholder="default"
                               class="mt-1 w-full rounded-md border border-[#DFE2DD] px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#A8761C]" />
                    </div>
                    <button type="submit" class="rounded-md bg-[#14543F] px-3 py-2 text-sm font-medium text-white">Check out</button>
                    <p v-if="errors.code" class="text-sm text-[#8A2B3B] sm:col-span-2">{{ errors.code[0] }}</p>
                </form>
            </section>

            <section class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-[10px] border border-[#DFE2DD] bg-white p-5">
                    <h2 class="text-[16px] font-medium text-[#14211F]">Check in</h2>
                    <form method="post" action="/staff/desk/checkin" class="mt-2 flex gap-2">
                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                        <input name="code" required placeholder="BS-XXXX"
                               class="w-full rounded-md border border-[#DFE2DD] px-3 py-2 font-mono text-sm uppercase outline-none focus:ring-2 focus:ring-[#A8761C]" />
                        <button class="rounded-md bg-[#14543F] px-3 py-2 text-sm text-white">In</button>
                    </form>
                </div>
                <div class="rounded-[10px] border border-[#DFE2DD] bg-white p-5">
                    <h2 class="text-[16px] font-medium text-[#14211F]">Renew</h2>
                    <form method="post" action="/staff/desk/renew" class="mt-2 flex gap-2">
                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                        <input name="code" required placeholder="LN-XXXX"
                               class="w-full rounded-md border border-[#DFE2DD] px-3 py-2 font-mono text-sm uppercase outline-none focus:ring-2 focus:ring-[#A8761C]" />
                        <button class="rounded-md bg-[#14543F] px-3 py-2 text-sm text-white">Renew</button>
                    </form>
                </div>
            </section>
        </main>
    </div>
</template>
