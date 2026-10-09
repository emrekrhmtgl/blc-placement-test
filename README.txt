# BLC Placement Test

British Language Center için bağımsız WordPress İngilizce seviye ön değerlendirme eklentisi. **Sürüm: 1.0.1** · PHP 7.4 veya üzeri.

**Akış: anonim test → sonuç → isteğe bağlı öğrenci iletişim bilgileri.** Teste başlamak veya sonucu görmek için hesap, isim, telefon ya da e-posta gerekmez. Başvuru formunun zorunlu alanları yalnızca danışmanlık talebi göndermek isteyenler içindir.

## Kurulum

1. Bu depoda **Code → Download ZIP** seçin. ZIP'in kök klasörünün içinde `blc-placement-test.php` ve `assets/` bulunmalıdır. Ayrıca hazırlanmış `blc-placement-test-v1.0.1.zip` paketi kullanılabilir.
2. WordPress → Eklentiler → Yeni Eklenti Ekle → Eklenti Yükle bölümünden ZIP'i yükleyip etkinleştirin. Daha önce kuruluysa aynı eklentinin güncelleme seçeneğini kullanın; farklı klasör adlarıyla iki kopyayı aynı anda etkinleştirmeyin.
3. Testi göstermek istediğiniz sayfaya Kısa Kod bloğu ekleyip `[blc_placement_test]` yazın. WPBakery kullanılıyorsa kısa kod destekleyen bir metin bileşeni kullanılabilir.
4. **BLC Seviye Testi** menüsünden başlangıç sorularını inceleyin. A1, A2, B1, B2 ve C1 seviyelerinin her birinde en az 8 aktif soru bulunmalıdır.
5. Sonuç ekranının `/subelerimiz/` ve `/kvkk-aydinlatma-metni/` bağlantılarını kendi sitenizde kontrol edin. Farklı adresler kullanılıyorsa `assets/test.js` içindeki bu iki bağlantıyı uyarlayın; alt dizine kurulu sitelerde de adresleri kontrol edin.
6. Gizli pencerede testi tamamlayın; sonucu kişisel bilgi girmeden görebildiğinizi doğrulayın. İstenirse örnek bir başvuruyu gönderip **BLC Seviye Testi → Başvurular** ekranından kontrol edin ve silin.

Bu paket başka tema veya eklentilerin dosyalarını değiştirmez. Etkinleştirme yalnızca bu eklentiye ait üç veritabanı tablosunu ve günlük temizlik görevini oluşturur. Sayfaya kısa kod ekleme işlemi site yöneticisine aittir.

## İçerik ve puanlama

- 40 başlangıç sorusu: her seviyeden 8 soru; dil bilgisi, kelime bilgisi ve kısa okuma.
- Cevap anahtarı tarayıcıya gönderilmez; puanlama sunucuda yapılır.
- Her seviyede en az 6/8 doğru ve önceki seviyelerin tamamında aynı eşik gerekir. A1 eşiği geçilemezse sonuç Pre-A1 olur. Örneğin A1=8, A2=5, B1=8 sonucu A1'dir.
- Sonuçlar tahmini yazılı dil yerleştirme önerisidir. Resmî CEFR belgesi değildir; konuşma, dinleme ve yazılı üretim ölçülmez. Sorular ve eşikler eğitmen incelemesi/pilot değerlendirme gerektirir.
- Soru düzenleme, etkinleştirme, başvuru görüntüleme ve silme yalnızca `manage_options` yetkili yöneticilere açıktır.

## Veri davranışı

Tablolar WordPress tablo önekiyle `blc_pt_questions`, `blc_pt_attempts` ve `blc_pt_leads` adlarını kullanır. Anonim deneme kaydı soru kimliklerini, sınav anahtarının SHA-256 özetini, tarihleri ve tamamlandığında puanları içerir. İletişim bilgileri yalnızca sonuçtan sonra onay verilerek gönderilir; harici servise veya e-postaya gönderim yapılmaz.

Başvuruya bağlı olmayan 30 günden eski denemeler günlük WP-Cron çalıştığında temizlenir. Başvurular ve bunlara bağlı sonuçlar otomatik silinmez; yönetici başvuruları panelden silebilir. Devre dışı bırakma temizlik görevini kaldırır, verileri korur. Eklentiyi silmek de bu sürümde tabloları otomatik silmez.

Sınav oluşturma saatte IP başına 12, başvuru gönderme saatte IP başına 10 istekle geçici olarak sınırlandırılır. IP adresi bu sayaçların anahtarında özetlenir. Aynı internet bağlantısını kullanan öğrenciler bu kotayı paylaşabilir. Bu basit sayaç kapsamlı spam koruması değildir.

## Doğrulama ve bilinen sınırlar

1.0.1 için PHP sözdizimi ve JavaScript sözdizimi kontrolleri, 27 izole PHP kontrolü ve tarayıcı akış testi geçti. Test kapsamı: soru sayıları, Pre-A1–C1 eşikleri, anonim başlangıç, eksik yanıt, geçersiz doğrulama anahtarı, süre aşımı, kayıt hatası, eşzamanlı/tekrar gönderim, isteğe bağlı başvuru ve 390 px mobil genişlik.

PHP kontrolleri WordPress/veritabanı yerine test taklitleri, tarayıcı testi taklit AJAX yanıtları kullanır. **Canlı WordPress/MySQL entegrasyonu veya mevcut sitenin tema/önbellek uyumu test edilmedi.** Yayına almadan önce deneme ortamında yukarıdaki kurulum kontrolünü tamamlayın.

- Devam eden denemeler soru kimliklerini saklar; soru metni/cevabının anlık kopyası tutulmaz. Başlamış testler tamamlanmadan soruları düzenlemeyin veya silmeyin. Test gönderme süresi 24 saattir.
- Sonuçtan sonraki başvuru süresi denemenin başlangıcından itibaren 7 gündür.
- Uzun süre önbellekte kalan sayfalarda WordPress doğrulama anahtarı süresi dolabilir; test sayfasını önbellekten hariç tutun veya önbellek süresini uyarlayın.
- JavaScript açık olmalıdır. Yeniden yükleme ilerlemeyi geri getirmez.

## 1.0.1 değişiklikleri

- İlk soruda Önceki düğmesinin yanlışlıkla etkinleşmesi ve negatif soru indeksine geçiş düzeltildi.
- Sonuç veritabanına kaydedilemezse başarı yerine tekrar deneme hatası gösterilir.
- Eşzamanlı gönderimde veritabanındaki kesinleşmiş sonuç döndürülür.
- Devre dışı bırakmada günlük temizlik görevi kaldırılır.

Kaynak: önceki `blc-placement-test-v1.0.0.zip` paketindeki PHP, JS ve CSS; yukarıdaki düzeltmelerle yayımlanmıştır.
