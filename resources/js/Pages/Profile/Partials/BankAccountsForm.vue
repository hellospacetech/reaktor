<template>
    <div>
        <ActionSection>
            <template #title>
                Banka Hesapları
            </template>

            <template #description>
                Banka hesap bilgilerinizi yönetin. Burada yeni banka hesapları ekleyebilir, mevcut hesapları düzenleyebilir veya silebilirsiniz.
            </template>

            <template #content>
                <div v-if="bankAccounts.length > 0">
                    <div class="space-y-6">
                        <div
                            v-for="account in bankAccounts"
                            :key="account.id"
                            class="flex items-center justify-between p-4 bg-card-background rounded-lg transition-colors hover:bg-card-background-active">
                            <div class="flex items-center gap-4">
                                <div v-if="account.bank.logo_path" class="flex-shrink-0">
                                    <img
                                        :src="account.bank.logo_path"
                                        :alt="account.bank.name"
                                        class="h-10 w-auto object-contain"
                                    />
                                </div>
                                <div class="flex-grow">
                                    <div class="flex items-center">
                                        <h3 class="text-sm font-medium text-white">
                                            {{ account.account_name || account.bank.name }}
                                        </h3>
                                        <span
                                            v-if="account.is_default"
                                            class="ml-2 px-2 py-0.5 text-xs rounded-full bg-primary text-white">
                                            Varsayılan
                                        </span>
                                    </div>
                                    <div class="mt-1 text-xs text-muted">
                                        <div>{{ account.bank.name }}</div>
                                        <div class="font-mono">IBAN: {{ formatIban(account.iban) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <SecondaryButton
                                    type="button"
                                    @click="openEditModal(account)"
                                    class="text-xs py-1">
                                    Düzenle
                                </SecondaryButton>
                                <DangerButton
                                    type="button"
                                    @click="openDeleteModal(account)"
                                    class="text-xs py-1">
                                    Sil
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-muted text-center py-3">
                    Henüz banka hesabı eklenmemiş.
                </div>

                <div class="mt-5">
                    <div class="flex justify-end">
                        <SecondaryButton @click="openAddModal">
                            Banka Hesabı Ekle
                        </SecondaryButton>
                    </div>
                </div>
            </template>
        </ActionSection>

        <!-- Banka Hesabı Ekleme Modalı -->
        <Modal :show="showAddModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-white">
                    Banka Hesabı Ekle
                </h2>

                <div class="mt-6 space-y-6">
                    <div v-if="isLoading" class="py-4 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
                        <p class="mt-2 text-white">Yükleniyor...</p>
                        <button tabindex="0" class="sr-only">Yükleniyor</button>
                    </div>
                    <div v-else>
                        <div>
                            <InputLabel for="bank" value="Banka" />
                            <select
                                id="bank"
                                v-model="form.bank_id"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-white bg-card-background border-default-background-separator rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50"
                                required>
                                <option disabled value="">Banka Seçiniz</option>
                                <option
                                    v-for="bank in banks"
                                    :key="bank.id"
                                    :value="bank.id">
                                    {{ bank.name }}
                                </option>
                            </select>
                            <InputError :message="formErrors.bank_id" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="account_name" value="Hesap Adı (Opsiyonel)" />
                            <TextInput
                                id="account_name"
                                v-model="form.account_name"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Örn: Ana İş Hesabım"
                            />
                            <InputError :message="formErrors.account_name" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="iban" value="IBAN" />
                            <TextInput
                                id="iban"
                                v-model="form.iban"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                @input="form.iban = formatIban(form.iban)"
                                placeholder="TR00 0000 0000 0000 0000 0000 00"
                            />
                            <InputError :message="formErrors.iban" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="account_number" value="Hesap Numarası (Opsiyonel)" />
                            <TextInput
                                id="account_number"
                                v-model="form.account_number"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="formErrors.account_number" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="branch_code" value="Şube Kodu (Opsiyonel)" />
                            <TextInput
                                id="branch_code"
                                v-model="form.branch_code"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="formErrors.branch_code" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <div class="flex items-center">
                                <Checkbox
                                    id="is_default"
                                    v-model:checked="form.is_default"
                                />
                                <InputLabel for="is_default" value="Varsayılan hesap olarak ayarla" class="ml-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal" class="mr-3" tabindex="0">
                        İptal
                    </SecondaryButton>

                    <PrimaryButton
                        :class="{ 'opacity-25': processing }"
                        :disabled="processing || isLoading"
                        @click="saveAccount"
                        tabindex="0">
                        Kaydet
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Banka Hesabı Düzenleme Modalı -->
        <Modal :show="showEditModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-white">
                    Banka Hesabını Düzenle
                </h2>

                <div class="mt-6 space-y-6">
                    <div v-if="isLoading" class="py-4 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
                        <p class="mt-2 text-white">Yükleniyor...</p>
                        <button tabindex="0" class="sr-only">Yükleniyor</button>
                    </div>
                    <div v-else>
                        <div>
                            <InputLabel for="edit_bank" value="Banka" />
                            <select
                                id="edit_bank"
                                v-model="form.bank_id"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-white bg-card-background border-default-background-separator rounded-md shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50"
                                required>
                                <option disabled value="">Banka Seçiniz</option>
                                <option
                                    v-for="bank in banks"
                                    :key="bank.id"
                                    :value="bank.id">
                                    {{ bank.name }}
                                </option>
                            </select>
                            <InputError :message="formErrors.bank_id" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="edit_account_name" value="Hesap Adı (Opsiyonel)" />
                            <TextInput
                                id="edit_account_name"
                                v-model="form.account_name"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Örn: Ana İş Hesabım"
                            />
                            <InputError :message="formErrors.account_name" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="edit_iban" value="IBAN" />
                            <TextInput
                                id="edit_iban"
                                v-model="form.iban"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                @input="form.iban = formatIban(form.iban)"
                                placeholder="TR00 0000 0000 0000 0000 0000 00"
                            />
                            <InputError :message="formErrors.iban" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="edit_account_number" value="Hesap Numarası (Opsiyonel)" />
                            <TextInput
                                id="edit_account_number"
                                v-model="form.account_number"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="formErrors.account_number" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="edit_branch_code" value="Şube Kodu (Opsiyonel)" />
                            <TextInput
                                id="edit_branch_code"
                                v-model="form.branch_code"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="formErrors.branch_code" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <div class="flex items-center">
                                <Checkbox
                                    id="edit_is_default"
                                    v-model:checked="form.is_default"
                                />
                                <InputLabel for="edit_is_default" value="Varsayılan hesap olarak ayarla" class="ml-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal" class="mr-3" tabindex="0">
                        İptal
                    </SecondaryButton>

                    <PrimaryButton
                        :class="{ 'opacity-25': processing }"
                        :disabled="processing || isLoading"
                        @click="updateAccount"
                        tabindex="0">
                        Güncelle
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Banka Hesabı Silme Onayı -->
        <Modal :show="showDeleteModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-white">
                    Banka Hesabını Sil
                </h2>

                <div class="mt-4 text-sm text-muted">
                    <p>Bu banka hesabını silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.</p>
                    
                    <div v-if="currentAccount" class="mt-4 font-medium text-white">
                        <div class="flex items-center gap-3">
                            <img v-if="currentAccount.bank.logo_path" 
                                 :src="currentAccount.bank.logo_path" 
                                 :alt="currentAccount.bank.name" 
                                 class="h-8 w-auto object-contain" />
                            <div>
                                <p>{{ currentAccount.account_name || currentAccount.bank.name }}</p>
                                <p class="text-xs font-mono">{{ formatIban(currentAccount.iban) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <button v-if="!currentAccount" tabindex="0" class="sr-only">Odaklanılabilir eleman</button>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal" class="mr-3" tabindex="0">
                        İptal
                    </SecondaryButton>

                    <DangerButton
                        :class="{ 'opacity-25': processing }"
                        :disabled="processing"
                        @click="deleteAccount"
                        tabindex="0">
                        Hesabı Sil
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue';
import ActionSection from '@/Components/ActionSection.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DangerButton from '@/packages/ui/src/Buttons/DangerButton.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import InputError from '@/packages/ui/src/Input/InputError.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import Checkbox from '@/packages/ui/src/Input/Checkbox.vue';
import Modal from '@/packages/ui/src/Modal.vue';
import axios from 'axios';

interface Bank {
    id: string;
    name: string;
    short_name: string | null;
    logo_path: string | null;
}

interface BankAccount {
    id: string;
    user_id: string;
    bank_id: string;
    account_name: string | null;
    account_number: string | null;
    iban: string;
    branch_code: string | null;
    is_default: boolean;
    is_active: boolean;
    bank: Bank;
}

interface FormErrors {
    bank_id?: string;
    account_name?: string;
    account_number?: string;
    iban?: string;
    branch_code?: string;
    is_default?: string;
    [key: string]: string | undefined;
}

// Veri durumları
const banks = ref<Bank[]>([]);
const bankAccounts = ref<BankAccount[]>([]);
const processing = ref(false);
const isLoading = ref(true);
const showAddModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const currentAccount = ref<BankAccount | null>(null);
const formErrors = ref<FormErrors>({});

// Form durumu
const form = reactive({
    bank_id: '',
    account_name: '',
    account_number: '',
    iban: '',
    branch_code: '',
    is_default: false
});

// Sayfa yüklendiğinde veri çekme işlemleri
onMounted(async () => {
    isLoading.value = true;
    try {
        await Promise.all([
            fetchBanks(),
            fetchBankAccounts()
        ]);
    } finally {
        isLoading.value = false;
    }
});

// Bankaları çek
async function fetchBanks() {
    try {
        const response = await axios.get('/api/v1/banks');
        banks.value = response.data.data || [];
    } catch (error) {
        console.error('Bankalar yüklenirken hata oluştu:', error);
        banks.value = []; // Hata durumunda boş dizi
    }
}

// Kullanıcının banka hesaplarını çek
async function fetchBankAccounts() {
    try {
        const response = await axios.get('/api/v1/users/me/bank-accounts');
        bankAccounts.value = response.data.data;
    } catch (error) {
        console.error('Banka hesapları yüklenirken hata oluştu:', error);
        bankAccounts.value = [];
    }
}

// IBAN formatla (TR12 3456 7890 1234 5678 9012 34 şeklinde)
function formatIban(iban: string): string {
    if (!iban) return '';
    return iban.replace(/(.{4})/g, '$1 ').trim();
}

// IBAN'dan boşlukları kaldır
function cleanIban(iban: string): string {
    return iban.replace(/\s/g, '');
}

// Ekleme modalını aç
async function openAddModal() {
    resetForm();
    
    // Eğer bankalar henüz yüklenmediyse, yükleme işlemini başlat
    if (banks.value.length === 0) {
        isLoading.value = true;
        await fetchBanks();
        isLoading.value = false;
    }
    
    showAddModal.value = true;
}

// Düzenleme modalını aç
async function openEditModal(account: BankAccount) {
    resetForm();
    populateFormWithAccount(account);
    currentAccount.value = account;
    
    // Eğer bankalar henüz yüklenmediyse, yükleme işlemini başlat
    if (banks.value.length === 0) {
        isLoading.value = true;
        await fetchBanks();
        isLoading.value = false;
    }
    
    showEditModal.value = true;
}

// Silme modalını aç
function openDeleteModal(account: BankAccount) {
    currentAccount.value = account;
    showDeleteModal.value = true;
}

// Modalları kapat
function closeModal() {
    showAddModal.value = false;
    showEditModal.value = false;
    showDeleteModal.value = false;
    currentAccount.value = null;
    resetForm();
}

// Form durumunu sıfırla
function resetForm() {
    form.bank_id = '';
    form.account_name = '';
    form.account_number = '';
    form.iban = '';
    form.branch_code = '';
    form.is_default = false;
    formErrors.value = {};
}

// Form durmunu editlenmek istenilen hesapla doldur
function populateFormWithAccount(account: BankAccount) {
    form.bank_id = account.bank_id;
    form.account_name = account.account_name || '';
    form.account_number = account.account_number || '';
    form.iban = account.iban;
    form.branch_code = account.branch_code || '';
    form.is_default = account.is_default;
}

// Yeni hesap kaydet
async function saveAccount() {
    processing.value = true;
    formErrors.value = {};

    try {
        // IBAN'daki boşlukları kaldır
        const formData = { ...form, iban: cleanIban(form.iban) };
        
        const response = await axios.post('/api/v1/users/me/bank-accounts', formData);
        await fetchBankAccounts(); // Hesap listesini yenile
        closeModal();
    } catch (error: any) {
        if (error.response?.status === 422) {
            // Laravel validation hatalarını düzenleyerek formErrors'e ekleyelim
            const serverErrors = error.response.data.errors;
            const formattedErrors: FormErrors = {};
            
            // Her alan için ilk hata mesajını alıyoruz
            for (const field in serverErrors) {
                formattedErrors[field] = Array.isArray(serverErrors[field]) 
                    ? serverErrors[field][0] 
                    : serverErrors[field];
            }
            
            formErrors.value = formattedErrors;
        } else {
            console.error('Banka hesabı kaydedilirken hata oluştu:', error);
        }
    } finally {
        processing.value = false;
    }
}

// Banka hesabını güncelle
async function updateAccount() {
    if (!currentAccount.value) return;
    
    processing.value = true;
    formErrors.value = {};

    try {
        // IBAN'daki boşlukları kaldır
        const formData = { ...form, iban: cleanIban(form.iban) };
        
        const response = await axios.put(`/api/v1/users/me/bank-accounts/${currentAccount.value.id}`, formData);
        await fetchBankAccounts(); // Hesap listesini yenile
        closeModal();
    } catch (error: any) {
        if (error.response?.status === 422) {
            // Laravel validation hatalarını düzenleyerek formErrors'e ekleyelim
            const serverErrors = error.response.data.errors;
            const formattedErrors: FormErrors = {};
            
            // Her alan için ilk hata mesajını alıyoruz
            for (const field in serverErrors) {
                formattedErrors[field] = Array.isArray(serverErrors[field]) 
                    ? serverErrors[field][0] 
                    : serverErrors[field];
            }
            
            formErrors.value = formattedErrors;
        } else {
            console.error('Banka hesabı güncellenirken hata oluştu:', error);
        }
    } finally {
        processing.value = false;
    }
}

// Banka hesabını sil
async function deleteAccount() {
    if (!currentAccount.value) return;
    
    processing.value = true;

    try {
        await axios.delete(`/api/v1/users/me/bank-accounts/${currentAccount.value.id}`);
        await fetchBankAccounts(); // Hesap listesini yenile
        closeModal();
    } catch (error) {
        console.error('Banka hesabı silinirken hata oluştu:', error);
    } finally {
        processing.value = false;
    }
}
</script> 