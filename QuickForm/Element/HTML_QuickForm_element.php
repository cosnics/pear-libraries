<?php
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997, 1998, 1999, 2000, 2001 The PHP Group             |
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
 * Base class for form elements
 *
 * @author       Adam Daniel <adaniel1@eesus.jnj.com>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 */
abstract class HTML_QuickForm_element extends HTML_Common
{

    protected bool $_flagFrozen = false;

    protected string $_label = '';

    /**
     * Does the element support persistant data when frozen
     */
    protected bool $_persistantFreeze = false;

    protected string $_type = '';

    /**
     * @param ?array|?string $attributes Associative array of tag attributes or HTML attributes name="value" pairs
     */
    public function __construct(?string $elementName = null, ?string $elementLabel = null, $attributes = null)
    {
        parent::__construct($attributes);

        if (isset($elementName))
        {
            $this->setName($elementName);
        }

        if (isset($elementLabel))
        {
            $this->setLabel($elementLabel);
        }
    }

    /**
     * Tries to find the element value from the values array
     */
    protected function _findValue($values)
    {
        if (empty($values))
        {
            return null;
        }

        $elementName = $this->getName();

        if (isset($values[$elementName]))
        {
            return $values[$elementName];
        }
        elseif (strpos($elementName, '['))
        {
            $myVar = "['" . str_replace([']', '['], ['', "']['"], $elementName) . "']";
            $stringToEvaluate = 'return (isset($values' . $myVar . ')) ? $values' . $myVar . ' : null;';

            return eval($stringToEvaluate);
        }
        else
        {
            return null;
        }
    }

    /**
     * Automatically generates and assigns an 'id' attribute for the element. Currently used to ensure that labels work
     * on radio buttons and checkboxes. Per idea of Alexander Radivanovich.
     */
    protected function _generateId()
    {
        static $idx = 1;

        if (!$this->getAttribute('id'))
        {
            $this->updateAttributes(['id' => 'qf_' . substr(md5(microtime() . $idx ++), 0, 6)]);
        }
    }

    /**
     * Used by getFrozenHtml() to pass the element's value if _persistantFreeze is on
     */
    public function _getPersistantData(): string
    {
        if (!$this->_persistantFreeze)
        {
            return '';
        }
        else
        {
            $id = $this->getAttribute('id');

            if (isset($id))
            {
                // Id of persistant input is different then the actual input.
                $id = ['id' => $id . '_persistant'];
            }
            else
            {
                $id = [];
            }

            return '<input' . $this->_getAttrString(
                    [
                        'type' => 'hidden',
                        'name' => $this->getName(),
                        'value' => $this->getValue()
                    ] + $id
                ) . ' />';
        }
    }

    /**
     * Used by exportValue() to prepare the value for returning
     *
     * @param mixed $value the value found in exportValue()
     * @param bool $assoc  whether to return the value as associative array
     *
     * @return mixed
     */
    protected function _prepareValue($value, bool $assoc)
    {
        if (null === $value)
        {
            return null;
        }
        elseif (!$assoc)
        {
            return $value;
        }
        else
        {
            $name = $this->getName();

            if (!strpos($name, '['))
            {
                return [$name => $value];
            }
            else
            {
                $valueAry = [];
                $myIndex = "['" . str_replace([']', '['], ['', "']['"], $name) . "']";
                $stringToEvaluate = '$valueAry' . $myIndex . ' = $value;';
                eval($stringToEvaluate);

                return $valueAry;
            }
        }
    }

    /**
     * Accepts a renderer
     *
     * @param HTML_QuickForm_Renderer $renderer An HTML_QuickForm_Renderer object
     * @param bool $required                    Whether an element is required
     * @param ?string $error                    An error message associated with an element
     */
    public function accept(HTML_QuickForm_Renderer $renderer, bool $required = false, ?string $error = null)
    {
        $renderer->renderElement($this, $required, $error);
    }

    /**
     * Returns a 'safe' element's value
     *
     * @param array $submitValues array of submitted values to search
     * @param bool $assoc         whether to return the value as associative array
     */
    public function exportValue(array &$submitValues, bool $assoc = false)
    {
        $value = $this->_findValue($submitValues);
        if (null === $value)
        {
            $value = $this->getValue();
        }

        return $this->_prepareValue($value, $assoc);
    }

    /**
     * Freeze the element so that only its value is returned
     */
    public function freeze()
    {
        $this->_flagFrozen = true;
    }

    /**
     * Returns the value of field without HTML tags
     */
    public function getFrozenHtml(): string
    {
        $value = $this->getValue();

        return ('' != $value ? htmlspecialchars($value) : '&nbsp;') . $this->_getPersistantData();
    }

    public function getLabel(): string
    {
        return $this->_label;
    }

    public function setLabel(string $label)
    {
        $this->_label = $label;
    }

    abstract public function getName(): ?string;

    public function getType(): string
    {
        return $this->_type;
    }

    abstract public function getValue();

    public function isFrozen(): bool
    {
        return $this->_flagFrozen;
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event          Name of event
     * @param mixed $arg             event arguments
     * @param ?HTML_QuickForm $caller calling object
     */
    public function onQuickFormEvent(string $event, $arg, ?HTML_QuickForm $caller = null): bool
    {
        switch ($event)
        {
            case 'createElement':
                $class = new ReflectionClass($this);
                $parameters = $class->getConstructor()->getParameters();

                foreach ($parameters as $key => $parameter)
                {
                    $arg[$key] = is_null($arg[$key]) ?
                        ($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null) :
                        $arg[$key];
                }

                static::__construct($arg[0], $arg[1], $arg[2], $arg[3], $arg[4]);
                break;
            case 'addElement':
                $this->onQuickFormEvent('createElement', $arg, $caller);
                $this->onQuickFormEvent('updateValue', null, $caller);
                break;
            case 'updateValue':
                // constant values override both default and submitted ones
                // default values are overriden by submitted
                $value = $this->_findValue($caller->getConstantValues());

                if (null === $value)
                {
                    $value = $this->_findValue($caller->getSubmitValues());
                    if (null === $value)
                    {
                        $value = $this->_findValue($caller->getDefaultValues());
                    }
                }

                if (null !== $value)
                {
                    $this->setValue($value);
                }
                break;
            case 'setGroupValue':
                $this->setValue($arg);
        }

        return true;
    }

    abstract public function setName(string $name);

    /**
     * Sets wether an element value should be kept in an hidden field
     * when the element is frozen or not
     */
    public function setPersistantFreeze(bool $persistant = false)
    {
        $this->_persistantFreeze = $persistant;
    }

    abstract public function setValue($value);

    /**
     * Unfreezes the element so that it becomes editable
     */
    public function unfreeze()
    {
        $this->_flagFrozen = false;
    }

}