<?php
/**
 * @package    mahara
 * @subpackage test/behat
 * @author     Son Nguyen
 * @author     Catalyst IT Limited <mahara@catalyst.net.nz>
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 * @copyright  portions from Moodle Behat, 2013 David Monllaó
 *
 */

/**
 * Behat base class for mahara step definitions.
 *
 * All mahara step definitions should be extended from this class
 */

use Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException,
    Behat\Mink\Element\NodeElement as NodeElement,
    Behat\Mink\Selector\Xpath\Escaper;
use WebDriver\Exception\NoSuchWindow;
use WebDriver\Exception\UnknownError;

/**
 * Base class
 *
 * @method NodeElement find_field(string $locator) Finds a form element
 * @method NodeElement find_button(string $locator) Finds a form input submit element or a button
 * @method NodeElement find_link(string $locator) Finds a link on a page
 * @method NodeElement find_file(string $locator) Finds a forum input file element
 *
 */
class BehatBase extends Behat\MinkExtension\Context\RawMinkContext {

    /**
     * Small timeout.
     *
     * A reduced timeout for cases where self::TIMEOUT is too much
     * and a simple $this->getSession()->getPage()->find() could not
     * be enough.
     */
    const REDUCED_TIMEOUT = 2;

    /**
     * The timeout for each Behat step (load page, wait for an element to load...).
     */
    const TIMEOUT = 3;

    /**
     * And extended timeout for specific cases.
     */
    const EXTENDED_TIMEOUT = 10;

    /**
     * Timeout in milliseconds for the getSession->wait() function when 2nd option is false
     */
    const WAIT_TIMEOUT = 250;

    /**
     * Number of retries to wait for the editor to be ready.
     */
    const WAIT_FOR_EDITOR_RETRIES = 10;

    /**
     * The JS code to check that the page is ready.
     */
    const PAGE_READY_JS = 'window.isMaharaPageReady()';

    /**
     * @var Escaper
     */
    public $escaper;

    /**
     * Instantiates the base class
     */
    public function __construct() {
        $this->escaper = new Escaper();
    }

    /**
     * Locates url, based on provided path.
     * Override to provide custom routing mechanism.
     *
     * @see Behat\MinkExtension\Context\MinkContext
     * @param string $path
     * @return string
     */
    protected function locate_path($path) {
        $starturl = rtrim($this->getMinkParameter('base_url'), '/') . '/';
        return 0 !== strpos($path, 'http') ? $starturl . ltrim($path, '/') : $path;
    }

    /**
     * Returns the first matching element.
     *
     * @link http://mink.behat.org/#traverse-the-page-selectors
     * @param string $selector The selector type (css, xpath, named...)
     * @param mixed $locator It depends on the $selector, can be the xpath, a name, a css locator...
     * @param Exception $exception Otherwise we throw exception with generic info
     * @param NodeElement $node Spins around certain DOM node instead of the whole page
     * @return NodeElement
     */
    protected function find($selector, $locator, $exception = false, $node = false) {
        $returnitems = [];
        // Returns the first match.
        if ($items = $this->find_all($selector, $locator, $exception, $node)) {
            foreach ($items as $key => $value) {
                if ($value->getTagName() != 'script') {
                    $returnitems[] = $value;
                }
            }
        }
        return count($returnitems) ? reset($returnitems) : null;
    }

    /**
     * Returns all matching elements.
     *
     * Adapter to Behat\Mink\Element\Element::findAll() using the spin() method.
     *
     * @link http://mink.behat.org/#traverse-the-page-selectors
     * @param string $selector The selector type (css, xpath, named...)
     * @param mixed $locator It depends on the $selector, can be the xpath, a name, a css locator...
     * @param Exception $exception Otherwise we throw expcetion with generic info
     * @param NodeElement $node Spins around certain DOM node instead of the whole page
     * @return array NodeElements list
     */
    protected function find_all($selector, $locator, $exception = false, $node = false) {

        // Fix deprecated selector
        if ($selector === 'named') {
            $selector = $selector . '_partial';
        }

        // Generic info.
        if (!$exception) {

            // With named selectors we can be more specific.
            if ($selector === 'named_partial') {
                $exceptiontype = $locator[0];
                $exceptionlocator = $locator[1];

                // If we are in a @javascript session all contents would be displayed as HTML characters.
                if ($this->running_javascript()) {
                    $locator[1] = html_entity_decode($locator[1], ENT_NOQUOTES);
                }

            }
            else {
                $exceptiontype = $selector;
                $exceptionlocator = $locator;
            }

            $exception = new ElementNotFoundException($this->getSession(), $exceptiontype, null, $exceptionlocator);
        }

        $params = array('selector' => $selector, 'locator' => $locator);

        // Pushing $node if required.
        if ($node) {
            $params['node'] = $node;
        }

        // Waits for the node to appear if it exists, otherwise will timeout and throw the provided exception.
        return $this->spin(
            function($context, $args) {

                // If no DOM node provided look in all the page.
                if (empty($args['node'])) {
                    return $context->getSession()->getPage()->findAll($args['selector'], $args['locator']);
                }

                // For nodes contained in other nodes we can not use the basic named selectors
                // as they include unions and they would look for matches in the DOM root.
                $elementxpath = $context->getSession()->getSelectorsHandler()->selectorToXpath($args['selector'], $args['locator']);

                // Split the xpath in unions and prefix them with the container xpath.
                $unions = explode('|', $elementxpath);
                foreach ($unions as &$union) {
                    $union = trim($union);

                    // We are in the container node.
                    if (strpos($union, '.') === 0) {
                        $union = substr($union, 1);
                    }
                    else if (strpos($union, '/') !== 0) {
                        // Adding the path separator in case it is not there.
                        $union = '/' . $union;
                    }
                    $union = $args['node']->getXpath() . $union;
                }

                // We can not use usual Element::find() as it prefixes with DOM root.
                return $context->getSession()->getDriver()->find(implode('|', $unions));
            },
            $params,
            self::TIMEOUT,
            $exception
        );
    }

    /**
     * Finds DOM nodes in the page using named selectors.
     *
     * The point of using this method instead of Mink ones is the spin
     * method of BehatBase::find() that looks for the element until it
     * is available or it timeouts, this avoids the false failures received
     * when selenium tries to execute commands on elements that are not
     * ready to be used.
     *
     * All steps that requires elements to be available before interact with
     * them should use one of the find* methods.
     *
     * The methods calls requires a {'find_' . $elementtype}($locator)
     * format, like find_link($locator), find_select($locator),
     * find_button($locator)...
     *
     * @link http://mink.behat.org/#named-selectors
     * @throws Exception
     * @param string $name The name of the called method
     * @param mixed $arguments
     * @return NodeElement
     */
    public function __call($name, $arguments) {

        if (substr($name, 0, 5) !== 'find_') {
            throw new Exception('The "' . $name . '" method does not exist');
        }

        // Only the named selector identifier.
        $cleanname = substr($name, 5);

        // All named selectors shares the interface.
        if (count($arguments) !== 1) {
            throw new Exception('The "' . $cleanname . '" named selector needs the locator as it\'s single argument');
        }

        // Redirecting execution to the find method with the specified selector.
        // It will detect if it's pointing to an unexisting named selector.
        return $this->find('named',
            array(
                $cleanname,
                $this->escaper->escapeLiteral($arguments[0])
            )
        );
    }

    /**
     * Escapes the double quote characters.
     *
     * @param string $string
     * @return string
     */
    public function escapeDoubleQuotes($string) {
        return str_replace('"', '\"', $string);
    }

    /**
     * Unescapes the double quote characters.
     *
     * @param string $string
     * @return string
     */
    public function unescapeDoubleQuotes($string) {
        return str_replace('\"', '"', $string);
    }

    /**
     * Executes the passed closure until returns true or time outs.
     *
     * In most cases the document.readyState === 'complete' will be enough, but sometimes JS
     * requires more time to be completely loaded or an element to be visible or whatever is required to
     * perform some action on an element; this method receives a closure which should contain the
     * required statements to ensure the step definition actions and assertions have all their needs
     * satisfied and executes it until they are satisfied or it timeouts. Redirects the return of the
     * closure to the caller.
     *
     * The closures requirements to work well with this spin method are:
     * - Must return false, null or '' if something goes wrong
     * - Must return something != false if finishes as expected, this will be the (mixed) value
     * returned by spin()
     *
     * The arguments of the closure are mixed, use $args depending on your needs.
     *
     * You can provide an exception to give more accurate feedback to tests writers, otherwise the
     * closure exception will be used, but you must provide an exception if the closure does not throws
     * an exception.
     *
     * @throws Exception If it timeouts without receiving something != false from the closure
     * @param Function|array|string $lambda The function to execute or an array passed to call_user_func (maps to a class method)
     * @param mixed $args Arguments to pass to the closure
     * @param int $timeout Timeout in seconds
     * @param Exception $exception The exception to throw in case it time outs.
     * @param bool $microsleep If set to true it'll sleep micro seconds rather than seconds.
     * @return mixed The value returned by the closure
     */
    protected function spin($lambda, $args = false, $timeout = false, $exception = false, $microsleep = false) {

        // Using default timeout which is pretty high.
        if (!$timeout) {
            $timeout = self::TIMEOUT;
        }
        if ($microsleep) {
            // Will sleep 1/10th of a second by default for self::TIMEOUT seconds.
            $loops = $timeout * 10;
        }
        else {
            // Will sleep for self::TIMEOUT seconds.
            $loops = $timeout;
        }

        for ($i = 0; $i < $loops; $i++) {
            // We catch the exception thrown by the step definition to execute it again.
            try {
                // We don't check with !== because most of the time closures will return
                // direct Behat methods returns and we are not sure it will be always (bool)false
                // if it just runs the behat method without returning anything $return == null.
                if ($return = call_user_func($lambda, $this, $args)) {
                    return $return;
                }
            }
            catch (Exception $e) {
                // We would use the first closure exception if no exception has been provided.
                if (!$exception) {
                    $exception = $e;
                }
                // We wait until no exception is thrown or timeout expires.
                continue;
            }

            if ($microsleep) {
                usleep(100000);
            }
            else {
                sleep(1);
            }
        }

        // Using Exception as is a development issue if no exception has been provided.
        if (!$exception) {
            $exception = new Exception('spin method requires an exception if the callback does not throw an exception');
        }

        // Throwing exception to the user.
        throw $exception;
    }

    /**
     * Gets a NodeElement based on the locator and selector type received as argument from steps definitions.
     *
     * Use BehatBase::get_text_selector_node() for text-based selectors.
     *
     * @throws ElementNotFoundException Thrown by BehatBase::find
     * @param string $selectortype
     * @param string $element
     * @return NodeElement
     */
    protected function get_selected_node($selectortype, $element) {

        // Getting Mink selector and locator.
        list($selector, $locator) = $this->transform_selector($selectortype, $element);

        // Returns the NodeElement.
        return $this->find($selector, $locator);
    }

    /**
     * Gets a NodeElement based on the locator and selector type received as argument from steps definitions.
     *
     * @throws ElementNotFoundException Thrown by BehatBase::find
     * @param string $selectortype
     * @param string $element
     * @return NodeElement
     */
    protected function get_text_selector_node($selectortype, $element) {

        // Getting Mink selector and locator.
        list($selector, $locator) = $this->transform_text_selector($selectortype, $element);

        // Returns the NodeElement.
        return $this->find($selector, $locator);
    }

    /**
     * Gets the requested element inside the specified container.
     *
     * @throws ElementNotFoundException Thrown by BehatBase::find
     * @param mixed $selectortype The element selector type.
     * @param mixed $element The element locator.
     * @param mixed $containerselectortype The container selector type.
     * @param mixed $containerelement The container locator.
     * @return NodeElement
     */
    protected function get_node_in_container($selectortype, $element, $containerselectortype, $containerelement) {

        // Gets the container, it will always be text based.
        $containernode = $this->get_text_selector_node($containerselectortype, $containerelement);

        list($selector, $locator) = $this->transform_selector($selectortype, $element);

        // Specific exception giving info about where can't we find the element.
        $locatorexceptionmsg = "'$element' in the '{$containerelement}' '{$containerselectortype}'";
        $exception = new ElementNotFoundException($this->getSession(), $selectortype, null, $locatorexceptionmsg);

        // Looks for the requested node inside the container node.
        return $this->find($selector, $locator, $exception, $containernode);
    }

    /**
     * Transforms from step definition's argument style to Mink format.
     *
     * Mink has 3 different selectors css, xpath and named, where named
     * selectors includes link, button, field... to simplify and group multiple
     * steps in one we use the same interface, considering all link, buttons...
     * at the same level as css selectors and xpath; this method makes the
     * conversion from the arguments received by the steps to the selectors and locators
     * required to interact with Mink.
     *
     * @throws ExpectationException
     * @param string $selectortype It can be css, xpath or any of the named selectors.
     * @param string $element The locator (or string) we are looking for.
     * @return array Contains the selector and the locator expected by Mink.
     */
    protected function transform_selector($selectortype, $element) {

        // Here we don't know if an allowed text selector is being used.
        $selectors = BehatSelectors::get_allowed_selectors();
        if (!isset($selectors[$selectortype])) {
            throw new ExpectationException('The "' . $selectortype . '" selector type does not exist', $this->getSession());
        }

        return BehatSelectors::get_behat_selector($selectortype, $element, $this->getSession());
    }

    /**
     * Transforms from step definition's argument style to Mink format.
     *
     * Delegates all the process to BehatBase::transform_selector() checking
     * the provided $selectortype.
     *
     * @throws ExpectationException
     * @param string $selectortype It can be css, xpath or any of the named selectors.
     * @param string $element The locator (or string) we are looking for.
     * @return array Contains the selector and the locator expected by Mink.
     */
    protected function transform_text_selector($selectortype, $element) {

        $selectors = BehatSelectors::get_allowed_text_selectors();
        if (empty($selectors[$selectortype])) {
            throw new ExpectationException('The "' . $selectortype . '" selector can not be used to select text nodes', $this->getSession());
        }

        return $this->transform_selector($selectortype, $element);
    }

    /**
     * Returns whether the scenario is running in a browser that can run Javascript or not.
     *
     * @return boolean
     */
    protected function running_javascript() {
        return get_class($this->getSession()->getDriver()) !== 'Behat\Mink\Driver\GoutteDriver';
    }

    /**
     * Spins around an element until it exists
     *
     * @throws ExpectationException
     * @param string $element
     * @param string $selectortype
     * @param integer $timeout (optional, seconds)
     * @return void
     */
    protected function ensure_element_exists($element, $selectortype, $timeout=self::EXTENDED_TIMEOUT) {

        // Getting the behat selector & locator.
        list($selector, $locator) = $this->transform_selector($selectortype, $element);

        // Exception if it timesout and the element is still there.
        $msg = 'The "' . $element . '" element does not exist and should exist';
        $exception = new ExpectationException($msg, $this->getSession());

        // It will stop spinning once the find() method returns true.
        $this->spin(
            function($context, $args) {
                // We don't use BehatBase::find as it is already spinning.
                if ($context->getSession()->getPage()->find($args['selector'], $args['locator'])) {
                    return true;
                }
                return false;
            },
            array('selector' => $selector, 'locator' => $locator),
            $timeout,
            $exception,
            true
        );

    }

    /**
     * Spins until the element does not exist
     *
     * @throws ExpectationException
     * @param string $element
     * @param string $selectortype
     * @return void
     */
    protected function ensure_element_does_not_exist($element, $selectortype) {

        // Getting the behat selector & locator.
        list($selector, $locator) = $this->transform_selector($selectortype, $element);

        // Exception if it timesout and the element is still there.
        $msg = 'The "' . $element . '" element exists and should not exist';
        $exception = new ExpectationException($msg, $this->getSession());

        // It will stop spinning once the find() method returns false.
        $this->spin(
            function($context, $args) {
                // We don't use BehatBase::find() as we are already spinning.
                if (!$context->getSession()->getPage()->find($args['selector'], $args['locator'])) {
                    return true;
                }
                return false;
            },
            array('selector' => $selector, 'locator' => $locator),
            self::EXTENDED_TIMEOUT,
            $exception,
            true
        );
    }

    /**
     * Ensures that the provided node is visible and we can interact with it.
     *
     * @throws ExpectationException
     * @param NodeElement $node
     * @return void Throws an exception if it times out without the element being visible
     */
    protected function ensure_node_is_visible($node) {

        if (!$this->running_javascript()) {
            return;
        }

        // Exception if it timesout and the element is still there.
        $msg = 'The "' . $node->getXPath() . '" xpath node is not visible and it should be visible';
        $exception = new ExpectationException($msg, $this->getSession());

        // It will stop spinning once the isVisible() method returns true.
        $this->spin(
            function($context, $args) {
                if ($args->isVisible()) {
                    return true;
                }
                return false;
            },
            $node,
            self::EXTENDED_TIMEOUT,
            $exception,
            true
        );
    }

    /**
     * Helper function to make sure the node to be clicked is within the viewport.
     *
     * @throws ExpectationException
     * @param NodeElement $node
     * @param string $text
     * @param string $parenttype
     * @param string $parentelement
     * @return void Throws an exception if it times out without the element being visible in viewport
     */
    public function ensure_node_is_in_viewport($node, $text, $parenttype=null, $parentelement=null) {
        $element = $node->getTagName();
        $hasvalue = $node->getValue();
        $hastitle = $node->getAttribute('title');
        $textliteral = $this->escaper->escapeLiteral($text);
        $textliteraljs = $this->escapeDoubleQuotes($textliteral);
        if ($element == 'input') {
            if ($node->getAttribute('type') == 'radio' || $node->getAttribute('type') == 'checkbox') {
                $jquerystr = $element . " + label:contains($textliteraljs)";
            }
            else {
                $jquerystr = $element . '[value=' . $textliteraljs . ']';
            }
        }
        else if ($hasvalue) {
            $jquerystr = $element . '[value=' . $textliteraljs . '], ' . "$element:contains($textliteraljs)";
        }
        else if ($hastitle) {
            $jquerystr = $element . '[title=' . $textliteraljs . '], ' . "$element:contains($textliteraljs)";
        }
        else {
            $jquerystr = "$element:contains($textliteraljs)";
        }
        if ($parenttype == 'css_element') {
            // This allows for better targeting of groups of elements.
            // We need to split $parentelement and $jquerystr by comma and then join them back with a space.
            $parentelements = explode(',', $parentelement);
            $jquerystrs = explode(',', $jquerystr);
            // For each $parentelements, we need to add each $jquerystrs to it.
            $newjquerystrs = array();
            foreach ($parentelements as $parent) {
                foreach ($jquerystrs as $str) {
                    $newjquerystrs[] = trim($parent) . ' ' . trim($str);
                }
            }
            $jquerystr = implode(',', $newjquerystrs);
        }
        // Need to single escape the double escaped doublequotes
        $jquerystr = str_replace("\\\\\"", "\\\"", $jquerystr);
        $function = <<<JS
          (function() {
              var elem = jQuery("$jquerystr")[0];
              // We do not appear to be able to use elem.scrollIntoView() here.
              // Even with setting block and inline to center.
              var elementRect = elem.getBoundingClientRect();
              var absoluteElementTop = elementRect.top + window.pageYOffset;
              var middle = absoluteElementTop - (window.innerHeight / 2);
              window.scrollTo(0, middle);
              return 1;
          })();
JS;
        try {
            $this->getSession()->wait(5000, $function);
        }
        catch(Exception $e) {
            throw new \Exception("scrollIntoView failed for clicking");
        }
        // Add small delay to allow scrolling to finish
        $this->getSession()->wait(self::WAIT_TIMEOUT, false);
    }

    /**
     * Ensures that the provided element is visible and we can interact with it.
     *
     * Returns the node in case other actions are interested in using it.
     *
     * @throws ExpectationException
     * @param string $element
     * @param string $selectortype
     * @return NodeElement Throws an exception if it times out without being visible
     */
    protected function ensure_element_is_visible($element, $selectortype) {

        if (!$this->running_javascript()) {
            return;
        }

        $node = $this->get_selected_node($selectortype, $element);
        $this->ensure_node_is_visible($node);

        return $node;
    }

    /**
     * Ensures that all the page's editors are loaded.
     *
     * This method is expensive as it waits for .mceEditor CSS
     * so use with caution and only where there will be editors.
     *
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @return void
     */
    protected function ensure_editors_are_loaded() {

        if (!$this->running_javascript()) {
            return;
        }

        // If there are no editors we don't need to wait.
        try {
            $this->find('css', '.mceEditor');
        }
        catch (ElementNotFoundException $e) {
            return;
        }

        // Exception if it timesout and the element is not appearing.
        $msg = 'The editors are not completely loaded';
        $exception = new ExpectationException($msg, $this->getSession());

        // Here we know that there are .mceEditor editors in the page and we will
        // probably need to interact with them, if we use tinyMCE JS var before
        // it exists it will throw an exception and we want to catch it until all
        // the page's editors are ready to interact with them.
        $this->spin(
            function($context) {

                // It may return 0 if tinyMCE is loaded but not the instances, so we just loop again.
                $neditors = $context->getSession()->evaluateScript('return tinymce.editors.length;');
                if ($neditors == 0) {
                    return false;
                }

                // It may be there but not ready.
                $iframeready = $context->getSession()->evaluateScript('
                    var readyeditors = new Array;
                    for (editorid in tinymce.editors) {
                        if (tinymce.editors[editorid].getDoc().readyState === "complete") {
                            readyeditors[editorid] = editorid;
                        }
                    }
                    if (tinymce.editors.length === readyeditors.length) {
                        return "complete";
                    }
                    return "";
                ');

                // Now we know that the editors are there.
                if ($iframeready) {
                    return true;
                }

                // Loop again if it is not ready.
                return false;
            },
            false,
            self::EXTENDED_TIMEOUT,
            $exception,
            true
        );
    }

    /**
     * Change browser window size.
     *   - small: 640x480
     *   - medium: 1024x768
     *   - large: 2560x1600
     *
     * @param string $windowsize size of window.
     * @throws ExpectationException
     */
    protected function resize_window($windowsize) {
        // Non JS don't support resize window.
        if (!$this->running_javascript()) {
            return;
        }

        switch ($windowsize) {
            case "small":
                $width = 640;
                $height = 480;
                break;
            case "medium":
                $width = 1024;
                $height = 768;
                break;
            case "default":
                // Update this to match the value in run_xvfb in mahara_behat.sh.
                $width = 1280;
                $height = 1024;
                break;
            case "large":
                $width = 2560;
                $height = 1600;
                break;
            default:
                preg_match('/^(\d+x\d+)$/', $windowsize, $matches);
                if (empty($matches) || (count($matches) != 2)) {
                    throw new ExpectationException("Invalid screen size, can't resize", $this->getSession());
                }
                $size = explode('x', $windowsize);
                $width = (int) $size[0];
                $height = (int) $size[1];
        }
        $this->getSession()->getDriver()->resizeWindow($width, $height);
    }

    /**
     * Waits for all the JS to be loaded.
     *
     * @throws Exception
     * @throws NoSuchWindow
     * @throws UnknownError
     * @return bool True or false depending whether all the JS is loaded or not.
     */
    protected function wait_for_pending_js() {

        for ($i = 0; $i < self::EXTENDED_TIMEOUT * 10; $i++) {
            $ready = false;
            try {
                $jscode = 'return ' . self::PAGE_READY_JS;
                $ready = $this->getSession()->evaluateScript($jscode);
            }
            catch (NoSuchWindow $nsw) {
                // We catch an exception here, in case we just closed the window we were interacting with.
                // No javascript is running if there is no window right?
                $ready = true;
            }
            catch (UnknownError $e) {
                $ready = true;
            }

            // If there are no pending JS we stop waiting.
            if ($ready) {
                return true;
            }

            // 0.1 seconds.
            usleep(100000);
        }
        return false;
    }

    /**
   * @param string $element - thing to look for
   * @param string $selectortype - e.g. css/xpath
   */
   protected function i_click_on_element($element, $selectortype='css_element') {

       // Getting Mink selector and locator.
       list($selector, $locator) = $this->transform_selector($selectortype, $element);
       // Will throw an ElementNotFoundException if it does not exist.
       $this->find($selector, $locator);
       $page = $this->getSession()->getPage();
       $element = $page->find($selector , $locator);

       if (empty($element)) {
           throw new Exception("No html element found for the selector ('$element')");
       }
       $this->ensure_node_is_visible($element);
       $this->getSession()->wait(self::WAIT_TIMEOUT, false);
       $element->click();
   }


}
