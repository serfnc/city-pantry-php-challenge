<?php

require_once './class.Friend.php';

date_default_timezone_set('Europe/London');

$friend = new Friend();
$results = $friend->letsGo();

/*
 *  The results come as an array. Here's an example
 * (
            [name] => Ghana Kitchen
            [postcode] => NW42QA
            [covers] => 40
            [menus] => Array
                (
                    [0] => Array
                        (
                            [name] => Premium meat selection
                            [allergies] =>
                            [noticePeriod] => 36h
                        )

                    [1] => Array
                        (
                            [name] => Breakfast
                            [allergies] => gluten,eggs
                            [noticePeriod] => 12h
                        )

                )

        )

 */


// Let's print as in the example-input format.
$print = '';

foreach ($results as $result) {
    $print .= implode(';', [$result['name'], $result['postcode'], $result['covers']]) . "\n";

    foreach ($result['menus'] as $menu) {
        $print .= implode(';', [$menu['name'], $menu['allergies']]) . "\n";
    }

    $print .= "\n";
}

echo $print;
