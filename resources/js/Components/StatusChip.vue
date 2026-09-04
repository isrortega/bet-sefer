<script setup>
import { BookOpenCheck, CircleAlert, Hammer, Package, Route, UserRound, Wrench } from 'lucide-vue-next';
import { computed } from 'vue';
import { useTrans } from '../i18n';

const props = defineProps({
    status: { type: String, required: true },
});

const { t } = useTrans();

const ICONS = {
    available: BookOpenCheck,
    on_loan: UserRound,
    at_reception: Package,
    in_transit: Route,
    in_repair: Hammer,
    lost: CircleAlert,
    reserved: Wrench,
    pending_email: CircleAlert,
    pending_identity: CircleAlert,
    active: BookOpenCheck,
    suspended: CircleAlert,
    closed: CircleAlert,
    overdue: CircleAlert,
    deleted: CircleAlert,
};

const icon = computed(() => ICONS[props.status] ?? CircleAlert);
const label = computed(() => t(`status.${props.status}`));
</script>

<template>
    <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium capitalize"
          :class="{
              'bg-available-bg text-available': status === 'available' || status === 'active',
              'bg-onloan-bg text-onloan': status === 'on_loan',
              'bg-reception-bg text-reception': status === 'at_reception' || status === 'pending_email' || status === 'pending_identity',
              'bg-transit-bg text-transit': status === 'in_transit',
              'bg-repair-bg text-repair': status === 'in_repair',
              'bg-lost-bg text-lost': ['lost', 'suspended', 'overdue', 'closed', 'deleted'].includes(status),
          }">
        <component :is="icon" class="h-3.5 w-3.5" aria-hidden="true" />
        <span>{{ label }}</span>
    </span>
</template>
