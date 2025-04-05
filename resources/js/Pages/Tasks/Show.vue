<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import { Link, Head, router } from '@inertiajs/vue3';
import { onMounted, ref, computed, onUnmounted } from 'vue';
import LoadingSpinner from '@/packages/ui/src/LoadingSpinner.vue';
import { currentUserHasPermission } from '@/utils/permissions';
import { formatHumanReadableDuration } from '@/packages/ui/src/utils/time';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import TaskEditModal from '@/Components/Common/Task/TaskEditModal.vue';
import { CheckCircleIcon, BeakerIcon, ArrowPathIcon, ClipboardDocumentIcon } from '@heroicons/vue/20/solid';
import { formatDate } from '@/utils/dateUtils';
import { useTasksStore } from '@/utils/useTasks';
import type { Task } from '@/packages/api/src';

const props = defineProps<{
    id: number;
}>();

const showEditModal = ref(false);
const taskStore = useTasksStore();

// Kendi lokal state'lerimizi tanımlayalım
const loading = ref(true);
const error = ref<string | null>(null);
const taskDetail = ref<Task | null>(null);

// İzin kontrolleri
function canViewTaskDetails(): boolean {
    return currentUserHasPermission('tasks:view') || currentUserHasPermission('tasks:view:details');
}

function canUpdateTask(): boolean {
    return currentUserHasPermission('tasks:update');
}

// Görev detaylarını getiren fonksiyon
async function fetchTaskDetails() {
    loading.value = true;
    error.value = null;
    
    try {
        // getTask string türünde parametre bekliyor
        const task = await taskStore.getTask(props.id.toString());
        taskDetail.value = task;
    } catch (err: any) {
        console.error('Görev detayı yüklenirken hata oluştu:', err);
        error.value = err.message || 'Görev detayları yüklenirken bir hata oluştu.';
    } finally {
        loading.value = false;
    }
}

// Component unmount olunca state'i temizle
onUnmounted(() => {
    taskDetail.value = null;
    loading.value = false;
    error.value = null;
});

// Component mount edildiğinde görev detaylarını yükle
onMounted(async () => {
    if (!canViewTaskDetails()) {
        router.visit('/tasks');
        return;
    }
    
    // Görev detaylarını yükle
    await fetchTaskDetails();
});
</script>

<template>
    <AppLayout title="Görev Detayı">
        <Head title="Görev Detayı" />
        
        <!-- Yükleniyor durumu -->
        <MainContainer v-if="loading" class="pt-6">
            <div class="flex justify-center py-8">
                <LoadingSpinner class="w-10 h-10" />
                <p class="ml-3 text-lg text-muted">Görev detayları yükleniyor...</p>
            </div>
        </MainContainer>
        
        <!-- Hata durumu -->
        <MainContainer v-else-if="error" class="pt-6">
            <div class="bg-card-background rounded-lg shadow p-6 text-center">
                <p class="text-red-400 text-lg mb-4">{{ error }}</p>
                <button 
                    @click="fetchTaskDetails()" 
                    class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-md"
                >
                    <ArrowPathIcon class="w-5 h-5 mr-2" />
                    Yeniden Dene
                </button>
            </div>
        </MainContainer>
        
        <!-- Ana içerik -->
        <template v-else-if="taskDetail">
            <div class="py-6">
                <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div class="bg-card-background shadow rounded-lg overflow-hidden">
                        <!-- Başlık ve Butonlar -->
                        <div class="flex justify-between items-center p-6 border-b border-card-border">
                            <h1 class="text-2xl font-semibold text-white">{{ taskDetail.name }}</h1>
                            
                            <div class="flex space-x-3">
                                <Link :href="route('projects.show', taskDetail.project_id)" class="inline-flex items-center text-blue-400 hover:underline">
                                    Projeye Dön
                                </Link>
                                
                                <SecondaryButton v-if="canUpdateTask()" @click="showEditModal = true">
                                    Düzenle
                                </SecondaryButton>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <!-- Durum Bilgisi -->
                            <div class="mb-6 flex items-center space-x-2 p-4 rounded-lg bg-card-background-hover">
                                <CheckCircleIcon v-if="taskDetail.status === 'done'" class="w-6 h-6 text-green-500" />
                                <BeakerIcon v-else-if="taskDetail.status === 'internal_test'" class="w-6 h-6 text-blue-500" />
                                <span class="text-muted">Durum: </span>
                                <span class="text-white text-lg font-medium">{{ taskDetail.status_label }}</span>
                            </div>
                            
                            <!-- Açıklama -->
                            <div v-if="taskDetail.description" class="mb-6">
                                <h2 class="text-lg font-medium mb-2 text-white flex items-center">
                                    <ClipboardDocumentIcon class="w-5 h-5 mr-2 text-muted" />
                                    Açıklama
                                </h2>
                                <div class="bg-card-background-hover p-4 rounded-md text-white whitespace-pre-wrap">
                                    {{ taskDetail.description }}
                                </div>
                            </div>
                            
                            <!-- Zaman Bilgileri -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="bg-card-background-hover p-4 rounded-md">
                                    <h2 class="text-lg font-medium mb-2 text-white">Harcanan Zaman</h2>
                                    <p class="text-white">
                                        {{ taskDetail.spent_time ? formatHumanReadableDuration(taskDetail.spent_time) : 'Henüz zaman kaydı yok' }}
                                    </p>
                                </div>
                                
                                <div class="bg-card-background-hover p-4 rounded-md">
                                    <h2 class="text-lg font-medium mb-2 text-white">Tahmini Zaman</h2>
                                    <p class="text-white">
                                        {{ taskDetail.estimated_time ? formatHumanReadableDuration(taskDetail.estimated_time) : 'Tahmini zaman belirlenmemiş' }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Oluşturulma ve Güncellenme Tarihleri -->
                            <div class="text-sm text-muted mt-6">
                                <div>Oluşturulma Tarihi: {{ formatDate(taskDetail.created_at, true) }}</div>
                                <div>Son Güncelleme: {{ formatDate(taskDetail.updated_at, true) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Düzenleme Modal -->
            <TaskEditModal 
                v-model:show="showEditModal" 
                :task="taskDetail"></TaskEditModal>
        </template>
    </AppLayout>
</template>

<style scoped>
</style> 