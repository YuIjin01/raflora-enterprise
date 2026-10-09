$c = file_get_contents('resources/views/admin/booking-show.blade.php');
preg_match_all('/@forelse\b/', $c, $m1);
preg_match_all('/@empty\b/', $c, $m2);
preg_match_all('/@endforelse\b/', $c, $m3);
echo 'Forelses: ' . count($m1[0]) . ' Empties: ' . count($m2[0]) . ' Endforelses: ' . count($m3[0]) . \"\n\";
