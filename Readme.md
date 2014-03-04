Simple PHP Virtual page handler library
=======================================

This is a simple page handler for PHP for creating virtual pages. I use a similar library
in numerous sites and projects, and rather than keep cutting and pasting, I figured it
deserved its own library.


Usage
-----

```php
    require_once('Page.php');
    
    // Register a page
    \simple_page_handler\Page::create('my/page/', function($page, array $subpages) {
    

        // Your page handling code

    });


    .
    .
    .


    // And in your example page handler endpoint
    try {
        if (!\simple_page_handler\Page::call(\simple_page_handler\Input::get('page'))) {
            \simple_page_handler\Page::set503();
            echo "Something went wrong.";
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }


```

Once you've created your endpoint handler you should use .htaccess to redirect any unhandled
requests to your endpoint. See example_htaccess_dist.

Author
------

* Marcus Povey <marcus@marcus-povey.co.uk>
