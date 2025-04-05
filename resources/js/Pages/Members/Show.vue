<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import { UserGroupIcon } from '@heroicons/vue/20/solid';
import { onMounted, ref, computed, watch, onUnmounted } from 'vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import { canViewMemberReports } from '@/utils/permissions';
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon, ClipboardDocumentIcon, CheckIcon } from '@heroicons/vue/20/solid';
import { formatDate, calculateDuration } from '@/utils/dateUtils';
import { useMemberStore } from '@/utils/useMemberStore';

// IBAN formatla (TR12 3456 7890 1234 5678 9012 34 şeklinde)
function formatIban(iban: string): string {
  if (!iban) return '';
  return iban.replace(/(.{4})/g, '$1 ').trim();
}

// IBAN kopyalama işlemi
const copiedIban = ref<string | null>(null);
function copyIbanToClipboard(iban: string) {
  const cleanIban = iban.replace(/\s/g, ''); // Boşlukları temizle
  navigator.clipboard.writeText(cleanIban)
    .then(() => {
      copiedIban.value = iban;
      setTimeout(() => {
        copiedIban.value = null;
      }, 2000);
    })
    .catch(err => {
      console.error('IBAN kopyalama hatası:', err);
    });
}

const props = defineProps<{
  memberId: string;
}>();

// Tab durumunu takip etmek için
const activeTab = ref<'profile' | 'projects' | 'time-entries'>('profile');

// Member store'unu kullan
const memberStore = useMemberStore();

// Eğer memberId bir JSON string ise, düzgün ID'yi çıkart
const memberIdValue = computed(() => {
  try {
    // Eğer memberId bir JSON string ise parse et ve ID'yi çıkar
    if (props.memberId && props.memberId.includes('{')) {
      const parsedMember = JSON.parse(props.memberId);
      return parsedMember.id || props.memberId;
    }
    // Değilse olduğu gibi kullan
    return props.memberId;
  } catch (error) {
    console.error('Üye ID işleme hatası:', error);
    return props.memberId;
  }
});

// Tarih filtreleri
const startDate = ref<string>(new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0]);
const endDate = ref<string>(new Date().toISOString().split('T')[0]);

// Tab değişikliği izleyicisi
function changeTab(tab: 'profile' | 'projects' | 'time-entries') {
  activeTab.value = tab;
  
  if (tab === 'projects' && !memberStore.memberProjects.length) {
    memberStore.fetchMemberProjects(memberIdValue.value);
  } else if (tab === 'time-entries' && !memberStore.memberTimeEntries.length && canViewMemberReports()) {
    memberStore.fetchMemberTimeEntries(memberIdValue.value, startDate.value, endDate.value);
  }
}

// Tarih filtreleriyle zaman kayıtlarını yeniden yükle
function applyDateFilter() {
  if (activeTab.value === 'time-entries') {
    memberStore.fetchMemberTimeEntries(memberIdValue.value, startDate.value, endDate.value);
  }
}

// Hesaplanan özellikler
const formattedMemberSince = computed(() => {
  if (!memberStore.memberDetail?.created_at) return '--';
  return formatDate(memberStore.memberDetail.created_at);
});

const roleName = computed(() => {
  if (!memberStore.memberDetail) return '--';
  return memberStore.memberDetail.role_label || memberStore.memberDetail.role;
});

const memberStatus = computed(() => {
  if (!memberStore.memberDetail) return '--';
  return memberStore.memberDetail.is_placeholder ? 'Pasif' : 'Aktif';
});

// Component unmount olunca store'u temizle
onUnmounted(() => {
  memberStore.clear();
});

// Component mount edildiğinde üye detaylarını yükle
onMounted(() => {
  // Store'u temizle 
  memberStore.clear();
  
  // Üye detaylarını ve banka hesaplarını yükle
  memberStore.fetchMemberDetail(memberIdValue.value);
  memberStore.fetchMemberBankAccounts(memberIdValue.value);
  
  // Aktif taba göre ilgili verileri yükle
  if (activeTab.value === 'projects') {
    memberStore.fetchMemberProjects(memberIdValue.value);
  } else if (activeTab.value === 'time-entries' && canViewMemberReports()) {
    memberStore.fetchMemberTimeEntries(memberIdValue.value, startDate.value, endDate.value);
  }
});

function isActiveTab(tab: string) {
  return activeTab.value === tab;
}
</script>

<template>
  <AppLayout title="Üye Detayları">
    <template #header>
      <div class="flex items-center">
        <nav class="flex" aria-label="Breadcrumb">
          <ol role="list" class="flex items-center space-x-2">
            <li>
              <div class="flex items-center space-x-6">
                <Link :href="route('members')" class="flex items-center space-x-2.5">
                  <UserGroupIcon class="w-6 text-icon-default"></UserGroupIcon>
                  <span class="font-medium">Üyeler</span>
                </Link>
              </div>
            </li>
            <li>
              <div class="flex items-center space-x-3 text-white font-bold text-base">
                <ChevronRightIcon class="h-5 w-5 flex-shrink-0 text-muted" aria-hidden="true" />
                <span>{{ memberStore.memberDetail?.name || 'Üye Detayları' }}</span>
              </div>
            </li>
          </ol>
        </nav>
      </div>
      
      <TabBar class="mt-6">
        <TabBarItem :active="isActiveTab('profile')" @click="changeTab('profile')">Profil</TabBarItem>
        <TabBarItem :active="isActiveTab('projects')" @click="changeTab('projects')">Projeler</TabBarItem>
        <TabBarItem v-if="canViewMemberReports()" :active="isActiveTab('time-entries')" @click="changeTab('time-entries')">Zaman Kayıtları</TabBarItem>
      </TabBar>
    </template>
    
    <!-- Yükleniyor durumu -->
    <MainContainer v-if="memberStore.loading.detail">
      <div class="text-center py-8">
        <p class="text-lg text-muted">Üye bilgileri yükleniyor...</p>
      </div>
    </MainContainer>
    
    <!-- Hata durumu -->
    <MainContainer v-else-if="memberStore.error.detail">
      <div class="text-center py-8 text-red-400">
        <p class="text-lg">{{ memberStore.error.detail }}</p>
        <button 
          @click="memberStore.fetchMemberDetail(memberIdValue)" 
          class="mt-4 px-4 py-2 bg-default-background-active rounded hover:bg-card-background-active"
        >
          Yeniden Dene
        </button>
      </div>
    </MainContainer>
    
    <!-- Ana içerik -->
    <MainContainer v-else-if="memberStore.memberDetail">
      <!-- Profil Bilgileri -->
      <div v-if="activeTab === 'profile'" class="pt-4">
        <div class="bg-card-background border border-card-border rounded-md shadow-sm overflow-hidden mb-6">
          <div class="px-4 py-5 sm:px-6 border-b border-card-border">
            <h3 class="text-lg font-medium leading-6 text-white">Üye Bilgileri</h3>
            <p class="mt-1 max-w-2xl text-sm text-muted">Kişisel ve hesap bilgileri.</p>
          </div>
          <div class="px-4 py-5 sm:px-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
              <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted">Ad Soyad</dt>
                <dd class="mt-1 text-sm text-white">{{ memberStore.memberDetail.name }}</dd>
              </div>
              <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted">E-posta</dt>
                <dd class="mt-1 text-sm text-white">{{ memberStore.memberDetail.email }}</dd>
              </div>
              <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted">Rol</dt>
                <dd class="mt-1 text-sm text-white">{{ roleName }}</dd>
              </div>
              <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted">Durum</dt>
                <dd class="mt-1 text-sm text-white">{{ memberStatus }}</dd>
              </div>
              <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted">Faturalandırma Oranı</dt>
                <dd class="mt-1 text-sm text-white">
                  {{ memberStore.memberDetail.billable_rate ? memberStore.memberDetail.billable_rate + ' / saat' : '--' }}
                </dd>
              </div>
              <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-muted">Katılım Tarihi</dt>
                <dd class="mt-1 text-sm text-white">{{ formattedMemberSince }}</dd>
              </div>
              
              <!-- Banka Bilgileri -->
              <div class="sm:col-span-2 mt-4">
                <dt class="text-sm font-medium text-muted">Banka Bilgileri</dt>
                
                <!-- Yükleniyor durumu -->
                <dd v-if="memberStore.loading.bankAccounts" class="mt-2">
                  <p class="text-sm text-muted">Banka bilgileri yükleniyor...</p>
                </dd>
                
                <!-- Hata durumu -->
                <dd v-else-if="memberStore.error.bankAccounts" class="mt-2">
                  <p class="text-sm text-red-400">{{ memberStore.error.bankAccounts }}</p>
                  <button 
                    @click="memberStore.fetchMemberBankAccounts(memberIdValue)" 
                    class="mt-2 px-3 py-1 bg-default-background-active rounded text-xs hover:bg-card-background-active"
                  >
                    Yeniden Dene
                  </button>
                </dd>
                
                <!-- Banka hesapları listesi -->
                <dd v-else-if="memberStore.memberBankAccounts.length > 0" class="mt-2">
                  <div class="space-y-4">
                    <div v-for="account in memberStore.memberBankAccounts" :key="account.id" 
                         class="bg-card-background rounded-lg p-4 border border-card-border shadow-sm transition-all hover:shadow-md">
                      <div class="flex items-center gap-4">
                        <div v-if="account.bank && account.bank.logo_path" class="flex-shrink-0 w-12 h-12 bg-default-background rounded-md p-1 flex items-center justify-center">
                          <img :src="'/' + account.bank.logo_path" :alt="account.bank.name" class="w-10 h-10 object-contain" />
                        </div>
                        <div class="flex-grow">
                          <div class="flex items-center gap-2 mb-1">
                            <h4 class="text-sm font-medium text-white">{{ account.bank?.name || 'Banka' }}</h4>
                            <span v-if="account.is_default" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary text-white">
                              Varsayılan
                            </span>
                          </div>
                          <div class="grid grid-cols-1 md:grid-cols-2 gap-y-2 gap-x-4">
                            <div v-if="account.account_name" class="text-xs">
                              <span class="text-muted">Hesap Adı:</span>
                              <span class="text-white ml-1 font-medium">{{ account.account_name }}</span>
                            </div>
                            <div v-if="account.iban" class="text-xs flex items-center">
                              <span class="text-muted">IBAN:</span>
                              <span class="text-white ml-1 font-mono flex-1">{{ account.iban }}</span>
                              <button 
                                @click="copyIbanToClipboard(account.iban)" 
                                class="ml-2 p-1 rounded-md bg-default-background hover:bg-default-background-active transition-colors" 
                                title="IBAN'ı Kopyala"
                              >
                                <CheckIcon v-if="copiedIban === account.iban" class="h-4 w-4 text-green-500" />
                                <ClipboardDocumentIcon v-else class="h-4 w-4 text-muted hover:text-white" />
                              </button>
                            </div>
                            <div v-if="account.account_number" class="text-xs">
                              <span class="text-muted">Hesap No:</span>
                              <span class="text-white ml-1 font-mono">{{ account.account_number }}</span>
                            </div>
                            <div v-if="account.branch_code" class="text-xs">
                              <span class="text-muted">Şube Kodu:</span>
                              <span class="text-white ml-1 font-mono">{{ account.branch_code }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </dd>
                
                <!-- Hesap yoksa -->
                <dd v-else class="mt-2">
                  <p class="text-sm text-muted bg-card-background rounded-lg p-4 text-center">Henüz banka hesabı bulunmuyor.</p>
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
      
      <!-- Projeler -->
      <div v-if="activeTab === 'projects'" class="pt-4">
        <div class="bg-card-background border border-card-border rounded-md shadow-sm overflow-hidden">
          <div class="px-4 py-5 sm:px-6 border-b border-card-border">
            <h3 class="text-lg font-medium leading-6 text-white">Üyenin Projeleri</h3>
            <p class="mt-1 max-w-2xl text-sm text-muted">Üyenin çalıştığı projeler.</p>
          </div>
          <div class="px-4 py-5 sm:px-6">
            <!-- Yükleniyor durumu -->
            <div v-if="memberStore.loading.projects" class="text-center py-8">
              <p class="text-lg text-muted">Projeler yükleniyor...</p>
            </div>
            
            <!-- Hata durumu -->
            <div v-else-if="memberStore.error.projects" class="text-center py-8 text-red-400">
              <p class="text-lg">{{ memberStore.error.projects }}</p>
              <button 
                @click="memberStore.fetchMemberProjects(memberIdValue)" 
                class="mt-4 px-4 py-2 bg-default-background-active rounded hover:bg-card-background-active"
              >
                Yeniden Dene
              </button>
            </div>
            
            <!-- Projeler tablosu -->
            <div v-else-if="memberStore.memberProjects.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-card-border">
                <thead>
                  <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                      Proje Adı
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                      Müşteri
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                      Faturalandırma Oranı
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                      Katılım Tarihi
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-card-border">
                  <tr v-for="project in memberStore.memberProjects" :key="project.id" class="hover:bg-card-background-active">
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-white">
                      <Link :href="route('projects.show', project.id)" class="text-primary-600 hover:text-primary-500">
                        {{ project.name }}
                      </Link>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-white">
                      {{ project.client?.name || '--' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-white">
                      {{ project.billable_rate ? project.billable_rate + ' / saat' : '--' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-white">
                      {{ project.created_at ? formatDate(project.created_at) : '--' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <!-- Veri yok durumu -->
            <div v-else class="text-center py-8">
              <p class="text-muted text-sm">Bu üye henüz hiçbir projede yer almıyor.</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Zaman Kayıtları -->
      <div v-if="activeTab === 'time-entries' && canViewMemberReports()" class="pt-4">
        <div class="bg-card-background border border-card-border rounded-md shadow-sm overflow-hidden">
          <div class="px-4 py-5 sm:px-6 border-b border-card-border">
            <h3 class="text-lg font-medium leading-6 text-white">Zaman Kayıtları</h3>
            <p class="mt-1 max-w-2xl text-sm text-muted">Üyenin zaman kayıtları.</p>
            
            <!-- Tarih filtresi -->
            <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-4">
              <div class="flex items-center space-x-2">
                <label for="start-date" class="text-sm text-muted">Başlangıç:</label>
                <input 
                  type="date" 
                  id="start-date" 
                  v-model="startDate" 
                  class="rounded-md border border-input-border bg-input-background text-white text-sm px-3 py-1.5 w-40" 
                />
              </div>
              <div class="flex items-center space-x-2">
                <label for="end-date" class="text-sm text-muted">Bitiş:</label>
                <input 
                  type="date" 
                  id="end-date" 
                  v-model="endDate" 
                  class="rounded-md border border-input-border bg-input-background text-white text-sm px-3 py-1.5 w-40" 
                />
              </div>
              <button 
                @click="applyDateFilter" 
                class="px-3 py-1.5 bg-primary-700 text-white text-sm rounded-md hover:bg-primary-600"
              >
                Filtrele
              </button>
            </div>
          </div>
          <div class="px-4 py-5 sm:px-6">
            <!-- Yükleniyor durumu -->
            <div v-if="memberStore.loading.timeEntries" class="text-center py-8">
              <p class="text-lg text-muted">Zaman kayıtları yükleniyor...</p>
            </div>
            
            <!-- Hata durumu -->
            <div v-else-if="memberStore.error.timeEntries" class="text-center py-8 text-red-400">
              <p class="text-lg">{{ memberStore.error.timeEntries }}</p>
              <button 
                @click="memberStore.fetchMemberTimeEntries(memberIdValue, startDate, endDate)" 
                class="mt-4 px-4 py-2 bg-default-background-active rounded hover:bg-card-background-active"
              >
                Yeniden Dene
              </button>
            </div>
            
            <!-- Zaman kayıtları tablosu -->
            <div v-else-if="memberStore.memberTimeEntries.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-card-border">
                <thead>
                  <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                      Açıklama
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                      Proje
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                      Başlangıç
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                      Bitiş
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                      Süre
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-card-border">
                  <tr v-for="entry in memberStore.memberTimeEntries" :key="entry.id" class="hover:bg-card-background-active">
                    <td class="px-4 py-3 text-sm text-white">
                      {{ entry.description || '--' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-white">
                      {{ entry.project?.name || '--' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-white">
                      {{ entry.start ? formatDate(entry.start, true) : '--' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-white">
                      {{ entry.end ? formatDate(entry.end, true) : 'Devam ediyor' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-white">
                      {{ calculateDuration(entry.start, entry.end) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <!-- Veri yok durumu -->
            <div v-else class="text-center py-8">
              <p class="text-muted text-sm">Seçilen tarih aralığında zaman kaydı bulunmamaktadır.</p>
            </div>
          </div>
        </div>
      </div>
    </MainContainer>
    
    <!-- Veri bulunamadı durumu -->
    <MainContainer v-else class="pt-4">
      <div class="text-center py-8">
        <p class="text-lg text-muted">Üye bilgileri bulunamadı.</p>
      </div>
    </MainContainer>
  </AppLayout>
</template> 