<script setup lang="ts">
import type { Task } from '@/packages/api/src';
import { CheckCircleIcon, BeakerIcon } from '@heroicons/vue/20/solid';
import { useTasksStore } from '@/utils/useTasks';
import TaskMoreOptionsDropdown from '@/Components/Common/Task/TaskMoreOptionsDropdown.vue';
import TableRow from '@/Components/TableRow.vue';
import { canDeleteTasks, canUpdateTasks, canMarkTaskAsInternalTest, canMarkTaskAsDone } from '@/utils/permissions';
import TaskEditModal from '@/Components/Common/Task/TaskEditModal.vue';
import { ref } from 'vue';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import EstimatedTimeProgress from '@/packages/ui/src/EstimatedTimeProgress.vue';
import UpgradeBadge from '@/Components/Common/UpgradeBadge.vue';
import { formatHumanReadableDuration } from '../../../packages/ui/src/utils/time';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    task: Task;
}>();

function deleteTask() {
    useTasksStore().deleteTask(props.task.id);
}

function markTaskAsDone() {
    if (props.task.status === 'internal_test') {
        useTasksStore().updateTaskStatus(props.task.id, 'done');
    }
}

function markTaskAsActive() {
    if (props.task.status === 'internal_test' || props.task.status === 'done') {
        useTasksStore().updateTaskStatus(props.task.id, 'active');
    }
}

function markTaskAsInternalTest() {
    if (props.task.status === 'active' || props.task.status === 'done') {
        useTasksStore().updateTaskStatus(props.task.id, 'internal_test');
    }
}

const showTaskEditModal = ref(false);
</script>

<template>
    <TableRow>
        <div
            class="whitespace-nowrap min-w-0 flex items-center space-x-2 3xl:pl-12 py-4 pr-3 text-sm font-medium text-white pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            <div class="flex items-center space-x-1 overflow-hidden">
                <Link :href="route('tasks.show', task.id)" class="truncate max-w-[200px] hover:text-blue-400 hover:underline">
                    {{ task.name }}
                </Link>
            </div>
        </div>
        <div
            class="whitespace-nowrap px-3 py-4 text-sm text-muted flex space-x-1 items-center font-medium">
            <span v-if="task.spent_time">
                {{ formatHumanReadableDuration(task.spent_time) }}
            </span>
            <span v-else> -- </span>
        </div>
        <div
            class="whitespace-nowrap px-3 flex items-center text-sm text-muted">
            <UpgradeBadge
                v-if="!isAllowedToPerformPremiumAction()"></UpgradeBadge>
            <EstimatedTimeProgress
                v-else-if="task.estimated_time"
                :estimated="task.estimated_time"
                :current="task.spent_time"></EstimatedTimeProgress>
            <span v-else> -- </span>
        </div>
        <div
            class="whitespace-nowrap px-3 py-4 text-sm text-muted flex space-x-1 items-center font-medium">
            <template v-if="task.status === 'done'">
                <CheckCircleIcon class="w-5"></CheckCircleIcon>
                <span>{{ task.status_label }}</span>
            </template>
            <template v-else-if="task.status === 'internal_test'">
                <BeakerIcon class="w-5"></BeakerIcon>
                <span>{{ task.status_label }}</span>
            </template>
            <template v-else>
                <span>{{ task.status_label }}</span>
            </template>
        </div>
        <div
            class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <TaskMoreOptionsDropdown
                v-if="canUpdateTasks() || canMarkTaskAsInternalTest() || canMarkTaskAsDone() || canDeleteTasks()"
                :task="task"
                @internal-test="markTaskAsInternalTest"
                @done="markTaskAsDone"
                @active="markTaskAsActive"
                @edit="showTaskEditModal = true"
                @delete="deleteTask"></TaskMoreOptionsDropdown>
        </div>
        <TaskEditModal
            v-model:show="showTaskEditModal"
            :task="task"></TaskEditModal>
    </TableRow>
</template>

<style scoped></style>
