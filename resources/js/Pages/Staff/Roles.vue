<script setup>
import { useTrans } from '../../i18n';

const { t } = useTrans();
const props = defineProps({ roles: Array, permissions: Array });
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
            <h1 class="text-[25px] font-medium text-ink">{{ t('admin.roles_title') }}</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ t('roles.description') }}</p>

            <div class="mt-4 overflow-x-auto rounded-[10px] border border-rule bg-paper">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-rule text-ink-muted">
                        <tr>
                            <th class="px-4 py-2 font-medium">{{ t('roles.permission') }}</th>
                            <th v-for="r in roles" :key="r.name" class="px-4 py-2 text-center font-medium">{{ r.name }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in permissions" :key="p" class="border-b border-rule last:border-0">
                            <td class="px-4 py-1.5 font-mono text-xs text-ink">{{ p }}</td>
                            <td v-for="r in roles" :key="r.name" class="px-4 py-1.5 text-center">
                                <span class="text-available" :class="r.permissions.includes(p) ? '' : 'opacity-0'">●</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</template>
