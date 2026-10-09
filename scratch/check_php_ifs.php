<?php
\ = file_get_contents('storage/framework/views/5e03052249df5bcd4c1f1547fec965db.php');
preg_match_all('/(<?php if|<?php endif;)/', \, \, PREG_OFFSET_CAPTURE);
\ = 0;
\ = 0;
foreach (\[0] as \) {
    if (\[0] === '<?php if') { \++; echo "IF at \[1]\n"; }
    if (\[0] === '<?php endif;') { \++; echo "ENDIF at \[1]\n"; }
}
echo "If: \, Endif: \\n";
