<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import StatusChip from '../../Components/StatusChip.vue';
import { useTrans } from '../../i18n';

const { t } = useTrans();
const props = defineProps({ users: Object, roles: Array });
const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const errors = computed(() => page.props.errors ?? {});
const firstError = computed(() => Object.values(errors.value).flat()[0] ?? null);
const q = new URLSearchParams(window.location.search).get('q') ?? '';
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-5xl items-center justify-between">
                <span class="font-serif text-lg font-medium text-ink">Bet-Sefer · Staff</span>
                <a href="/account" class="text-sm text-ink-muted hover:text-ink">{{ t('nav.my_account') }}</a>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-[25px] font-medium text-ink">{{ t('admin.users_title') }}</h1>
                <form method="get" action="/staff/users" class="flex gap-2">
                    <input name="q" :value="q" :placeholder="t('readers.search_ph')"
                           class="rounded-md border border-rule bg-paper px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brass" />
                    <button class="rounded-md bg-buckram px-3 py-2 text-sm font-medium text-paper">{{ t('readers.search') }}</button>
                </form>
            </div>

            <div v-if="flash.message || flash.error || firstError" class="mt-4 rounded-md border px-3 py-2 text-sm"
                 :class="(flash.error || firstError) ? 'border-lost-bg bg-lost-bg text-lost' : 'border-available-bg bg-available-bg text-available'">
                {{ flash.error || flash.message || firstError }}
            </div>

            <form method="post" action="/staff/users" class="mt-4 grid grid-cols-2 gap-3 rounded-[10px] border border-rule bg-paper p-4 sm:grid-cols-5">
                <input type="hidden" name="_token" :value="page.props.csrf_token" />
                <div><label class="text-xs text-ink-muted">{{ t('admin.name') }}</label>
                    <input name="name" required class="mt-1 w-full rounded border border-rule px-2 py-1 text-sm" /></div>
                <div><label class="text-xs text-ink-muted">{{ t('admin.email') }}</label>
                    <input name="email" type="email" required class="mt-1 w-full rounded border border-rule px-2 py-1 text-sm" /></div>
                <div><label class="text-xs text-ink-muted">{{ t('admin.password') }}</label>
                    <input name="password" type="password" required minlength="8" class="mt-1 w-full rounded border border-rule px-2 py-1 text-sm" /></div>
                <div><label class="text-xs text-ink-muted">{{ t('admin.role') }}</label>
                    <select name="role" class="mt-1 w-full rounded border border-rule px-2 py-1 text-sm">
                        <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                    </select></div>
                <div class="flex items-end"><button class="w-full rounded-md bg-buckram px-3 py-2 text-sm font-medium text-paper">{{ t('admin.add_user') }}</button></div>
            </form>

            <div class="mt-4 overflow-hidden rounded-[10px] border border-rule bg-paper">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-rule text-ink-muted">
                        <tr>
                            <th class="px-4 py-2 font-medium">{{ t('admin.name_header') }}</th>
                            <th class="px-4 py-2 font-medium">{{ t('admin.role') }}</th>
                            <th class="px-4 py-2 font-medium">{{ t('common.status') }}</th>
                            <th class="px-4 py-2 text-right font-medium">{{ t('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in users.data" :key="u.ulid" class="border-b border-rule last:border-0">
                            <td class="px-4 py-3">
                                <p class="text-ink">{{ u.name }}</p>
                                <p class="text-xs text-ink-muted">{{ u.email }} · <span class="font-mono">{{ u.member_code }}</span></p>
                            </td>
                            <td class="px-4 py-3">
                                <form method="post" :action="`/staff/users/${u.ulid}/update`" class="flex items-center gap-1">
                                    <input type="hidden" name="_token" :value="page.props.csrf_token" />
                                    <select name="role" class="rounded border border-rule px-1 py-0.5 text-xs">
                                        <option v-for="r in roles" :key="r" :value="r" :selected="u.roles.includes(r)">{{ r }}</option>
                                    </select>
                                    <button class="text-xs font-medium text-buckram hover:underline">{{ t('admin.save') }}</button>
                                </form>
                            </td>
                            <td class="px-4 py-3"><StatusChip :status="u.deleted ? 'deleted' : u.status" /></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-2 text-xs">
                                    <form v-if="u.deleted" method="post" :action="`/staff/users/${u.ulid}/restore`">
                                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                                        <button class="font-medium text-buckram hover:underline">{{ t('admin.reopen') }}</button>
                                    </form>
                                    <template v-else>
                                        <form v-if="!['active'].includes(u.status)" method="post" :action="`/staff/users/${u.ulid}/activate`">
                                            <input type="hidden" name="_token" :value="page.props.csrf_token" />
                                            <button class="font-medium text-available hover:underline">{{ t('admin.activate') }}</button>
                                        </form>
                                        <form v-if="u.status === 'active'" method="post" :action="`/staff/users/${u.ulid}/suspend`">
                                            <input type="hidden" name="_token" :value="page.props.csrf_token" />
                                            <input name="reason" :placeholder="t('admin.reason')" class="w-28 rounded border border-rule px-1.5 py-0.5 text-xs" />
                                            <button class="font-medium text-reception hover:underline">{{ t('admin.suspend') }}</button>
                                        </form>
                                        <form method="post" :action="`/staff/users/${u.ulid}/close`"
                                              @submit.prevent="confirm(t('admin.close')+'?') && $event.target.submit()">
                                            <input type="hidden" name="_token" :value="page.props.csrf_token" />
                                            <button class="font-medium text-lost hover:underline">{{ t('admin.close') }}</button>
                                        </form>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</template>
