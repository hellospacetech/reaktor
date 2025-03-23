<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BanksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Logo klasörü yolunu ayarla
        $logoBasePath = public_path('images/banks');
        
        // CSV dosyasını oku
        $csvPath = database_path('data/bankalar.csv');
        
        if (!file_exists($csvPath)) {
            $this->command->error('Banka CSV dosyası bulunamadı: ' . $csvPath);
            return;
        }
        
        $this->command->info('Banka verilerini yükleme başlatılıyor...');
        
        // CSV dosyasını aç
        $handle = fopen($csvPath, 'r');
        
        // Header satırını atla
        $headers = fgetcsv($handle, 0, ',');
        
        // Her satırı işle
        $count = 0;
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $bankName = trim($data[0]);
            $address = trim($data[1]);
            $phone = trim($data[2]);
            $fax = trim($data[3]);
            $website = trim($data[4]);
            $telex = trim($data[5]);
            $eftCode = trim($data[6]);
            $swiftCode = trim($data[7]);
            
            // Kısa banka adını çıkar
            $shortName = $this->extractShortName($bankName);
            
            // Logo dosya adı oluştur (URL-safe)
            $slugName = Str::slug($shortName);
            
            // Olası logo dosya isimleri
            $possibleLogoNames = [
                $slugName . '.png',  // örn: ziraat.png
                Str::slug($bankName) . '.png', // örn: turkiye-is-bankasi.png
            ];
            
            // Logo yolu (varsayılan boş)
            $logoRelativePath = null;
            
            // Olası logoları kontrol et
            foreach ($possibleLogoNames as $logoName) {
                $logoFullPath = $logoBasePath . '/' . $logoName;
                if (File::exists($logoFullPath)) {
                    $logoRelativePath = 'images/banks/' . $logoName;
                    break;
                }
            }
            
            // Banka varsa güncelle, yoksa oluştur
            Bank::updateOrCreate(
                ['name' => $bankName],
                [
                    'short_name' => $shortName,
                    'address' => $address,
                    'phone' => $phone,
                    'fax' => $fax,
                    'website' => $website,
                    'telex' => $telex,
                    'eft_code' => $eftCode,
                    'swift_code' => $swiftCode,
                    'logo_path' => $logoRelativePath,
                    'country_code' => 'TR',
                    'is_active' => true,
                ]
            );
            
            $count++;
        }
        
        fclose($handle);
        
        $this->command->info("Toplam $count banka yüklendi.");
    }
    
    /**
     * Banka adından kısa isim çıkar
     */
    private function extractShortName(string $fullName): string
    {
        // A.Ş., T.A.Ş. gibi sonekleri kaldır
        $name = preg_replace('/(A\.Ş\.|T\.A\.Ş\.|T\.A\.O\.|S\.p\.A\.|S\.A\.|N\.A\.|Plc\.|Limited).*$/', '', $fullName);
        
        // "Türkiye", "Türk" gibi başlangıçları kaldır
        $name = preg_replace('/^(Türkiye|Türk)\s+/', '', $name);
        
        // Ekstra boşlukları temizle
        $name = trim($name);
        
        return $name;
    }
}
