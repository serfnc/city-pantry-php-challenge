<?php

error_reporting(0);

require_once './vendor/autoload.php';
require_once './class.Friend.php';

date_default_timezone_set('Europe/London');

use PHPUnit\Framework\TestCase;

class CalculatorTests extends TestCase
{
    public function testData()
    {
        $friend = new Friend();
        $friend->filename = 'tests/test-example-input';
        $friend->day = date('d/m/Y', strtotime('+37 hours'));
        $friend->time = date('H:i', strtotime('+37 hours'));
        $friend->location = 'E143BL';
        $friend->covers = 50;

        $results = $friend->getResults();

        // Only 2 restaurants can cover up to 50 portions, 1.5 days ahead
        $this->assertSame(2, count($results));

        foreach ($results as $result) {
            if ($result['name'] == 'The Gun') {
                // Expect all 3 menus to be available
                $this->assertSame(3, count($result['menus']));
            }
        }



        $friend2 = new Friend();
        $friend2->filename = 'tests/test-example-input';
        $friend2->day = date('d/m/Y', strtotime('+35 hours'));
        $friend2->time = date('H:i', strtotime('+35 hours'));
        $friend2->location = 'E143BL';
        $friend2->covers = 100;

        $results = $friend2->getResults();

        // Only 2 restaurants can cover up to 100 portions, 1.5 days ahead
        $this->assertSame(2, count($results));

        foreach ($results as $result) {
            $this->assertArrayHasKey('name', $result);
            $this->assertArrayHasKey('postcode', $result);
            $this->assertArrayHasKey('covers', $result);
            $this->assertArrayHasKey('menus', $result);

            if ($result['name'] == 'The Gun') {
                // Expect only 2 menus to be available as one has a notice period of 36 hours
                $this->assertSame(2, count($result['menus']));

                foreach ($result['menus'] as $menu) {
                    $this->assertArrayHasKey('name', $menu);
                    $this->assertArrayHasKey('allergies', $menu);
                    $this->assertArrayHasKey('noticePeriod', $menu);
                }
            }
        }
    }

}
