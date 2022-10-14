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
 * HTML class for a form element group
 *
 * @author       Adam Daniel <adaniel1@eesus.jnj.com>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 */
class HTML_QuickForm_group extends HTML_QuickForm_element
{

    /**
     * Whether to change elements' names to $groupName[$elementName] or leave them as is
     */
    protected bool $_appendName = true;

    /**
     * @var \HTML_QuickForm_element[]
     */
    protected array $_elements = [];

    protected ?string $_name = '';

    /**
     * Required elements in this group
     *
     * @var string[]
     */
    protected array $_required = [];

    /**
     * @var ?string|?array String to separate elements
     */
    protected $_separator = null;

    /**
     * @param ?string $elementName                (optional)Group name
     * @param ?string $elementLabel               (optional)Group label
     * @param \HTML_QuickForm_element[] $elements (optional)Group elements
     * @param mixed $separator                    (optional)Use a string for one separator,
     *                                            use an array to alternate the separators.
     * @param bool $appendName                    (optional)whether to change elements' names to
     *                                            the form $groupName[$elementName] or leave
     *                                            them as is.
     */
    public function __construct(
        ?string $elementName = null, ?string $elementLabel = null, ?array $elements = null, $separator = null,
        bool $appendName = true
    )
    {
        parent::__construct($elementName, $elementLabel);
        $this->_type = 'group';

        if (isset($elements))
        {
            $this->setElements($elements);
        }

        if (isset($separator))
        {
            $this->_separator = $separator;
        }

        if (isset($appendName))
        {
            $this->_appendName = $appendName;
        }
    }

    /**
     * Creates the group's elements.
     * This should be overriden by child classes that need to create their
     * elements. The method will be called automatically when needed, calling
     * it from the constructor is discouraged as the constructor is usually
     * called _twice_ on element creation, first time with _no_ parameters.
     *
     * @abstract
     */
    public function _createElements()
    {
        // abstract
    }

    /**
     * A wrapper around _createElements()
     * This method calls _createElements() if the group's _elements array
     * is empty. It also performs some updates, e.g. freezes the created
     * elements if the group is already frozen.
     */
    protected function _createElementsIfNotExist()
    {
        if (empty($this->_elements))
        {
            $this->_createElements();

            if ($this->_flagFrozen)
            {
                $this->freeze();
            }
        }
    }

    /**
     * Accepts a renderer
     *
     * @param HTML_QuickForm_Renderer $renderer An HTML_QuickForm_Renderer object
     * @param bool $required                    Whether a group is required
     * @param ?string $error                    An error message associated with a group
     */
    public function accept(HTML_QuickForm_Renderer $renderer, bool $required = false, ?string $error = null)
    {
        $this->_createElementsIfNotExist();
        $renderer->startGroup($this, $required, $error);
        $name = $this->getName();

        foreach (array_keys($this->_elements) as $key)
        {
            $element =& $this->_elements[$key];

            if ($this->_appendName)
            {
                $elementName = $element->getName();

                if (isset($elementName))
                {
                    $element->setName($name . '[' . (strlen($elementName) ? $elementName : $key) . ']');
                }
                else
                {
                    $element->setName($name);
                }
            }

            $required = !$element->isFrozen() && in_array($element->getName(), $this->_required);

            $element->accept($renderer, $required);

            // restore the element's name
            if ($this->_appendName && isset($elementName))
            {
                $element->setName($elementName);
            }
        }
        $renderer->finishGroup($this);
    }

    public function addRequired(string $elementName)
    {
        $this->_required[] = $elementName;
    }

    /**
     * As usual, to get the group's value we access its elements and call
     * their exportValue() methods
     */
    public function exportValue(&$submitValues, $assoc = false)
    {
        $value = null;

        foreach (array_keys($this->_elements) as $key)
        {
            $elementName = $this->_elements[$key]->getName();

            if ($this->_appendName)
            {
                if (is_null($elementName))
                {
                    $this->_elements[$key]->setName($this->getName());
                }
                elseif ('' === $elementName)
                {
                    $this->_elements[$key]->setName($this->getName() . '[' . $key . ']');
                }
                else
                {
                    $this->_elements[$key]->setName($this->getName() . '[' . $elementName . ']');
                }
            }

            $v = $this->_elements[$key]->exportValue($submitValues, $assoc);

            if ($this->_appendName)
            {
                $this->_elements[$key]->setName($elementName);
            }

            if (null !== $v)
            {
                // Make $value an array, we will use it like one
                if (null === $value)
                {
                    $value = [];
                }

                if ($assoc)
                {
                    // just like HTML_QuickForm::exportValues()
                    $value = HTML_QuickForm::arrayMerge($value, $v);
                }
                // just like getValue(), but should work OK every time here
                elseif (is_null($elementName))
                {
                    $value = $v;
                }
                elseif ('' === $elementName)
                {
                    $value[] = $v;
                }
                else
                {
                    $value[$elementName] = $v;
                }
            }
        }

        // do not pass the value through _prepareValue, we took care of this already
        return $value;
    }

    public function freeze()
    {
        parent::freeze();

        foreach (array_keys($this->_elements) as $key)
        {
            $this->_elements[$key]->freeze();
        }
    }

    /**
     * Returns the element name inside the group such as found in the html form
     *
     * @param string|int $index Element name or element index in the group
     *
     * @return string|bool string with element name, false if not found
     */
    public function getElementName($index)
    {
        $this->_createElementsIfNotExist();
        $elementName = false;

        if (is_int($index) && isset($this->_elements[$index]))
        {
            $elementName = $this->_elements[$index]->getName();

            if (isset($elementName) && $elementName == '')
            {
                $elementName = $index;
            }

            if ($this->_appendName)
            {
                if (is_null($elementName))
                {
                    $elementName = $this->getName();
                }
                else
                {
                    $elementName = $this->getName() . '[' . $elementName . ']';
                }
            }
        }
        elseif (is_string($index))
        {
            foreach (array_keys($this->_elements) as $key)
            {
                $elementName = $this->_elements[$key]->getName();

                if ($index == $elementName)
                {
                    if ($this->_appendName)
                    {
                        $elementName = $this->getName() . '[' . $elementName . ']';
                    }
                    break;
                }
                elseif ($this->_appendName && $this->getName() . '[' . $elementName . ']' == $index)
                {
                    break;
                }
            }
        }

        return $elementName;
    }

    /**
     * @return \HTML_QuickForm_element[]
     */
    public function getElements(): array
    {
        $this->_createElementsIfNotExist();

        return $this->_elements;
    }

    /**
     * @param \HTML_QuickForm_element[] $elements Array of elements
     */
    public function setElements(array $elements)
    {
        $this->_elements = array_values($elements);

        if ($this->_flagFrozen)
        {
            $this->freeze();
        }
    }

    public function getFrozenHtml(): string
    {
        $flags = [];
        $this->_createElementsIfNotExist();
        foreach (array_keys($this->_elements) as $key)
        {
            if (false === ($flags[$key] = $this->_elements[$key]->isFrozen()))
            {
                $this->_elements[$key]->freeze();
            }
        }
        $html = $this->toHtml();
        foreach (array_keys($this->_elements) as $key)
        {
            if (!$flags[$key])
            {
                $this->_elements[$key]->unfreeze();
            }
        }

        return $html;
    }

    /**
     * Gets the group type based on its elements
     * Will return 'mixed' if elements contained in the group
     * are of different types.
     */
    public function getGroupType(): string
    {
        $this->_createElementsIfNotExist();
        $type = '';
        $prevType = '';

        foreach (array_keys($this->_elements) as $key)
        {
            $type = $this->_elements[$key]->getType();

            if ($type != $prevType && $prevType != '')
            {
                return 'mixed';
            }

            $prevType = $type;
        }

        return $type;
    }

    public function getName(): ?string
    {
        return $this->_name;
    }

    public function setName(?string $name)
    {
        $this->_name = $name;
    }

    /**
     * @return ?string|?array
     */
    public function getSeparator()
    {
        return $this->_separator;
    }

    public function getValue()
    {
        $value = null;

        foreach (array_keys($this->_elements) as $key)
        {
            $element = $this->_elements[$key];

            if ($element instanceof HTML_QuickForm_radio)
            {
                $v = $element->getChecked() ? $element->getValue() : null;
            }
            elseif ($element instanceof HTML_QuickForm_checkbox)
            {
                $v = $element->getChecked() ? true : null;
            }
            else
            {
                $v = $element->getValue();
            }

            if (null !== $v)
            {
                $elementName = $element->getName();

                if (is_null($elementName))
                {
                    $value = $v;
                }
                else
                {
                    if (!is_array($value))
                    {
                        $value = is_null($value) ? [] : [$value];
                    }

                    if ('' === $elementName)
                    {
                        $value[] = $v;
                    }
                    else
                    {
                        $value[$elementName] = $v;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event  Name of event
     * @param mixed $arg     event arguments
     * @param object $caller calling object
     */
    public function onQuickFormEvent(string $event, $arg, object $caller): bool
    {
        switch ($event)
        {
            case 'updateValue':
                $this->_createElementsIfNotExist();
                foreach (array_keys($this->_elements) as $key)
                {
                    if ($this->_appendName)
                    {
                        $elementName = $this->_elements[$key]->getName();

                        if (is_null($elementName))
                        {
                            $this->_elements[$key]->setName($this->getName());
                        }
                        elseif ('' === $elementName)
                        {
                            $this->_elements[$key]->setName($this->getName() . '[' . $key . ']');
                        }
                        else
                        {
                            $this->_elements[$key]->setName($this->getName() . '[' . $elementName . ']');
                        }
                    }

                    $this->_elements[$key]->onQuickFormEvent('updateValue', $arg, $caller);

                    if ($this->_appendName && isset($elementName))
                    {
                        $this->_elements[$key]->setName($elementName);
                    }
                }
                break;

            default:
                parent::onQuickFormEvent($event, $arg, $caller);
        }

        return true;
    }

    public function setPersistantFreeze(bool $persistant = false)
    {
        parent::setPersistantFreeze($persistant);

        foreach (array_keys($this->_elements) as $key)
        {
            $this->_elements[$key]->setPersistantFreeze($persistant);
        }
    }

    public function setValue($value)
    {
        $this->_createElementsIfNotExist();

        foreach (array_keys($this->_elements) as $key)
        {
            if (!$this->_appendName)
            {
                $v = $this->_elements[$key]->_findValue($value);

                if (null !== $v)
                {
                    $this->_elements[$key]->onQuickFormEvent('setGroupValue', $v, $this);
                }
            }
            else
            {
                $elementName = $this->_elements[$key]->getName();
                $index = strlen($elementName) ? $elementName : $key;

                if (is_array($value))
                {
                    if (isset($value[$index]))
                    {
                        $this->_elements[$key]->onQuickFormEvent('setGroupValue', $value[$index], $this);
                    }
                }
                elseif (isset($value))
                {
                    $this->_elements[$key]->onQuickFormEvent('setGroupValue', $value, $this);
                }
            }
        }
    }

    public function toHtml(): string
    {
        $renderer = new HTML_QuickForm_Renderer_Default();
        $renderer->setElementTemplate('{element}');
        $this->accept($renderer);

        return $renderer->toHtml();
    }

    public function unfreeze()
    {
        parent::unfreeze();

        foreach (array_keys($this->_elements) as $key)
        {
            $this->_elements[$key]->unfreeze();
        }
    }

}
