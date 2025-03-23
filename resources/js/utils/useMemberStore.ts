import { ref, reactive } from 'vue';
import { defineStore } from 'pinia';
import { getCurrentOrganizationId } from './useUser';
import { useNotificationsStore } from './notification';
import { api } from '@/packages/api/src';
import { MemberDetailResponse, MemberProjectsResponse, MemberTimeEntriesResponse, MemberBankAccountsResponse } from '@/packages/api/src';

export const useMemberStore = defineStore('member', () => {
  // State tanımları
  const memberDetail = ref<any>(null);
  const memberProjects = ref<any[]>([]);
  const memberTimeEntries = ref<any[]>([]);
  const memberBankAccounts = ref<any[]>([]);
  
  const loading = reactive({
    detail: false,
    projects: false,
    timeEntries: false,
    bankAccounts: false
  });
  
  const error = reactive({
    detail: null as string | null,
    projects: null as string | null,
    timeEntries: null as string | null,
    bankAccounts: null as string | null
  });

  // Bildirimleri yönetmek için
  const { handleApiRequestNotifications } = useNotificationsStore();

  /**
   * JSON formatında bir memberId string'inden gerçek ID'yi çıkarır
   * @param memberId - String formatında üye ID'si veya JSON nesnesi
   * @returns String formatında temiz üye ID'si
   */
  function extractMemberId(memberId: string): string {
    try {
      // Eğer memberId bir JSON string ise parse et ve ID'yi çıkar
      if (memberId && typeof memberId === 'string' && memberId.includes('{')) {
        const parsedMember = JSON.parse(memberId);
        return parsedMember.id || memberId;
      }
      // Değilse olduğu gibi kullan
      return memberId;
    } catch (error) {
      console.error('Üye ID işleme hatası:', error);
      return memberId;
    }
  }

  /**
   * Üye detaylarını getirir
   * @param memberId - Üye ID'si
   */
  async function fetchMemberDetail(memberId: string) {
    // ID'yi temizle
    const cleanMemberId = extractMemberId(memberId);
    
    loading.detail = true;
    error.detail = null;
    
    const organization = getCurrentOrganizationId();
    if (!organization) {
      error.detail = "Organizasyon bilgisi bulunamadı";
      loading.detail = false;
      return;
    }
 
    
    try {
      const response = await handleApiRequestNotifications<MemberDetailResponse>(
        () => api.getMemberDetails({
          params: { 
            organization,
            member: cleanMemberId
          }
        }),
        undefined,
        'Üye bilgileri yüklenirken bir hata oluştu'
      );
      
      // Başarılı istek sonrası log
      console.log('Üye detay isteği tamamlandı:', {
        status: 'success',
        time: new Date().toISOString(),
        memberId: cleanMemberId,
        dataReceived: !!response?.data
      });
      
      memberDetail.value = response?.data;
    } catch (err: any) {
      // Hata durumunda log
      console.log('Üye detay isteği başarısız:', {
        status: 'error',
        time: new Date().toISOString(),
        memberId: cleanMemberId,
        error: err.message
      });
      
      error.detail = err.message || "Üye bilgileri yüklenirken bir hata oluştu";
      console.error("API hatası:", err);
    } finally {
      loading.detail = false;
    }
  }

  /**
   * Üyenin projelerini getirir
   * @param memberId - Üye ID'si
   */
  async function fetchMemberProjects(memberId: string) {
    // ID'yi temizle
    const cleanMemberId = extractMemberId(memberId);
    
    loading.projects = true;
    error.projects = null;
    
    const organization = getCurrentOrganizationId();
    if (!organization) {
      error.projects = "Organizasyon bilgisi bulunamadı";
      loading.projects = false;
      return;
    }
    
    // İstek öncesi log
    console.log('Üye projeleri isteği yapılıyor:', {
      endpoint: `/api/v1/organizations/${organization}/members/${cleanMemberId}/projects`,
      time: new Date().toISOString(),
      memberId: cleanMemberId
    });
    
    try {
      const response = await handleApiRequestNotifications<MemberProjectsResponse>(
        () => api.getMemberProjects({
          params: { 
            organization,
            member: cleanMemberId
          }
        }),
        undefined,
        'Projeler yüklenirken bir hata oluştu'
      );
      
      // Başarılı istek sonrası log
      console.log('Üye projeleri isteği tamamlandı:', {
        status: 'success',
        time: new Date().toISOString(),
        memberId: cleanMemberId,
        projectCount: response?.data?.length || 0
      });
      
      memberProjects.value = response?.data || [];
    } catch (err: any) {
      // Hata durumunda log
      console.log('Üye projeleri isteği başarısız:', {
        status: 'error',
        time: new Date().toISOString(),
        memberId: cleanMemberId,
        error: err.message
      });
      
      error.projects = err.message || "Projeler yüklenirken bir hata oluştu";
      console.error("API hatası:", err);
    } finally {
      loading.projects = false;
    }
  }

  /**
   * Üyenin zaman kayıtlarını getirir
   * @param memberId - Üye ID'si
   * @param startDate - Başlangıç tarihi
   * @param endDate - Bitiş tarihi
   */
  async function fetchMemberTimeEntries(memberId: string, startDate: string, endDate: string) {
    // ID'yi temizle
    const cleanMemberId = extractMemberId(memberId);
    
    loading.timeEntries = true;
    error.timeEntries = null;
    
    const organization = getCurrentOrganizationId();
    if (!organization) {
      error.timeEntries = "Organizasyon bilgisi bulunamadı";
      loading.timeEntries = false;
      return;
    }
    
    // İstek öncesi log
    console.log('Üye zaman kayıtları isteği yapılıyor:', {
      endpoint: `/api/v1/organizations/${organization}/members/${cleanMemberId}/time-entries`,
      time: new Date().toISOString(),
      memberId: cleanMemberId,
      startDate,
      endDate
    });
    
    try {
      const response = await handleApiRequestNotifications<MemberTimeEntriesResponse>(
        () => api.getMemberTimeEntries({
          params: { 
            organization,
            member: cleanMemberId
          },
          queries: {
            start: startDate,
            end: endDate
          }
        }),
        undefined,
        'Zaman kayıtları yüklenirken bir hata oluştu'
      );
      
      // Başarılı istek sonrası log
      console.log('Üye zaman kayıtları isteği tamamlandı:', {
        status: 'success',
        time: new Date().toISOString(),
        memberId: cleanMemberId,
        entriesCount: response?.data?.length || 0
      });
      
      memberTimeEntries.value = response?.data || [];
    } catch (err: any) {
      // Hata durumunda log
      console.log('Üye zaman kayıtları isteği başarısız:', {
        status: 'error',
        time: new Date().toISOString(),
        memberId: cleanMemberId,
        error: err.message
      });
      
      error.timeEntries = err.message || "Zaman kayıtları yüklenirken bir hata oluştu";
      console.error("API hatası:", err);
    } finally {
      loading.timeEntries = false;
    }
  }

  /**
   * Üyenin banka hesaplarını getirir
   * @param memberId - Üye ID'si
   */
  async function fetchMemberBankAccounts(memberId: string) {
    // ID'yi temizle
    const cleanMemberId = extractMemberId(memberId);
    
    loading.bankAccounts = true;
    error.bankAccounts = null;
    
    const organization = getCurrentOrganizationId();
    if (!organization) {
      error.bankAccounts = "Organizasyon bilgisi bulunamadı";
      loading.bankAccounts = false;
      return;
    }
    

    
    try {
      const response = await handleApiRequestNotifications<MemberBankAccountsResponse>(
        () => api.getMemberBankAccounts({
          params: { 
            organization,
            member: cleanMemberId
          }
        }),
        undefined,
        'Banka hesapları yüklenirken bir hata oluştu'
      );
      
      // TypeScript hatalarını önlemek için as any kullanıyoruz
      // API gerçekte { data: [...] } şeklinde yanıt veriyor
      const apiResponse = response as any;
      
      
      // API response.data içinde geliyor, bunu kullanmalıyız
      memberBankAccounts.value = apiResponse?.data || [];
      
    } catch (err: any) {
      // Hata durumunda log
      console.log('Üye banka hesapları isteği başarısız:', {
        status: 'error',
        time: new Date().toISOString(),
        memberId: cleanMemberId,
        error: err.message
      });
      
      error.bankAccounts = err.message || "Banka hesapları yüklenirken bir hata oluştu";
      console.error("API hatası:", err);
    } finally {
      loading.bankAccounts = false;
    }
  }

  /**
   * Tüm verileri temizler
   */
  function clear() {
    memberDetail.value = null;
    memberProjects.value = [];
    memberTimeEntries.value = [];
    memberBankAccounts.value = [];
  }

  return {
    // State
    memberDetail,
    memberProjects,
    memberTimeEntries,
    memberBankAccounts,
    loading,
    error,
    
    // Actions
    fetchMemberDetail,
    fetchMemberProjects,
    fetchMemberTimeEntries,
    fetchMemberBankAccounts,
    clear
  };
}); 