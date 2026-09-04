<script setup>
defineProps({
    name: String,
});
</script>

<template>
    <div class="flex min-h-screen items-center justify-center p-6">
        <div class="max-w-md text-center">
            <h1 class="text-[31px] font-medium text-[#14211F]">Bet-Sefer</h1>
            <p class="mt-2 text-[#55625E]">Library management system · scaffold running</p>
            <p class="mt-8 font-mono text-sm text-[#7C8783]">{{ name ?? 'Hello' }}</p>
        </div>
    </div>
</template>
