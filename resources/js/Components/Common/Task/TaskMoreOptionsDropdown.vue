<script setup lang="ts">
import {
    TrashIcon,
    PencilSquareIcon,
    CheckCircleIcon,
    BeakerIcon,
} from '@heroicons/vue/20/solid';
import type { Task } from '@/packages/api/src';
import { canDeleteTasks, canUpdateTasks, canMarkTaskAsInternalTest, canMarkTaskAsDone } from '@/utils/permissions';
import MoreOptionsDropdown from '@/packages/ui/src/MoreOptionsDropdown.vue';
const emit = defineEmits<{
    delete: [];
    edit: [];
    done: [];
    'internal-test': [];
    active: [];
}>();
const props = defineProps<{
    task: Task;
}>();
</script>

<template>
    <MoreOptionsDropdown :label="'Actions for Task ' + props.task.name">
        <div class="min-w-[150px]">
            <button
                v-if="canUpdateTasks()"
                :aria-label="'Edit Task ' + props.task.name"
                data-testid="task_edit"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click="emit('edit')">
                <PencilSquareIcon
                    class="w-5 text-icon-active"></PencilSquareIcon>
                <span>Edit</span>
            </button>
            <button
                v-if="canMarkTaskAsInternalTest() && props.task.status === 'active'"
                :aria-label="'Mark Task ' + props.task.name + ' as internal test'"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click="emit('internal-test')">
                <BeakerIcon class="w-5 text-icon-active"></BeakerIcon>
                <span>Mark as Internal Test</span>
            </button>
            <button
                v-if="canMarkTaskAsDone() && props.task.status === 'internal_test'"
                :aria-label="'Mark Task ' + props.task.name + ' as done'"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click="emit('done')">
                <CheckCircleIcon class="w-5 text-icon-active"></CheckCircleIcon>
                <span>Mark as Done</span>
            </button>
            <button
                v-if="canMarkTaskAsInternalTest() && props.task.status === 'internal_test'"
                :aria-label="'Mark Task ' + props.task.name + ' as active'"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click="emit('active')">
                <CheckCircleIcon class="w-5 text-icon-active"></CheckCircleIcon>
                <span>Mark as Active</span>
            </button>
            <button
                v-if="canMarkTaskAsInternalTest() && props.task.status === 'done'"
                :aria-label="'Mark Task ' + props.task.name + ' as internal test'"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click="emit('internal-test')">
                <BeakerIcon class="w-5 text-icon-active"></BeakerIcon>
                <span>Mark as Internal Test</span>
            </button>
            <button
                v-if="canUpdateTasks() && props.task.status === 'done'"
                :aria-label="'Mark Task ' + props.task.name + ' as active'"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click="emit('active')">
                <CheckCircleIcon class="w-5 text-icon-active"></CheckCircleIcon>
                <span>Mark as Active</span>
            </button>
            <button
                v-if="canDeleteTasks()"
                :aria-label="'Delete Task ' + props.task.name"
                data-testid="task_delete"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click="emit('delete')">
                <TrashIcon class="w-5 text-icon-active"></TrashIcon>
                <span>Delete</span>
            </button>
        </div>
    </MoreOptionsDropdown>
</template>

<style scoped></style>
