<script setup>
import { useForm } from '@inertiajs/vue3';
import { useTrans } from '../../i18n';

const { t } = useTrans();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register');
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-shelf p-4">
        <div class="w-full max-w-sm">
            <p class="mb-6 text-ink">
                <span class="font-serif text-[31px] font-medium">{{ t('app.name') }}</span>
            </p>
            <p class="-mt-3 mb-5 text-sm text-ink-muted">{{ t('auth.register_hint') }}</p>

            <form class="rounded-[10px] border border-rule bg-paper p-5" @submit.prevent="submit">
                <label class="block text-sm text-ink" for="name">{{ t('auth.full_name') }}</label>
                <input id="name" v-model="form.name" required maxlength="160"
                       class="mt-1 w-full rounded-md border border-rule px-3 py-2 text-[16px] outline-none focus:ring-2 focus:ring-brass focus:ring-offset-2" />
                <p v-if="form.errors.name" class="mt-1 text-sm text-lost">{{ form.errors.name }}</p>

                <label class="mt-4 block text-sm text-ink" for="email">{{ t('auth.email') }}</label>
                <input id="email" v-model="form.email" type="email" required
                       class="mt-1 w-full rounded-md border border-rule px-3 py-2 text-[16px] outline-none focus:ring-2 focus:ring-brass focus:ring-offset-2" />
                <p v-if="form.errors.email" class="mt-1 text-sm text-lost">{{ form.errors.email }}</p>

                <label class="mt-4 block text-sm text-ink" for="password">{{ t('auth.password') }}</label>
                <input id="password" v-model="form.password" type="password" minlength="12" required
                       class="mt-1 w-full rounded-md border border-rule px-3 py-2 text-[16px] outline-none focus:ring-2 focus:ring-brass focus:ring-offset-2" />
                <p v-if="form.errors.password" class="mt-1 text-sm text-lost">{{ form.errors.password }}</p>

                <label class="mt-4 block text-sm text-ink" for="password_confirmation">{{ t('auth.repeat_password') }}</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" required
                       class="mt-1 w-full rounded-md border border-rule px-3 py-2 text-[16px] outline-none focus:ring-2 focus:ring-brass focus:ring-offset-2" />

                <button type="submit" :disabled="form.processing"
                        class="mt-5 w-full rounded-md bg-buckram px-3 py-2 font-medium text-paper outline-none hover:bg-buckram-deep focus:ring-2 focus:ring-brass focus:ring-offset-2 disabled:opacity-60">
                    {{ t('auth.create_account') }}
                </button>
            </form>

            <p class="mt-4 text-center text-sm text-ink-muted">
                {{ t('auth.already_registered') }}
                <a href="/login" class="font-medium text-buckram hover:underline">{{ t('auth.sign_in') }}</a>
            </p>
        </div>
    </div>
</template>
