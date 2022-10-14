<?php

// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
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
// | Authors: Alexey Borzov <avb@php.net>                                 |
// +----------------------------------------------------------------------+

/**
 * Class for HTML 4.0 <button> element
 *
 * @author  Alexey Borzov <avb@php.net>
 */
class HTML_QuickForm_xbutton extends HTML_QuickForm_element
{
    /**
     * Contents of the <button> tag
     *
     * @var      string
     */
    protected $_content;

    /**
     * Class constructor
     *
     * @param string  Button name
     * @param string  Button content (HTML to add between <button></button> tags)
     * @param mixed   Either a typical HTML attribute string or an associative array
     *
     */
    public function __construct($elementName = null, $elementContent = null, $attributes = null)
    {
        parent::__construct($elementName, null, $attributes);
        $this->setContent($elementContent);
        $this->setPersistantFreeze(false);
        $this->_type = 'xbutton';
    }

    /**
     * Returns a 'safe' element's value
     * The value is only returned if the button's type is "submit" and if this
     * particlular button was clicked
     */
    public function exportValue(array &$submitValues, bool $assoc = false)
    {
        if ('submit' == $this->getAttribute('type'))
        {
            return $this->_prepareValue($this->_findValue($submitValues), $assoc);
        }
        else
        {
            return null;
        }
    }

    public function freeze()
    {
        return false;
    }

    public function getFrozenHtml(): string
    {
        return $this->toHtml();
    }

    public function getName(): string
    {
        return $this->getAttribute('name');
    }

    public function getValue()
    {
        return $this->getAttribute('value');
    }

    public function onQuickFormEvent(string $event, $arg, object $caller): bool
    {
        if ('updateValue' != $event)
        {
            return parent::onQuickFormEvent($event, $arg, $caller);
        }
        else
        {
            $value = $this->_findValue($caller->_constantValues);
            if (null === $value)
            {
                $value = $this->_findValue($caller->_defaultValues);
            }
            if (null !== $value)
            {
                $this->setValue($value);
            }
        }

        return true;
    }

    /**
     * Sets the contents of the button element
     *
     * @param string  Button content (HTML to add between <button></button> tags)
     */
    public function setContent($content)
    {
        $this->_content = $content;
    }

    public function setName($name)
    {
        $this->updateAttributes([
            'name' => $name
        ]);
    }

    public function setValue($value)
    {
        $this->updateAttributes([
            'value' => $value
        ]);
    }

    public function toHtml(): string
    {
        return '<button' . $this->getAttributes(true) . '>' . $this->_content . '</button>';
    }
}


