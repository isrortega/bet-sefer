<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    readers: Object,
    canVerify: Boolean,
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const q = new URLSearchParams(window.location.search).get('q') ?? '';
</script>

<template>
    <div class="min-h-screen bg-[#F5F6F4]">
        <header class="border-b border-[#DFE2DD] bg-white px-4 py-3">
            <div class="mx-auto flex max-w-5xl items-center justify-between">
                <a href="/" class="font-serif text-lg font-medium text-[#14211F]">Bet-Sefer · Staff</a>
                <a href="/account" class="text-sm text-[#55625E] hover:text-[#14211F]">My account</a>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-[25px] font-medium text-[#14211F]">Readers</h1>
                <form method="get" action="/staff/readers" class="flex gap-2">
                    <input name="q" :value="q" placeholder="Name, email or member code"
                           class="rounded-md border border-[#DFE2DD] bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#A8761C]" />
                    <button type="submit" class="rounded-md bg-[#14543F] px-3 py-2 text-sm font-medium text-white">Search</button>
                </form>
            </div>

            <div v-if="flash.message || flash.error" class="mb-4 mt-4 rounded-md border px-3 py-2 text-sm"
                 :class="flash.error ? 'border-[#F8E7EA] bg-[#F8E7EA] text-[#8A2B3B]' : 'border-[#E6F1EB] bg-[#E6F1EB] text-[#1E6B4F]'">
                {{ flash.error || flash.message }}
            </div>

            <div class="mt-4 overflow-hidden rounded-[10px] border border-[#DFE2DD] bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-[#DFE2DD] text-[#55625E]">
                        <tr>
                            <th class="px-4 py-2 font-medium">Reader</th>
                            <th class="px-4 py-2 font-medium">Member code</th>
                            <th class="px-4 py-2 font-medium">Status</th>
                            <th class="px-4 py-2 font-medium">Roles</th>
                            <th class="px-4 py-2 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="reader in readers.data" :key="reader.ulid" class="border-b border-[#DFE2DD] last:border-0">
                            <td class="px-4 py-3">
                                <p class="text-[#14211F]">{{ reader.name }}</p>
                                <p class="text-xs text-[#55625E]">{{ reader.email }}</p>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-[#55625E]">{{ reader.member_code }}</td>
                            <td class="px-4 py-3">
                                <span v-if="reader.deleted" class="rounded-md bg-[#F8E7EA] px-2 py-0.5 text-xs font-medium text-[#8A2B3B]">Closed</span>
                                <span v-else class="rounded-md bg-[#FAF0DC] px-2 py-0.5 text-xs font-medium text-[#8A6212]">{{ reader.status.replaceAll('_', ' ') }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-[#55625E]">{{ reader.roles.join(', ') }}</td>
                            <td class="px-4 py-3">
                                <div v-if="reader.deleted">
                                    <form method="post" :action="`/staff/readers/${reader.ulid}/restore`">
                                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                                        <button class="text-sm font-medium text-[#14543F] hover:underline">Reopen</button>
                                    </form>
                                </div>
                                <form v-else-if="canVerify && !reader.verified_at && reader.roles.includes('reader')"
                                      method="post" :action="`/staff/readers/${reader.ulid}/verify`" class="flex flex-wrap items-center justify-end gap-1">
                                    <input type="hidden" name="_token" :value="page.props.csrf_token" />
                                    <select name="document_type" class="rounded border border-[#DFE2DD] px-1.5 py-1 text-xs">
                                        <option value="CC">CC</option>
                                        <option value="CE">CE</option>
                                        <option value="passport">Passport</option>
                                    </select>
                                    <input name="document_number" required placeholder="ID number"
                                           class="rounded border border-[#DFE2DD] px-2 py-1 text-xs" />
                                    <button class="rounded bg-[#14543F] px-2 py-1 text-xs font-medium text-white">Verify</button>
                                </form>
                                <span v-else class="text-xs text-[#7C8783]">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="readers.links && readers.links.length > 3" class="mt-4 flex gap-2 text-sm">
                <template v-for="link in readers.links" :key="link.label">
                    <span v-if="link.url" class="rounded border border-[#DFE2DD] bg-white px-2 py-1 text-[#14543F]">
                        <a v-html="link.label" :href="link.url"></a>
                    </span>
                    <span v-else class="rounded border border-[#DFE2DD] px-2 py-1 text-[#7C8783]" v-html="link.label"></span>
                </template>
            </div>
        </main>
    </div>
</template>
