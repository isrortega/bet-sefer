<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '../../i18n';

const props = defineProps({
    ssoEnabled: Boolean,
    googleUrl: String,
});

const { t } = useTrans();
const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login');
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-shelf p-4">
        <div class="w-full max-w-sm">
            <p class="mb-6 text-ink">
                <span class="font-serif text-[31px] font-medium">{{ t('app.name') }}</span>
                <span class="ml-2 inline-block h-5 w-px translate-y-1 bg-buckram" aria-hidden="true"></span>
            </p>

            <div v-if="flash.message || flash.error" class="mb-4 rounded-md border px-3 py-2 text-sm"
                 :class="flash.error ? 'border-lost-bg bg-lost-bg text-lost' : 'border-available-bg bg-available-bg text-available'">
                {{ flash.error || flash.message }}
            </div>

            <form class="rounded-[10px] border border-rule bg-paper p-5" @submit.prevent="submit">
                <label class="block text-sm text-ink" for="email">{{ t('auth.email') }}</label>
                <input id="email" v-model="form.email" type="email" required autofocus
                       class="mt-1 w-full rounded-md border border-rule px-3 py-2 text-[16px] text-ink outline-none focus:ring-2 focus:ring-brass focus:ring-offset-2" />
                <p v-if="form.errors.email" class="mt-1 text-sm text-lost">{{ form.errors.email }}</p>

                <label class="mt-4 block text-sm text-ink" for="password">{{ t('auth.password') }}</label>
                <input id="password" v-model="form.password" type="password" required
                       class="mt-1 w-full rounded-md border border-rule px-3 py-2 text-[16px] text-ink outline-none focus:ring-2 focus:ring-brass focus:ring-offset-2" />
                <p v-if="form.errors.password" class="mt-1 text-sm text-lost">{{ form.errors.password }}</p>

                <label class="mt-4 flex items-center gap-2 text-sm text-ink-muted">
                    <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-rule" />
                    {{ t('auth.remember') }}
                </label>

                <button type="submit" :disabled="form.processing"
                        class="mt-5 w-full rounded-md bg-buckram px-3 py-2 font-medium text-paper outline-none hover:bg-buckram-deep focus:ring-2 focus:ring-brass focus:ring-offset-2 disabled:opacity-60">
                    {{ t('auth.sign_in') }}
                </button>
            </form>

            <a v-if="ssoEnabled" :href="googleUrl"
               class="mt-4 flex items-center justify-center gap-2 rounded-md border border-rule bg-paper px-3 py-2 text-sm font-medium text-ink outline-none hover:bg-shelf focus:ring-2 focus:ring-brass">
                {{ t('auth.continue_google') }}
            </a>

            <p class="mt-4 text-center text-sm text-ink-muted">
                {{ t('auth.no_account') }}
                <a href="/register" class="font-medium text-buckram hover:underline">{{ t('auth.create_one') }}</a>
            </p>
        </div>
    </div>
</template>
