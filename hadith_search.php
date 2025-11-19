<?php
$query = $_GET['q'] ?? '';
$results = [];
$total = 0;

if ($query) {
    $search_url = "https://dorar.net/hadith/search?st=w&q=" . urlencode($query);

    $ch = curl_init($search_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    if ($html) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $nodes = $xpath->query("//div[contains(@class,'border-bottom py-4')]");
        $total = min(15, $nodes->length);

        for ($i=0; $i<$total; $i++) {
            $node = $nodes->item($i);

            $textNode = $xpath->query(".//h5[contains(@class,'h5-responsive')]", $node);
            $text = $textNode->length ? trim($textNode[0]->textContent) : '';

            $gradeNode = $xpath->query(".//strong[contains(text(),'خلاصة حكم المحدث')]", $node);
            if($gradeNode->length){
                $gradeSpan = $xpath->query(".//span", $gradeNode[0]);
                $grade = $gradeSpan->length ? trim($gradeSpan[0]->textContent) : '';
                $color = "#444"; // default dark gray
                if(stripos($grade,'صحيح')!==false) $color = "#1abc9c"; // أخضر داكن
                elseif(stripos($grade,'حسن')!==false) $color = "#3498db"; // أزرق داكن
                elseif(stripos($grade,'ضعيف')!==false) $color = "#e67e22"; // برتقالي داكن
            } else {
                $grade = "-";
                $color = "#555";
            }

            $rawiNode = $xpath->query(".//strong[contains(text(),'الراوي')]", $node);
            $rawi = ($rawiNode->length && $xpath->query(".//span",$rawiNode[0])->length) ? trim($xpath->query(".//span",$rawiNode[0])[0]->textContent) : "-";

            $muhaddithNode = $xpath->query(".//strong[contains(text(),'المحدث')]", $node);
            $muhaddith = ($muhaddithNode->length && $xpath->query(".//span",$muhaddithNode[0])->length) ? trim($xpath->query(".//span",$muhaddithNode[0])[0]->textContent) : "-";

            $sourceNode = $xpath->query(".//strong[contains(text(),'المصدر')]", $node);
            $source = ($sourceNode->length && $xpath->query(".//span",$sourceNode[0])->length) ? trim($xpath->query(".//span",$sourceNode[0])[0]->textContent) : "-";

            $pageNode = $xpath->query(".//strong[contains(text(),'الصفحة')]", $node);
            $page = ($pageNode->length && $xpath->query(".//span",$pageNode[0])->length) ? trim($xpath->query(".//span",$pageNode[0])[0]->textContent) : "-";

            $results[] = [
                'text'=>$text,
                'grade'=>$grade,
                'color'=>$color,
                'rawi'=>$rawi,
                'muhaddith'=>$muhaddith,
                'source'=>$source,
                'page'=>$page
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>بحث صحة الحديث - درر الحديثية</title>
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { font-family:'Amiri', serif; background:#121212; margin:0; padding:0; color:#f5f5f5;}
header { background:#1e1e1e; color:#f5f5f5; text-align:center; padding:25px; font-size:30px; font-weight:bold; letter-spacing:1px; box-shadow:0 5px 15px rgba(0,0,0,0.3);}
.container { max-width:1100px; margin:30px auto; padding:30px; background:#1c1c1c; border-radius:25px; box-shadow:0 20px 50px rgba(0,0,0,0.5);}
form { text-align:center; margin-bottom:30px;}
form input[type=text]{width:70%; padding:15px; font-size:18px; border-radius:15px; border:1px solid #333; background:#222; color:#f5f5f5; transition:0.3s;}
form input[type=text]:focus{border-color:#3498db; outline:none; box-shadow:0 0 10px rgba(52,152,219,0.5);}
form button{padding:15px 25px; font-size:18px; border-radius:15px; border:none; background:#3498db; color:#fff; cursor:pointer; transition:0.3s;}
form button:hover{background:#1abc9c;}
.result-count {font-size:18px; font-weight:bold; margin-bottom:25px; text-align:center; color:#f5f5f5;}
.hadith-item{margin-bottom:25px; padding:25px; border-radius:20px; background:#1e1e1e; box-shadow:0 10px 25px rgba(0,0,0,0.7); transition:0.3s;}
.hadith-item:hover{transform:translateY(-5px); box-shadow:0 15px 35px rgba(0,0,0,0.9);}
.hadith-text{font-size:20px; margin-bottom:15px; line-height:1.7; color:#f5f5f5;}
.hadith-grade{display:inline-block; padding:8px 15px; border-radius:15px; font-weight:bold; margin-bottom:10px;}
.hadith-info{font-size:16px; color:#ccc; line-height:1.6;}
.hadith-info i{color:#3498db; margin-right:5px;}
</style>
</head>
<body>
<header>بحث صحة الحديث - درر الحديثية</header>
<div class="container">
<form method="get">
    <input type="text" name="q" placeholder="أدخل نص الحديث..." value="<?= htmlspecialchars($query) ?>" required>
    <button type="submit"><i class="fa fa-search"></i> بحث</button>
</form>

<?php if($query): ?>
<div class="result-count">عدد النتائج: <?= count($results) ?></div>
<?php foreach($results as $hadith): ?>
<div class="hadith-item">
    <div class="hadith-text"><?= htmlspecialchars($hadith['text']) ?></div>
    <div class="hadith-grade" style="background-color:<?= $hadith['color'] ?>; color:#fff;"><i class="fa fa-gavel"></i> <?= htmlspecialchars($hadith['grade']) ?></div>
    <div class="hadith-info">
        <i class="fa fa-user"></i> الراوي: <?= htmlspecialchars($hadith['rawi']) ?> |
        <i class="fa fa-book"></i> المحدث: <?= htmlspecialchars($hadith['muhaddith']) ?> |
        <i class="fa fa-book-open"></i> المصدر: <?= htmlspecialchars($hadith['source']) ?> |
        <i class="fa fa-hashtag"></i> الصفحة: <?= htmlspecialchars($hadith['page']) ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</body>
</html>
