# PR.PHP - Modern PHP Debug & Pretty Print Kütüphanesi

**PR.PHP**, PHP geliştiricileri için tasarlanmış gelişmiş ve modern bir debug & pretty print kütüphanesidir. Array, object, file ve directory verilerini görselleştirmek için kapsamlı araçlar sunar.

## ✨ Temel Özellikler
- 🎨 **Modern Dark Theme** - Responsive, mobil uyumlu tasarım
- 📊 **Multi-Modal Array Görüntüleme** - Tablo, JSON, Tree görünümleri  
- 🔍 **Detaylı Sınıf Analizi** - Properties, methods, constants, inheritance
- 📁 **Dosya Sistemi Araçları** - File/directory bilgileri ve ağaç yapısı
- ⚡ **Yüksek Performans** - Pagination, lazy loading, bellek optimizasyonu
- 🎛️ **İnteraktif Arayüz** - Keyboard shortcuts, copy functionality

## 📦 Kurulum
```php
<?php
require_once 'pr.php';
define('DEBUG', true); // Opsiyonel
?>
```

## 🔧 Kullanım
```php
<?php
// Array debug
$data = ['name' => 'John', 'age' => 30];
pr($data, 'User Data');

// Object debug  
$user = new User();
pr($user, 'User Analysis');

// File/Directory debug
pr('/path/to/file.txt', 'File Info');
pr('/path/to/directory', 'Directory Info');
?>
```

## ⚡ Array Görünüm Modları
- **Tablo Görünümü**: Key | Type | Value formatında düzenli görüntüleme
- **JSON Görünümü**: Syntax highlighted, kopyalanabilir JSON formatı  
- **Tree Görünümü**: Hiyerarşik ağaç yapısında görüntüleme
 

## 📚 Ana Fonksiyonlar
- `pr($data, $name, $debug)` - Ana debug fonksiyonu
- `analyzeArrayStructure($array)` - Array detaylı analizi
- `displayModernClassInfo($object)` - Sınıf detaylı görüntüleme
- `formatFileSize($bytes)` - Byte formatlama
- `getDataType($data)` - Gelişmiş tip algılama

## 🔍 Özellik Detayları

### Array Analizi
- Toplam öğe sayısı ve bellek kullanımı
- Veri tipi dağılımı (string, int, bool, array)
- Array derinlik analizi ve istatistikler
- Genişletilebilir satırlar ve pagination

### Sınıf Analizi  
- Class hierarchy (parent classes, interfaces)
- Properties listesi (visibility, type, value)
- Methods listesi (parameters, return types, docblocks)
- Constants listesi ve sınıf istatistikleri

### Dosya Sistemi
- Klasör içeriği ve ağaç yapısı
- Dosya türü dağılımı ve boyut analizi
- İzin bilgileri ve tarih detayları
- MIME type bilgisi

## ⚙️ Konfigürasyon
```php
define('DEBUG', true);  // Aktif
define('DEBUG', false); // Deaktif - pr() fonksiyonu çalışmaz
```

## 🖥️ Sistem Gereksinimleri
- **PHP**: 7.4+ (8.0+ önerilir)
- **Extensions**: `json`, `reflection`, `fileinfo`
- **Browser**: Modern web tarayıcısı

## 💡 İpuçları
- Production ortamında `DEBUG` false yapın
- Büyük veri setleri için otomatik pagination aktif olur
- Complex data structure'lar için tree görünümünü kullanın
- Hassas bilgileri (şifreler, API keys) debug etmeyin

---
**PR.PHP** - Modern PHP debugging made beautiful! 🚀 
