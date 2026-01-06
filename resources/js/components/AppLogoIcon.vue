<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>

    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="className" v-bind="$attrs">
        <!-- Core engine -->
        <circle cx="12" cy="12" r="3" />

        <!-- Exchange arrows -->
        <path d="M7 8h7l-2-2" />
        <path d="M17 16h-7l2 2" />

        <!-- Flow connectors -->
        <path d="M5 12a7 7 0 0 1 7-7" />
        <path d="M19 12a7 7 0 0 1-7 7" />
    </svg>

</template>
