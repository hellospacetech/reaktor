<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { twMerge } from 'tailwind-merge';

const props = defineProps<{
    name?: string;
    class?: string;
    rows?: number;
    placeholder?: string;
}>();

const textarea = ref<HTMLTextAreaElement | null>(null);

onMounted(() => {
    if (textarea.value?.hasAttribute('autofocus')) {
        textarea.value?.focus();
    }
});

defineExpose({ focus: () => textarea.value?.focus() });
const model = defineModel<string>();
</script>

<template>
    <textarea
        ref="textarea"
        v-model="model"
        :rows="rows || 3"
        :placeholder="placeholder"
        :class="
            twMerge(
                'border-input-border border bg-input-background text-white focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-transparent rounded-md shadow-sm resize-y min-h-[80px] w-full p-2',
                props.class
            )
        "
        :name="name" />
</template> 