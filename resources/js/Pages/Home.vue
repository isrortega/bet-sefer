<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '../i18n';

const { t, locale } = useTrans();
const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const caps = computed(() => user.value?.capabilities ?? {});
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-shelf p-6">
        <form method="post" action="/locale" class="fixed right-4 top-4 flex items-center gap-2">
            <input type="hidden" name="_token" :value="page.props.csrf_token" />
            <select name="locale" onchange="this.form.submit()"
                    class="rounded border border-rule bg-paper px-2 py-1 text-sm text-ink outline-none focus:ring-2 focus:ring-brass">
                <option value="en" :selected="locale === 'en'">EN</option>
                <option value="es" :selected="locale === 'es'">ES</option>
            </select>
        </form>

        <p class="text-ink">
            <span class="font-serif text-[39px] font-medium">{{ t('app.name') }}</span>
            <span class="ml-2 inline-block h-6 w-px translate-y-1 bg-buckram" aria-hidden="true"></span>
        </p>
        <p class="mt-1 text-ink-muted">
            <template v-if="user">{{ t('home.signed_in_as', { name: user.name }) }}</template>
            <template v-else>{{ t('brand.spine') }}</template>
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3 text-sm">
            <a href="/catalog"
               class="rounded-md bg-buckram px-4 py-2 font-medium text-paper outline-none hover:bg-buckram-deep focus:ring-2 focus:ring-brass focus:ring-offset-2">
                {{ t('home.browse_catalog') }}
            </a>
            <a href="/lookup"
               class="rounded-md border border-rule bg-paper px-4 py-2 font-medium text-ink outline-none hover:bg-shelf focus:ring-2 focus:ring-brass">
                {{ t('home.find_by_isbn') }}
            </a>

            <template v-if="user">
                <a v-if="caps.front_desk" href="/staff/desk"
                   class="rounded-md bg-brass px-4 py-2 font-medium text-paper outline-none hover:opacity-90 focus:ring-2 focus:ring-brass focus:ring-offset-2">
                    {{ t('home.front_desk') }}
                </a>
                <a v-if="caps.catalog_manage" href="/staff/catalog"
                   class="rounded-md border border-rule bg-paper px-4 py-2 font-medium text-ink outline-none hover:bg-shelf focus:ring-2 focus:ring-brass">
                    {{ t('home.catalog_manage') }}
                </a>
                <a v-if="caps.shelving" href="/staff/shelving"
                   class="rounded-md border border-rule bg-paper px-4 py-2 font-medium text-ink outline-none hover:bg-shelf focus:ring-2 focus:ring-brass">
                    {{ t('home.shelving') }}
                </a>
                <a v-if="caps.readers" href="/staff/readers"
                   class="rounded-md border border-rule bg-paper px-4 py-2 font-medium text-ink outline-none hover:bg-shelf focus:ring-2 focus:ring-brass">
                    {{ t('home.readers') }}
                </a>
                <a v-if="caps.demand" href="/staff/demand"
                   class="rounded-md border border-rule bg-paper px-4 py-2 font-medium text-ink outline-none hover:bg-shelf focus:ring-2 focus:ring-brass">
                    {{ t('home.demand') }}
                </a>
                <a v-if="caps.users_admin" href="/staff/users"
                   class="rounded-md border border-rule bg-paper px-4 py-2 font-medium text-ink outline-none hover:bg-shelf focus:ring-2 focus:ring-brass">
                    {{ t('home.users_roles') }}
                </a>
                <a v-if="caps.categories" href="/staff/categories"
                   class="rounded-md border border-rule bg-paper px-4 py-2 font-medium text-ink outline-none hover:bg-shelf focus:ring-2 focus:ring-brass">
                    {{ t('home.categories') }}
                </a>
                <a v-if="caps.locations" href="/staff/locations"
                   class="rounded-md border border-rule bg-paper px-4 py-2 font-medium text-ink outline-none hover:bg-shelf focus:ring-2 focus:ring-brass">
                    {{ t('home.locations') }}
                </a>
                <a v-if="caps.policies" href="/staff/policies"
                   class="rounded-md border border-rule bg-paper px-4 py-2 font-medium text-ink outline-none hover:bg-shelf focus:ring-2 focus:ring-brass">
                    {{ t('home.policies') }}
                </a>
                <a href="/account"
                   class="rounded-md border border-rule bg-paper px-4 py-2 font-medium text-ink outline-none hover:bg-shelf focus:ring-2 focus:ring-brass">
                    {{ t('home.my_account') }}
                </a>
            </template>
            <a v-else href="/login"
               class="rounded-md border border-rule bg-paper px-4 py-2 font-medium text-ink outline-none hover:bg-shelf focus:ring-2 focus:ring-brass">
                {{ t('home.sign_in') }}
            </a>
        </div>
    </div>
</template>
