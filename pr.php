<?php
/**
 * Advanced PHP Debug & Pretty Print Library
 * Modern, responsive and feature-rich debugging tool
 * @version 2.0
 * @author Enhanced Version
 */

// Sabitler
if (!defined('DEBUG')) {
    define('DEBUG', true); // Varsayılan debug durumu
}

// Global değişkenler
$isAddedCss = false;
$prInstanceCount = 0;

/**
 * Ana debug fonksiyonu - verileri güzelce formatlar ve görüntüler
 * @param mixed $data Görüntülenecek veri
 * @param string $name Veri için başlık
 * @param bool $debug Ek debug bilgileri gösterilsin mi
 * @return void
 */
function pr($data, $name = "", $debug = false): void  
{
    global $isAddedCss, $prInstanceCount;
    
    // Debug kontrolü - sadece DEBUG aktifse çalışır
    if (!defined("DEBUG") || !DEBUG) {
        return;
    }
    
    $prInstanceCount++;
    $instanceId = 'pr-instance-' . $prInstanceCount;
    
    // Modern CSS stilleri
    $style = getPrStyles();
    
    ob_start();
    
    // Ana container
    echo '<div class="pr-container" id="' . $instanceId . '">';
    echo '<div class="pr-header">';
    echo '<h3 class="pr-title">' . htmlspecialchars(is_string($name) ? $name : (is_array($name) ? implode(', ', $name) : 'Debug Output')) . '</h3>';
    echo '<div class="pr-meta">';
    echo '<span class="pr-timestamp">' . date('H:i:s') . '</span>';
    echo '<span class="pr-type">' . getDataType($data) . '</span>';
    echo '<button class="pr-toggle" onclick="togglePrContent(\'' . $instanceId . '\')">↕</button>';
    echo '</div>';
    echo '</div>';
    echo '<div class="pr-content" id="content-' . $instanceId . '">';

    // Dosya veya klasör kontrolü
    if (is_string($data) && (file_exists($data) || is_dir($data))) {
        if (is_file($data)) {
            // Dosya bilgilerini göster
            showFileInfo($data);
        } elseif (is_dir($data)) {
            // Klasör bilgilerini göster
            showDirectoryInfo($data);
        }
    } elseif (is_object($data)) {
        // Modern ve detaylı sınıf bilgilerini göster
        displayModernClassInfo($data, $instanceId, $prInstanceCount);
    } elseif (is_array($data)) {
        // Array için detaylı analiz
        $arrayInfo = analyzeArrayStructure($data);
        
        echo '<div class="array-overview">';
        echo '<div class="section-title">Array Bilgileri</div>';
        echo '<div class="stats-grid">';
        echo '<div class="stat-card"><div class="stat-value">' . number_format($arrayInfo['count']) . '</div><div class="stat-label">Toplam Öğe</div></div>';
        echo '<div class="stat-card"><div class="stat-value">' . ($arrayInfo['isAssoc'] ? 'Assoc' : 'Indexed') . '</div><div class="stat-label">Tip</div></div>';
        echo '<div class="stat-card"><div class="stat-value">' . formatFileSize($arrayInfo['memoryUsage']) . '</div><div class="stat-label">Bellek</div></div>';
        echo '<div class="stat-card"><div class="stat-value">' . $arrayInfo['maxDepth'] . '</div><div class="stat-label">Derinlik</div></div>';
        echo '</div>';
        echo '</div>';
        
        // Veri türleri dağılımı
        if (!empty($arrayInfo['typeDistribution'])) {
            echo '<div class="type-distribution">';
            echo '<div class="section-title">Veri Türleri</div>';
            echo '<div class="type-chart">';
            foreach ($arrayInfo['typeDistribution'] as $type => $count) {
                $percentage = round(($count / $arrayInfo['count']) * 100, 1);
                echo '<div class="type-item">';
                echo '<span class="type-icon">' . getTypeIcon($type) . '</span>';
                echo '<span class="type-name">' . ucfirst($type) . '</span>';
                echo '<span class="type-count">' . $count . '</span>';
                echo '<div class="type-bar"><div class="type-fill" style="width: ' . $percentage . '%"></div></div>';
                echo '<span class="type-percentage">' . $percentage . '%</span>';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        
        echo '<div class="section-title">Array İçeriği</div>';
        
        // JSON görünümü toggle
        echo '<div class="view-toggles">';
        echo '<button class="view-btn active" onclick="switchArrayView(\'' . $instanceId . '\', \'table\')">Tablo</button>';
        echo '<button class="view-btn" onclick="switchArrayView(\'' . $instanceId . '\', \'json\')">JSON</button>';
        echo '<button class="view-btn" onclick="switchArrayView(\'' . $instanceId . '\', \'tree\')">Tree</button>';
        
        // Modal butonu ekle
        $mainArrayId = 'main-array-' . $instanceId;
        $arrayJson = base64_encode(json_encode($data));
        $arrayTitle = 'Ana Array[' . count($data) . ']';
        echo '<button class="view-btn btn-primary" onclick="openArrayModalBase64(\'' . $arrayJson . '\', \'' . $arrayTitle . '\', \'' . $mainArrayId . '\')" title="Bootstrap Modal\'da Aç">';
        echo 'Modal';
        echo '</button>';
        
        echo '</div>';
        
        // Tablo görünümü
        echo '<div id="array-table-' . $instanceId . '" class="array-view active">';
        displayArrayAsTable($data, $instanceId);
        echo '</div>';
        
        // JSON görünümü
        echo '<div id="array-json-' . $instanceId . '" class="array-view">';
        echo '<div class="json-container">';
        echo formatAsJson($data);
        echo '</div>';
        echo '</div>';
        
        // Tree görünümü
        echo '<div id="array-tree-' . $instanceId . '" class="array-view">';
        echo '<div class="tree-container">';
        displayArrayAsTree($data, $instanceId);
        echo '</div>';
        echo '</div>';
    } else {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

    echo '</div>';
    echo '</div>';

    if ($debug) {
        pr(
            [
                
                "_GET" => $_GET,
                "_POST" => $_POST,
                "_SERVER" => $_SERVER,
            ],
            "DİĞER VERİLER",
            false
        );
    }
    $html = ob_get_clean();
    if($isAddedCss){
        echo $html;
    }else{
        $isAddedCss = true;
        echo $style . $html;
    }
    
}

// Metot parametrelerinin default değerleri için düz metin formatlama
function formatDefaultValueText($value) {
    if (is_null($value)) {
        return 'null';
    } elseif (is_string($value)) {
        if (strlen($value) > 30) {
            return '"' . htmlspecialchars(substr($value, 0, 30)) . '..."';
        }
        return '"' . htmlspecialchars($value) . '"';
    } elseif (is_int($value) || is_float($value)) {
        return $value;
    } elseif (is_bool($value)) {
        return $value ? 'true' : 'false';
    } elseif (is_array($value)) {
        return '[]';
    } elseif (is_object($value)) {
        return 'Object(' . get_class($value) . ')';
    } else {
        return 'unknown';
    }
}

// Metot parametrelerinin default değerleri için güvenli formatlama (button'sız)
function formatDefaultValue($value) {
    if (is_null($value)) {
        return '<span class="value-null"><i class="value-icon">🚫</i>null</span>';
    } elseif (is_string($value)) {
        if (strlen($value) > 50) {
            return '<span class="value-string"><i class="value-icon">📝</i>"' . htmlspecialchars(substr($value, 0, 50)) . '..."</span>';
        }
        return '<span class="value-string"><i class="value-icon">📝</i>"' . htmlspecialchars($value) . '"</span>';
    } elseif (is_int($value) || is_float($value)) {
        return '<span class="value-numeric"><i class="value-icon">🔢</i>' . $value . '</span>';
    } elseif (is_bool($value)) {
        $class = $value ? 'value-boolean-true' : 'value-boolean-false';
        $icon = $value ? '✅' : '❌';
        $text = $value ? 'true' : 'false';
        return '<span class="' . $class . '"><i class="value-icon">' . $icon . '</i>' . $text . '</span>';
    } elseif (is_array($value)) {
        $count = count($value);
        return '<span class="value-array"><i class="value-icon">🗃️</i>Array[' . $count . ']</span>';
    } elseif (is_object($value)) {
        return '<span class="value-object"><i class="value-icon">🎯</i>Object(' . get_class($value) . ')</span>';
    } else {
        return '<span class="value-unknown"><i class="value-icon">❓</i>unknown</span>';
    }
}

// Değerleri formatlamak için yardımcı fonksiyon
function formatValue($value) {
    if (is_null($value)) {
        return '<span class="value-null"><i class="value-icon">🚫</i>null</span>';
    } elseif (is_string($value)) {
        if (strlen($value) > 100) {
            $truncated = htmlspecialchars(substr($value, 0, 100));
            $full = htmlspecialchars($value);
            $result = '<span class="value-string">';
            $result .= '<i class="value-icon">📝</i>"';
            $result .= '<span class="truncated-text" onclick="toggleFullText(this)" title="Tam metni görmek için tıklayın">';
            $result .= $truncated . '<span class="expand-indicator">...</span>';
            $result .= '</span>';
            $result .= '<span class="full-text" style="display:none">' . $full . '</span>';
            $result .= '"</span>';
            return $result;
        } else {
            $value = htmlspecialchars($value);
        }
        return '<span class="value-string"><i class="value-icon">📝</i>"' . $value . '"</span>';
    } elseif (is_int($value) || is_float($value)) {
        $icon = is_int($value) ? '🔢' : '🔢';
        $formatted = number_format($value, is_float($value) ? 2 : 0);
        return '<span class="value-numeric"><i class="value-icon">' . $icon . '</i>' . $formatted . '</span>';
    } elseif (is_bool($value)) {
        $icon = $value ? '✅' : '❌';
        $text = $value ? 'true' : 'false';
        $class = $value ? 'value-boolean-true' : 'value-boolean-false';
        return '<span class="' . $class . '"><i class="value-icon">' . $icon . '</i>' . $text . '</span>';
    } elseif (is_array($value)) {
        $count = count($value);
        $isAssoc = array_keys($value) !== range(0, $count - 1);
        $icon = $isAssoc ? '🗃️' : '📋';
        $arrayId = 'array-' . uniqid();
        $arrayJson = base64_encode(json_encode($value));
        $arrayTitle = 'Array[' . number_format($count) . ']';
        
        return '<span class="value-array">' .
               '<i class="value-icon">' . $icon . '</i>' .
               '<button class="btn btn-sm btn-primary ms-2" onclick="openArrayModalBase64(\'' . $arrayJson . '\', \'' . $arrayTitle . '\', \'' . $arrayId . '\')" title="Modal\'da Aç">' .
               '<i class="btn-icon">📋</i> Array[' . number_format($count) . '] Modal' .
               '</button>' .
               '</span>';
    } elseif (is_object($value)) {
        return '<span class="value-object"><i class="value-icon">🎯</i>Object(' . get_class($value) . ')</span>';
    } elseif (is_resource($value)) {
        return '<span class="value-resource"><i class="value-icon">🔧</i>Resource(' . get_resource_type($value) . ')</span>';
    } else {
        return '<span class="value-unknown"><i class="value-icon">❓</i><pre>' . print_r($value, true) . '</pre></span>';
    }
}

// Yardımcı fonksiyon - Dosya bilgilerini gösterir
function showFileInfo($filePath) {
    $fileInfo = pathinfo($filePath);
    $fileStats = stat($filePath);
    $fileSize = filesize($filePath);
    $fileTime = filemtime($filePath);
    $filePerms = substr(sprintf('%o', fileperms($filePath)), -4);
    $mimeType = function_exists('mime_content_type') ? mime_content_type($filePath) : 'Bilinmiyor';
    $fileExt = isset($fileInfo['extension']) ? strtolower($fileInfo['extension']) : '';
    
    echo '<div class="section-title">Dosya Bilgileri</div>';
    echo '<table class="pr-table">';
    echo '<tr><th>Özellik</th><th>Değer</th></tr>';
    echo '<tr><td>Dosya Adı</td><td><span class="file-icon">📄</span> ' . $fileInfo['basename'] . '</td></tr>';
    echo '<tr><td>Tam Yol</td><td>' . $filePath . '</td></tr>';
    echo '<tr><td>Dizin</td><td>' . $fileInfo['dirname'] . '</td></tr>';
    echo '<tr><td>Uzantı</td><td>' . (isset($fileInfo['extension']) ? $fileInfo['extension'] : '-') . '</td></tr>';
    echo '<tr><td>MIME Türü</td><td>' . $mimeType . '</td></tr>';
    echo '<tr><td>Boyut</td><td>' . formatFileSize($fileSize) . ' <span class="file-size">(' . $fileSize . ' bytes)</span></td></tr>';
    echo '<tr><td>İzinler</td><td><span class="file-permissions">' . $filePerms . '</span> ' . formatPermissions(fileperms($filePath)) . '</td></tr>';
    echo '<tr><td>Son Değişiklik</td><td>' . date('Y-m-d H:i:s', $fileTime) . ' <span class="file-time">(' . timeAgo($fileTime) . ')</span></td></tr>';
    echo '<tr><td>Son Erişim</td><td>' . date('Y-m-d H:i:s', $fileStats['atime']) . '</td></tr>';
    echo '<tr><td>Oluşturulma</td><td>' . date('Y-m-d H:i:s', $fileStats['ctime']) . '</td></tr>';
    echo '</table>';
    
    // Dosya türüne göre ek bilgiler
    if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
        // Resim dosyası
        if (function_exists('getimagesize')) {
            $imgInfo = getimagesize($filePath);
            if ($imgInfo) {
                echo '<div class="section-title">Resim Bilgileri</div>';
                echo '<table class="pr-table">';
                echo '<tr><th>Özellik</th><th>Değer</th></tr>';
                echo '<tr><td>Genişlik x Yükseklik</td><td>' . $imgInfo[0] . ' x ' . $imgInfo[1] . ' px</td></tr>';
                echo '<tr><td>Tip</td><td>' . image_type_to_mime_type($imgInfo[2]) . '</td></tr>';
                if (isset($imgInfo['bits'])) {
                    echo '<tr><td>Bit Derinliği</td><td>' . $imgInfo['bits'] . '</td></tr>';
                }
                echo '</table>';
            }
        }
    } elseif (in_array($fileExt, ['php', 'html', 'css', 'js', 'txt', 'md', 'json', 'xml'])) {
        // Metin dosyası içeriği
        echo '<div class="section-title">Dosya İçeriği (İlk 10 Satır)</div>';
        echo '<pre>';
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            $displayLines = array_slice($lines, 0, 10);
            foreach ($displayLines as $i => $line) {
                echo htmlspecialchars(($i + 1) . ": " . $line) . "\n";
            }
            if (count($lines) > 10) {
                echo "...\n";
                echo count($lines) . " satırdan 10 satır gösteriliyor";
            }
        } else {
            echo "Dosya içeriği okunamadı veya boş";
        }
        echo '</pre>';
    }
}

// Yardımcı fonksiyon - Klasör bilgilerini gösterir
function showDirectoryInfo($dirPath) {
    $dirInfo = pathinfo($dirPath);
    $dirStats = stat($dirPath);
    $dirPerms = substr(sprintf('%o', fileperms($dirPath)), -4);
    $dirTime = filemtime($dirPath);
    
    echo '<div class="section-title">Klasör Bilgileri</div>';
    echo '<table class="pr-table">';
    echo '<tr><th>Özellik</th><th>Değer</th></tr>';
    echo '<tr><td>Klasör Adı</td><td><span class="folder-icon">📁</span> ' . $dirInfo['basename'] . '</td></tr>';
    echo '<tr><td>Tam Yol</td><td>' . $dirPath . '</td></tr>';
    echo '<tr><td>Üst Dizin</td><td>' . $dirInfo['dirname'] . '</td></tr>';
    echo '<tr><td>İzinler</td><td><span class="file-permissions">' . $dirPerms . '</span> ' . formatPermissions(fileperms($dirPath)) . '</td></tr>';
    echo '<tr><td>Son Değişiklik</td><td>' . date('Y-m-d H:i:s', $dirTime) . ' <span class="file-time">(' . timeAgo($dirTime) . ')</span></td></tr>';
    echo '<tr><td>Son Erişim</td><td>' . date('Y-m-d H:i:s', $dirStats['atime']) . '</td></tr>';
    echo '<tr><td>Oluşturulma</td><td>' . date('Y-m-d H:i:s', $dirStats['ctime']) . '</td></tr>';
    
    // Klasör içeriği analizi
    list($fileCount, $dirCount, $totalSize, $fileTypes) = analyzeDirectory($dirPath);
    
    echo '<tr><td>Dosya Sayısı</td><td>' . $fileCount . '</td></tr>';
    echo '<tr><td>Alt Klasörler</td><td>' . $dirCount . '</td></tr>';
    echo '<tr><td>Toplam Boyut</td><td>' . formatFileSize($totalSize) . ' <span class="file-size">(' . $totalSize . ' bytes)</span></td></tr>';
    echo '</table>';
    
    // Dosya türleri dağılımı
    if (!empty($fileTypes)) {
        echo '<div class="section-title">Dosya Türleri</div>';
        echo '<table class="pr-table">';
        echo '<tr><th>Uzantı</th><th>Sayı</th><th>Toplam Boyut</th><th>Oran</th></tr>';
        
        arsort($fileTypes);
        foreach ($fileTypes as $ext => $info) {
            $percentage = $totalSize > 0 ? round(($info['size'] / $totalSize) * 100, 2) : 0;
            echo '<tr>';
            echo '<td>' . ($ext ? $ext : 'uzantısız') . '</td>';
            echo '<td>' . $info['count'] . '</td>';
            echo '<td>' . formatFileSize($info['size']) . '</td>';
            echo '<td>' . $percentage . '%';
            echo '<div class="progress-bar"><div class="progress-fill" style="width: ' . $percentage . '%"></div></div>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    // Klasör ağacı
    echo '<div class="section-title">Klasör Ağacı</div>';
    echo '<ul class="file-tree">';
    displayDirectoryTree($dirPath, 0, 3); // Maksimum 3 seviye derinlik
    echo '</ul>';
}

// Yardımcı fonksiyon - Klasör ağacını gösterir
function displayDirectoryTree($dir, $level = 0, $maxDepth = 3) {
    if ($level >= $maxDepth) {
        echo '<li><span class="folder-icon">📁</span> ... (maksimum derinlik)</li>';
        return;
    }
    
    if (!is_readable($dir)) {
        echo '<li><span class="folder-icon">🔒</span> ' . basename($dir) . ' (erişim reddedildi)</li>';
        return;
    }
    
    $files = scandir($dir);
    $files = array_diff($files, ['.', '..']);
    
    if (empty($files)) {
        echo '<li><span class="folder-icon">📁</span> ' . basename($dir) . ' (boş klasör)</li>';
        return;
    }
    
    // Önce klasörleri, sonra dosyaları göster
    $dirs = [];
    $filesOnly = [];
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            $dirs[] = $file;
        } else {
            $filesOnly[] = $file;
        }
    }
    
    sort($dirs);
    sort($filesOnly);
    
    // Alt klasörleri göster
    foreach ($dirs as $subDir) {
        $path = $dir . DIRECTORY_SEPARATOR . $subDir;
        echo '<li><span class="folder-icon">📁</span> ' . $subDir;
        if ($level < $maxDepth - 1) {
            echo '<ul class="sub-tree">';
            displayDirectoryTree($path, $level + 1, $maxDepth);
            echo '</ul>';
        }
        echo '</li>';
    }
    
    // Dosyaları göster (en fazla 10 dosya)
    $fileCount = count($filesOnly);
    $displayFiles = array_slice($filesOnly, 0, 10);
    
    foreach ($displayFiles as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        echo '<li><span class="file-icon">📄</span> ' . $file . ' <span class="file-size">' . formatFileSize(filesize($path)) . '</span></li>';
    }
    
    if ($fileCount > 10) {
        echo '<li><span class="file-icon">...</span> ve ' . ($fileCount - 10) . ' dosya daha</li>';
    }
}

// Yardımcı fonksiyon - Klasör içeriğini analiz eder
function analyzeDirectory($dir, &$totalSize = 0, &$fileCount = 0, &$dirCount = 0, &$fileTypes = []) {
    if (!isset($totalSize)) $totalSize = 0;
    if (!isset($fileCount)) $fileCount = 0;
    if (!isset($dirCount)) $dirCount = 0;
    if (!isset($fileTypes)) $fileTypes = [];
    
    if (!is_readable($dir)) {
        return [$fileCount, $dirCount, $totalSize, $fileTypes];
    }
    
    $handle = opendir($dir);
    if (!$handle) {
        return [$fileCount, $dirCount, $totalSize, $fileTypes];
    }
    
    while (($file = readdir($handle)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_file($path)) {
            $fileCount++;
            $size = filesize($path);
            $totalSize += $size;
            
            // Dosya türü istatistikleri
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (!isset($fileTypes[$ext])) {
                $fileTypes[$ext] = ['count' => 0, 'size' => 0];
            }
            $fileTypes[$ext]['count']++;
            $fileTypes[$ext]['size'] += $size;
        } elseif (is_dir($path)) {
            $dirCount++;
            // Alt klasörleri de analiz et (derinlemesine)
            analyzeDirectory($path, $totalSize, $fileCount, $dirCount, $fileTypes);
        }
    }
    
    closedir($handle);
    return [$fileCount, $dirCount, $totalSize, $fileTypes];
}

// Yardımcı fonksiyon - Dosya boyutunu formatlar
function formatFileSize($bytes) {
    if ($bytes < 1024) {
        return $bytes . " B";
    } elseif ($bytes < 1048576) {
        return round($bytes / 1024, 2) . " KB";
    } elseif ($bytes < 1073741824) {
        return round($bytes / 1048576, 2) . " MB";
    } else {
        return round($bytes / 1073741824, 2) . " GB";
    }
}

// Yardımcı fonksiyon - Dosya izinlerini formatlar
function formatPermissions($perms) {
    $info = '';
    
    // Dosya türü
    $info .= (($perms & 0x4000) ? 'd' : '-'); // Klasör?
    $info .= (($perms & 0x2000) ? 'c' : '-'); // Karakter cihazı?
    $info .= (($perms & 0x6000) ? 'b' : '-'); // Blok cihazı?
    $info .= (($perms & 0x1000) ? 'p' : '-'); // Named pipe?
    $info .= (($perms & 0x8000) ? '-' : '-'); // Regular?
    $info .= (($perms & 0xA000) ? 'l' : '-'); // Sembolik link?
    $info .= (($perms & 0xC000) ? 's' : '-'); // Socket?
    
    // Sahip
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
    
    // Grup
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));
    
    // Dünya
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));
    
    return $info;
}

// Yardımcı fonksiyon - Zamanı "... önce" şeklinde formatlar
if(!function_exists("timeAgo")){
function timeAgo($time) {
    $diff = time() - $time;
    
    if ($diff < 60) {
        return $diff . ' saniye önce';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' dakika önce';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' saat önce';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' gün önce';
    } elseif ($diff < 31536000) {
        return floor($diff / 2592000) . ' ay önce';
    } else {
        return floor($diff / 31536000) . ' yıl önce';
    }
}
}

/**
 * Veri tipini belirler ve döndürür
 * @param mixed $data Analiz edilecek veri
 * @return string Veri tipi
 */
function getDataType($data): string {
    if (is_null($data)) {
        return 'NULL';
    } elseif (is_bool($data)) {
        return 'Boolean (' . ($data ? 'true' : 'false') . ')';
    } elseif (is_int($data)) {
        return 'Integer (' . number_format($data) . ')';
    } elseif (is_float($data)) {
        return 'Float (' . $data . ')';
    } elseif (is_string($data)) {
        $length = strlen($data);
        if (file_exists($data) || is_dir($data)) {
            return is_file($data) ? 'File Path' : 'Directory Path';
        }
        return 'String (' . number_format($length) . ' karakter)';
    } elseif (is_array($data)) {
        $count = count($data);
        $isAssoc = array_keys($data) !== range(0, $count - 1);
        return 'Array (' . number_format($count) . ' öğe, ' . ($isAssoc ? 'associative' : 'indexed') . ')';
    } elseif (is_object($data)) {
        return 'Object (' . get_class($data) . ')';
    } elseif (is_resource($data)) {
        return 'Resource (' . get_resource_type($data) . ')';
    } else {
        return ucfirst(gettype($data));
    }
}

/**
 * Modern CSS stillerini döndürür
 * @return string CSS stilleri
 */
function getPrStyles(): string {
    ob_start();
    ?>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    <style>
    /* Modern Minimal Debug UI */
    :root {
        /* Modern Professional Color Palette */
        --primary: #6366f1;
        --primary-light: #818cf8;
        --primary-dark: #4f46e5;
        --secondary: #06b6d4;
        --secondary-light: #22d3ee;
        --accent: #f59e0b;
        --accent-light: #fbbf24;
        
        /* Backgrounds */
        --background: #121212;
        --surface: #1e1e2e;
        --surface-hover:rgb(26, 26, 40);
        --bs-table-bg:rgb(26, 26, 40);
        --bs-table-striped-bg:rgb(26, 26, 40);
        --surface-elevated: #1e1e2e;
        
        /* Borders & Lines */
        --border: #404040;
        --border-light: #525252;
        --divider:rgb(104, 104, 104);
        
        /* Text Colors */
        --text: #fafafa;
        --text-muted: #a3a3a3;
        --text-dim:rgb(167, 167, 167);
        --text-accent: var(--primary-light);
        --bs-secondary-color:rgb(167, 167, 167);
        /* Status Colors */
        --success: #10b981;
        --success-light: #34d399;
        --warning: #f59e0b;
        --warning-light: #fbbf24;
        --error: #ef4444;
        --error-light: #f87171;
        --info: var(--secondary);
        
        /* Effects */
        --shadow: rgba(0, 0, 0, 0.6);
        --shadow-light: rgba(0, 0, 0, 0.3);
        --glow: rgba(99, 102, 241, 0.2);
        
        /* Sizing */
        --radius: 8px;
        --radius-lg: 12px;
        --radius-xl: 16px;
    }
    
    * { 
        margin: 0; 
        padding: 0; 
        box-sizing: border-box;
    }
    
    body {
        background: var(--background);
        color: var(--text);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        font-weight: 400;
        line-height: 1.5;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    
    /* Container */
    .pr-container {
        background: var(--surface);
        margin: 16px 0;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        overflow: hidden;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px var(--shadow);
    }
    
    .pr-container:hover {
        border-color: var(--primary);
        box-shadow: 0 8px 24px var(--shadow);
    }
    
    /* Header */
    .pr-header {
        background: var(--surface-hover);
        padding: 16px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border);
    }
    
    .pr-title {
        color: var(--primary);
        font-size: 16px;
        font-weight: 600;
        margin: 0;
        letter-spacing: -0.025em;
    }
    
    .pr-meta {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .pr-timestamp {
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 500;
        padding: 4px 8px;
        background: var(--background);
        border-radius: var(--radius);
        font-family: 'SF Mono', Consolas, monospace;
        border: 1px solid var(--border);
    }
    
    .pr-type {
        color: var(--text);
        font-size: 11px;
        font-weight: 600;
        padding: 4px 10px;
        background: var(--accent);
        border-radius: var(--radius);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .pr-toggle {
        background: var(--primary);
        color: var(--background);
        border: none;
        padding: 6px 12px;
        border-radius: var(--radius);
        cursor: pointer;
        font-weight: 600;
        font-size: 12px;
        transition: all 0.2s ease;
        min-width: 32px;
    }
    
    .pr-toggle:hover {
        background: var(--secondary);
        transform: translateY(-1px);
    }
    
    /* Content */
    .pr-content {
        padding: 24px;
    }
    
    .pr-content.collapsed {
        display: none;
    }
    
    /* Section Titles */
    .section-title {
        color: var(--text);
        font-size: 14px;
        font-weight: 700;
        margin: 24px 0 16px 0;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--border);
        text-transform: uppercase;
        letter-spacing: 0.075em;
    }
    
    /* Badges */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 6px;
        border-radius: var(--radius);
        font-size: 10px;
        font-weight: 600;
        margin-right: 4px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }
    
    .badge-public { background: var(--success); color: white; }
    .badge-protected { background: var(--warning); color: white; }
    .badge-private { background: var(--error); color: white; }
    .badge-static { background: var(--info); color: white; }
    .badge-abstract { background: var(--accent); color: white; }
    .badge-final { background: var(--text-dim); color: white; }
    
    .type-hint {
        color: var(--info);
        font-weight: 500;
        font-size: 13px;
    }
    
    /* Tables */
    .pr-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 16px 0;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        overflow: hidden;
        background: var(--surface);
    }
    
    .pr-table th {
        background: var(--surface-hover);
        color: var(--text-muted);
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        border-bottom: 1px solid var(--border);
    }
    
    .pr-table td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border);
        vertical-align: top;
        transition: background-color 0.2s ease;
        font-size: 14px;
    }
    
    .pr-table tr:last-child td {
        border-bottom: none;
    }
    
    .pr-table tr:hover td {
        background-color: var(--surface-hover);
    }
    
    /* Value Types */
    .value-null { color: var(--text-dim); }
    .value-string { color: var(--success); }
    .value-numeric { color: var(--primary); }
    .value-boolean-true { color: var(--success); }
    .value-boolean-false { color: var(--error); }
    .value-array { color: var(--secondary); }
    .value-object { color: var(--accent); }
    
    /* Code */
    .code-syntax {
        font-family: 'SF Mono', Consolas, 'Fira Code', monospace;
        background: var(--surface-hover);
        padding: 4px 8px;
        border-radius: var(--radius);
        font-size: 13px;
        border: 1px solid var(--border);
        color: var(--text-muted);
    }
    
    pre {
        background: var(--surface-hover);
        padding: 16px;
        border-radius: var(--radius);
        overflow-x: auto;
        font-family: 'SF Mono', Consolas, monospace;
        font-size: 13px;
        line-height: 1.4;
        border: 1px solid var(--border);
        color: var(--text-muted);
    }
    
    .file-icon, .folder-icon {
        margin-right: 8px;
        font-size: 16px;
        width: 20px;
        text-align: center;
    }
    
    .file-tree {
        margin: 0;
        padding: 0;
        list-style: none;
        background: var(--pr-surface-variant);
        border-radius: 8px;
        padding: 12px;
    }
    
    .file-tree li {
        margin: 4px 0;
        padding: 6px 8px;
        border-radius: 4px;
        transition: background-color 0.2s ease;
    }
    
    .file-tree li:hover {
        background-color: rgba(100, 255, 218, 0.1);
    }
    
    .sub-tree {
        margin-left: 20px;
        border-left: 2px dashed var(--pr-border);
        padding-left: 16px;
        margin-top: 8px;
    }
    
    .progress-bar {
        height: 8px;
        background: var(--pr-surface-variant);
        border-radius: 4px;
        margin-top: 8px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--pr-primary), var(--pr-secondary));
        transition: width 0.3s ease;
    }
    
    .file-permissions {
        font-family: "Consolas", "Monaco", monospace;
        background: var(--pr-surface-variant);
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 11px;
        border: 1px solid var(--pr-border);
    }
    
    .file-size, .file-time {
        color: var(--pr-text-dim);
        font-size: 11px;
        margin-left: 8px;
    }
    
    pre {
        background: var(--pr-surface-variant);
        padding: 16px;
        border-radius: 8px;
        overflow-x: auto;
        font-family: "Consolas", "Monaco", "Fira Code", monospace;
        font-size: 13px;
        line-height: 1.4;
        border: 1px solid var(--pr-border);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .pr-container { margin: 10px 5px; }
        .pr-header { padding: 12px 16px; }
        .pr-content { padding: 16px; }
        .pr-title { font-size: 16px; }
        .pr-table th, .pr-table td { padding: 8px 12px; font-size: 12px; }
    }
    
    /* Dark scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: var(--pr-surface);
    }
    
    ::-webkit-scrollbar-thumb {
        background: var(--pr-border);
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: var(--pr-primary);
    }
    
    /* Array özel stilleri */
    .array-overview {
        margin: 20px 0;
    }
    
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin: 16px 0;
    }
    
    .stat-card {
        background: var(--surface-hover);
        padding: 16px;
        border-radius: var(--radius);
        text-align: center;
        border: 1px solid var(--border);
        transition: all 0.2s ease;
    }
    
    .stat-card:hover {
        border-color: var(--primary);
        transform: translateY(-1px);
    }
    
    .stat-value {
        font-size: 20px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 4px;
        letter-spacing: -0.025em;
    }
    
    .stat-label {
        font-size: 10px;
        color: var(--text-muted);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.1em;
    }
    
    .type-distribution {
        margin: 20px 0;
    }
    
    .type-chart {
        background: var(--pr-surface-variant);
        border-radius: 8px;
        padding: 16px;
    }
    
    .type-item {
        display: grid;
        grid-template-columns: 30px 1fr 50px 100px 50px;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid var(--pr-border);
    }
    
    .type-item:last-child {
        border-bottom: none;
    }
    
    .type-icon {
        font-size: 18px;
        text-align: center;
    }
    
    .type-name {
        font-weight: 500;
        color: var(--pr-text);
    }
    
    .type-count {
        text-align: center;
        font-size: 14px;
        color: var(--pr-text-dim);
    }
    
    .type-bar {
        height: 8px;
        background: var(--pr-surface);
        border-radius: 4px;
        overflow: hidden;
    }
    
    .type-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--pr-primary), var(--pr-secondary));
        transition: width 0.6s ease;
    }
    
    .type-percentage {
        text-align: center;
        font-size: 12px;
        color: var(--pr-text-dim);
    }
    
    /* View Controls */
    .view-toggles {
        display: flex;
        gap: 8px;
        margin: 16px 0;
        flex-wrap: wrap;
    }
    
    .view-btn {
        background: var(--surface-hover);
        color: var(--text-muted);
        border: 1px solid var(--border);
        padding: 8px 16px;
        border-radius: var(--radius);
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 13px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }
    
    .view-btn:hover {
        background: var(--primary);
        color: var(--background);
        border-color: var(--primary);
        transform: translateY(-1px);
    }
    
    .view-btn.active {
        background: var(--primary);
        color: var(--background);
        border-color: var(--primary);
    }
    
    .btn-primary {
        background: var(--secondary) !important;
        color: white !important;
        border-color: var(--secondary) !important;
    }
    
    /* Icons */
    .btn-icon, .value-icon, .type-icon, .key-icon {
        font-size: 12px;
        font-weight: 600;
        opacity: 0.8;
        font-family: 'SF Mono', Consolas, monospace;
    }
    
    .expand-icon, .toggle-icon {
        font-size: 10px;
        transition: transform 0.2s ease;
    }
    
    .array-view {
        transition: all 0.3s ease;
        opacity: 1;
        max-height: none;
        overflow: visible;
    }
    
    .array-view:not(.active) {
        display: none;
    }
    
    .array-table-container {
        overflow-x: auto;
        border-radius: 8px;
    }
    
    .array-table {
        min-width: 100%;
    }
    
    .array-table th {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .expand-cell {
        text-align: center;
        width: 40px;
    }
    
    .expand-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    
    .expand-btn:hover {
        background: var(--pr-primary);
        transform: scale(1.1);
    }
    
    .expand-icon {
        font-size: 12px;
        transition: transform 0.2s ease;
    }
    
    .expand-btn.expanded .expand-icon {
        transform: rotate(90deg);
    }
    
    .array-key {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .string-key {
        color: var(--pr-warning);
    }
    
    .numeric-key {
        color: var(--pr-info);
    }
    
    .key-icon {
        font-size: 14px;
    }
    
    .type-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        background: var(--pr-surface);
        border-radius: 6px;
        font-size: 12px;
        border: 1px solid var(--pr-border);
    }
    
    .value-preview {
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
        background: var(--pr-surface);
        border: 1px solid var(--pr-border);
        transition: all 0.2s ease;
    }
    
    .value-preview:hover {
        background: var(--pr-surface-variant);
        border-color: var(--pr-primary);
    }
    
    .preview-text {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
    }
    
    .preview-icon {
        opacity: 0.7;
    }
    
    .value-expanded {
        margin-top: 10px;
        animation: expandIn 0.3s ease-out;
    }
    
    @keyframes expandIn {
        from {
            opacity: 0;
            max-height: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            max-height: 500px;
            transform: translateY(0);
        }
    }
    
    .expanded-content {
        background: var(--pr-surface-variant);
        border-radius: 6px;
        padding: 12px;
        border: 1px solid var(--pr-border);
    }
    
    .array-content {
        max-height: 300px;
        overflow-y: auto;
        margin: 0;
    }
    
    .simple-value {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .value-icon {
        opacity: 0.8;
    }
    
    .load-more-btn {
        background: linear-gradient(135deg, var(--pr-primary), var(--pr-secondary));
        color: var(--pr-background);
        border: none;
        padding: 12px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s ease;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .load-more-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }
    
    /* Tree View Stilleri */
    .tree-view {
        background: var(--pr-surface-variant);
        border-radius: 8px;
        padding: 16px;
        font-family: "Consolas", "Monaco", monospace;
        overflow-x: auto;
    }
    
    .tree-node {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
        border-radius: 4px;
        transition: background-color 0.2s ease;
    }
    
    .tree-node:hover {
        background: rgba(100, 255, 218, 0.05);
    }
    
    .tree-toggle {
        cursor: pointer;
        padding: 2px;
        border-radius: 3px;
        transition: all 0.2s ease;
    }
    
    .tree-toggle:hover {
        background: var(--pr-primary);
        transform: scale(1.1);
    }
    
    .toggle-icon {
        font-size: 12px;
        transition: transform 0.2s ease;
    }
    
    .tree-toggle.expanded .toggle-icon {
        transform: rotate(90deg);
    }
    
    .tree-leaf {
        width: 16px;
        text-align: center;
    }
    
    .leaf-icon {
        font-size: 10px;
        opacity: 0.6;
    }
    
    .tree-key {
        color: var(--pr-warning);
        font-weight: 500;
    }
    
    .tree-separator {
        color: var(--pr-text-dim);
        margin: 0 4px;
    }
    
    .tree-value {
        flex: 1;
    }
    
    .tree-type {
        color: var(--pr-info);
        font-style: italic;
    }
    
    .tree-children {
        margin-left: 20px;
        border-left: 1px dashed var(--pr-border);
        padding-left: 12px;
        animation: expandIn 0.3s ease-out;
    }
    
    /* JSON Container */
    .json-container {
        background: var(--pr-surface-variant);
        border-radius: 8px;
        padding: 16px;
        overflow-x: auto;
    }
    
    .json-output {
        margin: 0;
        font-family: "Consolas", "Monaco", monospace;
        font-size: 13px;
        line-height: 1.4;
        color: var(--pr-text);
    }
    
    /* Object Details */
    .object-details {
        background: var(--pr-surface);
        border-radius: 6px;
        padding: 12px;
        border: 1px solid var(--pr-border);
    }
    
    .object-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--pr-border);
    }
    
    .object-icon {
        font-size: 18px;
    }
    
    .object-class {
        font-weight: bold;
        color: var(--pr-primary);
    }
    
    .object-properties {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .object-property {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
    }
    
    .property-name {
        color: var(--pr-warning);
        font-weight: 500;
        min-width: 100px;
    }
    
    .property-separator {
        color: var(--pr-text-dim);
    }
    
    .property-value {
        flex: 1;
    }
    
    .property-more {
        font-style: italic;
        color: var(--pr-text-dim);
        margin-top: 8px;
    }
    
    /* Icon stilleri */
    .section-title i {
        margin-right: 8px;
        font-style: normal;
    }
    
    /* Responsive iyileştirmeler */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .stat-card {
            padding: 12px;
        }
        
        .stat-value {
            font-size: 18px;
        }
        
        .type-item {
            grid-template-columns: 25px 1fr 40px 80px 40px;
            gap: 6px;
        }
        
        .view-toggles {
            flex-direction: column;
        }
        
        .view-btn {
            justify-content: center;
        }
        
        .array-table th, .array-table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .tree-node {
            font-size: 12px;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .type-item {
            grid-template-columns: 20px 1fr 30px 60px 30px;
            gap: 4px;
            font-size: 12px;
        }
    }
    
    /* Object Property iyileştirmeleri */
    .object-stats {
        display: flex;
        gap: 8px;
        margin-top: 8px;
        flex-wrap: wrap;
    }
    
    .stat-badge {
        background: var(--pr-surface-variant);
        color: var(--pr-text-dim);
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        border: 1px solid var(--pr-border);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .stat-icon {
        font-size: 12px;
    }
    
    .object-property.enhanced {
        border: 1px solid var(--pr-border);
        border-radius: 8px;
        margin: 8px 0;
        padding: 12px;
        background: var(--pr-surface);
        transition: all 0.2s ease;
    }
    
    .object-property.enhanced:hover {
        border-color: var(--pr-primary);
        box-shadow: 0 2px 8px rgba(100, 255, 218, 0.1);
    }
    
    .property-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }
    
    .prop-expand-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s ease;
        margin-left: auto;
    }
    
    .prop-expand-btn:hover {
        background: var(--pr-primary);
        transform: scale(1.1);
    }
    
    .prop-expand-btn.expanded .expand-icon {
        transform: rotate(90deg);
    }
    
    .property-preview {
        cursor: pointer;
        padding: 8px 12px;
        background: var(--pr-surface-variant);
        border-radius: 6px;
        border: 1px solid var(--pr-border);
        transition: all 0.2s ease;
        margin-bottom: 8px;
    }
    
    .property-preview:hover {
        background: var(--pr-surface);
        border-color: var(--pr-primary);
    }
    
    .preview-text {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        font-size: 14px;
    }
    
    .property-expanded .expanded-content {
        background: var(--pr-surface-variant);
        border-radius: 6px;
        padding: 12px;
        border: 1px solid var(--pr-border);
        margin-top: 8px;
    }
    
    .simple-property-value {
        padding: 4px 0;
    }
    
    .show-more-props {
        background: linear-gradient(135deg, var(--pr-info), var(--pr-primary));
        color: var(--pr-background);
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s ease;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .show-more-props:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .object-error {
        background: rgba(255, 85, 85, 0.1);
        color: var(--pr-error);
        padding: 12px;
        border-radius: 6px;
        border: 1px solid rgba(255, 85, 85, 0.3);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .error-icon {
        font-size: 16px;
    }
    
    /* Object içindeki Array stilleri */
    .object-array-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .array-stats-inline {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .inline-stat {
        background: var(--pr-surface);
        color: var(--pr-text-dim);
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        border: 1px solid var(--pr-border);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .mini-view-toggles {
        display: flex;
        gap: 4px;
    }
    
    .mini-view-btn {
        background: var(--pr-surface);
        color: var(--pr-text-dim);
        border: 1px solid var(--pr-border);
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 11px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .mini-view-btn:hover {
        background: var(--pr-primary);
        color: var(--pr-background);
    }
    
    .mini-view-btn.active {
        background: var(--pr-primary);
        color: var(--pr-background);
    }
    
    .object-array-view {
        transition: all 0.3s ease;
    }
    
    .object-array-view:not(.active) {
        display: none;
    }
    
    /* Compact Array View */
    .compact-array {
        background: var(--pr-surface);
        border-radius: 6px;
        padding: 8px;
    }
    
    .compact-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
        border-bottom: 1px solid var(--pr-border);
    }
    
    .compact-item:last-child {
        border-bottom: none;
    }
    
    .compact-key {
        color: var(--pr-warning);
        font-weight: 500;
        min-width: 80px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .compact-separator {
        color: var(--pr-text-dim);
    }
    
    .compact-value {
        flex: 1;
        font-size: 13px;
    }
    
    .compact-more {
        padding: 8px;
        text-align: center;
        color: var(--pr-text-dim);
        font-style: italic;
        border-top: 1px dashed var(--pr-border);
        margin-top: 4px;
    }
    
    /* Mini Table */
    .mini-table-container {
        overflow-x: auto;
        border-radius: 6px;
    }
    
    .mini-array-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 12px;
        border-radius: 6px;
        overflow: hidden;
    }
    
    .mini-array-table th {
        background: var(--pr-surface);
        color: var(--pr-primary);
        padding: 8px 10px;
        text-align: left;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .mini-array-table td {
        padding: 6px 10px;
        border-bottom: 1px solid var(--pr-border);
        vertical-align: top;
    }
    
    .mini-array-table tr:nth-child(even) td {
        background-color: rgba(255, 255, 255, 0.02);
    }
    
    .mini-array-table tr:hover td {
        background-color: rgba(100, 255, 218, 0.05);
    }
    
    .mini-key-cell {
        color: var(--pr-warning);
        font-weight: 500;
        max-width: 150px;
        word-break: break-word;
    }
    
    .mini-value-cell {
        font-size: 12px;
    }
    
    .more-row {
        text-align: center;
        font-style: italic;
        color: var(--pr-text-dim);
        background: var(--pr-surface-variant) !important;
    }
    
    /* Mini JSON Container */
    .mini-json-container {
        background: var(--pr-surface);
        border-radius: 6px;
        padding: 8px;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .mini-json-container .json-output {
        margin: 0;
        font-size: 11px;
        line-height: 1.3;
    }
    
    /* Responsive iyileştirmeler */
    @media (max-width: 768px) {
        .object-array-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .array-stats-inline {
            justify-content: center;
        }
        
        .mini-view-toggles {
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .compact-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        
        .compact-key {
            min-width: auto;
        }
        
        .property-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .prop-expand-btn {
            margin-left: 0;
            align-self: flex-end;
        }
    }

    /* Modern Bootstrap Modal Styles */
    .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.8) !important;
        backdrop-filter: blur(4px) !important;
    }
    
    .modal-content {
        background: var(--surface-elevated) !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius-xl) !important;
        box-shadow: 0 25px 50px var(--shadow), 0 0 0 1px var(--glow) !important;
        overflow: hidden !important;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--surface-elevated), var(--surface-hover)) !important;
        border-bottom: 1px solid var(--divider) !important;
        color: var(--text) !important;
        padding: 20px 24px !important;
    }

    .modal-title {
        color: var(--text-accent) !important;
        font-weight: 700 !important;
        font-size: 18px !important;
        letter-spacing: -0.025em !important;
    }

    .modal-body {
        background: var(--surface-elevated) !important;
        color: var(--text) !important;
        max-height: 75vh !important;
        overflow-y: auto !important;
        padding: 24px !important;
        scrollbar-width: thin !important;
        scrollbar-color: var(--border) transparent !important;
    }

    .modal-body::-webkit-scrollbar {
        width: 6px !important;
    }

    .modal-body::-webkit-scrollbar-track {
        background: transparent !important;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: var(--border) !important;
        border-radius: var(--radius) !important;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: var(--primary) !important;
    }

    .modal-footer {
        background: var(--surface-elevated) !important;
        border-top: 1px solid var(--divider) !important;
        padding: 16px 24px !important;
    }

    .btn-close {
        background-color: var(--error) !important;
        border-radius: 50% !important;
        opacity: 0.8 !important;
        transition: all 0.2s ease !important;
    }

    .btn-close:hover {
        opacity: 1 !important;
        transform: scale(1.1) !important;
    }

    /* Modal Array Display */
    .modal-array-container {
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        padding: 20px !important;
        margin: 16px 0 !important;
        border: 1px solid var(--border) !important;
    }

    .modal-array-stats {
        display: flex !important;
        gap: 12px !important;
        margin-bottom: 16px !important;
        flex-wrap: wrap !important;
    }

    .modal-stat-badge {
        background: var(--primary) !important;
        color: white !important;
        padding: 8px 12px !important;
        border-radius: var(--radius) !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        box-shadow: 0 2px 8px var(--shadow-light) !important;
    }

    .modal-view-toggles {
        display: flex !important;
        gap: 8px !important;
        margin: 16px 0 !important;
        flex-wrap: wrap !important;
        padding: 16px 0 !important;
        border-top: 1px solid var(--divider) !important;
        border-bottom: 1px solid var(--divider) !important;
    }

    .modal-view-btn {
        background: var(--surface-hover) !important;
        color: var(--text-muted) !important;
        border: 1px solid var(--border) !important;
        padding: 10px 20px !important;
        border-radius: var(--radius) !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        text-decoration: none !important;
    }

    .modal-view-btn:hover {
        background: var(--primary) !important;
        color: white !important;
        border-color: var(--primary) !important;
        transform: translateY(-1px) !important;
    }

    .modal-view-btn.active {
        background: var(--primary) !important;
        color: white !important;
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px var(--glow) !important;
    }

    .modal-array-view {
        transition: all 0.3s ease !important;
        opacity: 1 !important;
    }

    .modal-array-view:not(.active) {
        display: none !important;
    }

    .modal-json-container {
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        padding: 20px !important;
        border: 1px solid var(--border) !important;
        max-height: 500px !important;
        overflow-y: auto !important;
    }

    .modal-tree-container {
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        padding: 20px !important;
        border: 1px solid var(--border) !important;
        max-height: 500px !important;
        overflow-y: auto !important;
    }

    /* Modal Full Screen Mode */
    .modal-fullscreen {
        max-width: 95vw !important;
        height: 95vh !important;
    }

    .modal-fullscreen .modal-body {
        max-height: 80vh !important;
    }

    .modal-fullscreen .modal-content {
        height: 95vh !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .modal-fullscreen .modal-body {
        flex: 1 !important;
        overflow-y: auto !important;
    }

    /* Modal Animation */
    .modal.fade .modal-dialog {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        transform: translateY(-30px) scale(0.95) !important;
        opacity: 0 !important;
    }

    .modal.show .modal-dialog {
        transform: translateY(0) scale(1) !important;
        opacity: 1 !important;
    }
    
    /* Object Property iyileştirmeleri */
    .object-stats {
        display: flex;
        gap: 8px;
        margin-top: 8px;
        flex-wrap: wrap;
    }
    
    .stat-badge {
        background: var(--pr-surface-variant);
        color: var(--pr-text-dim);
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        border: 1px solid var(--pr-border);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .object-property.enhanced {
        border: 1px solid var(--pr-border);
        border-radius: 8px;
        margin: 8px 0;
        padding: 12px;
        background: var(--pr-surface);
        transition: all 0.2s ease;
    }
    
    .object-property.enhanced:hover {
        border-color: var(--pr-primary);
        box-shadow: 0 2px 8px rgba(100, 255, 218, 0.1);
    }
    
    .property-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }
    
    .prop-expand-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s ease;
        margin-left: auto;
    }
    
    .prop-expand-btn:hover {
        background: var(--pr-primary);
        transform: scale(1.1);
    }
    
    .prop-expand-btn.expanded .expand-icon {
        transform: rotate(90deg);
    }
    
    .property-preview {
        cursor: pointer;
        padding: 8px 12px;
        background: var(--pr-surface-variant);
        border-radius: 6px;
        border: 1px solid var(--pr-border);
        transition: all 0.2s ease;
        margin-bottom: 8px;
    }
    
    .property-preview:hover {
        background: var(--pr-surface);
        border-color: var(--pr-primary);
    }
    
    .preview-text {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        font-size: 14px;
    }
    
    .show-more-props {
        background: linear-gradient(135deg, var(--pr-info), var(--pr-primary));
        color: var(--pr-background);
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s ease;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .show-more-props:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .object-error {
        background: rgba(255, 85, 85, 0.1);
        color: var(--pr-error);
        padding: 12px;
        border-radius: 6px;
        border: 1px solid rgba(255, 85, 85, 0.3);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Object içindeki Array stilleri */
    .object-array-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .array-stats-inline {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .inline-stat {
        background: var(--pr-surface);
        color: var(--pr-text-dim);
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        border: 1px solid var(--pr-border);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .mini-view-toggles {
        display: flex;
        gap: 4px;
    }
    
    .mini-view-btn {
        background: var(--pr-surface);
        color: var(--pr-text-dim);
        border: 1px solid var(--pr-border);
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 11px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .mini-view-btn:hover {
        background: var(--pr-primary);
        color: var(--pr-background);
    }
    
    .mini-view-btn.active {
        background: var(--pr-primary);
        color: var(--pr-background);
    }
    
    .object-array-view {
        transition: all 0.3s ease;
    }
    
    .object-array-view:not(.active) {
        display: none;
    }
    
    /* Compact Array View */
    .compact-array {
        background: var(--pr-surface);
        border-radius: 6px;
        padding: 8px;
    }
    
    .compact-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
        border-bottom: 1px solid var(--pr-border);
    }
    
    .compact-item:last-child {
        border-bottom: none;
    }
    
    .compact-key {
        color: var(--pr-warning);
        font-weight: 500;
        min-width: 80px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .compact-separator {
        color: var(--pr-text-dim);
    }
    
    .compact-value {
        flex: 1;
        font-size: 13px;
    }
    
    .compact-more {
        padding: 8px;
        text-align: center;
        color: var(--pr-text-dim);
        font-style: italic;
        border-top: 1px dashed var(--pr-border);
        margin-top: 4px;
    }
    
    /* Mini Table */
    .mini-table-container {
        overflow-x: auto;
        border-radius: 6px;
    }
    
    .mini-array-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 12px;
        border-radius: 6px;
        overflow: hidden;
    }
    
    .mini-array-table th {
        background: var(--pr-surface);
        color: var(--pr-primary);
        padding: 8px 10px;
        text-align: left;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .mini-array-table td {
        padding: 6px 10px;
        border-bottom: 1px solid var(--pr-border);
        vertical-align: top;
    }
    
    .mini-array-table tr:nth-child(even) td {
        background-color: rgba(255, 255, 255, 0.02);
    }
    
    .mini-array-table tr:hover td {
        background-color: rgba(100, 255, 218, 0.05);
    }
    
    .mini-key-cell {
        color: var(--pr-warning);
        font-weight: 500;
        max-width: 150px;
        word-break: break-word;
    }
    
    .mini-value-cell {
        font-size: 12px;
    }
    
    .more-row {
        text-align: center;
        font-style: italic;
        color: var(--pr-text-dim);
        background: var(--pr-surface-variant) !important;
    }
    
    /* Mini JSON Container */
    .mini-json-container {
        background: var(--pr-surface);
        border-radius: 6px;
        padding: 8px;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .mini-json-container .json-output {
        margin: 0;
        font-size: 11px;
        line-height: 1.3;
    }
    
    /* Responsive iyileştirmeler */
    @media (max-width: 768px) {
        .object-array-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .array-stats-inline {
            justify-content: center;
        }
        
        .mini-view-toggles {
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .compact-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        
        .compact-key {
            min-width: auto;
        }
        
        .property-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .prop-expand-btn {
            margin-left: 0;
            align-self: flex-end;
        }
    }

    /* Modern Bootstrap Modal Styles */
    .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.8) !important;
        backdrop-filter: blur(4px) !important;
    }
    
    .modal-content {
        background: var(--surface-elevated) !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius-xl) !important;
        box-shadow: 0 25px 50px var(--shadow), 0 0 0 1px var(--glow) !important;
        overflow: hidden !important;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--surface-elevated), var(--surface-hover)) !important;
        border-bottom: 1px solid var(--divider) !important;
        color: var(--text) !important;
        padding: 20px 24px !important;
    }

    .modal-title {
        color: var(--text-accent) !important;
        font-weight: 700 !important;
        font-size: 18px !important;
        letter-spacing: -0.025em !important;
    }

    .modal-body {
        background: var(--surface-elevated) !important;
        color: var(--text) !important;
        max-height: 75vh !important;
        overflow-y: auto !important;
        padding: 24px !important;
        scrollbar-width: thin !important;
        scrollbar-color: var(--border) transparent !important;
    }

    .modal-body::-webkit-scrollbar {
        width: 6px !important;
    }

    .modal-body::-webkit-scrollbar-track {
        background: transparent !important;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: var(--border) !important;
        border-radius: var(--radius) !important;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: var(--primary) !important;
    }

    .modal-footer {
        background: var(--surface-elevated) !important;
        border-top: 1px solid var(--divider) !important;
        padding: 16px 24px !important;
    }

    .btn-close {
        background-color: var(--error) !important;
        border-radius: 50% !important;
        opacity: 0.8 !important;
        transition: all 0.2s ease !important;
    }

    .btn-close:hover {
        opacity: 1 !important;
        transform: scale(1.1) !important;
    }

    /* Modal Array Display */
    .modal-array-container {
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        padding: 20px !important;
        margin: 16px 0 !important;
        border: 1px solid var(--border) !important;
    }

    .modal-array-stats {
        display: flex !important;
        gap: 12px !important;
        margin-bottom: 16px !important;
        flex-wrap: wrap !important;
    }

    .modal-stat-badge {
        background: var(--primary) !important;
        color: white !important;
        padding: 8px 12px !important;
        border-radius: var(--radius) !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        box-shadow: 0 2px 8px var(--shadow-light) !important;
    }

    .modal-view-toggles {
        display: flex !important;
        gap: 8px !important;
        margin: 16px 0 !important;
        flex-wrap: wrap !important;
        padding: 16px 0 !important;
        border-top: 1px solid var(--divider) !important;
        border-bottom: 1px solid var(--divider) !important;
    }

    .modal-view-btn {
        background: var(--surface-hover) !important;
        color: var(--text-muted) !important;
        border: 1px solid var(--border) !important;
        padding: 10px 20px !important;
        border-radius: var(--radius) !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        text-decoration: none !important;
    }

    .modal-view-btn:hover {
        background: var(--primary) !important;
        color: white !important;
        border-color: var(--primary) !important;
        transform: translateY(-1px) !important;
    }

    .modal-view-btn.active {
        background: var(--primary) !important;
        color: white !important;
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px var(--glow) !important;
    }

    .modal-array-view {
        transition: all 0.3s ease !important;
        opacity: 1 !important;
    }

    .modal-array-view:not(.active) {
        display: none !important;
    }

    .modal-json-container {
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        padding: 20px !important;
        border: 1px solid var(--border) !important;
        max-height: 500px !important;
        overflow-y: auto !important;
    }

    .modal-tree-container {
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        padding: 20px !important;
        border: 1px solid var(--border) !important;
        max-height: 500px !important;
        overflow-y: auto !important;
    }

    /* Modal Full Screen Mode */
    .modal-fullscreen {
        max-width: 95vw !important;
        height: 95vh !important;
    }

    .modal-fullscreen .modal-body {
        max-height: 80vh !important;
    }

    .modal-fullscreen .modal-content {
        height: 95vh !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .modal-fullscreen .modal-body {
        flex: 1 !important;
        overflow-y: auto !important;
    }

    /* Modal Animation */
    .modal.fade .modal-dialog {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        transform: translateY(-30px) scale(0.95) !important;
        opacity: 0 !important;
    }

    .modal.show .modal-dialog {
        transform: translateY(0) scale(1) !important;
        opacity: 1 !important;
    }
    
    /* Object Property iyileştirmeleri */
    .object-stats {
        display: flex;
        gap: 8px;
        margin-top: 8px;
        flex-wrap: wrap;
    }
    
    .stat-badge {
        background: var(--pr-surface-variant);
        color: var(--pr-text-dim);
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        border: 1px solid var(--pr-border);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .object-property.enhanced {
        border: 1px solid var(--pr-border);
        border-radius: 8px;
        margin: 8px 0;
        padding: 12px;
        background: var(--pr-surface);
        transition: all 0.2s ease;
    }
    
    .object-property.enhanced:hover {
        border-color: var(--pr-primary);
        box-shadow: 0 2px 8px rgba(100, 255, 218, 0.1);
    }
    
    .property-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }
    
    .prop-expand-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s ease;
        margin-left: auto;
    }
    
    .prop-expand-btn:hover {
        background: var(--pr-primary);
        transform: scale(1.1);
    }
    
    .prop-expand-btn.expanded .expand-icon {
        transform: rotate(90deg);
    }
    
    .property-preview {
        cursor: pointer;
        padding: 8px 12px;
        background: var(--pr-surface-variant);
        border-radius: 6px;
        border: 1px solid var(--pr-border);
        transition: all 0.2s ease;
        margin-bottom: 8px;
    }
    
    .property-preview:hover {
        background: var(--pr-surface);
        border-color: var(--pr-primary);
    }
    
    .preview-text {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        font-size: 14px;
    }
    
    .show-more-props {
        background: linear-gradient(135deg, var(--pr-info), var(--pr-primary));
        color: var(--pr-background);
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s ease;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .show-more-props:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .object-error {
        background: rgba(255, 85, 85, 0.1);
        color: var(--pr-error);
        padding: 12px;
        border-radius: 6px;
        border: 1px solid rgba(255, 85, 85, 0.3);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Object içindeki Array stilleri */
    .object-array-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .array-stats-inline {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .inline-stat {
        background: var(--pr-surface);
        color: var(--pr-text-dim);
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        border: 1px solid var(--pr-border);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .mini-view-toggles {
        display: flex;
        gap: 4px;
    }
    
    .mini-view-btn {
        background: var(--pr-surface);
        color: var(--pr-text-dim);
        border: 1px solid var(--pr-border);
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 11px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .mini-view-btn:hover {
        background: var(--pr-primary);
        color: var(--pr-background);
    }
    
    .mini-view-btn.active {
        background: var(--pr-primary);
        color: var(--pr-background);
    }
    
    .object-array-view {
        transition: all 0.3s ease;
    }
    
    .object-array-view:not(.active) {
        display: none;
    }
    
    /* Compact Array View */
    .compact-array {
        background: var(--pr-surface);
        border-radius: 6px;
        padding: 8px;
    }
    
    .compact-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
        border-bottom: 1px solid var(--pr-border);
    }
    
    .compact-item:last-child {
        border-bottom: none;
    }
    
    .compact-key {
        color: var(--pr-warning);
        font-weight: 500;
        min-width: 80px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .compact-separator {
        color: var(--pr-text-dim);
    }
    
    .compact-value {
        flex: 1;
        font-size: 13px;
    }
    
    .compact-more {
        padding: 8px;
        text-align: center;
        color: var(--pr-text-dim);
        font-style: italic;
        border-top: 1px dashed var(--pr-border);
        margin-top: 4px;
    }
    
    /* Mini Table */
    .mini-table-container {
        overflow-x: auto;
        border-radius: 6px;
    }
    
    .mini-array-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 12px;
        border-radius: 6px;
        overflow: hidden;
    }
    
    .mini-array-table th {
        background: var(--pr-surface);
        color: var(--pr-primary);
        padding: 8px 10px;
        text-align: left;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .mini-array-table td {
        padding: 6px 10px;
        border-bottom: 1px solid var(--pr-border);
        vertical-align: top;
    }
    
    .mini-array-table tr:nth-child(even) td {
        background-color: rgba(255, 255, 255, 0.02);
    }
    
    .mini-array-table tr:hover td {
        background-color: rgba(100, 255, 218, 0.05);
    }
    
    .mini-key-cell {
        color: var(--pr-warning);
        font-weight: 500;
        max-width: 150px;
        word-break: break-word;
    }
    
    .mini-value-cell {
        font-size: 12px;
    }
    
    .more-row {
        text-align: center;
        font-style: italic;
        color: var(--pr-text-dim);
        background: var(--pr-surface-variant) !important;
    }
    
    /* Mini JSON Container */
    .mini-json-container {
        background: var(--pr-surface);
        border-radius: 6px;
        padding: 8px;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .mini-json-container .json-output {
        margin: 0;
        font-size: 11px;
        line-height: 1.3;
    }
    
    /* Responsive iyileştirmeler */
    @media (max-width: 768px) {
        .object-array-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .array-stats-inline {
            justify-content: center;
        }
        
        .mini-view-toggles {
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .compact-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }
        
        .compact-key {
            min-width: auto;
        }
        
        .property-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .prop-expand-btn {
            margin-left: 0;
            align-self: flex-end;
        }
    }

    /* Modern Bootstrap Modal Styles */
    .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.8) !important;
        backdrop-filter: blur(4px) !important;
    }
    
    .modal-content {
        background: var(--surface-elevated) !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius-xl) !important;
        box-shadow: 0 25px 50px var(--shadow), 0 0 0 1px var(--glow) !important;
        overflow: hidden !important;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--surface-elevated), var(--surface-hover)) !important;
        border-bottom: 1px solid var(--divider) !important;
        color: var(--text) !important;
        padding: 20px 24px !important;
    }

    .modal-title {
        color: var(--text-accent) !important;
        font-weight: 700 !important;
        font-size: 18px !important;
        letter-spacing: -0.025em !important;
    }

    .modal-body {
        background: var(--surface-elevated) !important;
        color: var(--text) !important;
        max-height: 75vh !important;
        overflow-y: auto !important;
        padding: 24px !important;
        scrollbar-width: thin !important;
        scrollbar-color: var(--border) transparent !important;
    }

    .modal-body::-webkit-scrollbar {
        width: 6px !important;
    }

    .modal-body::-webkit-scrollbar-track {
        background: transparent !important;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: var(--border) !important;
        border-radius: var(--radius) !important;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: var(--primary) !important;
    }

    .modal-footer {
        background: var(--surface-elevated) !important;
        border-top: 1px solid var(--divider) !important;
        padding: 16px 24px !important;
    }

    .btn-close {
        background-color: var(--error) !important;
        border-radius: 50% !important;
        opacity: 0.8 !important;
        transition: all 0.2s ease !important;
    }

    .btn-close:hover {
        opacity: 1 !important;
        transform: scale(1.1) !important;
    }

    /* Modal Array Display */
    .modal-array-container {
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        padding: 20px !important;
        margin: 16px 0 !important;
        border: 1px solid var(--border) !important;
    }

    .modal-array-stats {
        display: flex !important;
        gap: 12px !important;
        margin-bottom: 16px !important;
        flex-wrap: wrap !important;
    }

    .modal-stat-badge {
        background: var(--primary) !important;
        color: white !important;
        padding: 8px 12px !important;
        border-radius: var(--radius) !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        box-shadow: 0 2px 8px var(--shadow-light) !important;
    }

    .modal-view-toggles {
        display: flex !important;
        gap: 8px !important;
        margin: 16px 0 !important;
        flex-wrap: wrap !important;
        padding: 16px 0 !important;
        border-top: 1px solid var(--divider) !important;
        border-bottom: 1px solid var(--divider) !important;
    }

    .modal-view-btn {
        background: var(--surface-hover) !important;
        color: var(--text-muted) !important;
        border: 1px solid var(--border) !important;
        padding: 10px 20px !important;
        border-radius: var(--radius) !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        text-decoration: none !important;
    }

    .modal-view-btn:hover {
        background: var(--primary) !important;
        color: white !important;
        border-color: var(--primary) !important;
        transform: translateY(-1px) !important;
    }

    .modal-view-btn.active {
        background: var(--primary) !important;
        color: white !important;
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px var(--glow) !important;
    }

    .modal-array-view {
        transition: all 0.3s ease !important;
        opacity: 1 !important;
    }

    .modal-array-view:not(.active) {
        display: none !important;
    }

    .modal-json-container {
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        padding: 20px !important;
        border: 1px solid var(--border) !important;
        max-height: 500px !important;
        overflow-y: auto !important;
    }

    .modal-tree-container {
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        padding: 20px !important;
        border: 1px solid var(--border) !important;
        max-height: 500px !important;
        overflow-y: auto !important;
    }

    /* Modal Full Screen Mode */
    .modal-fullscreen {
        max-width: 95vw !important;
        height: 95vh !important;
    }

    .modal-fullscreen .modal-body {
        max-height: 80vh !important;
    }

    .modal-fullscreen .modal-content {
        height: 95vh !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .modal-fullscreen .modal-body {
        flex: 1 !important;
        overflow-y: auto !important;
    }

    /* Modal Animation */
    .modal.fade .modal-dialog {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        transform: translateY(-30px) scale(0.95) !important;
        opacity: 0 !important;
    }

    .modal.show .modal-dialog {
        transform: translateY(0) scale(1) !important;
        opacity: 1 !important;
    }
    
    /* Object Property iyileştirmeleri */
    .object-stats {
        display: flex;
        gap: 8px;
        margin-top: 8px;
        flex-wrap: wrap;
    }
    
    .stat-badge {
        background: var(--pr-surface-variant);
        color: var(--pr-text-dim);
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        border: 1px solid var(--pr-border);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .object-property.enhanced {
        border: 1px solid var(--pr-border);
        border-radius: 8px;
        margin: 8px 0;
        padding: 12px;
        background: var(--pr-surface);
        transition: all 0.2s ease;
    }
    
    .object-property.enhanced:hover {
        border-color: var(--pr-primary);
        box-shadow: 0 2px 8px rgba(100, 255, 218, 0.1);
    }
    
    .property-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }
    
    .prop-expand-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s ease;
        margin-left: auto;
    }
    
    .prop-expand-btn:hover {
        background: var(--pr-primary);
        transform: scale(1.1);
    }
    
    .prop-expand-btn.expanded .expand-icon {
        transform: rotate(90deg);
    }
    
    .property-preview {
        cursor: pointer;
        padding: 8px 12px;
        background: var(--pr-surface-variant);
        border-radius: 6px;
        border: 1px solid var(--pr-border);
        transition: all 0.2s ease;
        margin-bottom: 8px;
    }
    
    .property-preview:hover {
        background: var(--pr-surface);
        border-color: var(--pr-primary);
    }
    
    .preview-text {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        font-size: 14px;
    }
    
    .show-more-props {
        background: linear-gradient(135deg, var(--pr-info), var(--pr-primary));
        color: var(--pr-background);
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s ease;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .show-more-props:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .object-error {
        background: rgba(255, 85, 85, 0.1);
        color: var(--pr-error);
        padding: 12px;
        border-radius: 6px;
        border: 1px solid rgba(255, 85, 85, 0.3);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Object içindeki Array stilleri */
    .object-array-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .array-stats-inline {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .inline-stat {
        background: var(--pr-surface);
        color: var(--pr-text-dim);
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        border: 1px solid var(--pr-border);
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .mini-view-toggles {
        display: flex;
        gap: 4px;
    }
    
    .mini-view-btn {
        background: var(--pr-surface);
        color: var(--pr-text-dim);
        border: 1px solid var(--pr-border);
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 11px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .mini-view-btn:hover {
        background: var(--pr-primary);
        color: var(--pr-background);
    }
    
    .mini-view-btn.active {
        background: var(--pr-primary);
        color: var(--pr-background);
    }
    
    .object-array-view {
        transition: all 0.3s ease;
    }
    
    .object-array-view:not(.active) {
        display: none;
    }
    
    /* Compact Array View */
    .compact-array {
        background: var(--pr-surface);
        border-radius: 6px;
        padding: 8px;
    }
    
    .compact-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
        border-bottom: 1px solid var(--pr-border);
    }
    
    .compact-item:last-child {
        border-bottom: none;
    }
    
    .compact-key {
        color: var(--pr-warning);
        font-weight: 500;
        min-width: 80px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .compact-separator {
        color: var(--pr-text-dim);
    }
    
    .compact-value {
        flex: 1;
        font-size: 13px;
    }
    
    .compact-more {
        padding: 8px;
        text-align: center;
        color: var(--pr-text-dim);
        font-style: italic;
        border-top: 1px dashed var(--pr-border);
        margin-top: 4px;
    }
    
    /* Mini Table */
    .mini-table-container {
        overflow-x: auto;
        border-radius: 6px;
    }
    
    .mini-array-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 12px;
        border-radius: 6px;
        overflow: hidden;
    }
    
    .mini-array-table th {
        background: var(--pr-surface);
        color: var(--pr-primary);
        padding: 8px 10px;
        text-align: left;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .mini-array-table td {
        padding: 6px 10px;
        border-bottom: 1px solid var(--pr-border);
        vertical-align: top;
    }
    
    .mini-array-table tr:nth-child(even) td {
        background-color: rgba(255, 255, 255, 0.02);
    }
    
    .mini-array-table tr:hover td {
        background-color: rgba(100, 255, 218, 0.05);
    }
    
    .mini-key-cell {
        color: var(--pr-warning);
        font-weight: 500;
        max-width: 150px;
        word-break: break-word;
    }
    
    .mini-value-cell {
        font-size: 12px;
    }
    
    .more-row {
        text-align: center;
        font-style: italic;
        color: var(--pr-text-dim);
        background: var(--pr-surface-variant) !important;
    }
    
    /* Mini JSON Container */
    .mini-json-container {
        background: var(--pr-surface);
        border-radius: 6px;
        padding: 8px;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .mini-json-container .json-output {
        margin: 0;
        font-size: 11px;
        line-height: 1.3;
    }

    /* Modern Class Viewer Styles */
    .modern-class-container {
        background: var(--surface-elevated) !important;
        border-radius: var(--radius-lg) !important;
        border: 1px solid var(--border) !important;
        overflow: hidden !important;
        box-shadow: 0 8px 24px var(--shadow) !important;
        margin: 20px 0 !important;
    }

    .class-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;
        padding: 24px !important;
        color: white !important;
    }

    .class-title-section {
        margin-bottom: 16px !important;
    }

    .class-name {
        margin: 0 !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        font-size: 20px !important;
        font-weight: 700 !important;
    }

    .class-icon {
        font-size: 24px !important;
        opacity: 0.9 !important;
    }

    .class-full-name {
        font-family: 'SF Mono', Consolas, monospace !important;
        letter-spacing: -0.5px !important;
    }

    .class-badges {
        display: flex !important;
        gap: 8px !important;
        flex-wrap: wrap !important;
        margin-top: 12px !important;
    }

    .class-badge {
        background: rgba(255, 255, 255, 0.2) !important;
        color: white !important;
        padding: 4px 12px !important;
        border-radius: var(--radius) !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
    }

    .class-stats-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)) !important;
        gap: 16px !important;
        margin-top: 20px !important;
    }

    .stat-item {
        text-align: center !important;
        background: rgba(255, 255, 255, 0.1) !important;
        padding: 12px !important;
        border-radius: var(--radius) !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
    }

    .stat-number {
        display: block !important;
        font-size: 24px !important;
        font-weight: 800 !important;
        color: white !important;
        margin-bottom: 4px !important;
    }

    .stat-label {
        font-size: 10px !important;
        color: rgba(255, 255, 255, 0.8) !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        font-weight: 600 !important;
    }

    .class-navigation {
        background: var(--surface-hover) !important;
        padding: 0 !important;
        display: flex !important;
        overflow-x: auto !important;
        border-bottom: 1px solid var(--border) !important;
    }

    .nav-tab {
        background: transparent !important;
        border: none !important;
        padding: 16px 24px !important;
        color: var(--text-muted) !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        border-bottom: 3px solid transparent !important;
        white-space: nowrap !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }

    .nav-tab:hover {
        background: var(--surface) !important;
        color: var(--text) !important;
    }

    .nav-tab.active {
        background: var(--surface-elevated) !important;
        color: var(--primary) !important;
        border-bottom-color: var(--primary) !important;
    }

    .class-content-tabs {
        min-height: 400px !important;
    }

    .tab-content {
        display: none !important;
        padding: 24px !important;
        animation: fadeIn 0.3s ease !important;
    }

    .tab-content.active {
        display: block !important;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Overview Tab Styles */
    .overview-grid {
        display: grid !important;
        gap: 20px !important;
    }

    .overview-section {
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        padding: 20px !important;
        border: 1px solid var(--border) !important;
    }

    .section-header {
        font-size: 16px !important;
        font-weight: 700 !important;
        color: var(--text) !important;
        margin: 0 0 16px 0 !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }

    .info-grid {
        display: grid !important;
        gap: 12px !important;
    }

    .info-item {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 8px 0 !important;
        border-bottom: 1px solid var(--divider) !important;
    }

    .info-item:last-child {
        border-bottom: none !important;
    }

    .info-label {
        font-weight: 600 !important;
        color: var(--text-muted) !important;
        font-size: 13px !important;
    }

    .info-value {
        font-family: 'SF Mono', Consolas, monospace !important;
        font-weight: 500 !important;
        color: var(--text) !important;
    }

    .class-name-highlight {
        color: var(--primary) !important;
        font-weight: 700 !important;
    }

    .namespace-highlight {
        color: var(--accent) !important;
    }

    .file-path {
        color: var(--secondary) !important;
    }

    .line-number {
        color: var(--text-muted) !important;
    }

    .relations-container {
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
    }

    .relation-item {
        display: flex !important;
        align-items: flex-start !important;
        gap: 12px !important;
        padding: 12px !important;
        background: var(--surface-hover) !important;
        border-radius: var(--radius) !important;
        border: 1px solid var(--border) !important;
    }

    .relation-type {
        font-weight: 600 !important;
        font-size: 12px !important;
        white-space: nowrap !important;
        min-width: 100px !important;
    }

    .parent-class {
        color: var(--primary) !important;
    }

    .interface {
        color: var(--secondary) !important;
    }

    .trait {
        color: var(--accent) !important;
    }

    .relation-name {
        font-family: 'SF Mono', Consolas, monospace !important;
        color: var(--text) !important;
        font-weight: 500 !important;
    }

    .relation-list {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 6px !important;
    }

    .interface-badge {
        background: var(--secondary) !important;
        color: white !important;
        padding: 4px 8px !important;
        border-radius: var(--radius) !important;
        font-size: 10px !important;
        font-weight: 600 !important;
    }

    .trait-badge {
        background: var(--accent) !important;
        color: white !important;
        padding: 4px 8px !important;
        border-radius: var(--radius) !important;
        font-size: 10px !important;
        font-weight: 600 !important;
    }

    .doc-comment {
        background: var(--surface-hover) !important;
        border-radius: var(--radius) !important;
        padding: 16px !important;
        border: 1px solid var(--border) !important;
    }

    .doc-formatted {
        font-family: 'SF Mono', Consolas, monospace !important;
        font-size: 13px !important;
        line-height: 1.6 !important;
    }

    .doc-tag {
        margin: 8px 0 !important;
        display: flex !important;
        gap: 8px !important;
    }

    .doc-tag-name {
        color: var(--primary) !important;
        font-weight: 700 !important;
        min-width: 80px !important;
    }

    .doc-tag-desc {
        color: var(--text-muted) !important;
    }

    .doc-description {
        color: var(--text) !important;
        margin: 4px 0 !important;
    }

    /* Hierarchy Tab Styles */
    .hierarchy-container {
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        padding: 20px !important;
        border: 1px solid var(--border) !important;
    }

    .hierarchy-levels {
        font-family: 'SF Mono', Consolas, monospace !important;
        margin-top: 16px !important;
    }

    .hierarchy-level {
        margin: 8px 0 !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }

    .hierarchy-connector {
        color: var(--text-muted) !important;
        font-weight: 600 !important;
    }

    .hierarchy-class {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 8px 12px !important;
        border-radius: var(--radius) !important;
        border: 1px solid var(--border) !important;
    }

    .target-class {
        background: var(--primary) !important;
        color: white !important;
        border-color: var(--primary) !important;
    }

    .parent-class {
        background: var(--surface-hover) !important;
    }

    .hierarchy-icon {
        font-size: 16px !important;
    }

    .hierarchy-name {
        font-weight: 700 !important;
    }

    .hierarchy-namespace {
        font-size: 11px !important;
        opacity: 0.7 !important;
        margin-left: 8px !important;
    }

    .interface-hierarchy, .trait-hierarchy {
        margin-top: 20px !important;
        padding-top: 20px !important;
        border-top: 1px solid var(--divider) !important;
    }

    .subsection-header {
        font-size: 14px !important;
        font-weight: 600 !important;
        color: var(--text-muted) !important;
        margin-bottom: 12px !important;
    }

    .interface-list, .trait-list {
        display: grid !important;
        gap: 8px !important;
    }

    .interface-item, .trait-item {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 8px 12px !important;
        background: var(--surface-hover) !important;
        border-radius: var(--radius) !important;
        border: 1px solid var(--border) !important;
        font-family: 'SF Mono', Consolas, monospace !important;
    }

    .interface-icon, .trait-icon {
        font-size: 14px !important;
    }

    .interface-name, .trait-name {
        font-weight: 600 !important;
        color: var(--text) !important;
    }

    .interface-namespace, .trait-namespace {
        font-size: 11px !important;
        color: var(--text-muted) !important;
        margin-left: auto !important;
    }

    /* Properties Tab Styles */
    .property-controls {
        background: var(--surface-hover) !important;
        padding: 16px !important;
        border-radius: var(--radius) !important;
        margin-bottom: 20px !important;
        border: 1px solid var(--border) !important;
    }

    .search-container {
        margin-bottom: 12px !important;
    }

    .property-search, .method-search {
        width: 100% !important;
        padding: 10px 16px !important;
        background: var(--surface) !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius) !important;
        color: var(--text) !important;
        font-size: 14px !important;
    }

    .property-search:focus, .method-search:focus {
        outline: none !important;
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px var(--glow) !important;
    }

    .filter-buttons {
        display: flex !important;
        gap: 8px !important;
        flex-wrap: wrap !important;
    }

    .filter-btn {
        background: var(--surface) !important;
        color: var(--text-muted) !important;
        border: 1px solid var(--border) !important;
        padding: 6px 12px !important;
        border-radius: var(--radius) !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        font-size: 12px !important;
        font-weight: 600 !important;
    }

    .filter-btn:hover {
        background: var(--primary) !important;
        color: white !important;
        border-color: var(--primary) !important;
    }

    .filter-btn.active {
        background: var(--primary) !important;
        color: white !important;
        border-color: var(--primary) !important;
    }

    .properties-container, .methods-container {
        display: grid !important;
        gap: 16px !important;
    }

    .property-card, .method-card {
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        border: 1px solid var(--border) !important;
        overflow: hidden !important;
        transition: all 0.2s ease !important;
    }

    .property-card:hover, .method-card:hover {
        border-color: var(--primary) !important;
        box-shadow: 0 4px 12px var(--shadow-light) !important;
    }

    .property-card-header, .method-card-header {
        background: var(--surface-hover) !important;
        padding: 16px !important;
        border-bottom: 1px solid var(--border) !important;
    }

    .property-signature, .method-signature {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        gap: 12px !important;
    }

    .property-modifiers, .method-modifiers {
        display: flex !important;
        gap: 6px !important;
        flex-wrap: wrap !important;
    }

    .modifier-badge {
        padding: 2px 6px !important;
        border-radius: var(--radius) !important;
        font-size: 10px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }

    .modifier-badge.public {
        background: var(--success) !important;
        color: white !important;
    }

    .modifier-badge.protected {
        background: var(--warning) !important;
        color: white !important;
    }

    .modifier-badge.private {
        background: var(--error) !important;
        color: white !important;
    }

    .modifier-badge.static {
        background: var(--info) !important;
        color: white !important;
    }

    .modifier-badge.abstract {
        background: var(--accent) !important;
        color: white !important;
    }

    .modifier-badge.final {
        background: var(--text-dim) !important;
        color: white !important;
    }

    .modifier-badge.readonly {
        background: var(--secondary) !important;
        color: white !important;
    }

    .property-declaration, .method-declaration {
        font-family: 'SF Mono', Consolas, monospace !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        color: var(--text) !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        flex-wrap: wrap !important;
    }

    .property-type, .return-type {
        color: var(--secondary) !important;
        font-weight: 500 !important;
    }

    .property-name, .method-name {
        color: var(--primary) !important;
    }

    .default-value {
        color: var(--accent) !important;
        font-style: italic !important;
    }

    .method-params {
        color: var(--text-muted) !important;
    }

    .param-type {
        color: var(--secondary) !important;
    }

    .param-name {
        color: var(--text) !important;
    }

    .param-default {
        color: var(--accent) !important;
        font-style: italic !important;
    }

    .property-actions, .method-actions {
        display: flex !important;
        gap: 8px !important;
    }

    .action-btn {
        background: var(--surface) !important;
        border: 1px solid var(--border) !important;
        padding: 6px 8px !important;
        border-radius: var(--radius) !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
    }

    .action-btn:hover {
        background: var(--primary) !important;
        border-color: var(--primary) !important;
        color: white !important;
    }

    .action-icon {
        font-size: 12px !important;
    }

    .property-details, .method-details {
        padding: 16px !important;
        border-top: 1px solid var(--divider) !important;
        background: var(--surface-elevated) !important;
        animation: slideDown 0.3s ease !important;
    }

    @keyframes slideDown {
        from { opacity: 0; max-height: 0; }
        to { opacity: 1; max-height: 500px; }
    }

    .property-doc, .method-doc {
        margin-bottom: 16px !important;
    }

    .property-value-section {
        margin-bottom: 16px !important;
    }

    .value-container {
        background: var(--surface-hover) !important;
        border-radius: var(--radius) !important;
        padding: 12px !important;
        border: 1px solid var(--border) !important;
    }

    .array-value-preview {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        gap: 12px !important;
        margin-bottom: 12px !important;
    }

    .array-info {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        font-weight: 600 !important;
        color: var(--text) !important;
    }

    .array-icon {
        font-size: 16px !important;
    }

    .array-type {
        color: var(--text-muted) !important;
        font-weight: 400 !important;
        font-size: 12px !important;
    }

    .view-array-btn, .view-object-btn {
        background: var(--secondary) !important;
        color: white !important;
        border: none !important;
        padding: 8px 12px !important;
        border-radius: var(--radius) !important;
        cursor: pointer !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        transition: all 0.2s ease !important;
    }

    .view-array-btn:hover, .view-object-btn:hover {
        background: var(--primary) !important;
        transform: translateY(-1px) !important;
    }

    .array-preview {
        max-height: 200px !important;
        overflow-y: auto !important;
    }

    .array-item-preview {
        display: flex !important;
        gap: 8px !important;
        padding: 4px 0 !important;
        border-bottom: 1px solid var(--divider) !important;
        font-size: 12px !important;
    }

    .array-item-preview:last-child {
        border-bottom: none !important;
    }

    .array-key {
        color: var(--accent) !important;
        font-weight: 600 !important;
        min-width: 80px !important;
    }

    .array-value {
        color: var(--text-muted) !important;
        flex: 1 !important;
    }

    .array-more {
        text-align: center !important;
        color: var(--text-muted) !important;
        font-style: italic !important;
        padding: 8px !important;
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        margin-top: 8px !important;
    }

    .object-value-preview {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        gap: 12px !important;
    }

    .object-info {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        font-weight: 600 !important;
        color: var(--text) !important;
    }

    .object-icon {
        font-size: 16px !important;
    }

    .nested-object {
        margin-top: 12px !important;
        padding: 12px !important;
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        border: 1px solid var(--border) !important;
    }

    .simple-value {
        padding: 8px !important;
    }

    .value-error {
        background: rgba(239, 68, 68, 0.1) !important;
        color: var(--error) !important;
        padding: 8px 12px !important;
        border-radius: var(--radius) !important;
        border: 1px solid var(--error) !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
    }

    .error-icon {
        font-size: 16px !important;
    }

    /* Method Details */
    .method-info-grid {
        display: grid !important;
        gap: 16px !important;
    }

    .method-info-section {
        background: var(--surface-hover) !important;
        padding: 12px !important;
        border-radius: var(--radius) !important;
        border: 1px solid var(--border) !important;
    }

    .parameters-list {
        display: grid !important;
        gap: 8px !important;
        margin-top: 8px !important;
    }

    .parameter-item {
        background: var(--surface) !important;
        padding: 8px 12px !important;
        border-radius: var(--radius) !important;
        border: 1px solid var(--border) !important;
    }

    .parameter-signature {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        flex-wrap: wrap !important;
        font-family: 'SF Mono', Consolas, monospace !important;
        font-size: 13px !important;
    }

    .param-type-badge {
        background: var(--secondary) !important;
        color: white !important;
        padding: 2px 6px !important;
        border-radius: var(--radius) !important;
        font-size: 10px !important;
        font-weight: 600 !important;
    }

    .param-name-highlight {
        color: var(--primary) !important;
        font-weight: 700 !important;
    }

    .param-optional {
        background: var(--success) !important;
        color: white !important;
        padding: 2px 6px !important;
        border-radius: var(--radius) !important;
        font-size: 9px !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
    }

    .param-required {
        background: var(--error) !important;
        color: white !important;
        padding: 2px 6px !important;
        border-radius: var(--radius) !important;
        font-size: 9px !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
    }

    .param-default-info {
        margin-top: 4px !important;
        font-size: 11px !important;
        color: var(--text-muted) !important;
    }

    .return-type-info {
        font-family: 'SF Mono', Consolas, monospace !important;
        background: var(--surface) !important;
        padding: 8px 12px !important;
        border-radius: var(--radius) !important;
        border: 1px solid var(--border) !important;
        color: var(--secondary) !important;
        font-weight: 600 !important;
    }

    .source-location {
        font-family: 'SF Mono', Consolas, monospace !important;
        font-size: 12px !important;
        color: var(--text-muted) !important;
    }

    .file-name {
        color: var(--accent) !important;
        font-weight: 600 !important;
    }

    .line-range {
        color: var(--text-dim) !important;
    }

    /* Constants Tab Styles */
    .constants-container {
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        padding: 20px !important;
        border: 1px solid var(--border) !important;
    }

    .constants-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important;
        gap: 16px !important;
    }

    .constant-card {
        background: var(--surface-hover) !important;
        border-radius: var(--radius) !important;
        padding: 16px !important;
        border: 1px solid var(--border) !important;
        transition: all 0.2s ease !important;
    }

    .constant-card:hover {
        border-color: var(--primary) !important;
        box-shadow: 0 4px 12px var(--shadow-light) !important;
    }

    .constant-header {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        margin-bottom: 12px !important;
    }

    .constant-icon {
        font-size: 16px !important;
        color: var(--accent) !important;
    }

    .constant-name {
        font-family: 'SF Mono', Consolas, monospace !important;
        font-weight: 700 !important;
        color: var(--text) !important;
        font-size: 14px !important;
    }

    .constant-value {
        margin-bottom: 8px !important;
        padding: 8px !important;
        background: var(--surface) !important;
        border-radius: var(--radius) !important;
        border: 1px solid var(--border) !important;
    }

    .constant-type {
        text-align: right !important;
    }

    .type-badge {
        background: var(--info) !important;
        color: white !important;
        padding: 4px 8px !important;
        border-radius: var(--radius) !important;
        font-size: 10px !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
    }

    /* Empty States */
    .empty-section {
        text-align: center !important;
        padding: 40px 20px !important;
        color: var(--text-muted) !important;
    }

    .empty-icon {
        font-size: 48px !important;
        opacity: 0.5 !important;
        margin-bottom: 16px !important;
        display: block !important;
    }

    .empty-section p {
        font-size: 14px !important;
        margin: 0 !important;
    }

    /* Class Error */
    .class-error {
        background: rgba(239, 68, 68, 0.1) !important;
        color: var(--error) !important;
        padding: 20px !important;
        border-radius: var(--radius) !important;
        border: 1px solid var(--error) !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        margin: 20px 0 !important;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .class-header {
            padding: 16px !important;
        }

        .class-stats-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 12px !important;
        }

        .class-navigation {
            flex-direction: column !important;
        }

        .nav-tab {
            padding: 12px 16px !important;
            border-bottom: 1px solid var(--border) !important;
            border-right: none !important;
        }

        .tab-content {
            padding: 16px !important;
        }

        .property-signature, .method-signature {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 8px !important;
        }

        .constants-grid {
            grid-template-columns: 1fr !important;
        }
    }
    </style>
    
    <script>
    // Ana toggle fonksiyonu
    function togglePrContent(instanceId) {
        const content = document.getElementById("content-" + instanceId);
        const button = content.previousElementSibling.querySelector(".pr-toggle");
        
        if (content.classList.contains("collapsed")) {
            content.classList.remove("collapsed");
            button.textContent = "↕";
        } else {
            content.classList.add("collapsed");
            button.textContent = "↓";
        }
    }
    
    // Array görünüm değiştirme
    function switchArrayView(instanceId, viewType) {
        // Tüm görünümleri gizle
        const views = ['table', 'json', 'tree'];
        views.forEach(view => {
            const element = document.getElementById('array-' + view + '-' + instanceId);
            const button = document.querySelector('[onclick*="switchArrayView(\'' + instanceId + '\', \'' + view + '\')"]');
            
            if (element) {
                element.classList.remove('active');
            }
            if (button) {
                button.classList.remove('active');
            }
        });
        
        // Seçilen görünümü göster
        const selectedView = document.getElementById('array-' + viewType + '-' + instanceId);
        const selectedButton = document.querySelector('[onclick*="switchArrayView(\'' + instanceId + '\', \'' + viewType + '\')"]');
        
        if (selectedView) {
            selectedView.classList.add('active');
        }
        if (selectedButton) {
            selectedButton.classList.add('active');
        }
        
        // Animasyon efekti
        if (selectedView) {
            selectedView.style.opacity = '0';
            setTimeout(() => {
                selectedView.style.opacity = '1';
            }, 50);
        }
    }
    
    // Array satırı toggle
    function toggleArrayRow(rowId) {
        const expandedContent = document.getElementById('expanded-' + rowId);
        const expandBtn = document.querySelector('[onclick*="toggleArrayRow(\'' + rowId + '\')"]');
        
        if (expandedContent && expandBtn) {
            if (expandedContent.style.display === 'none' || !expandedContent.style.display) {
                expandedContent.style.display = 'block';
                expandBtn.classList.add('expanded');
                
                // Smooth scroll animasyonu
                setTimeout(() => {
                    expandedContent.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }, 100);
            } else {
                expandedContent.style.display = 'none';
                expandBtn.classList.remove('expanded');
            }
        }
    }
    
    // Tree node toggle
    function toggleTreeNode(nodeId) {
        const children = document.getElementById(nodeId);
        const toggle = document.querySelector('[onclick*="toggleTreeNode(\'' + nodeId + '\')"]');
        
        if (children && toggle) {
            if (children.style.display === 'none' || !children.style.display) {
                children.style.display = 'block';
                toggle.classList.add('expanded');
                
                // Animasyon
                children.style.opacity = '0';
                children.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    children.style.opacity = '1';
                    children.style.transform = 'translateY(0)';
                }, 10);
            } else {
                children.style.display = 'none';
                toggle.classList.remove('expanded');
            }
        }
    }
    
    // Daha fazla array öğesi yükle
    function loadMoreArrayItems(instanceId, currentCount) {
        // Bu fonksiyon gerçek uygulamada AJAX ile daha fazla veri yükleyebilir
        const btn = event.target.closest('.load-more-btn');
        if (btn) {
            btn.innerHTML = '<i class="btn-icon">⏳</i> Yükleniyor...';
            btn.disabled = true;
            
            // Simüle edilmiş yükleme
            setTimeout(() => {
                btn.innerHTML = '<i class="btn-icon">✅</i> Tüm veriler yüklendi';
                btn.style.background = 'var(--pr-success)';
            }, 1000);
        }
    }
    
    // Otomatik syntax highlighting ve iyileştirmeler
    document.addEventListener("DOMContentLoaded", function() {
        // Syntax highlighting
        const codeBlocks = document.querySelectorAll("pre code, .code-syntax");
        codeBlocks.forEach(block => {
           highlightJSON(block);
        });
        
        // JSON syntax highlighting
        const jsonBlocks = document.querySelectorAll(".json-output");
        jsonBlocks.forEach(block => {
            highlightJSON(block);
        });
        
        // Lazy loading için intersection observer
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                }
            });
        }, observerOptions);
        
        // Tüm pr-container'ları gözlemle
        document.querySelectorAll('.pr-container').forEach(container => {
            observer.observe(container);
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // ESC tuşu ile tüm expanded içerikleri kapat
            if (e.key === 'Escape') {
                const expandedElements = document.querySelectorAll('.value-expanded[style*="block"], .tree-children[style*="block"]');
                expandedElements.forEach(element => {
                    element.style.display = 'none';
                    
                    // İlgili toggle butonlarını da güncelle
                    const toggleBtn = element.previousElementSibling?.querySelector('.expand-btn, .tree-toggle');
                    if (toggleBtn) {
                        toggleBtn.classList.remove('expanded');
                    }
                });
            }
            
            // Ctrl+J ile JSON view'a geç
            if (e.ctrlKey && e.key === 'j') {
                e.preventDefault();
                const activeJsonBtn = document.querySelector('.view-btn[onclick*="json"]');
                if (activeJsonBtn) {
                    activeJsonBtn.click();
                }
            }
            
            // Ctrl+T ile Tree view'a geç
            if (e.ctrlKey && e.key === 't') {
                e.preventDefault();
                const activeTreeBtn = document.querySelector('.view-btn[onclick*="tree"]');
                if (activeTreeBtn) {
                    activeTreeBtn.click();
                }
            }
        });
        
        // Copy to clipboard functionality
        addCopyButtons();
    });
    
    // JSON highlighting
    function highlightJSON(element) {
        let content = element.innerHTML;
        
        // String values
        content = content.replace(/"([^"\\\\]*(\\\\.[^"\\\\]*)*)"/g, '<span style="color: #f1fa8c">"$1"</span>');
        
        // Numbers
        content = content.replace(/\b(\d+\.?\d*)\b/g, '<span style="color: #bd93f9">$1</span>');
        
        // Booleans
        content = content.replace(/\b(true|false)\b/g, '<span style="color: #50fa7b">$1</span>');
        
        // null
        content = content.replace(/\bnull\b/g, '<span style="color: #bd93f9">null</span>');
        
        // Keys
        content = content.replace(/"([^"]+)"(\s*:)/g, '<span style="color: #8be9fd">"$1"</span>$2');
        
        element.innerHTML = content;
    }
    
    // Copy butonları ekle
    function addCopyButtons() {
        const codeBlocks = document.querySelectorAll('pre, .json-output');
        codeBlocks.forEach(block => {
            if (!block.querySelector('.copy-btn')) {
                const copyBtn = document.createElement('button');
                copyBtn.className = 'copy-btn';
                copyBtn.innerHTML = '📋';
                copyBtn.title = 'Kopyala';
                copyBtn.style.cssText = '' +
                    'position: absolute;' +
                    'top: 8px;' +
                    'right: 8px;' +
                    'background: var(--pr-primary);' +
                    'color: var(--pr-background);' +
                    'border: none;' +
                    'padding: 6px 8px;' +
                    'border-radius: 4px;' +
                    'cursor: pointer;' +
                    'font-size: 12px;' +
                    'opacity: 0;' +
                    'transition: opacity 0.2s ease;';
                
                const wrapper = document.createElement('div');
                wrapper.style.position = 'relative';
                block.parentNode.insertBefore(wrapper, block);
                wrapper.appendChild(block);
                wrapper.appendChild(copyBtn);
                
                wrapper.addEventListener('mouseenter', () => {
                    copyBtn.style.opacity = '1';
                });
                
                wrapper.addEventListener('mouseleave', () => {
                    copyBtn.style.opacity = '0';
                });
                
                copyBtn.addEventListener('click', () => {
                    const text = block.textContent;
                    navigator.clipboard.writeText(text).then(() => {
                        copyBtn.innerHTML = '✅';
                        setTimeout(() => {
                            copyBtn.innerHTML = '📋';
                        }, 1000);
                    });
                });
            }
        });
    }
    
    // Object property toggle fonksiyonu
    function toggleObjectProperty(propId) {
        const expandedContent = document.getElementById('expanded-' + propId);
        const expandBtn = document.querySelector('[onclick*="toggleObjectProperty(\'' + propId + '\')"]');
        
        if (expandedContent && expandBtn) {
            if (expandedContent.style.display === 'none' || !expandedContent.style.display) {
                expandedContent.style.display = 'block';
                expandBtn.classList.add('expanded');
                
                // Smooth scroll animasyonu
                setTimeout(() => {
                    expandedContent.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }, 100);
            } else {
                expandedContent.style.display = 'none';
                expandBtn.classList.remove('expanded');
            }
        }
    }
    
    // Object içindeki array view değiştirme
    function switchObjectArrayView(arrayId, viewType) {
        // Tüm görünümleri gizle
        const views = ['compact', 'table', 'json'];
        views.forEach(view => {
            const element = document.getElementById(arrayId + '-' + view);
            const button = document.querySelector('[onclick*="switchObjectArrayView(\'' + arrayId + '\', \'' + view + '\')"]');
            
            if (element) {
                element.classList.remove('active');
            }
            if (button) {
                button.classList.remove('active');
            }
        });
        
        // Seçilen görünümü göster
        const selectedView = document.getElementById(arrayId + '-' + viewType);
        const selectedButton = document.querySelector('[onclick*="switchObjectArrayView(\'' + arrayId + '\', \'' + viewType + '\')"]');
        
        if (selectedView) {
            selectedView.classList.add('active');
        }
        if (selectedButton) {
            selectedButton.classList.add('active');
        }
        
        // Animasyon efekti
        if (selectedView) {
            selectedView.style.opacity = '0';
            setTimeout(() => {
                selectedView.style.opacity = '1';
            }, 50);
        }
    }
    
    // Object property'lerde daha fazla göster
    function showMoreObjectProps(objectId) {
        // Bu fonksiyon genişletilebilir - daha fazla property yüklemek için AJAX kullanılabilir
        const btn = event.target.closest('.show-more-props');
        if (btn) {
            btn.innerHTML = '<i class="btn-icon">⏳</i> Yükleniyor...';
            btn.disabled = true;
            
            // Simüle edilmiş yükleme
            setTimeout(() => {
                btn.innerHTML = '<i class="btn-icon">✅</i> Tüm özellikler yüklendi';
                btn.style.background = 'var(--pr-success)';
            }, 1000);
        }
    }
    
    // Full text toggle fonksiyonu
    function toggleFullText(element) {
        const truncated = element.querySelector('.truncated-text');
        const full = element.parentElement.querySelector('.full-text');
        
        if (truncated && full) {
            if (full.style.display === 'none' || !full.style.display) {
                truncated.style.display = 'none';
                full.style.display = 'inline';
            } else {
                truncated.style.display = 'inline';
                full.style.display = 'none';
            }
        }
    }
    
    // Performance monitoring
    if (window.performance) {
        window.addEventListener('load', () => {
            const perfData = window.performance.timing;
            const loadTime = perfData.loadEventEnd - perfData.navigationStart;
            
            if (loadTime > 3000) {
                console.warn('PR Debug: Sayfa yükleme süresi uzun (' + loadTime + 'ms). Büyük veri setleri için pagination kullanmayı düşünün.');
            }
        });
    }

    // Modern Class Viewer JavaScript Functions
    
    // Class tab switching
    function switchClassTab(classId, tabName) {
        // Hide all tab contents
        const tabContents = document.querySelectorAll('#' + classId + ' .tab-content');
        tabContents.forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all nav tabs
        const navTabs = document.querySelectorAll('#' + classId + ' .nav-tab');
        navTabs.forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show selected tab content
        const selectedTab = document.getElementById(classId + '-' + tabName);
        if (selectedTab) {
            selectedTab.classList.add('active');
        }
        
        // Add active class to selected nav tab
        const selectedNavTab = document.querySelector('#' + classId + ' .nav-tab[onclick*="' + tabName + '"]');
        if (selectedNavTab) {
            selectedNavTab.classList.add('active');
        }
    }
    
    // Property filtering by visibility
    function filterPropertiesByVisibility(classId, visibility) {
        const container = document.getElementById(classId + '-properties-list');
        if (!container) return;
        
        const properties = container.querySelectorAll('.property-card');
        const filterBtns = container.parentElement.querySelectorAll('.filter-btn');
        
        // Update filter button states
        filterBtns.forEach(btn => btn.classList.remove('active'));
        const activeBtn = container.parentElement.querySelector('[onclick*="' + visibility + '"]');
        if (activeBtn) activeBtn.classList.add('active');
        
        // Filter properties
        properties.forEach(property => {
            let show = false;
            
            if (visibility === 'all') {
                show = true;
            } else if (visibility === 'public' && property.classList.contains('visibility-public')) {
                show = true;
            } else if (visibility === 'protected' && property.classList.contains('visibility-protected')) {
                show = true;
            } else if (visibility === 'private' && property.classList.contains('visibility-private')) {
                show = true;
            } else if (visibility === 'static' && property.classList.contains('is-static')) {
                show = true;
            }
            
            property.style.display = show ? 'block' : 'none';
        });
    }
    
    // Property search filtering
    function filterClassProperties(classId, searchTerm) {
        const container = document.getElementById(classId + '-properties-list');
        if (!container) return;
        
        const properties = container.querySelectorAll('.property-card');
        const term = searchTerm.toLowerCase();
        
        properties.forEach(property => {
            const propertyName = property.getAttribute('data-property-name');
            const matches = propertyName.includes(term);
            
            if (property.style.display !== 'none') {
                property.style.opacity = matches ? '1' : '0.3';
                property.style.transform = matches ? 'scale(1)' : 'scale(0.95)';
            }
        });
    }
    
    // Method filtering by visibility
    function filterMethodsByVisibility(classId, visibility) {
        const container = document.getElementById(classId + '-methods-list');
        if (!container) return;
        
        const methods = container.querySelectorAll('.method-card');
        const filterBtns = container.parentElement.querySelectorAll('.filter-btn');
        
        // Update filter button states
        filterBtns.forEach(btn => btn.classList.remove('active'));
        const activeBtn = container.parentElement.querySelector('[onclick*="' + visibility + '"]');
        if (activeBtn) activeBtn.classList.add('active');
        
        // Filter methods
        methods.forEach(method => {
            let show = false;
            
            if (visibility === 'all') {
                show = true;
            } else if (visibility === 'public' && method.classList.contains('visibility-public')) {
                show = true;
            } else if (visibility === 'protected' && method.classList.contains('visibility-protected')) {
                show = true;
            } else if (visibility === 'private' && method.classList.contains('visibility-private')) {
                show = true;
            } else if (visibility === 'static' && method.classList.contains('is-static')) {
                show = true;
            } else if (visibility === 'abstract' && method.classList.contains('is-abstract')) {
                show = true;
            }
            
            method.style.display = show ? 'block' : 'none';
        });
    }
    
    // Method search filtering
    function filterClassMethods(classId, searchTerm) {
        const container = document.getElementById(classId + '-methods-list');
        if (!container) return;
        
        const methods = container.querySelectorAll('.method-card');
        const term = searchTerm.toLowerCase();
        
        methods.forEach(method => {
            const methodName = method.getAttribute('data-method-name');
            const matches = methodName.includes(term);
            
            if (method.style.display !== 'none') {
                method.style.opacity = matches ? '1' : '0.3';
                method.style.transform = matches ? 'scale(1)' : 'scale(0.95)';
            }
        });
    }
    
    // Toggle property details
    function togglePropertyDetails(propId) {
        const details = document.getElementById('details-' + propId);
        const btn = document.querySelector('[onclick*="togglePropertyDetails(\'' + propId + '\')"]');
        
        if (details && btn) {
            if (details.style.display === 'none' || !details.style.display) {
                details.style.display = 'block';
                btn.style.background = 'var(--primary)';
                btn.style.color = 'white';
                
                // Smooth scroll
                setTimeout(() => {
                    details.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }, 100);
            } else {
                details.style.display = 'none';
                btn.style.background = '';
                btn.style.color = '';
            }
        }
    }
    
    // Toggle method details
    function toggleMethodDetails(methodId) {
        const details = document.getElementById('details-' + methodId);
        const btn = document.querySelector('[onclick*="toggleMethodDetails(\'' + methodId + '\')"]');
        
        if (details && btn) {
            if (details.style.display === 'none' || !details.style.display) {
                details.style.display = 'block';
                btn.style.background = 'var(--primary)';
                btn.style.color = 'white';
                
                // Smooth scroll
                setTimeout(() => {
                    details.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }, 100);
            } else {
                details.style.display = 'none';
                btn.style.background = '';
                btn.style.color = '';
            }
        }
    }
    
    // Expand nested object
    function expandNestedObject(objectId) {
        const nested = document.getElementById(objectId);
        const btn = document.querySelector('[onclick*="expandNestedObject(\'' + objectId + '\')"]');
        
        if (nested && btn) {
            if (nested.style.display === 'none' || !nested.style.display) {
                nested.style.display = 'block';
                btn.innerHTML = '<i class="btn-icon">📄</i> Gizle';
                btn.style.background = 'var(--error)';
                
                // Animate appearance
                nested.style.opacity = '0';
                nested.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    nested.style.opacity = '1';
                    nested.style.transform = 'translateY(0)';
                    nested.style.transition = 'all 0.3s ease';
                }, 10);
            } else {
                nested.style.display = 'none';
                btn.innerHTML = '<i class="btn-icon">🔍</i> Ayrıntıları Gör';
                btn.style.background = '';
            }
        }
    }
    
    // Class viewer keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+1-5 for tab switching in focused class viewer
        if (e.ctrlKey && e.key >= '1' && e.key <= '5') {
            const focusedClass = document.querySelector('.modern-class-container:hover');
            if (focusedClass) {
                e.preventDefault();
                const tabNames = ['overview', 'hierarchy', 'properties', 'methods', 'constants'];
                const tabIndex = parseInt(e.key) - 1;
                if (tabNames[tabIndex]) {
                    const classId = focusedClass.id;
                    switchClassTab(classId, tabNames[tabIndex]);
                }
            }
        }
        
        // F for filter toggle
        if (e.key === 'f' && !e.ctrlKey && !e.altKey) {
            const activeSearch = document.querySelector('.property-search:focus, .method-search:focus');
            if (!activeSearch) {
                const searchInput = document.querySelector('.property-search, .method-search');
                if (searchInput) {
                    e.preventDefault();
                    searchInput.focus();
                }
            }
        }
    });
    
    // Auto-expand first class on page load
    document.addEventListener('DOMContentLoaded', function() {
        const firstClass = document.querySelector('.modern-class-container');
        if (firstClass) {
            // Auto-scroll to class viewer
            setTimeout(() => {
                firstClass.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 500);
        }
    });

    // Modal ile Array görüntüleme fonksiyonları
    let currentModalArrayData = null;
    let currentModalArrayId = null;

    // Array'i modal içerisinde aç - Base64 destekli versiyon
    function openArrayModalBase64(arrayDataBase64, arrayTitle, instanceId) {
        try {
            // Base64 string'i decode et ve JSON parse et
            let arrayDataJson = atob(arrayDataBase64);
            currentModalArrayData = JSON.parse(arrayDataJson);
        } catch (e) {
            console.error('Base64 array verisi parse edilemedi:', e);
            showToast('Array verisi geçersiz format!', 'error');
            return;
        }
        
        currentModalArrayId = 'modal-' + instanceId;
        
        // Modal HTML'ini oluştur
        const modalHtml = createArrayModalHtml(currentModalArrayData, arrayTitle, currentModalArrayId);
        
        // Eğer modal zaten varsa, remove et
        const existingModal = document.getElementById(currentModalArrayId);
        if (existingModal) {
            existingModal.remove();
        }
        
        // Yeni modal'ı body'ye ekle
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Bootstrap modal'ını aç
        const modal = new bootstrap.Modal(document.getElementById(currentModalArrayId));
        modal.show();
        
        // Modal açıldıktan sonra default view'ı göster
        setTimeout(() => {
            switchModalArrayView('table');
        }, 100);
    }

    // Array'i modal içerisinde aç
    function openArrayModal(arrayDataJson, arrayTitle, instanceId) {
        try {
            // JSON string'i parse et
            currentModalArrayData = typeof arrayDataJson === 'string' ? JSON.parse(arrayDataJson) : arrayDataJson;
        } catch (e) {
            console.error('Array verisi parse edilemedi:', e);
            showToast('Array verisi geçersiz format!', 'error');
            return;
        }
        
        currentModalArrayId = 'modal-' + instanceId;
        
        // Modal HTML'ini oluştur
        const modalHtml = createArrayModalHtml(currentModalArrayData, arrayTitle, currentModalArrayId);
        
        // Eğer modal zaten varsa, remove et
        const existingModal = document.getElementById(currentModalArrayId);
        if (existingModal) {
            existingModal.remove();
        }
        
        // Yeni modal'ı body'ye ekle
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Bootstrap modal'ını aç
        const modal = new bootstrap.Modal(document.getElementById(currentModalArrayId));
        modal.show();
        
        // Modal açıldıktan sonra default view'ı göster
        setTimeout(() => {
            switchModalArrayView('table');
        }, 100);
    }

    // Modal HTML'ini oluştur
    function createArrayModalHtml(arrayData, title, modalId) {
        const stats = analyzeModalArrayData(arrayData);
        
        let modalHtml = '<div class="modal fade" id="' + modalId + '" tabindex="-1" aria-labelledby="' + modalId + 'Label" aria-hidden="true">';
        modalHtml += '<div class="modal-dialog modal-xl">';
        modalHtml += '<div class="modal-content">';
        modalHtml += '<div class="modal-header">';
        modalHtml += '<h5 class="modal-title" id="' + modalId + 'Label">';
        modalHtml += '<i class="me-2">📋</i>' + title + ' ';
        modalHtml += '<small class="text-muted">(' + stats.count + ' öğe)</small>';
        modalHtml += '</h5>';
        modalHtml += '<div class="d-flex align-items-center">';
        modalHtml += '<button type="button" class="btn btn-outline-light btn-sm me-2" onclick="toggleModalFullscreen(\'' + modalId + '\')" title="Tam Ekran">';
        modalHtml += '<i>⛶</i>';
        modalHtml += '</button>';
        modalHtml += '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '<div class="modal-body">';
        modalHtml += '<div class="modal-array-container">';
        modalHtml += '<div class="modal-array-stats">';
        modalHtml += '<div class="modal-stat-badge">';
        modalHtml += '<i>📊</i> <strong>' + stats.count + '</strong> öğe';
        modalHtml += '</div>';
        modalHtml += '<div class="modal-stat-badge">';
        modalHtml += '<i>🏷️</i> ' + (stats.isAssoc ? 'Associative' : 'Indexed');
        modalHtml += '</div>';
        modalHtml += '<div class="modal-stat-badge">';
        modalHtml += '<i>📏</i> ' + stats.maxDepth + ' seviye derinlik';
        modalHtml += '</div>';
        modalHtml += '<div class="modal-stat-badge">';
        modalHtml += '<i>💾</i> ' + formatMemorySize(stats.memoryUsage);
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '<div class="modal-view-toggles">';
        modalHtml += '<button class="modal-view-btn" onclick="switchModalArrayView(\'table\')" data-view="table">';
        modalHtml += '<i>📋</i> Tablo Görünümü';
        modalHtml += '</button>';
        modalHtml += '<button class="modal-view-btn" onclick="switchModalArrayView(\'json\')" data-view="json">';
        modalHtml += '<i>💾</i> JSON Görünümü';
        modalHtml += '</button>';
        modalHtml += '<button class="modal-view-btn" onclick="switchModalArrayView(\'tree\')" data-view="tree">';
        modalHtml += '<i>🌳</i> Ağaç Görünümü';
        modalHtml += '</button>';
        modalHtml += '</div>';
        modalHtml += '<div id="modal-array-views">';
        modalHtml += '<div id="modal-view-table" class="modal-array-view active">';
        modalHtml += generateModalTableView(arrayData, modalId);
        modalHtml += '</div>';
        modalHtml += '<div id="modal-view-json" class="modal-array-view">';
        modalHtml += '<div class="modal-json-container">';
        modalHtml += '<pre><code>' + JSON.stringify(arrayData, null, 2) + '</code></pre>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '<div id="modal-view-tree" class="modal-array-view">';
        modalHtml += '<div class="modal-tree-container">';
        modalHtml += generateModalTreeView(arrayData, modalId);
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '<div class="modal-footer">';
        modalHtml += '<button type="button" class="btn btn-secondary" onclick="exportModalArrayData(\'json\')">';
        modalHtml += '<i>📥</i> JSON Export';
        modalHtml += '</button>';
        modalHtml += '<button type="button" class="btn btn-info" onclick="copyModalArrayData()">';
        modalHtml += '<i>📋</i> Kopyala';
        modalHtml += '</button>';
        modalHtml += '<button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Kapat</button>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        
        return modalHtml;
    }

    // Modal array view değiştir
    function switchModalArrayView(viewType) {
        // Tüm view'ları gizle
        const views = document.querySelectorAll('#modal-array-views .modal-array-view');
        views.forEach(view => view.classList.remove('active'));
        
        // Tüm butonları deaktif et
        const buttons = document.querySelectorAll('.modal-view-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        
        // Seçilen view'ı göster
        const selectedView = document.getElementById('modal-view-' + viewType);
        const selectedButton = document.querySelector('[data-view="' + viewType + '"]');
        
        if (selectedView) {
            selectedView.classList.add('active');
        }
        if (selectedButton) {
            selectedButton.classList.add('active');
        }
    }

    // Modal tam ekran toggle
    function toggleModalFullscreen(modalId) {
        const modal = document.getElementById(modalId);
        const modalDialog = modal.querySelector('.modal-dialog');
        
        if (modalDialog.classList.contains('modal-fullscreen')) {
            modalDialog.classList.remove('modal-fullscreen');
        } else {
            modalDialog.classList.add('modal-fullscreen');
        }
    }

    // Modal array verilerini analiz et
    function analyzeModalArrayData(arrayData) {
        const count = Object.keys(arrayData).length;
        const isAssoc = Object.keys(arrayData).some(key => isNaN(key));
        let maxDepth = 1;
        let memoryUsage = JSON.stringify(arrayData).length;
        
        function getDepth(obj, depth = 1) {
            let max = depth;
            if (typeof obj === 'object' && obj !== null) {
                for (let key in obj) {
                    if (obj.hasOwnProperty(key)) {
                        const newDepth = getDepth(obj[key], depth + 1);
                        max = Math.max(max, newDepth);
                    }
                }
            }
            return max;
        }
        
        maxDepth = getDepth(arrayData);
        
        return { count, isAssoc, maxDepth, memoryUsage };
    }

    // Modal tablo view oluştur
    function generateModalTableView(arrayData, modalId) {
        let html = '<div class="table-responsive"><table class="table table-dark table-striped table-hover">';
        html += '<thead class="table-dark"><tr>';
        html += '<th width="5%"><i>🔗</i></th>';
        html += '<th width="25%">Anahtar</th>';
        html += '<th width="20%">Tip</th>';
        html += '<th width="50%">Değer</th>';
        html += '</tr></thead><tbody>';
        
        let index = 0;
        for (let key in arrayData) {
            if (arrayData.hasOwnProperty(key)) {
                const value = arrayData[key];
                const type = typeof value;
                const isExpandable = (type === 'object' && value !== null) || Array.isArray(value);
                
                html += '<tr class="table-row" data-index="' + index + '">';
                
                // Expand button
                html += '<td>';
                if (isExpandable) {
                    html += '<button class="btn btn-sm btn-outline-primary" onclick="toggleModalRowExpand(' + index + ')" title="Genişlet">';
                    html += '<i class="expand-icon">▶️</i>';
                    html += '</button>';
                }
                html += '</td>';
                
                // Key
                html += '<td>';
                html += '<span class="badge ' + (isNaN(key) ? 'bg-info' : 'bg-secondary') + ' me-1">';
                html += isNaN(key) ? '🔑' : '#️⃣';
                html += '</span>';
                html += '<code>' + key + '</code>';
                html += '</td>';
                
                // Type
                html += '<td>';
                html += '<span class="badge bg-primary">' + getTypeIconForModal(type) + ' ' + type + '</span>';
                html += '</td>';
                
                // Value
                html += '<td>';
                if (isExpandable) {
                    html += '<button class="btn btn-sm btn-outline-success" onclick="openNestedArrayModal(\'' + key + '\', ' + index + ')">';
                    html += '<i>📋</i> ' + (Array.isArray(value) ? 'Array' : 'Object') + ' (' + Object.keys(value).length + ' öğe)';
                    html += '</button>';
                } else {
                    html += '<code>' + formatModalValue(value) + '</code>';
                }
                html += '</td>';
                
                html += '</tr>';
                
                // Expandable row content
                if (isExpandable) {
                    html += '<tr class="expandable-row" id="expand-row-' + index + '" style="display: none;">';
                    html += '<td colspan="4">';
                    html += '<div class="p-3 bg-dark rounded">';
                    html += '<pre><code>' + JSON.stringify(value, null, 2) + '</code></pre>';
                    html += '</div>';
                    html += '</td>';
                    html += '</tr>';
                }
                
                index++;
            }
        }
        
        html += '</tbody></table></div>';
        return html;
    }

    // Modal tree view oluştur
    function generateModalTreeView(arrayData, modalId) {
        function createTreeNode(obj, key = null, level = 0) {
            let html = '';
            const indent = '　'.repeat(level);
            
            if (typeof obj === 'object' && obj !== null) {
                const isArray = Array.isArray(obj);
                const count = Object.keys(obj).length;
                
                html += '<div class="tree-node">';
                html += '<div class="tree-line" style="padding-left: ' + (level * 20) + 'px;">';
                html += '<button class="btn btn-sm btn-link text-info tree-toggle" onclick="toggleModalTreeNode(this)">';
                html += '<i class="tree-icon">▼</i>';
                html += '</button>';
                
                if (key !== null) {
                    html += '<span class="tree-key">' + key + ': </span>';
                }
                
                html += '<span class="tree-type badge ' + (isArray ? 'bg-success' : 'bg-info') + '">' + (isArray ? 'Array' : 'Object') + ' [' + count + ']</span>';
                html += '</div>';
                
                html += '<div class="tree-children">';
                for (let subKey in obj) {
                    if (obj.hasOwnProperty(subKey)) {
                        html += createTreeNode(obj[subKey], subKey, level + 1);
                    }
                }
                html += '</div>';
                html += '</div>';
            } else {
                html += '<div class="tree-leaf" style="padding-left: ' + (level * 20) + 'px;">';
                html += '<span class="tree-key">' + key + ': </span>';
                html += '<span class="tree-value badge bg-secondary">' + formatModalValue(obj) + '</span>';
                html += '</div>';
            }
            
            return html;
        }
        
        return '<div class="tree-container">' + createTreeNode(arrayData) + '</div>';
    }

    // Modal tree node toggle
    function toggleModalTreeNode(button) {
        const treeNode = button.closest('.tree-node');
        const children = treeNode.querySelector('.tree-children');
        const icon = button.querySelector('.tree-icon');
        
        if (children.style.display === 'none') {
            children.style.display = 'block';
            icon.textContent = '▼';
        } else {
            children.style.display = 'none';
            icon.textContent = '▶️';
        }
    }

    // Modal row expand toggle
    function toggleModalRowExpand(index) {
        const expandRow = document.getElementById('expand-row-' + index);
        if (expandRow) {
            expandRow.style.display = expandRow.style.display === 'none' ? 'table-row' : 'none';
        }
    }

    // Nested array modal aç
    function openNestedArrayModal(key, index) {
        const nestedData = currentModalArrayData[key];
        const title = key + ' İçeriği';
                        let nestedDataBase64 = btoa(JSON.stringify(nestedData));
                openArrayModalBase64(nestedDataBase64, title, 'nested-' + Date.now());
    }

    // Modal array verilerini kopyala
    function copyModalArrayData() {
        if (currentModalArrayData) {
            const jsonString = JSON.stringify(currentModalArrayData, null, 2);
            navigator.clipboard.writeText(jsonString).then(() => {
                showToast('Array verisi kopyalandı!', 'success');
            }).catch(() => {
                showToast('Kopyalama başarısız!', 'error');
            });
        }
    }

    // Modal array verilerini export et
    function exportModalArrayData(format) {
        if (!currentModalArrayData) return;
        
        let content, filename, mimeType;
        
        if (format === 'json') {
            content = JSON.stringify(currentModalArrayData, null, 2);
            filename = 'array-data.json';
            mimeType = 'application/json';
        }
        
        // Dosya indir
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        showToast('Dosya indiriliyor...', 'info');
    }

    // Yardımcı fonksiyonlar
    function getTypeIconForModal(type) {
        const icons = {
            'string': '📝',
            'number': '🔢',
            'boolean': '☑️',
            'object': '🎯',
            'undefined': '🚫',
            'function': '⚙️'
        };
        return icons[type] || '❓';
    }

    function formatModalValue(value) {
        if (value === null) return 'null';
        if (value === undefined) return 'undefined';
        if (typeof value === 'string') {
            return value.length > 100 ? value.substring(0, 100) + '...' : value;
        }
        return String(value);
    }

    function formatMemorySize(bytes) {
        const sizes = ['B', 'KB', 'MB', 'GB'];
        if (bytes === 0) return '0 B';
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    }

    // Toast bildirimi göster
    function showToast(message, type = 'info') {
        // Bootstrap toast oluştur
        const bgClass = type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info';
        const toastHtml = '<div class="toast align-items-center text-white bg-' + bgClass + ' border-0" role="alert">' +
            '<div class="d-flex">' +
            '<div class="toast-body">' + message + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '</div>' +
            '</div>';
        
        // Toast container'ı oluştur (yoksa)
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        // Toast'ı ekle
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Toast kapandıktan sonra DOM'dan kaldır
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
    </script>
<?php 
return ob_get_clean();
}

/**
 * Array yapısını analiz eder
 * @param array $array Analiz edilecek array
 * @return array Array hakkında bilgiler
 */
function analyzeArrayStructure($array): array {
    $count = count($array);
    $isAssoc = array_keys($array) !== range(0, $count - 1);
    $valueTypes = [];
    $typeDistribution = [];
    $maxDepth = 1;
    $memoryUsage = strlen(serialize($array));
    
    function getArrayDepth($arr, $depth = 1) {
        $maxDepth = $depth;
        foreach ($arr as $value) {
            if (is_array($value)) {
                $currentDepth = getArrayDepth($value, $depth + 1);
                $maxDepth = max($maxDepth, $currentDepth);
            }
        }
        return $maxDepth;
    }
    
    $maxDepth = getArrayDepth($array);
    
    foreach ($array as $value) {
        $type = gettype($value);
        $valueTypes[] = $type;
        
        if (!isset($typeDistribution[$type])) {
            $typeDistribution[$type] = 0;
        }
        $typeDistribution[$type]++;
    }
    
    return [
        'count' => $count,
        'isAssoc' => $isAssoc,
        'valueTypes' => $valueTypes,
        'typeDistribution' => $typeDistribution,
        'maxDepth' => $maxDepth,
        'memoryUsage' => $memoryUsage
    ];
}

/**
 * Veri tipi için ikon döndürür
 * @param string $type Veri tipi
 * @return string Unicode icon
 */
function getTypeIcon($type): string {
    $icons = [
        'string' => 'S',
        'integer' => '#',
        'double' => '#',
        'float' => '#',
        'boolean' => '?',
        'array' => '[]',
        'object' => '{}',
        'null' => '∅',
        'resource' => 'R',
        'unknown type' => '?'
    ];
    
    return $icons[$type] ?? $icons['unknown type'];
}

/**
 * Array'i tablo formatında görüntüler
 * @param array $data Görüntülenecek array
 * @param string $instanceId Instance ID
 */
function displayArrayAsTable($data, $instanceId): void {
    echo '<div class="array-table-container">';
    echo '<table class="pr-table array-table">';
    echo '<thead><tr>';
    echo '<th width="5%"><i class="expand-icon">+</i></th>';
    echo '<th width="25%">Anahtar</th>';
    echo '<th width="20%">Tip</th>';
    echo '<th width="50%">Değer</th>';
    echo '</tr></thead><tbody>';
    
    $displayCount = 0;
    $maxDisplay = 100;
    
    foreach ($data as $key => $value) {
        if ($displayCount >= $maxDisplay) {
            echo '<tr class="show-more-row">';
            echo '<td colspan="4">';
            echo '<button class="load-more-btn" onclick="loadMoreArrayItems(\'' . $instanceId . '\', ' . $maxDisplay . ')">';
            echo '+ ' . (count($data) - $maxDisplay) . ' öğe daha göster';
            echo '</button>';
            echo '</td>';
            echo '</tr>';
            break;
        }
        
        $rowId = $instanceId . '-row-' . $displayCount;
        $isExpandable = is_array($value) || is_object($value);
        
        echo '<tr class="array-row" data-row-id="' . $rowId . '">';
        
        // Expand/Collapse button
        echo '<td class="expand-cell">';
        if ($isExpandable) {
            echo '<button class="expand-btn" onclick="toggleArrayRow(\'' . $rowId . '\')" title="Genişlet/Daralt">';
            echo '<i class="expand-icon">▶️</i>';
            echo '</button>';
        }
        echo '</td>';
        
        // Key
        echo '<td class="key-cell">';
        echo '<span class="array-key ' . (is_string($key) ? 'string-key' : 'numeric-key') . '">';
        echo '<i class="key-icon">' . (is_string($key) ? 'K' : '#') . '</i>';
        echo htmlspecialchars($key);
        echo '</span>';
        echo '</td>';
        
        // Type
        echo '<td class="type-cell">';
        $typeInfo = getDataType($value);
        echo '<span class="type-badge">';
        echo '<i class="type-icon">' . getTypeIcon(gettype($value)) . '</i>';
        echo $typeInfo;
        echo '</span>';
        echo '</td>';
        
        // Value
        echo '<td class="value-cell">';
        if ($isExpandable) {
            echo '<div class="value-preview">';
            if (is_array($value)) {
                $arrayId = 'table-array-' . $instanceId . '-' . $displayCount;
                $arrayJson = base64_encode(json_encode($value));
                $arrayTitle = 'Array[' . count($value) . '] - ' . htmlspecialchars($key);
                 
                echo '<button class="btn btn-sm btn-success ms-2" onclick="openArrayModalBase64(\'' . $arrayJson . '\', \'' . $arrayTitle . '\', \'' . $arrayId . '\')" title="Modal\'da Aç">';
                echo $arrayTitle;
                echo '</button>';
            } else {
                echo '<span class="preview-text">Object(' . get_class($value) . ') <i class="preview-icon">🎯</i></span>';
            }
            echo '</div>';
            echo '<div class="value-expanded" id="expanded-' . $rowId . '" style="display: none;">';
            echo '<div class="expanded-content">';
            if (is_array($value)) {
                echo '<pre class="array-content">' . htmlspecialchars(print_r($value, true)) . '</pre>';
            } else {
                echo renderObjectDetails($value);
            }
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="simple-value">';
            echo formatValueWithIcon($value);
            echo '</div>';
        }
        echo '</td>';
        
        echo '</tr>';
        $displayCount++;
    }
    
    echo '</tbody></table>';
    echo '</div>';
}

/**
 * Array'i ağaç formatında görüntüler
 * @param mixed $data Görüntülenecek veri
 * @param string $instanceId Instance ID
 * @param int $depth Mevcut derinlik
 * @param string $key Anahtar
 */
function displayArrayAsTree($data, $instanceId, $depth = 0, $key = null): void {
    $indent = str_repeat('  ', $depth);
    $nodeId = $instanceId . '-tree-' . uniqid();
    
    echo '<div class="tree-view">';
    
    if (is_array($data)) {
        foreach ($data as $k => $value) {
            echo '<div class="tree-node" style="padding-left: ' . ($depth * 20) . 'px;">';
            
            $hasChildren = is_array($value) || is_object($value);
            
            if ($hasChildren) {
                echo '<span class="tree-toggle" onclick="toggleTreeNode(\'' . $nodeId . '-' . $k . '\')">';
                echo '<i class="toggle-icon">▶️</i>';
                echo '</span>';
            } else {
                echo '<span class="tree-leaf"><i class="leaf-icon">🍃</i></span>';
            }
            
            echo '<span class="tree-key">';
            echo '<i class="key-icon">' . (is_string($k) ? '🔑' : '#️⃣') . '</i>';
            echo htmlspecialchars($k);
            echo '</span>';
            
            echo '<span class="tree-separator">:</span>';
            
            echo '<span class="tree-value">';
            if ($hasChildren) {
                if (is_array($value)) {
                    echo '<span class="tree-type">Array[' . count($value) . '] <i class="type-icon">📋</i></span>';
                } else {
                    echo '<span class="tree-type">Object(' . get_class($value) . ') <i class="type-icon">🎯</i></span>';
                }
            } else {
                echo formatValueWithIcon($value);
            }
            echo '</span>';
            
            if ($hasChildren) {
                echo '<div class="tree-children" id="' . $nodeId . '-' . $k . '" style="display: none;">';
                displayArrayAsTree($value, $instanceId, $depth + 1, $k);
                echo '</div>';
            }
            
            echo '</div>';
        }
    }
    
    echo '</div>';
}

/**
 * Değeri icon ile formatlar
 * @param mixed $value Formatlanacak değer
 * @return string Formatlanmış değer
 */
function formatValueWithIcon($value): string {
    $typeIcon = getTypeIcon(gettype($value));
    $formattedValue = formatValue($value);
    
    return '<i class="value-icon">' . $typeIcon . '</i>' . $formattedValue;
}

/**
 * Object detaylarını render eder
 * @param object $object Render edilecek object
 * @return string HTML output
 */
function renderObjectDetails($object): string {
    try {
        $reflection = new ReflectionClass($object);
        $objectId = 'obj-' . uniqid();
        
        $output = '<div class="object-details">';
        $output .= '<div class="object-header">';
        $output .= '<i class="object-icon">🎯</i>';
        $output .= '<span class="object-class">' . $reflection->getName() . '</span>';
        
        // Object stats
        $propertyCount = count($reflection->getProperties());
        $methodCount = count($reflection->getMethods());
        $output .= '<div class="object-stats">';
        $output .= '<span class="stat-badge"><i class="stat-icon">🏷️</i>' . $propertyCount . ' property</span>';
        $output .= '<span class="stat-badge"><i class="stat-icon">⚙️</i>' . $methodCount . ' method</span>';
        $output .= '</div>';
        
        $output .= '</div>';
        
        $properties = $reflection->getProperties();
        if (!empty($properties)) {
            $output .= '<div class="object-properties">';
            
            $displayCount = 0;
            $maxDisplay = 10; // Object için daha fazla property göster
            
            foreach ($properties as $property) {
                if ($displayCount >= $maxDisplay) {
                    $remaining = count($properties) - $maxDisplay;
                    $output .= '<div class="property-more">';
                    $output .= '<button class="show-more-props" onclick="showMoreObjectProps(\'' . $objectId . '\')">';
                    $output .= '<i class="btn-icon">⬇️</i> ' . $remaining . ' özellik daha göster';
                    $output .= '</button>';
                    $output .= '</div>';
                    break;
                }
                
                $property->setAccessible(true);
                $propId = $objectId . '-prop-' . $displayCount;
                
                $output .= '<div class="object-property" data-prop-id="' . $propId . '">';
                
                // Property visibility badge
                $visibility = '';
                if ($property->isPublic()) $visibility = '<span class="badge badge-public">public</span>';
                elseif ($property->isProtected()) $visibility = '<span class="badge badge-protected">protected</span>';
                elseif ($property->isPrivate()) $visibility = '<span class="badge badge-private">private</span>';
                
                if ($property->isStatic()) $visibility .= '<span class="badge badge-static">static</span>';
                
                $output .= '<div class="property-header">';
                $output .= $visibility;
                $output .= '<span class="property-name">$' . $property->getName() . '</span>';
                $output .= '<span class="property-separator">:</span>';
                
                try {
                    $value = $property->getValue($object);
                    $isExpandable = is_array($value) || is_object($value);
                    
                    if ($isExpandable) {
                        $output .= '<button class="prop-expand-btn" onclick="toggleObjectProperty(\'' . $propId . '\')" title="Genişlet/Daralt">';
                        $output .= '<i class="expand-icon">▶️</i>';
                        $output .= '</button>';
                    }
                    
                    $output .= '</div>'; // property-header end
                    
                    $output .= '<div class="property-value">';
                    
                    if ($isExpandable) {
                        // Preview
                        $output .= '<div class="property-preview">';
                        if (is_array($value)) {
                            $count = count($value);
                            $isAssoc = array_keys($value) !== range(0, $count - 1);
                            $arrayType = $isAssoc ? 'Associative' : 'Indexed';
                            $output .= '<span class="preview-text">';
                            $output .= '<i class="type-icon">📋</i>';
                            $output .= 'Array[' . number_format($count) . '] (' . $arrayType . ')';
                            $output .= '</span>';
                        } else {
                            $output .= '<span class="preview-text">';
                            $output .= '<i class="type-icon">🎯</i>';
                            $output .= 'Object(' . get_class($value) . ')';
                            $output .= '</span>';
                        }
                        $output .= '</div>';
                        
                        // Expandable content
                        $output .= '<div class="property-expanded" id="expanded-' . $propId . '" style="display: none;">';
                        $output .= '<div class="expanded-content">';
                        
                        if (is_array($value)) {
                            $output .= renderArrayInObject($value, $propId);
                        } else {
                            $output .= renderObjectDetails($value); // Recursive for nested objects
                        }
                        
                        $output .= '</div>';
                        $output .= '</div>';
                    } else {
                        // Simple value
                        $output .= '<div class="simple-property-value">';
                        $output .= formatValueWithIcon($value);
                        $output .= '</div>';
                    }
                    
                } catch (Exception $e) {
                    $output .= '<div class="property-header-end"></div>';
                    $output .= '<div class="property-value">';
                    $output .= '<span class="property-value value-null"><i class="value-icon">🚫</i>erişilemiyor (' . $e->getMessage() . ')</span>';
                    $output .= '</div>';
                }
                
                $output .= '</div>'; // property-value end
                $output .= '</div>'; // object-property end
                
                $displayCount++;
            }
            
            $output .= '</div>'; // object-properties end
        }
        
        $output .= '</div>'; // object-details end
        return $output;
    } catch (Exception $e) {
        return '<div class="object-error"><i class="error-icon">❌</i>Object analiz edilemedi: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

/**
 * Object içindeki array'i özel formatta render eder
 * @param array $array Render edilecek array
 * @param string $propId Property ID
 * @return string HTML output
 */
function renderArrayInObject($array, $propId): string {
    $arrayId = $propId . '-array';
    $output = '';
    
    // Array stats
    $count = count($array);
    $isAssoc = array_keys($array) !== range(0, $count - 1);
    
    $output .= '<div class="object-array-header">';
    $output .= '<div class="array-stats-inline">';
    $output .= '<span class="inline-stat"><i class="stat-icon">📊</i>' . number_format($count) . ' öğe</span>';
    $output .= '<span class="inline-stat"><i class="stat-icon">🔑</i>' . ($isAssoc ? 'Associative' : 'Indexed') . '</span>';
    
    // Modal butonu ekle
    $modalArrayId = 'obj-array-' . uniqid();
    $arrayJson = base64_encode(json_encode($array));
    $arrayTitle = 'Object Array[' . $count . ']';
    $output .= '<button class="btn btn-sm btn-info ms-2" onclick="openArrayModalBase64(\'' . $arrayJson . '\', \'' . $arrayTitle . '\', \'' . $modalArrayId . '\')" title="Modal\'da Aç">';
    $output .= '<i class="btn-icon">🚀</i> Modal Aç';
    $output .= '</button>';
    
    $output .= '</div>';
    
    // View toggles for array in object
    $output .= '<div class="mini-view-toggles">';
    $output .= '<button class="mini-view-btn active" onclick="switchObjectArrayView(\'' . $arrayId . '\', \'compact\')">';
    $output .= '<i class="btn-icon">📝</i>Compact';
    $output .= '</button>';
    $output .= '<button class="mini-view-btn" onclick="switchObjectArrayView(\'' . $arrayId . '\', \'table\')">';
    $output .= '<i class="btn-icon">📋</i>Table';
    $output .= '</button>';
    $output .= '<button class="mini-view-btn" onclick="switchObjectArrayView(\'' . $arrayId . '\', \'json\')">';
    $output .= '<i class="btn-icon">🔗</i>JSON';
    $output .= '</button>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Compact view (default)
    $output .= '<div id="' . $arrayId . '-compact" class="object-array-view active">';
    $output .= '<div class="compact-array">';
    $displayCount = 0;
    $maxCompactDisplay = 5;
    
    foreach ($array as $key => $value) {
        if ($displayCount >= $maxCompactDisplay) {
            $remaining = $count - $maxCompactDisplay;
            $output .= '<div class="compact-more">... ve ' . $remaining . ' öğe daha</div>';
            break;
        }
        
        $output .= '<div class="compact-item">';
        $output .= '<span class="compact-key">';
        $output .= '<i class="key-icon">' . (is_string($key) ? '🔑' : '#️⃣') . '</i>';
        $output .= htmlspecialchars($key);
        $output .= '</span>';
        $output .= '<span class="compact-separator">:</span>';
        $output .= '<span class="compact-value">' . formatValueWithIcon($value) . '</span>';
        $output .= '</div>';
        
        $displayCount++;
    }
    $output .= '</div>';
    $output .= '</div>';
    
    // Table view
    $output .= '<div id="' . $arrayId . '-table" class="object-array-view">';
    $output .= '<div class="mini-table-container">';
    $output .= '<table class="mini-array-table">';
    $output .= '<thead><tr><th>Key</th><th>Value</th></tr></thead>';
    $output .= '<tbody>';
    
    $displayCount = 0;
    $maxTableDisplay = 8;
    
    foreach ($array as $key => $value) {
        if ($displayCount >= $maxTableDisplay) {
            $output .= '<tr><td colspan="2" class="more-row">... ve ' . ($count - $maxTableDisplay) . ' öğe daha</td></tr>';
            break;
        }
        
        $output .= '<tr>';
        $output .= '<td class="mini-key-cell">';
        $output .= '<i class="key-icon">' . (is_string($key) ? '🔑' : '#️⃣') . '</i>';
        $output .= htmlspecialchars($key);
        $output .= '</td>';
        $output .= '<td class="mini-value-cell">' . formatValueWithIcon($value) . '</td>';
        $output .= '</tr>';
        
        $displayCount++;
    }
    
    $output .= '</tbody></table>';
    $output .= '</div>';
    $output .= '</div>';
    
    // JSON view
    $output .= '<div id="' . $arrayId . '-json" class="object-array-view">';
    $output .= '<div class="mini-json-container">';
    $output .= formatAsJson($array);
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * JSON formatında veri görüntüleme
 * @param mixed $data JSON formatında gösterilecek veri
 * @return string JSON string
 */
function formatAsJson($data): string {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return '<span class="value-null">JSON dönüştürülemedi</span>';
    }
    return '<pre class="json-output">' . $json . '</pre>';
}

/**
 * SQL query formatı için özel görüntüleme
 * @param string $query SQL sorgusu
 * @return string Formatlanmış SQL
 */
function formatSqlQuery($query): string {
    $keywords = ['SELECT', 'FROM', 'WHERE', 'JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'INNER JOIN', 
                 'ORDER BY', 'GROUP BY', 'HAVING', 'LIMIT', 'INSERT', 'UPDATE', 'DELETE', 
                 'CREATE', 'DROP', 'ALTER', 'INDEX', 'PRIMARY KEY'];
    
    $formatted = $query;
    foreach ($keywords as $keyword) {
        $formatted = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', 
                                '<span class="sql-keyword">' . $keyword . '</span>', $formatted);
    }
    
    return '<pre class="sql-output">' . $formatted . '</pre>';
}

/**
 * Modern sınıf bilgilerini detaylı şekilde görüntüleme
 * @param object $object Analiz edilecek object
 * @param string $instanceId Instance ID
 * @param int $prInstanceCount Instance sayısı
 */
function displayModernClassInfo($object, $instanceId, $prInstanceCount): void {
    try {
        $reflection = new ReflectionClass($object);
        $classId = 'class-' . $instanceId . '-' . uniqid();
        
        // Ana class container
        echo '<div class="modern-class-container" id="' . $classId . '">';
        
        // Class header - genel bilgiler
        echo '<div class="class-header">';
        echo '<div class="class-title-section">';
        echo '<h4 class="class-name">';
        echo '<i class="class-icon">🏛️</i>';
        echo '<span class="class-full-name">' . $reflection->getName() . '</span>';
        echo '</h4>';
        
        // Class badges
        echo '<div class="class-badges">';
        if ($reflection->isAbstract()) echo '<span class="class-badge badge-abstract">Abstract</span>';
        if ($reflection->isFinal()) echo '<span class="class-badge badge-final">Final</span>';
        if ($reflection->isInterface()) echo '<span class="class-badge badge-interface">Interface</span>';
        if ($reflection->isTrait()) echo '<span class="class-badge badge-trait">Trait</span>';
        if ($reflection->isInstantiable()) echo '<span class="class-badge badge-instantiable">Instantiable</span>';
        echo '</div>';
        echo '</div>';
        
        // Quick stats
        $stats = getClassStats($reflection, $object);
        echo '<div class="class-stats-grid">';
        echo '<div class="stat-item"><span class="stat-number">' . $stats['properties'] . '</span><span class="stat-label">Properties</span></div>';
        echo '<div class="stat-item"><span class="stat-number">' . $stats['methods'] . '</span><span class="stat-label">Methods</span></div>';
        echo '<div class="stat-item"><span class="stat-number">' . $stats['constants'] . '</span><span class="stat-label">Constants</span></div>';
        echo '<div class="stat-item"><span class="stat-number">' . $stats['traits'] . '</span><span class="stat-label">Traits</span></div>';
        echo '</div>';
        echo '</div>';
        
        // Navigation tabs
        echo '<div class="class-navigation">';
        echo '<button class="nav-tab active" onclick="switchClassTab(\'' . $classId . '\', \'overview\')">📋 Genel Bakış</button>';
        echo '<button class="nav-tab" onclick="switchClassTab(\'' . $classId . '\', \'hierarchy\')">🏗️ Hiyerarşi</button>';
        echo '<button class="nav-tab" onclick="switchClassTab(\'' . $classId . '\', \'properties\')">🏷️ Özellikler (' . $stats['properties'] . ')</button>';
        echo '<button class="nav-tab" onclick="switchClassTab(\'' . $classId . '\', \'methods\')">⚙️ Metotlar (' . $stats['methods'] . ')</button>';
        if ($stats['constants'] > 0) {
            echo '<button class="nav-tab" onclick="switchClassTab(\'' . $classId . '\', \'constants\')">📐 Sabitler (' . $stats['constants'] . ')</button>';
        }
        echo '</div>';
        
        // Tab contents
        echo '<div class="class-content-tabs">';
        
        // Overview tab
        echo '<div id="' . $classId . '-overview" class="tab-content active">';
        displayClassOverview($reflection, $object, $classId);
        echo '</div>';
        
        // Hierarchy tab
        echo '<div id="' . $classId . '-hierarchy" class="tab-content">';
        displayClassHierarchy($reflection, $classId);
        echo '</div>';
        
        // Properties tab
        echo '<div id="' . $classId . '-properties" class="tab-content">';
        displayClassProperties($reflection, $object, $classId, $prInstanceCount);
        echo '</div>';
        
        // Methods tab  
        echo '<div id="' . $classId . '-methods" class="tab-content">';
        displayClassMethods($reflection, $classId);
        echo '</div>';
        
        // Constants tab
        if ($stats['constants'] > 0) {
            echo '<div id="' . $classId . '-constants" class="tab-content">';
            displayClassConstants($reflection, $classId);
            echo '</div>';
        }
        
        echo '</div>'; // class-content-tabs
        echo '</div>'; // modern-class-container
        
    } catch (Exception $e) {
        echo '<div class="class-error">';
        echo '<i class="error-icon">❌</i>';
        echo '<span>Sınıf analiz edilemedi: ' . htmlspecialchars($e->getMessage()) . '</span>';
        echo '</div>';
    }
}

/**
 * Sınıf istatistiklerini hesapla
 */
function getClassStats($reflection, $object): array {
    $properties = count($reflection->getProperties());
    $methods = count($reflection->getMethods());
    $constants = count($reflection->getConstants());
    $traits = count($reflection->getTraitNames());
    
    return [
        'properties' => $properties,
        'methods' => $methods,
        'constants' => $constants,
        'traits' => $traits
    ];
}

/**
 * Sınıf genel bakış görünümü
 */
function displayClassOverview($reflection, $object, $classId): void {
    echo '<div class="overview-grid">';
    
    // Temel bilgiler
    echo '<div class="overview-section">';
    echo '<h5 class="section-header">🏛️ Temel Bilgiler</h5>';
    echo '<div class="info-grid">';
    
    echo '<div class="info-item">';
    echo '<span class="info-label">Sınıf Adı:</span>';
    echo '<span class="info-value class-name-highlight">' . $reflection->getShortName() . '</span>';
    echo '</div>';
    
    if ($reflection->getNamespaceName()) {
        echo '<div class="info-item">';
        echo '<span class="info-label">Namespace:</span>';
        echo '<span class="info-value namespace-highlight">' . $reflection->getNamespaceName() . '</span>';
        echo '</div>';
    }
    
    if ($reflection->getFileName()) {
        echo '<div class="info-item">';
        echo '<span class="info-label">Dosya:</span>';
        echo '<span class="info-value file-path">' . basename($reflection->getFileName()) . '</span>';
        echo '</div>';
        
        echo '<div class="info-item">';
        echo '<span class="info-label">Satır:</span>';
        echo '<span class="info-value line-number">' . $reflection->getStartLine() . ' - ' . $reflection->getEndLine() . '</span>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    
    // Miras ve arayüzler
    echo '<div class="overview-section">';
    echo '<h5 class="section-header">🔗 İlişkiler</h5>';
    echo '<div class="relations-container">';
    
    // Parent class
    if ($reflection->getParentClass()) {
        echo '<div class="relation-item">';
        echo '<span class="relation-type parent-class">👆 Üst Sınıf:</span>';
        echo '<span class="relation-name">' . $reflection->getParentClass()->getName() . '</span>';
        echo '</div>';
    }
    
    // Interfaces
    $interfaces = $reflection->getInterfaceNames();
    if (!empty($interfaces)) {
        echo '<div class="relation-item">';
        echo '<span class="relation-type interface">🔌 Arayüzler:</span>';
        echo '<div class="relation-list">';
        foreach ($interfaces as $interface) {
            echo '<span class="interface-badge">' . basename($interface) . '</span>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    // Traits
    $traits = $reflection->getTraitNames();
    if (!empty($traits)) {
        echo '<div class="relation-item">';
        echo '<span class="relation-type trait">🧩 Trait\'ler:</span>';
        echo '<div class="relation-list">';
        foreach ($traits as $trait) {
            echo '<span class="trait-badge">' . basename($trait) . '</span>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    
    // DocBlock bilgisi varsa
    $docComment = $reflection->getDocComment();
    if ($docComment) {
        echo '<div class="overview-section">';
        echo '<h5 class="section-header">📝 Dokümantasyon</h5>';
        echo '<div class="doc-comment">';
        echo formatDocComment($docComment);
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>'; // overview-grid
}

/**
 * Sınıf hiyerarşisi görünümü
 */
function displayClassHierarchy($reflection, $classId): void {
    echo '<div class="hierarchy-container">';
    
    // Class hierarchy tree
    echo '<div class="hierarchy-tree">';
    echo '<h5 class="section-header">🌳 Sınıf Hiyerarşisi</h5>';
    
    $hierarchy = [];
    $current = $reflection;
    
    // Üst sınıfları topla
    while ($current) {
        $hierarchy[] = $current;
        $current = $current->getParentClass();
    }
    
    $hierarchy = array_reverse($hierarchy);
    
    echo '<div class="hierarchy-levels">';
    foreach ($hierarchy as $index => $class) {
        $isTarget = ($class->getName() === $reflection->getName());
        $level = count($hierarchy) - $index - 1;
        
        echo '<div class="hierarchy-level" style="margin-left: ' . ($index * 20) . 'px;">';
        
        if ($index > 0) {
            echo '<div class="hierarchy-connector">└─</div>';
        }
        
        echo '<div class="hierarchy-class ' . ($isTarget ? 'target-class' : 'parent-class') . '">';
        echo '<i class="hierarchy-icon">' . ($isTarget ? '🎯' : '👆') . '</i>';
        echo '<span class="hierarchy-name">' . $class->getShortName() . '</span>';
        if ($class->getNamespaceName()) {
            echo '<span class="hierarchy-namespace">' . $class->getNamespaceName() . '</span>';
        }
        echo '</div>';
        
        echo '</div>';
    }
    echo '</div>';
    
    // Interface hiyerarşisi
    $interfaces = $reflection->getInterfaceNames();
    if (!empty($interfaces)) {
        echo '<div class="interface-hierarchy">';
        echo '<h6 class="subsection-header">🔌 Implement Edilen Arayüzler</h6>';
        echo '<div class="interface-list">';
        foreach ($interfaces as $interface) {
            echo '<div class="interface-item">';
            echo '<i class="interface-icon">🔌</i>';
            echo '<span class="interface-name">' . basename($interface) . '</span>';
            if (strpos($interface, '\\') !== false) {
                echo '<span class="interface-namespace">' . dirname(str_replace('\\', '/', $interface)) . '</span>';
            }
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    // Trait hiyerarşisi
    $traits = $reflection->getTraitNames();
    if (!empty($traits)) {
        echo '<div class="trait-hierarchy">';
        echo '<h6 class="subsection-header">🧩 Kullanılan Trait\'ler</h6>';
        echo '<div class="trait-list">';
        foreach ($traits as $trait) {
            echo '<div class="trait-item">';
            echo '<i class="trait-icon">🧩</i>';
            echo '<span class="trait-name">' . basename($trait) . '</span>';
            if (strpos($trait, '\\') !== false) {
                echo '<span class="trait-namespace">' . dirname(str_replace('\\', '/', $trait)) . '</span>';
            }
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}

/**
 * Sınıf özelliklerini detaylı görüntüleme
 */
function displayClassProperties($reflection, $object, $classId, $prInstanceCount): void {
    $properties = $reflection->getProperties();
    
    if (empty($properties)) {
        echo '<div class="empty-section">';
        echo '<i class="empty-icon">📭</i>';
        echo '<p>Bu sınıfta özellik bulunmuyor.</p>';
        echo '</div>';
        return;
    }
    
    // Filtreleme ve arama
    echo '<div class="property-controls">';
    echo '<div class="search-container">';
    echo '<input type="text" class="property-search" placeholder="🔍 Özellik ara..." onkeyup="filterClassProperties(\'' . $classId . '\', this.value)">';
    echo '</div>';
    
    echo '<div class="filter-buttons">';
    echo '<button class="filter-btn active" onclick="filterPropertiesByVisibility(\'' . $classId . '\', \'all\')">Tümü</button>';
    echo '<button class="filter-btn" onclick="filterPropertiesByVisibility(\'' . $classId . '\', \'public\')">Public</button>';
    echo '<button class="filter-btn" onclick="filterPropertiesByVisibility(\'' . $classId . '\', \'protected\')">Protected</button>';
    echo '<button class="filter-btn" onclick="filterPropertiesByVisibility(\'' . $classId . '\', \'private\')">Private</button>';
    echo '<button class="filter-btn" onclick="filterPropertiesByVisibility(\'' . $classId . '\', \'static\')">Static</button>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="properties-container" id="' . $classId . '-properties-list">';
    
    foreach ($properties as $index => $property) {
        $property->setAccessible(true);
        $propId = $classId . '-prop-' . $index;
        
        // Property visibility classes
        $visibilityClasses = [];
        if ($property->isPublic()) $visibilityClasses[] = 'visibility-public';
        if ($property->isProtected()) $visibilityClasses[] = 'visibility-protected';  
        if ($property->isPrivate()) $visibilityClasses[] = 'visibility-private';
        if ($property->isStatic()) $visibilityClasses[] = 'is-static';
        
        echo '<div class="property-card ' . implode(' ', $visibilityClasses) . '" data-property-name="' . strtolower($property->getName()) . '">';
        
        // Property header
        echo '<div class="property-card-header">';
        echo '<div class="property-signature">';
        
        // Visibility badges
        echo '<div class="property-modifiers">';
        if ($property->isPublic()) echo '<span class="modifier-badge public">public</span>';
        if ($property->isProtected()) echo '<span class="modifier-badge protected">protected</span>';
        if ($property->isPrivate()) echo '<span class="modifier-badge private">private</span>';
        if ($property->isStatic()) echo '<span class="modifier-badge static">static</span>';
        if ($property->isReadOnly() && method_exists($property, 'isReadOnly')) echo '<span class="modifier-badge readonly">readonly</span>';
        echo '</div>';
        
        // Property type ve name
        echo '<div class="property-declaration">';
        
        // Type hint (PHP 7.4+)
        if (method_exists($property, 'getType') && $property->getType()) {
            echo '<span class="property-type">' . $property->getType() . '</span> ';
        }
        
        echo '<span class="property-name">$' . $property->getName() . '</span>';
        
        // Default value if available
        if ($property->hasDefaultValue() && method_exists($property, 'getDefaultValue')) {
            try {
                $defaultValue = $property->getDefaultValue();
                echo ' = <span class="default-value">' . formatDefaultValueText($defaultValue) . '</span>';
            } catch (Exception $e) {
                // Ignore default value errors
            }
        }
        
        echo '</div>';
        
        // Property actions
        echo '<div class="property-actions">';
        echo '<button class="action-btn" onclick="togglePropertyDetails(\'' . $propId . '\')" title="Ayrıntıları göster/gizle">';
        echo '<i class="action-icon">⚙️</i>';
        echo '</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        
        // Property details (collapsible)
        echo '<div class="property-details" id="details-' . $propId . '" style="display: none;">';
        
        // DocComment
        $docComment = $property->getDocComment();
        if ($docComment) {
            echo '<div class="property-doc">';
            echo '<h6>📝 Dokümantasyon:</h6>';
            echo formatDocComment($docComment);
            echo '</div>';
        }
        
        // Current value
        echo '<div class="property-value-section">';
        echo '<h6>🔍 Mevcut Değer:</h6>';
        echo '<div class="value-container">';
        
        try {
            $value = $property->getValue($object);
            
            if (is_array($value) && !empty($value)) {
                // Array için özel görüntüleme
                $arrayId = $propId . '-array';
                $arrayJson = base64_encode(json_encode($value));
                $arrayTitle = 'Property: ' . $property->getName() . ' [' . count($value) . ' öğe]';
                
                echo '<div class="array-value-preview">';
                echo '<span class="array-info">';
                echo '<i class="array-icon">📋</i>';
                echo 'Array[' . number_format(count($value)) . '] ';
                echo '<span class="array-type">(' . (array_keys($value) !== range(0, count($value) - 1) ? 'Associative' : 'Indexed') . ')</span>';
                echo '</span>';
                echo '<button class="view-array-btn" onclick="openArrayModalBase64(\'' . $arrayJson . '\', \'' . $arrayTitle . '\', \'' . $arrayId . '\')">';
                echo '<i class="btn-icon">🚀</i> Modal\'da Görüntüle';
                echo '</button>';
                echo '</div>';
                
                // İlk birkaç elemanı önizle
                echo '<div class="array-preview">';
                $previewCount = 0;
                foreach ($value as $k => $v) {
                    if ($previewCount >= 3) {
                        echo '<div class="array-more">... ve ' . (count($value) - 3) . ' öğe daha</div>';
                        break;
                    }
                    echo '<div class="array-item-preview">';
                    echo '<span class="array-key">' . htmlspecialchars($k) . ':</span>';
                    echo '<span class="array-value">' . formatValue($v) . '</span>';
                    echo '</div>';
                    $previewCount++;
                }
                echo '</div>';
                
            } elseif (is_object($value)) {
                echo '<div class="object-value-preview">';
                echo '<span class="object-info">';
                echo '<i class="object-icon">🎯</i>';
                echo 'Object(' . get_class($value) . ')';
                echo '</span>';
                echo '<button class="view-object-btn" onclick="expandNestedObject(\'' . $propId . '-nested\')">';
                echo '<i class="btn-icon">🔍</i> Ayrıntıları Gör';
                echo '</button>';
                echo '</div>';
                
                echo '<div class="nested-object" id="' . $propId . '-nested" style="display: none;">';
                echo renderObjectDetails($value);
                echo '</div>';
                
            } else {
                echo '<div class="simple-value">';
                echo formatValue($value);
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="value-error">';
            echo '<i class="error-icon">⚠️</i>';
            echo '<span>Erişilemiyor: ' . htmlspecialchars($e->getMessage()) . '</span>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // property-details
        echo '</div>'; // property-card
    }
    
    echo '</div>'; // properties-container
}

/**
 * Sınıf metotlarını detaylı görüntüleme
 */
function displayClassMethods($reflection, $classId): void {
    $methods = $reflection->getMethods();
    
    if (empty($methods)) {
        echo '<div class="empty-section">';
        echo '<i class="empty-icon">📭</i>';
        echo '<p>Bu sınıfta metot bulunmuyor.</p>';
        echo '</div>';
        return;
    }
    
    // Method kontrolları
    echo '<div class="method-controls">';
    echo '<div class="search-container">';
    echo '<input type="text" class="method-search" placeholder="🔍 Metot ara..." onkeyup="filterClassMethods(\'' . $classId . '\', this.value)">';
    echo '</div>';
    
    echo '<div class="filter-buttons">';
    echo '<button class="filter-btn active" onclick="filterMethodsByVisibility(\'' . $classId . '\', \'all\')">Tümü</button>';
    echo '<button class="filter-btn" onclick="filterMethodsByVisibility(\'' . $classId . '\', \'public\')">Public</button>';
    echo '<button class="filter-btn" onclick="filterMethodsByVisibility(\'' . $classId . '\', \'protected\')">Protected</button>';
    echo '<button class="filter-btn" onclick="filterMethodsByVisibility(\'' . $classId . '\', \'private\')">Private</button>';
    echo '<button class="filter-btn" onclick="filterMethodsByVisibility(\'' . $classId . '\', \'static\')">Static</button>';
    echo '<button class="filter-btn" onclick="filterMethodsByVisibility(\'' . $classId . '\', \'abstract\')">Abstract</button>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="methods-container" id="' . $classId . '-methods-list">';
    
    foreach ($methods as $index => $method) {
        $methodId = $classId . '-method-' . $index;
        
        // Method visibility classes
        $visibilityClasses = [];
        if ($method->isPublic()) $visibilityClasses[] = 'visibility-public';
        if ($method->isProtected()) $visibilityClasses[] = 'visibility-protected';
        if ($method->isPrivate()) $visibilityClasses[] = 'visibility-private';
        if ($method->isStatic()) $visibilityClasses[] = 'is-static';
        if ($method->isAbstract()) $visibilityClasses[] = 'is-abstract';
        if ($method->isFinal()) $visibilityClasses[] = 'is-final';
        
        echo '<div class="method-card ' . implode(' ', $visibilityClasses) . '" data-method-name="' . strtolower($method->getName()) . '">';
        
        // Method header
        echo '<div class="method-card-header">';
        echo '<div class="method-signature">';
        
        // Method modifiers
        echo '<div class="method-modifiers">';
        if ($method->isPublic()) echo '<span class="modifier-badge public">public</span>';
        if ($method->isProtected()) echo '<span class="modifier-badge protected">protected</span>';
        if ($method->isPrivate()) echo '<span class="modifier-badge private">private</span>';
        if ($method->isStatic()) echo '<span class="modifier-badge static">static</span>';
        if ($method->isAbstract()) echo '<span class="modifier-badge abstract">abstract</span>';
        if ($method->isFinal()) echo '<span class="modifier-badge final">final</span>';
        echo '</div>';
        
        // Method declaration
        echo '<div class="method-declaration">';
        
        // Return type
        $returnType = '';
        if (method_exists($method, 'getReturnType') && $method->getReturnType()) {
            $returnType = $method->getReturnType();
            echo '<span class="return-type">' . $returnType . '</span> ';
        }
        
        echo '<span class="method-name">' . $method->getName() . '</span>';
        
        // Parameters
        echo '<span class="method-params">(';
        $parameters = $method->getParameters();
        $paramStrings = [];
        
        foreach ($parameters as $param) {
            $paramStr = '';
            
            // Parameter type
            if (method_exists($param, 'getType') && $param->getType()) {
                $paramStr .= '<span class="param-type">' . $param->getType() . '</span> ';
            }
            
            // Parameter name
            $paramStr .= '<span class="param-name">$' . $param->getName() . '</span>';
            
            // Default value
            if ($param->isDefaultValueAvailable()) {
                $defaultValue = $param->getDefaultValue();
                $paramStr .= ' = <span class="param-default">' . formatDefaultValueText($defaultValue) . '</span>';
            }
            
            $paramStrings[] = $paramStr;
        }
        
        echo implode(', ', $paramStrings);
        echo ')</span>';
        
        echo '</div>';
        
        // Method actions
        echo '<div class="method-actions">';
        echo '<button class="action-btn" onclick="toggleMethodDetails(\'' . $methodId . '\')" title="Ayrıntıları göster/gizle">';
        echo '<i class="action-icon">⚙️</i>';
        echo '</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        
        // Method details (collapsible)
        echo '<div class="method-details" id="details-' . $methodId . '" style="display: none;">';
        
        // DocComment
        $docComment = $method->getDocComment();
        if ($docComment) {
            echo '<div class="method-doc">';
            echo '<h6>📝 Dokümantasyon:</h6>';
            echo formatDocComment($docComment);
            echo '</div>';
        }
        
        // Method info
        echo '<div class="method-info-grid">';
        
        // Parameters detail
        if (!empty($parameters)) {
            echo '<div class="method-info-section">';
            echo '<h6>📥 Parametreler:</h6>';
            echo '<div class="parameters-list">';
            
            foreach ($parameters as $param) {
                echo '<div class="parameter-item">';
                echo '<div class="parameter-signature">';
                
                // Type
                if (method_exists($param, 'getType') && $param->getType()) {
                    echo '<span class="param-type-badge">' . $param->getType() . '</span>';
                }
                
                echo '<span class="param-name-highlight">$' . $param->getName() . '</span>';
                
                // Optional/Required
                if ($param->isOptional()) {
                    echo '<span class="param-optional">isteğe bağlı</span>';
                } else {
                    echo '<span class="param-required">zorunlu</span>';
                }
                
                echo '</div>';
                
                // Default value
                if ($param->isDefaultValueAvailable()) {
                    echo '<div class="param-default-info">';
                    echo 'Varsayılan: <code>' . formatDefaultValueText($param->getDefaultValue()) . '</code>';
                    echo '</div>';
                }
                
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        // Return type info
        if ($returnType) {
            echo '<div class="method-info-section">';
            echo '<h6>📤 Dönüş Türü:</h6>';
            echo '<span class="return-type-info">' . $returnType . '</span>';
            echo '</div>';
        }
        
        // Method source location
        if ($method->getFileName()) {
            echo '<div class="method-info-section">';
            echo '<h6>📍 Konum:</h6>';
            echo '<div class="source-location">';
            echo '<span class="file-name">' . basename($method->getFileName()) . '</span>';
            echo '<span class="line-range">: ' . $method->getStartLine() . ' - ' . $method->getEndLine() . '</span>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>'; // method-info-grid
        echo '</div>'; // method-details
        echo '</div>'; // method-card
    }
    
    echo '</div>'; // methods-container
}

/**
 * Sınıf sabitlerini görüntüleme
 */
function displayClassConstants($reflection, $classId): void {
    $constants = $reflection->getConstants();
    
    if (empty($constants)) {
        echo '<div class="empty-section">';
        echo '<i class="empty-icon">📭</i>';
        echo '<p>Bu sınıfta sabit bulunmuyor.</p>';
        echo '</div>';
        return;
    }
    
    echo '<div class="constants-container">';
    echo '<div class="constants-grid">';
    
    foreach ($constants as $name => $value) {
        echo '<div class="constant-card">';
        echo '<div class="constant-header">';
        echo '<i class="constant-icon">📐</i>';
        echo '<span class="constant-name">' . $name . '</span>';
        echo '</div>';
        
        echo '<div class="constant-value">';
        echo formatValue($value);
        echo '</div>';
        
        // Type info
        echo '<div class="constant-type">';
        echo '<span class="type-badge">' . gettype($value) . '</span>';
        echo '</div>';
        
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}

/**
 * DocComment formatla
 */
function formatDocComment($docComment): string {
    if (!$docComment) return '';
    
    $lines = explode("\n", $docComment);
    $formatted = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        $line = ltrim($line, '/*');
        $line = rtrim($line, '*/');
        $line = ltrim($line, '*');
        $line = trim($line);
        
        if (empty($line)) continue;
        
        // @param, @return vb. etiketleri vurgula
        if (preg_match('/^@(\w+)(.*)/', $line, $matches)) {
            $formatted .= '<div class="doc-tag">';
            $formatted .= '<span class="doc-tag-name">@' . $matches[1] . '</span>';
            if (isset($matches[2]) && !empty(trim($matches[2]))) {
                $formatted .= '<span class="doc-tag-desc">' . htmlspecialchars(trim($matches[2])) . '</span>';
            }
            $formatted .= '</div>';
        } else {
            $formatted .= '<div class="doc-description">' . htmlspecialchars($line) . '</div>';
        }
    }
    
    return '<div class="doc-formatted">' . $formatted . '</div>';
}
 
