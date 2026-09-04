<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import StatusChip from '../../Components/StatusChip.vue';
import { useTrans } from '../../i18n';

const { t } = useTrans();
const props = defineProps({
    mode: String,
    edition: Object,
    prefill: Object,
    existing_edition: String,
    categories: Array,
    locations: Array,
    formats: Array,
    languages: Array,
    loan_types: Array,
    conditions: Array,
    can_delete_edition: Boolean,
});
const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const errors = computed(() => page.props.errors ?? {});
const firstError = computed(() => Object.values(errors.value).flat()[0] ?? null);
const canDeleteCopies = computed(() => page.props.auth?.user?.capabilities?.editions_delete ?? false);
const e = computed(() => props.edition ?? {});
const p = computed(() => props.prefill ?? {});
const ind = (depth) => '·'.repeat(depth);
</script>

<template>
    <div class="min-h-screen bg-shelf">
        <header class="border-b border-rule bg-paper px-4 py-3">
            <div class="mx-auto flex max-w-4xl items-center justify-between">
                <span class="font-serif text-lg font-medium text-ink">Bet-Sefer · Staff</span>
                <nav class="flex items-center gap-4 text-sm">
                    <a href="/staff/catalog" class="text-ink-muted hover:text-ink">{{ t('catalog.staff_title') }}</a>
                    <a href="/account" class="text-ink-muted hover:text-ink">{{ t('nav.my_account') }}</a>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            <div class="flex items-center justify-between">
                <h1 class="text-[25px] font-medium text-ink">
                    {{ mode === 'create' ? t('catalog.new_edition') : t('catalog.edit_edition') }}
                </h1>
                <form v-if="mode === 'create'" method="get" action="/staff/catalog/create" class="flex gap-2">
                    <input name="isbn" :value="p.isbn_13 ?? ''" :placeholder="t('public.isbn_ph')"
                           class="rounded-md border border-rule bg-paper px-3 py-2 font-mono text-sm outline-none focus:ring-2 focus:ring-brass" />
                    <button class="rounded-md bg-buckram px-3 py-2 text-sm font-medium text-paper">{{ t('catalog.lookup') }}</button>
                </form>
            </div>

            <div v-if="existing_edition" class="mt-4 rounded-md border border-reception-bg bg-reception-bg px-3 py-2 text-sm text-reception">
                {{ t('catalog.isbn_exists') }} — <a :href="`/staff/catalog/${existing_edition}/edit`" class="underline">{{ t('catalog.goto_edition') }}</a>
            </div>

            <div v-if="flash.message || flash.error || firstError" class="mt-4 rounded-md border px-3 py-2 text-sm"
                 :class="(flash.error || firstError) ? 'border-lost-bg bg-lost-bg text-lost' : 'border-available-bg bg-available-bg text-available'">
                {{ flash.error || flash.message || firstError }}
            </div>

            <form method="post" :action="mode === 'create' ? '/staff/catalog' : `/staff/catalog/${e.ulid}/update`"
                  class="mt-4 grid grid-cols-1 gap-3 rounded-[10px] border border-rule bg-paper p-5 sm:grid-cols-2">
                <input type="hidden" name="_token" :value="page.props.csrf_token" />

                <div class="sm:col-span-2"><label class="text-sm text-ink">{{ t('catalog.title_field') }} *</label>
                    <input name="title" :value="e.title ?? p.title ?? ''" required maxlength="500" class="mt-1 w-full rounded-md border border-rule px-3 py-2" />
                </div>
                <div><label class="text-sm text-ink">ISBN-13</label>
                    <input name="isbn_13" :value="e.isbn_13 ?? p.isbn_13 ?? ''" class="mt-1 w-full rounded-md border border-rule px-3 py-2 font-mono text-sm" />
                    <p v-if="errors.isbn_13" class="text-xs text-lost">{{ errors.isbn_13[0] }}</p></div>
                <div><label class="text-sm text-ink">{{ t('catalog.subtitle') }}</label>
                    <input name="subtitle" :value="e.subtitle ?? p.subtitle ?? ''" class="mt-1 w-full rounded-md border border-rule px-3 py-2" /></div>
                <div><label class="text-sm text-ink">{{ t('catalog.authors') }}</label>
                    <input name="authors" :value="e.authors ?? p.authors ?? ''" :placeholder="t('catalog.comma_list')" class="mt-1 w-full rounded-md border border-rule px-3 py-2" /></div>
                <div><label class="text-sm text-ink">{{ t('catalog.publisher') }}</label>
                    <input name="publisher" :value="e.publisher ?? p.publisher ?? ''" class="mt-1 w-full rounded-md border border-rule px-3 py-2" /></div>
                <div><label class="text-sm text-ink">{{ t('admin.categories_title') }}</label>
                    <select name="category_id" class="mt-1 w-full rounded-md border border-rule px-3 py-2">
                        <option value="">—</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id" :selected="String(c.id) === String(e.category_id ?? '')">{{ ind(c.depth) }} {{ c.name }}</option>
                    </select></div>
                <div><label class="text-sm text-ink">{{ t('catalog.tags') }}</label>
                    <input name="tags" :value="e.tags ?? p.tags ?? ''" :placeholder="t('catalog.comma_list')" class="mt-1 w-full rounded-md border border-rule px-3 py-2" /></div>
                <div><label class="text-sm text-ink">{{ t('catalog.language') }}</label>
                    <select name="language" class="mt-1 w-full rounded-md border border-rule px-3 py-2">
                        <option v-for="l in languages" :key="l" :value="l" :selected="(e.language ?? p.language ?? 'en') === l">{{ l }}</option>
                    </select></div>
                <div><label class="text-sm text-ink">{{ t('catalog.format') }}</label>
                    <select name="format" class="mt-1 w-full rounded-md border border-rule px-3 py-2">
                        <option v-for="f in formats" :key="f" :value="f" :selected="(e.format ?? 'paperback') === f">{{ f }}</option>
                    </select></div>
                <div><label class="text-sm text-ink">{{ t('catalog.loan_type') }}</label>
                    <select name="loan_type" class="mt-1 w-full rounded-md border border-rule px-3 py-2">
                        <option v-for="lt in loan_types" :key="lt" :value="lt" :selected="(e.loan_type ?? 'general') === lt">{{ lt }}</option>
                    </select></div>
                <div><label class="text-sm text-ink">{{ t('catalog.year') }}</label>
                    <input name="published_year" type="number" :value="e.published_year ?? p.published_year ?? ''" min="1000" max="2100" class="mt-1 w-full rounded-md border border-rule px-3 py-2" /></div>
                <div><label class="text-sm text-ink">{{ t('catalog.pages') }}</label>
                    <input name="page_count" type="number" :value="e.page_count ?? p.page_count ?? ''" min="1" class="mt-1 w-full rounded-md border border-rule px-3 py-2" /></div>
                <div><label class="text-sm text-ink">{{ t('catalog.edition_statement') }}</label>
                    <input name="edition_statement" :value="e.edition_statement ?? ''" class="mt-1 w-full rounded-md border border-rule px-3 py-2" /></div>

                <div class="sm:col-span-2">
                    <label class="mr-4 inline-flex items-center gap-2 text-sm text-ink">
                        <input name="special_material" type="checkbox" value="1" :checked="!!(e.special_material ?? p.special_material)" /> {{ t('catalog.special_material') }}
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-ink">
                        <input name="loan_restricted_default" type="checkbox" value="1" :checked="!!(e.loan_restricted_default ?? p.loan_restricted_default)" /> {{ t('catalog.restricted') }}
                    </label>
                </div>
                <div class="sm:col-span-2"><label class="text-sm text-ink">{{ t('catalog.summary') }}</label>
                    <textarea name="summary" rows="3" class="mt-1 w-full rounded-md border border-rule px-3 py-2">{{ e.summary ?? p.summary ?? '' }}</textarea></div>
                <div class="sm:col-span-2"><label class="text-sm text-ink">{{ t('catalog.internal_notes') }}</label>
                    <textarea name="internal_notes" rows="2" class="mt-1 w-full rounded-md border border-rule px-3 py-2">{{ e.internal_notes ?? '' }}</textarea></div>

                <div class="flex items-center gap-3 sm:col-span-2">
                    <button class="rounded-md bg-buckram px-4 py-2 text-sm font-medium text-paper">
                        {{ mode === 'create' ? t('admin.create') : t('admin.save') }}
                    </button>
                    <form v-if="mode === 'edit' && can_delete_edition" method="post" :action="`/staff/catalog/${e.ulid}`"
                          @submit.prevent="confirm(t('catalog.confirm_delete')+'?') && $event.target.submit()">
                        <input type="hidden" name="_token" :value="page.props.csrf_token" />
                        <input type="hidden" name="_method" value="DELETE" />
                        <button class="text-sm font-medium text-lost hover:underline">{{ t('admin.delete') }}</button>
                    </form>
                </div>
            </form>

            <section v-if="mode === 'edit'" class="mt-6">
                <h2 class="text-[20px] font-medium text-ink">{{ t('catalog.copies') }}</h2>

                <form method="post" :action="`/staff/catalog/${e.ulid}/copies`" class="mt-3 flex flex-wrap items-end gap-2 rounded-[10px] border border-rule bg-paper p-4">
                    <input type="hidden" name="_token" :value="page.props.csrf_token" />
                    <div><label class="text-xs text-ink-muted">{{ t('catalog.add_copy') }}</label></div>
                    <div>
                        <select name="location_id" class="rounded border border-rule px-2 py-1 text-sm">
                            <option value="">—</option>
                            <option v-for="l in locations" :key="l.id" :value="l.id">{{ ind(l.depth) }} {{ l.name }}</option>
                        </select></div>
                    <button class="rounded-md bg-buckram px-3 py-2 text-sm font-medium text-paper">{{ t('catalog.add_copy') }}</button>
                </form>

                <div v-for="c in e.copies ?? []" :key="c.id" class="mt-3 rounded-[10px] border border-rule bg-paper p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-sm text-ink">{{ c.code }}</span>
                            <StatusChip :status="c.status" />
                            <span v-if="c.location_label" class="text-xs text-ink-muted">{{ c.location_label }}</span>
                        </div>
                        <form method="post" :action="`/staff/copies/${c.id}/update`" class="flex flex-wrap items-end gap-2">
                            <input type="hidden" name="_token" :value="page.props.csrf_token" />
                            <select name="location_id" class="rounded border border-rule px-1.5 py-1 text-xs">
                                <option value="">—</option>
                                <option v-for="l in locations" :key="l.id" :value="l.id"
                                        :selected="String(l.id) === String(c.location_id ?? '')">{{ ind(l.depth) }} {{ l.name }}</option>
                            </select>
                            <select name="condition" class="rounded border border-rule px-1.5 py-1 text-xs">
                                <option v-for="con in conditions" :key="con" :value="con" :selected="c.condition === con">{{ con }}</option>
                            </select>
                            <select name="loan_restricted" class="rounded border border-rule px-1.5 py-1 text-xs">
                                <option value="inherit" :selected="c.loan_restricted === null">{{ t('catalog.inherit') }}</option>
                                <option value="1" :selected="c.loan_restricted === true">Yes</option>
                                <option value="0" :selected="c.loan_restricted === false">No</option>
                            </select>
                            <button class="rounded bg-buckram px-2 py-1 text-xs text-paper">{{ t('admin.save') }}</button>
                        </form>
                        <form method="post" :action="`/staff/copies/${c.id}`"
                              @submit.prevent="confirm(t('admin.delete')+'?') && $event.target.submit()">
                            <input type="hidden" name="_token" :value="page.props.csrf_token" />
                            <input type="hidden" name="_method" value="DELETE" />
                            <button class="text-xs font-medium text-lost hover:underline">{{ t('admin.delete') }}</button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
