<?php

// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Adam Daniel <adaniel1@eesus.jnj.com>                        |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+

/**
 * HTML class for static data
 *
 * @author       Wojciech Gdela <eltehaem@poczta.onet.pl>
 * @access       public
 */
class HTML_QuickForm_static extends HTML_QuickForm_element
{

    /**
     * Display text
     *
     * @var       string
     * @access    private
     */
    var $_text = null;

    /**
     * Class constructor
     *
     * @param string $elementLabel (optional)Label
     * @param string $text         (optional)Display text
     *
     * @access    public
     * @return    void
     */
    public function __construct($elementName = null, $elementLabel = null, $text = null)
    {
        parent::__construct($elementName, $elementLabel);
        $this->_persistantFreeze = false;
        $this->_type = 'static';
        $this->_text = $text;
    }

    /**
     * We override this here because we don't want any values from static elements
     */
    function exportValue(&$submitValues, $assoc = false)
    {
        return null;
    }

    /**
     * Returns the value of field without HTML tags
     *
     * @access    public
     * @return    string
     */
    function getFrozenHtml()
    {
        return $this->toHtml();
    }

    /**
     * Returns the element name
     *
     * @access    public
     * @return    string
     */
    function getName()
    {
        return $this->getAttribute('name');
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event  Name of event
     * @param mixed $arg     event arguments
     * @param object $caller calling object
     *
     * @return    void
     * @throws
     * @since     1.0
     * @access    public
     */
    function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event)
        {
            case 'updateValue':
                // do NOT use submitted values for static elements
                $value = $this->_findValue($caller->_constantValues);
                if (null === $value)
                {
                    $value = $this->_findValue($caller->_defaultValues);
                }
                if (null !== $value)
                {
                    $this->setValue($value);
                }
                break;
            default:
                parent::onQuickFormEvent($event, $arg, $caller);
        }

        return true;
    }

    /**
     * Sets the element name
     *
     * @param string $name Element name
     *
     * @access    public
     * @return    void
     */
    function setName($name)
    {
        $this->updateAttributes(['name' => $name]);
    }

    /**
     * Sets the text
     *
     * @param string $text
     *
     * @access    public
     * @return    void
     */
    function setText($text)
    {
        $this->_text = $text;
    }

    /**
     * Sets the text (uses the standard setValue call to emulate a form element.
     *
     * @param string $text
     *
     * @access    public
     * @return    void
     */
    function setValue($text)
    {
        $this->setText($text);
    }

    /**
     * Returns the static text element in HTML
     *
     * @access    public
     * @return    string
     */
    function toHtml()
    {
        return $this->_getTabs() . $this->_text;
    }

}

