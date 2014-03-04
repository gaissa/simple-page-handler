<?php

/**
 * @file
 * Virtual page handling methods.
 * 
 * @package core
 * @copyright Marcus Povey 2014
 * @license The MIT License (see LICENCE.txt), other licenses available.
 * @author Marcus Povey <marcus@marcus-povey.co.uk>
 * @link http://www.marcus-povey.co.uk
 */

namespace simple_page_handler {

    /**
     * Virtual page methods.
     */
    class Page {

        /// Pages
        private static $pages;
        
        /// Context
        private static $context;

        /**
         * Create a new virtual page or file and assign a handler to it.
         * 
         * A page handler should be a function defined as :
         * 
         * 	handler($page, array $subpages);
         * 
         * @param type $page Page identifier, e.g. /foo/bar/
         * @param type $handler Handler function or static method
         */
        public static function create($page, callable $handler) {
            if (!isset(self::$pages))
                self::$pages = [];

            self::$pages[$page] = $handler;

            return true;
        }

        /**
         * Handle a virtual page and call its handler.
         *
         * @param string $page The page
         * @return bool
         */
        public static function call($page) {
            // Work out which page
            $pages = explode('/', $page);

            $key = "";
            foreach ($pages as $p) {
                $key .= $p;
                if ((isset(self::$pages[$key])) || (isset(self::$pages["$key/"])))
                    break;

                $key .= "/";
            }

            // Tokenise input variables
            $query = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?') + 1);
            if (isset($query)) {
                parse_str($query, $query_arr);
                if (is_array($query_arr)) {
                    foreach ($query_arr as $name => $val) {
                        Input::set($name, $val);
                    }
                }
            }

            // We have a page registered for this
            if ($key) {
                // Get sub pages below the handler, pass these as subpage variables.
                $pages = substr($page, strlen($key));
                $pages = trim($pages, "/?");
                $pages = explode('/', $pages);

                // Execute handler
                $handler = self::$pages[$key];
                if (!$handler)
                    $handler = self::$pages["$key/"]; // See if this was registered with a trailing slash

                if (!$handler)
                    throw new PageNotFoundException("Page \"$page\" not found");
                
                if (is_callable($handler)) {
                    // Set the context of a page
                    self::setContext($key);

                    if (call_user_func($handler, $key, $pages) !== false)
                        return true;
                }
                else
                    throw new NotCallableException("Handler for page not callable."); // 503 - Key exists, but the handler isn't actually callable.

            }
            else
                throw new PageNotFoundException("Could not find $key"); // 404
            
            return false;
        }

        /**
         * Sometimes pages have a context.
         * 
         * Contexts define an arbitrary grouping for pages. This is useful to set menu options etc.
         * @param string $context The context
         */
        public static function setContext($context) {
            self::$context = $context;
        }

        /**
         * Retrieve the current page context.
         * @return string
         */
        public static function getContext() {
            return self::$context;
        }

        /**
         * Set 404 headers
         */
        public static function set404() {
            header("HTTP/1.1 404 Not Found");
            header("Status: 404");
        }

        /**
         * Forbidden
         */
        public static function set403() {
            header("HTTP/1.1 403 Forbidden");
            header("Status: 403");
        }

        /**
         * Temporarily unavailable
         */
        public static function set503() {
            header("HTTP/1.1 503 Service Unavailable");
            header("Status: 503");
        }

    }

    /**
     * Handle inputs
     */
    class Input {

        /// Cached and sanitised input variables
        protected static $sanitised_input = [];

        /**
         * Retrieve input.
         *
         * @param string $variable The variable to retrieve.
         * @param mixed $default Optional default value.
         * @param callable $filter_hook Optional hook for input filtering, takes one parameter and returns the filtered version. eg function($var){return htmlentities($var);}
         * @return mixed
         */
        public static function get($variable, $default = null, callable $filter_hook = null) {
            // Has input been set already
            if (isset(self::$sanitised_input[$variable])) {
                $var = self::$sanitised_input[$variable];

                return $var;
            }

            if (isset($_REQUEST[$variable])) {
                if (is_array($_REQUEST[$variable]))
                    $var = $_REQUEST[$variable];
                else
                    $var = trim($_REQUEST[$variable]);

                if (is_callable($filter_hook))
                    $var = $filter_hook($var);

                return $var;
            }

            return $default;
        }

        /**
         * Set an input value
         *
         * @param string $variable The name of the variable
         * @param mixed $value its value
         */
        public static function set($variable, $value) {
            if (!isset(self::$sanitised_input))
                self::$sanitised_input = array();

            if (is_array($value)) {
                foreach ($value as $key => $val)
                    $value[$key] = trim($val);

                self::$sanitised_input[trim($variable)] = $value;
            } else
                self::$sanitised_input[trim($variable)] = trim($value);
        }

        /**
         * Get raw POST request data.
         * @param callable $filter_hook Optional hook for input filtering, takes one parameter and returns the filtered version. eg function($var){return htmlentities($var);}
         * @return string|false
         */
        public static function getPOST($filter_hook) {
            global $GLOBALS;

            $post = '';

            if (isset($GLOBALS['HTTP_RAW_POST_DATA']))
                $post = $GLOBALS['HTTP_RAW_POST_DATA'];

            // If always_populate_raw_post_data is switched off, attempt another method.
            if (!$post)
                $post = file_get_contents('php://input');

            // If we have some results then return them
            if ($post) {

                if (is_callable($filter_hook))
                    $post = $filter_hook($post);

                return $post;
            }

            return false;
        }

    }

    /**
     * Page not found!
     */
    class PageNotFoundException extends \Exception {
        public function __construct($message, $code, $previous) {
            parent::__construct($message, $code, $previous);
            Page::set404();
        }
    }
    
    /**
     * Can't call a page handler
     */
    class NotCallableException extends \Exception {
        public function __construct($message, $code, $previous) {
            parent::__construct($message, $code, $previous);
            Page::set503();
        }
    }
    

}