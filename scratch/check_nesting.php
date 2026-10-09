<?php
\ = file_get_contents('resources/views/admin/booking-show.blade.php');
preg_match_all('/@(if|elseif|else|endif|forelse|empty|endforelse|unless|endunless|foreach|endforeach|can|endcan|auth|endauth|guest|endguest)\b/', \, \, PREG_OFFSET_CAPTURE);
\ = [];
foreach (\[0] as \) {
    \ = \[0];
    \ = \[1];
    if (in_array(\, ['@if', '@forelse', '@unless', '@foreach', '@can', '@auth', '@guest'])) {
        \[] = \;
    } elseif (in_array(\, ['@endif', '@endforelse', '@endunless', '@endforeach', '@endcan', '@endauth', '@endguest'])) {
        if (empty(\)) {
            echo "Unexpected \ at offset \\n";
        } else {
            \ = array_pop(\);
            \ = str_replace('@', '@end', \);
            if (\ === '@if') \ = '@endif';
            if (\ === '@forelse') \ = '@endforelse';
            if (\ !== \) {
                echo "Mismatch: expected \ but got \ at offset \\n";
            }
        }
    }
}
if (!empty(\)) {
    echo "Unclosed tags: " . implode(', ', \) . "\n";
} else {
    echo "All tags balanced correctly!\n";
}
