<script setup>
import { useForm } from '@inertiajs/vue3';

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
    <div class="flex min-h-screen items-center justify-center bg-[#F5F6F4] p-4">
        <div class="w-full max-w-sm">
            <p class="mb-6 text-[#14211F]">
                <span class="font-serif text-[31px] font-medium">Bet-Sefer</span>
            </p>
            <p class="-mt-3 mb-5 text-sm text-[#55625E]">
                Create a reader account. Borrowing activates after your identity is verified at the front desk.
            </p>

            <form class="rounded-[10px] border border-[#DFE2DD] bg-white p-5" @submit.prevent="submit">
                <label class="block text-sm text-[#14211F]" for="name">Full name</label>
                <input id="name" v-model="form.name" required maxlength="160"
                       class="mt-1 w-full rounded-md border border-[#DFE2DD] px-3 py-2 text-[16px] outline-none focus:ring-2 focus:ring-[#A8761C] focus:ring-offset-2" />
                <p v-if="form.errors.name" class="mt-1 text-sm text-[#8A2B3B]">{{ form.errors.name }}</p>

                <label class="mt-4 block text-sm text-[#14211F]" for="email">Email</label>
                <input id="email" v-model="form.email" type="email" required
                       class="mt-1 w-full rounded-md border border-[#DFE2DD] px-3 py-2 text-[16px] outline-none focus:ring-2 focus:ring-[#A8761C] focus:ring-offset-2" />
                <p v-if="form.errors.email" class="mt-1 text-sm text-[#8A2B3B]">{{ form.errors.email }}</p>

                <label class="mt-4 block text-sm text-[#14211F]" for="password">Password</label>
                <input id="password" v-model="form.password" type="password" minlength="12" required
                       class="mt-1 w-full rounded-md border border-[#DFE2DD] px-3 py-2 text-[16px] outline-none focus:ring-2 focus:ring-[#A8761C] focus:ring-offset-2" />
                <p v-if="form.errors.password" class="mt-1 text-sm text-[#8A2B3B]">{{ form.errors.password }}</p>

                <label class="mt-4 block text-sm text-[#14211F]" for="password_confirmation">Repeat password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" required
                       class="mt-1 w-full rounded-md border border-[#DFE2DD] px-3 py-2 text-[16px] outline-none focus:ring-2 focus:ring-[#A8761C] focus:ring-offset-2" />

                <button type="submit" :disabled="form.processing"
                        class="mt-5 w-full rounded-md bg-[#14543F] px-3 py-2 font-medium text-white outline-none hover:bg-[#0f4433] focus:ring-2 focus:ring-[#A8761C] focus:ring-offset-2 disabled:opacity-60">
                    Create account
                </button>
            </form>

            <p class="mt-4 text-center text-sm text-[#55625E]">
                Already registered?
                <a href="/login" class="font-medium text-[#14543F] hover:underline">Sign in</a>
            </p>
        </div>
    </div>
</template>
