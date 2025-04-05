<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import TextareaInput from '@/packages/ui/src/Input/TextareaInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref } from 'vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useTasksStore } from '@/utils/useTasks';
import type { Task, UpdateTaskBody } from '@/packages/api/src';
import EstimatedTimeSection from '@/packages/ui/src/EstimatedTimeSection.vue';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';

const { updateTask } = useTasksStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    task: Task;
}>();

const taskBody = ref<UpdateTaskBody>({
    name: props.task.name,
    description: props.task.description || '',
    estimated_time: props.task.estimated_time,
});

const description = ref<string>(typeof props.task.description === 'string' ? props.task.description : '');

async function submit() {
    taskBody.value.description = description.value;
    await updateTask(props.task.id, taskBody.value);
    show.value = false;
}

const taskNameInput = ref<HTMLInputElement | null>(null);

useFocus(taskNameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Update Task </span>
            </div>
        </template>

        <template #content>
            <div class="space-y-4">
                <div class="flex items-center space-x-4">
                    <div class="col-span-6 sm:col-span-4 flex-1">
                        <TextInput
                            id="taskName"
                            ref="taskNameInput"
                            v-model="taskBody.name"
                            type="text"
                            placeholder="Task Name"
                            class="mt-1 block w-full"
                            required
                            autocomplete="taskName" />
                    </div>
                </div>
                
                <div class="flex flex-col">
                    <label for="taskDescription" class="text-sm text-muted mb-1">Task Description</label>
                    <TextareaInput
                        id="taskDescription"
                        v-model="description"
                        placeholder="Enter task details..."
                        class="mt-1 block w-full"
                        :rows="4" />
                </div>
                
                <EstimatedTimeSection
                    v-if="isAllowedToPerformPremiumAction()"
                    v-model="taskBody.estimated_time"
                    @submit="submit()"></EstimatedTimeSection>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel </SecondaryButton>
            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Update Task
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
