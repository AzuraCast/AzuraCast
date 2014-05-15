<?php

function strtest($s)
{
$string_parts = str_split($s);
$new_string = '';

for($i = count($string_parts)-1; $i >= 0; $i--)
    $new_string .= $string_parts[$i];

return $new_string;
}

echo strtest('Hello world!');
