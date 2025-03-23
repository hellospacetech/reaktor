import { useProjectsStore } from '@/utils/useProjects';
import { useTasksStore } from '@/utils/useTasks';
import { useTagsStore } from '@/utils/useTags';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { useClientsStore } from '@/utils/useClients';
import { useMembersStore } from '@/utils/useMembers';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import { canViewClients, canViewMembers } from '@/utils/permissions';

export function initializeStores() {
    refreshStores();
}

export function refreshStores() {
    useProjectsStore().fetchProjects();
    useTasksStore().fetchTasks();
    useTagsStore().fetchTags();
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
    useTimeEntriesStore().patchTimeEntries();
    
    // Üye detay sayfasında değilsek tüm üyeleri yükle
    // window.location.pathname ile mevcut URL'yi kontrol ediyoruz
    const currentPath = window.location.pathname;
    const isMemberDetailPage = currentPath.startsWith('/members/') && currentPath !== '/members';
    
    if (canViewMembers() && !isMemberDetailPage) {
        useMembersStore().fetchMembers();
    }
    
    if (canViewClients()) {
        useClientsStore().fetchClients();
    }
}
