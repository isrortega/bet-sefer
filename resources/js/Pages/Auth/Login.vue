<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    ssoEnabled: Boolean,
    googleUrl: String,
});

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
    <div class="flex min-h-screen items-center justify-center bg-[#F5F6F4] p-4">
        <div class="w-full max-w-sm">
            <p class="mb-6 text-[#14211F]">
                <span class="font-serif text-[31px] font-medium">Bet-Sefer</span>
                <span class="ml-2 inline-block h-5 w-px translate-y-1 bg-[#14543F]" aria-hidden="true"></span>
            </p>

            <div v-if="flash.message || flash.error" class="mb-4 rounded-md border px-3 py-2 text-sm"
                 :class="flash.error ? 'border-[#F8E7EA] bg-[#F8E7EA] text-[#8A2B3B]' : 'border-[#E6F1EB] bg-[#E6F1EB] text-[#1E6B4F]'">
                {{ flash.error || flash.message }}
            </div>

            <form class="rounded-[10px] border border-[#DFE2DD] bg-white p-5 shadow-none" @submit.prevent="submit">
                <label class="block text-sm text-[#14211F]" for="email">Email</label>
                <input id="email" v-model="form.email" type="email" required autofocus
                       class="mt-1 w-full rounded-md border border-[#DFE2DD] px-3 py-2 text-[16px] text-[#14211F] outline-none focus:ring-2 focus:ring-[#A8761C] focus:ring-offset-2" />
                <p v-if="form.errors.email" class="mt-1 text-sm text-[#8A2B3B]">{{ form.errors.email }}</p>

                <label class="mt-4 block text-sm text-[#14211F]" for="password">Password</label>
                <input id="password" v-model="form.password" type="password" required
                       class="mt-1 w-full rounded-md border border-[#DFE2DD] px-3 py-2 text-[16px] text-[#14211F] outline-none focus:ring-2 focus:ring-[#A8761C] focus:ring-offset-2" />
                <p v-if="form.errors.password" class="mt-1 text-sm text-[#8A2B3B]">{{ form.errors.password }}</p>

                <label class="mt-4 flex items-center gap-2 text-sm text-[#55625E]">
                    <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-[#DFE2DD]" />
                    Keep me signed in
                </label>

                <button type="submit" :disabled="form.processing"
                        class="mt-5 w-full rounded-md bg-[#14543F] px-3 py-2 font-medium text-white outline-none hover:bg-[#0f4433] focus:ring-2 focus:ring-[#A8761C] focus:ring-offset-2 disabled:opacity-60">
                    Sign in
                </button>
            </form>

            <a v-if="ssoEnabled" :href="googleUrl"
               class="mt-4 flex items-center justify-center gap-2 rounded-md border border-[#DFE2DD] bg-white px-3 py-2 text-sm font-medium text-[#14211F] outline-none hover:bg-[#F5F6F4] focus:ring-2 focus:ring-[#A8761C]">
                Continue with Google
            </a>

            <p class="mt-4 text-center text-sm text-[#55625E]">
                No account?
                <a :href="'/register'" class="font-medium text-[#14543F] hover:underline">Create one</a>
            </p>
        </div>
    </div>
</template>
