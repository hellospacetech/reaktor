import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import 'dayjs/locale/tr';

// DayJS eklentilerini yükle
dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.locale('tr');

/**
 * Kullanıcı zaman dilimini getir
 * Varsayılan olarak 'Europe/Istanbul' kullanır
 */
export function getUserTimezone(): string {
  return localStorage.getItem('timezone') || 'Europe/Istanbul';
}

/**
 * Tarihi biçimlendirir
 * 
 * @param date - ISO 8601 formatında tarih
 * @param includeTime - Saat bilgisini içersin mi
 * @returns Formatlanmış tarih (örn: 01.05.2024 veya 01.05.2024 14:30)
 */
export function formatDate(date: string, includeTime: boolean = false): string {
  if (!date) return '--';
  
  try {
    const format = includeTime ? 'DD.MM.YYYY HH:mm' : 'DD.MM.YYYY';
    return dayjs.utc(date).tz(getUserTimezone()).format(format);
  } catch (error) {
    console.error('Tarih biçimlendirme hatası:', error);
    return '--';
  }
}

/**
 * İki tarih arasındaki süreyi hesaplayıp biçimlendirir
 * 
 * @param startDate - Başlangıç tarihi
 * @param endDate - Bitiş tarihi
 * @returns Formatlanmış süre (örn: 2 saat 30 dk)
 */
export function calculateDuration(startDate: string, endDate: string | null): string {
  if (!endDate) return 'Devam ediyor';
  
  try {
    const start = dayjs.utc(startDate);
    const end = dayjs.utc(endDate);
    
    const durationMs = end.diff(start);
    const hours = Math.floor(durationMs / (1000 * 60 * 60));
    const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
    
    return `${hours} saat ${minutes} dk`;
  } catch (error) {
    console.error('Süre hesaplama hatası:', error);
    return '--';
  }
}

/**
 * Tarih insan tarafından okunabilir formatta biçimlendirir
 * 
 * @param date - ISO 8601 formatında tarih
 * @returns İnsan tarafından okunabilir tarih (örn: Bugün, Dün, 2 gün önce)
 */
export function formatHumanReadableDate(date: string): string {
  if (!date) return '--';
  
  try {
    const now = dayjs();
    const targetDate = dayjs.utc(date).tz(getUserTimezone());
    
    if (targetDate.isSame(now, 'day')) {
      return 'Bugün';
    } else if (targetDate.isSame(now.subtract(1, 'day'), 'day')) {
      return 'Dün';
    } else if (now.diff(targetDate, 'day') < 7) {
      return `${now.diff(targetDate, 'day')} gün önce`;
    } else {
      return targetDate.format('DD.MM.YYYY');
    }
  } catch (error) {
    console.error('Tarih biçimlendirme hatası:', error);
    return '--';
  }
} 