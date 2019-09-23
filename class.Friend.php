<?php

require_once './class.Postcode.php';

class Friend
{

    var $filename = null;
    var $day = null;
    var $time = null;
    var $location = null;
    var $covers = null;

    var $vendors = [];

    public function letsGo()
    {
        if (!empty($_SERVER['argv'][1]) && !empty($_SERVER['argv'][2]) && !empty($_SERVER['argv'][3]) && !empty($_SERVER['argv'][4]) && !empty($_SERVER['argv'][5])) {
            $this->filename = $_SERVER['argv'][1];
            $this->day = $_SERVER['argv'][2];
            $this->time = $_SERVER['argv'][3];
            $this->location = $_SERVER['argv'][4];
            $this->covers = $_SERVER['argv'][5];

            if (!file_exists($this->filename)) {
                exit('Input filename is not valid.');
            }

            if (!Postcode::isValidFormat($this->location)) {
                exit('Postcode is not valid.');
            }

            return $this->getResults();
        } else {
            return $this->askFilename();
        }
    }

    public function getInput($description)
    {
        echo $description;
        $value = trim(fgets(STDIN));

        if (in_array(strtolower(trim($value)), ['q', 'quit', 'exit'])) {
            exit;
        }

        return $value;
    }

    public function askFilename()
    {
        $value = $this->getInput('Input file with the vendors data: ');

        if (empty($value) || !file_exists($value)) {
            echo "Please provide a valid input file. Otherwise, enter quit or q to exit.\n";

            return $this->askFilename();
        }

        $this->filename = $value;

        return $this->askDay();
    }

    public function askDay()
    {
        $value = $this->getInput('Delivery day (dd/mm/yyyy) : ');

        if (empty($value)) {
            echo "Please provide a valid delivery day. Otherwise, enter quit or q to exit.\n";

            return $this->askDay();
        }

        $this->day = $value;

        return $this->askTime();
    }

    public function askTime()
    {
        $value = $this->getInput("Deliver time (hh:mm) : ");

        if (empty($value)) {
            echo "Please provide a valid time. Otherwise, enter quit or q to exit.\n";

            return $this->askTime();
        }

        $this->time = $value;

        return $this->askLocation();
    }

    public function askLocation()
    {
        $value = $this->getInput("Delivery location (e.g. NW43QB) : ");

        if (empty($value) || !Postcode::isValidFormat($value)) {
            echo "Please provide a valid postcode. Otherwise, enter quit or q to exit.\n";

            return $this->askLocation();
        }

        $this->location = $value;

        return $this->askCovers();
    }

    public function askCovers()
    {
        $value = $this->getInput("Number of people to feed: ");

        if (empty($value)) {
            echo "Please provide a valid number. Otherwise, enter quit or q to exit.\n";

            return $this->askCovers();
        }

        $this->covers = $value;

        return $this->getResults();
    }

    public function getResults()
    {
        if (empty($this->vendors)) {
            $this->vendors = $this->getVendors();
        }

        $vendorsByPostcode = array_filter($this->vendors, function ($var) {
            // Safer to use a library for postcode validation
            return (Postcode::getArea($var['postcode']) == Postcode::getArea($this->location));
        });

        foreach ($vendorsByPostcode as $vendorKey => $vendorByPostcode) {
            if ($vendorByPostcode['covers'] < $this->covers) {
                unset($vendorsByPostcode[$vendorKey]);
            } else {
                foreach ($vendorByPostcode['menus'] as $menuKey => $menu) {
                    $noticePeriodInt = preg_replace('/[^0-9]/', '', $menu['noticePeriod']);

                    // If we don't assume the opening and closing hours of the kitchens
                    $t1 = strtotime(str_replace('/', '-', $this->day) . ' ' . $this->time);

                    $t2 = strtotime('-' . $noticePeriodInt . ' hours', $t1);

                    if ($t2 < time()) {
                        unset($vendorsByPostcode[$vendorKey]['menus'][$menuKey]);
                    }
                }
            }
        }

        $vendorsByPostcode = array_filter($vendorsByPostcode, function ($var) {
            return !empty($var['menus']);
        });

        return $vendorsByPostcode;
    }

    public function getVendors()
    {
        $contents = file_get_contents($this->filename);

        $rawVendors = explode("\n\n", $contents);

        $vendors = [];

        foreach ($rawVendors as $rawVendor) {
            $lines = explode("\n", $rawVendor);

            $vendorName = null;
            $vendorPostcode = null;
            $vendorCovers = null;

            $menus = [];

            foreach ($lines as $key => $line) {
                // More validation required here

                if (!empty($line)) {
                    if ($key == 0) {
                        $firstLine = explode(';', $line);

                        $vendorName = $firstLine[0];
                        $vendorPostcode = $firstLine[1];
                        $vendorCovers = $firstLine[2];
                    } else {
                        $menuLine = explode(';', $line);

                        array_push($menus, [
                            'name' => $menuLine[0],
                            'allergies' => $menuLine[1],
                            'noticePeriod' => $menuLine[2],
                        ]);
                    }
                }
            }

            array_push($vendors, [
                'name' => $vendorName,
                'postcode' => $vendorPostcode,
                'covers' => $vendorCovers,
                'menus' => $menus
            ]);
        }

        return $vendors;
    }
}
