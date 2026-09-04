<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '../../i18n';

const { t } = useTrans();
const props = defineProps({ categories: Array, parents: Array });
const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const errors = computed(() => page.props.errors ?? {});
const firstError = computed(() => Object.values(errors.value).flat()[0] ?? null);
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-4xl items-center justify-between">
                <span class="font-serif text-lg font-medium text-ink">Bet-Sefer · Staff</span>
                <a href="/account" class="text-sm text-ink-muted hover:text-ink">{{ t('nav.my_account') }}</a>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            <h1 class="text-[25px] font-medium text-ink">{{ t('admin.categories_title') }}</h1>

            <div v-if="flash.message || flash.error || firstError" class="mt-4 rounded-md border px-3 py-2 text-sm"
                 :class="(flash.error || firstError) ? 'border-lost-bg bg-lost-bg text-lost' : 'border-available-bg bg-available-bg text-available'">
                {{ flash.error || flash.message || firstError }}
            </div>

            <form method="post" action="/staff/categories" class="mt-4 flex flex-wrap items-end gap-2 rounded-[10px] border border-rule bg-paper p-4">
                <input type="hidden" name="_token" :value="page.props.csrf_token" />
                <div><label class="text-xs text-ink-muted">{{ t('admin.name') }}</label>
                    <input name="name" required class="mt-1 rounded border border-rule px-2 py-1 text-sm" /></div>
                <div><label class="text-xs text-ink-muted">{{ t('admin.parent') }}</label>
                    <select name="parent_id" class="mt-1 rounded border border-rule px-2 py-1 text-sm">
                        <option value="">—</option>
                        <option v-for="c in parents" :key="c.id" :value="c.id">{{ '·'.repeat(c.depth) }} {{ c.name }}</option>
                    </select></div>
                <button class="rounded-md bg-buckram px-3 py-2 text-sm font-medium text-paper">{{ t('admin.create_category') }}</button>
            </form>

            <ul class="mt-4 space-y-2">
                <li v-for="c in categories" :key="c.id" class="rounded-[10px] border border-rule bg-paper p-3">
                    <form method="post" :action="`/staff/categories/${c.id}/update`" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                        <span class="w-6 text-ink-subtle">{{ '·'.repeat(c.depth) }}</span>
                        <input name="name" :value="c.name" required class="rounded border border-rule px-2 py-1 text-sm" />
                        <select name="parent_id" class="rounded border border-rule px-2 py-1 text-sm">
                            <option value="">—</option>
                            <option v-for="p in parents" :key="p.id" :value="p.id"
                                    :selected="String(p.id) === String(c.parent_id ?? '')">{{ '·'.repeat(p.depth) }} {{ p.name }}</option>
                        </select>
                        <button class="rounded bg-buckram px-2 py-1 text-xs font-medium text-paper">{{ t('admin.save') }}</button>
                    </form>
                    <div v-if="c.children_count" class="mt-1 pl-10 text-xs text-ink-subtle">
                        {{ c.children_count }} {{ t('admin.children_first') }}
                    </div>
                    <form v-else method="post" :action="`/staff/categories/${c.id}`" class="mt-1 pl-10"
                          @submit.prevent="confirm(t('admin.delete')+'?') && $event.target.submit()">
                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                        <input type="hidden" name="_method" value="DELETE" />
                        <button class="text-xs font-medium text-lost hover:underline">{{ t('admin.delete') }}</button>
                    </form>
                </li>
            </ul>
        </main>
    </div>
</template>
