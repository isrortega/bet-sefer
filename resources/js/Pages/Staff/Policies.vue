<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '../../i18n';

const { t } = useTrans();
const props = defineProps({ policies: Array });
const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const errors = computed(() => page.props.errors ?? {});
const firstError = computed(() => Object.values(errors.value).flat()[0] ?? null);
const TYPE_KEY = { general: 'policies.general', reference: 'policies.reference', periodical: 'policies.periodical' };
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
            <h1 class="text-[25px] font-medium text-ink">{{ t('admin.policies_title') }}</h1>

            <div v-if="flash.message || flash.error || firstError" class="mt-4 rounded-md border px-3 py-2 text-sm"
                 :class="(flash.error || firstError) ? 'border-lost-bg bg-lost-bg text-lost' : 'border-available-bg bg-available-bg text-available'">
                {{ flash.error || flash.message || firstError }}
            </div>

            <div v-for="p in policies" :key="p.id" class="mt-4 rounded-[10px] border border-rule bg-paper p-4">
                <h2 class="text-[16px] font-medium capitalize text-ink">{{ t(TYPE_KEY[p.loan_type] ?? p.loan_type) }}</h2>
                <form method="post" :action="`/staff/policies/${p.id}`" class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                    <input type="hidden" name="_token" :value="page.props.csrf_token" />
                    <div><label class="text-xs text-ink-muted">{{ t('policies.default_hours') }}</label>
                        <input name="default_hours" type="number" :value="p.default_hours" required min="1" class="mt-1 w-full rounded border border-rule px-2 py-1" /></div>
                    <div><label class="text-xs text-ink-muted">{{ t('policies.min_hours') }}</label>
                        <input name="min_hours" type="number" :value="p.min_hours" required min="1" class="mt-1 w-full rounded border border-rule px-2 py-1" /></div>
                    <div><label class="text-xs text-ink-muted">{{ t('policies.max_hours') }}</label>
                        <input name="max_hours" type="number" :value="p.max_hours" required min="1" class="mt-1 w-full rounded border border-rule px-2 py-1" /></div>
                    <div><label class="text-xs text-ink-muted">{{ t('policies.renewals') }}</label>
                        <input name="renewals_allowed" type="number" :value="p.renewals_allowed" required min="0" class="mt-1 w-full rounded border border-rule px-2 py-1" /></div>
                    <div><label class="text-xs text-ink-muted">{{ t('policies.factor') }}</label>
                        <input name="special_material_factor" type="number" step="0.01" :value="p.special_material_factor" required min="0.05" max="1" class="mt-1 w-full rounded border border-rule px-2 py-1" /></div>
                    <div><label class="text-xs text-ink-muted">{{ t('policies.grace') }}</label>
                        <input name="grace_hours" type="number" :value="p.grace_hours" required min="0" class="mt-1 w-full rounded border border-rule px-2 py-1" /></div>
                    <div><label class="text-xs text-ink-muted">{{ t('policies.max_active') }}</label>
                        <input name="max_active_loans_per_user" type="number" :value="p.max_active_loans_per_user" required min="1" class="mt-1 w-full rounded border border-rule px-2 py-1" /></div>
                    <div class="flex items-end justify-between gap-2">
                        <label class="flex items-center gap-1 text-xs text-ink-muted">
                            <input name="is_active" type="checkbox" value="1" :checked="!!p.is_active" class="h-4 w-4" /> {{ t('policies.active') }}
                        </label>
                        <button class="rounded-md bg-buckram px-3 py-2 text-sm font-medium text-paper">{{ t('admin.save') }}</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</template>
