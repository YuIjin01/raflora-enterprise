$content = file_get_contents('resources/views/admin/booking-show.blade.php');
preg_match_all('/@if\b/', $content, $m1);
preg_match_all('/@endif\b/', $content, $m2);
echo 'Ifs: ' . count($m1[0]) . ' Endifs: ' . count($m2[0]) . \"\n\";
