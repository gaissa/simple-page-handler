<?php

/**
 * @file
 * 
 * Example handler for virtual pages.
 * 
 * Virtual pages are pages which don't physically exist, rather they are 
 * provided virtually and dynamically by classes and library functions in
 * the engine.
 * 
 * 
 * @package Simple Page Handler
 * @copyright Marcus Povey 2014
 * @license The MIT License (see LICENCE.txt), other licenses available.
 * @author Marcus Povey <marcus@marcus-povey.co.uk>
 * @link http://www.marcus-povey.co.uk
 */
require_once(dirname(__FILE__) . '/Page.php');

\simple_page_handler\Page::create('foo/bar', function($page, array $subpages) {
    echo "Foo bar page handled!";
});

try {
    if (!\simple_page_handler\Page::call(\simple_page_handler\Input::get('page'))) {
        \simple_page_handler\Page::set503();
        echo "Something went wrong.";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}