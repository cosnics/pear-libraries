<?php

// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Herim Vasquez <vasquezh@iro.umontreal.ca>                   |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// |          Alexey Borzov <avb@php.net>
// +----------------------------------------------------------------------+

/**
 * Class to dynamically create two or more HTML Select elements
 * The first select changes the content of the second select and so on.
 * This element is considered as a group. Selects will be named
 * groupName[0], groupName[1], groupName[2]...
 *
 * @author       Herim Vasquez <vasquezh@iro.umontreal.ca>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 */
class HTML_QuickForm_hierselect extends HTML_QuickForm_group
{

    /**
     * The javascript used to set and change the options
     */
    protected string $_js = '';

    /**
     * Number of select elements on this group
     */
    protected int $_nbElements = 0;

    /**
     * Options for all the select elements
     * Format is a bit more complex as we need to know which options
     * are related to the ones in the previous select:
     * Ex:
     * // first select
     * $select1[0] = 'Pop';
     * $select1[1] = 'Classical';
     * $select1[2] = 'Funeral doom';
     * // second select
     * $select2[0][0] = 'Red Hot Chil Peppers';
     * $select2[0][1] = 'The Pixies';
     * $select2[1][0] = 'Wagner';
     * $select2[1][1] = 'Strauss';
     * $select2[2][0] = 'Pantheist';
     * $select2[2][1] = 'Skepticism';
     * // If only need two selects
     * //     - and using the depracated functions
     * $sel =& $form->addElement('hierselect', 'cds', 'Choose CD:');
     * $sel->setSecOptions($select2);
     * //     - and using the new setOptions function
     * $sel =& $form->addElement('hierselect', 'cds', 'Choose CD:');
     * $sel->setOptions(array($select1, $select2));
     * // If you have a third select with prices for the cds
     * $select3[0][0][0] = '15.00$';
     * $select3[0][0][1] = '17.00$';
     * etc
     * // You can now use
     * $sel =& $form->addElement('hierselect', 'cds', 'Choose CD:');
     * $sel->setOptions(array($select1, $select2, $select3));
     */
    protected array $_options = [];

    /**
     * @param mixed $attributes             (optional)Either a typical HTML attribute string
     *                                      or an associative array. Date format is passed along the attributes.
     * @param mixed $separator              (optional)Use a string for one separator,
     *                                      use an array to alternate the separators.
     */
    public function __construct($elementName = null, $elementLabel = null, $attributes = null, $separator = null)
    {
        parent::__construct($elementName, $elementLabel, null, $separator, true, $attributes);

        $this->_persistantFreeze = true;
        $this->_type = 'hierselect';
    }

    /**
     * Converts PHP array to its Javascript analog
     *
     * @param mixed $array       PHP array to convert
     * @param bool $assoc        Generate Javascript object literal (default, works like PHP's associative array) or
     *                           array literal
     *
     * @return string Javascript representation of the value
     */
    protected function _convertArrayToJavascript($array, bool $assoc = true): string
    {
        if (!is_array($array))
        {
            return $this->_convertScalarToJavascript($array);
        }
        else
        {
            $items = [];

            foreach ($array as $key => $val)
            {
                $item = $assoc ? "'" . $this->_escapeString($key) . "': " : '';

                if (is_array($val))
                {
                    $item .= $this->_convertArrayToJavascript($val, $assoc);
                }
                else
                {
                    $item .= $this->_convertScalarToJavascript($val);
                }

                $items[] = $item;
            }
        }
        $js = implode(', ', $items);

        return $assoc ? '{ ' . $js . ' }' : '[' . $js . ']';
    }

    /**
     * Converts PHP's scalar value to its Javascript analog
     *
     * @param mixed $val PHP value to convert
     *
     * @return string Javascript representation of the value
     */
    protected function _convertScalarToJavascript($val): string
    {
        if (is_bool($val))
        {
            return $val ? 'true' : 'false';
        }
        elseif (is_int($val) || is_double($val))
        {
            return (string) $val;
        }
        elseif (is_string($val))
        {
            return "'" . $this->_escapeString($val) . "'";
        }
        elseif (is_null($val))
        {
            return 'null';
        }
        else
        {
            // don't bother
            return '{}';
        }
    }

    /**
     * Creates all the elements for the group
     */
    public function _createElements()
    {
        for ($i = 0; $i < $this->_nbElements; $i ++)
        {
            $this->_elements[] = new HTML_QuickForm_select((string) $i, null, [], $this->getAttributes());
        }
    }

    /**
     * Quotes the string so that it can be used in Javascript string constants
     */
    protected function _escapeString(string $str): string
    {
        return strtr($str, [
            "\r" => '\r',
            "\n" => '\n',
            "\t" => '\t',
            "'" => "\\'",
            '"' => '\"',
            '\\' => '\\\\'
        ]);
    }

    /**
     * Sets the options for each select element
     */
    protected function _setOptions()
    {
        $toLoad = '';

        foreach (array_keys($this->_elements) as $key)
        {
            $variableName = '$this->_options[' . $key . ']' . $toLoad;
            $stringToEvaluate = 'return isset(' . $$variableName . ') ? ' . $$variableName . ' : null;';

            $array = eval($stringToEvaluate);

            if (is_array($array))
            {
                $select = $this->_elements[$key];

                if ($select instanceof HTML_QuickForm_select)
                {
                    $select->setOptions([]);
                    $select->loadArray($array);

                    $value = is_array($v = $select->getValue()) ? $v[0] : key($array);
                    $toLoad .= '[\'' . str_replace(['\\', '\''], ['\\\\', '\\\''], $value) . '\']';
                }
            }
        }
    }

    /**
     * Initialize the array structure containing the options for each select element.
     * Call the functions that actually do the magic.
     *
     * @param array $options Array of options defining each element
     *
     * @return    void
     */
    public function setOptions(array $options)
    {
        $this->_options = $options;

        if (empty($this->_elements))
        {
            $this->_nbElements = count($this->_options);
            $this->_createElements();
        }
        else
        {
            // setDefaults has probably been called before this function
            // check if all elements have been created
            $totalNbElements = count($this->_options);

            for ($i = $this->_nbElements; $i < $totalNbElements; $i ++)
            {
                $this->_elements[] = new HTML_QuickForm_select((string) $i, null, [], $this->getAttributes());
                $this->_nbElements ++;
            }
        }

        $this->_setOptions();
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
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event           Name of event
     * @param mixed $arg              event arguments
     * @param ?HTML_QuickForm $caller calling object
     */
    public function onQuickFormEvent(string $event, $arg, ?HTML_QuickForm $caller = null): bool
    {
        if ('updateValue' == $event)
        {
            // we need to call setValue() so that the secondary option
            // matches the main option
            return HTML_QuickForm_element::onQuickFormEvent($event, $arg, $caller);
        }
        else
        {
            $ret = parent::onQuickFormEvent($event, $arg, $caller);

            // add onreset handler to form to properly reset hierselect (see bug #2970)
            if ('addElement' == $event)
            {
                $onReset = $caller->getAttribute('onreset');

                if (strlen($onReset))
                {
                    if (strpos($onReset, '_hs_setupOnReset'))
                    {
                        $caller->updateAttributes(
                            [
                                'onreset' => str_replace(
                                    '_hs_setupOnReset(this, [',
                                    "_hs_setupOnReset(this, ['" . $this->_escapeString($this->getName()) . "', ",
                                    $onReset
                                )
                            ]
                        );
                    }
                    else
                    {
                        $caller->updateAttributes(
                            [
                                'onreset' => "var temp = function() { $onReset } ; if (!temp()) { return false; } ; if (typeof _hs_setupOnReset != 'undefined') { return _hs_setupOnReset(this, ['" .
                                    $this->_escapeString($this->getName()) . "']); } "
                            ]
                        );
                    }
                }
                else
                {
                    $caller->updateAttributes(
                        [
                            'onreset' => "if (typeof _hs_setupOnReset != 'undefined') { return _hs_setupOnReset(this, ['" .
                                $this->_escapeString($this->getName()) . "']); } "
                        ]
                    );
                }
            }

            return $ret;
        }
    }

    /**
     * Sets values for group's elements
     *
     * @param array $value            An array of 2 or more values, for the first,
     *                                the second, the third etc. select
     */
    public function setValue($value)
    {
        // fix for bug #6766. Hope this doesn't break anything more
        // after bug #7961. Forgot that _nbElements was used in
        // _createElements() called in several places...
        $this->_nbElements = max($this->_nbElements, count($value));

        parent::setValue($value);

        $this->_setOptions();
    }

    public function toHtml(): string
    {
        $this->_js = '';

        if (!$this->_flagFrozen)
        {
            // set the onchange attribute for each element except last
            $keys = array_keys($this->_elements);
            $onChange = [];

            for ($i = 0; $i < count($keys) - 1; $i ++)
            {
                $select =& $this->_elements[$keys[$i]];
                $onChange[$i] = $select->getAttribute('onchange');
                $select->updateAttributes(
                    [
                        'onchange' => '_hs_swapOptions(this.form, \'' . $this->_escapeString($this->getName()) .
                            '\', ' . $keys[$i] . ');' . $onChange[$i]
                    ]
                );
            }

            // create the js function to call
            if (!defined('HTML_QUICKFORM_HIERSELECT_EXISTS'))
            {
                $this->_js .= <<<JAVASCRIPT
function _hs_findOptions(ary, keys)
{
    var key = keys.shift();
    if (!key in ary) {
        return {};
    } else if (0 == keys.length) {
        return ary[key];
    } else {
        return _hs_findOptions(ary[key], keys);
    }
}

function _hs_findSelect(form, groupName, selectIndex)
{
    if (groupName+'['+ selectIndex +']' in form) {
        return form[groupName+'['+ selectIndex +']'];
    } else {
        return form[groupName+'['+ selectIndex +'][]'];
    }
}

function _hs_unescapeEntities(str)
{
    var div = document.createElement('div');
    div.innerHTML = str;
    return div.childNodes[0] ? div.childNodes[0].nodeValue : '';
}

function _hs_replaceOptions(ctl, optionList)
{
    var j = 0;
    ctl.options.length = 0;
    for (i in optionList) {
        var optionText = (-1 == optionList[i].indexOf('&'))? optionList[i]: _hs_unescapeEntities(optionList[i]);
        ctl.options[j++] = new Option(optionText, i, false, false);
    }
}

function _hs_setValue(ctl, value)
{
    var testValue = {};
    if (value instanceof Array) {
        for (var i = 0; i < value.length; i++) {
            testValue[value[i]] = true;
        }
    } else {
        testValue[value] = true;
    }
    for (var i = 0; i < ctl.options.length; i++) {
        if (ctl.options[i].value in testValue) {
            ctl.options[i].selected = true;
        }
    }
}

function _hs_swapOptions(form, groupName, selectIndex)
{
    var hsValue = [];
    for (var i = 0; i <= selectIndex; i++) {
        hsValue[i] = _hs_findSelect(form, groupName, i).value;
    }

    _hs_replaceOptions(_hs_findSelect(form, groupName, selectIndex + 1),
                       _hs_findOptions(_hs_options[groupName][selectIndex], hsValue));
    if (selectIndex + 1 < _hs_options[groupName].length) {
        _hs_swapOptions(form, groupName, selectIndex + 1);
    }
}

function _hs_onReset(form, groupNames)
{
    for (var i = 0; i < groupNames.length; i++) {
        try {
            for (var j = 0; j <= _hs_options[groupNames[i]].length; j++) {
                _hs_setValue(_hs_findSelect(form, groupNames[i], j), _hs_defaults[groupNames[i]][j]);
                if (j < _hs_options[groupNames[i]].length) {
                    _hs_replaceOptions(_hs_findSelect(form, groupNames[i], j + 1),
                                       _hs_findOptions(_hs_options[groupNames[i]][j], _hs_defaults[groupNames[i]].slice(0, j + 1)));
                }
            }
        } catch (e) {
            if (!(e instanceof TypeError)) {
                throw e;
            }
        }
    }
}

function _hs_setupOnReset(form, groupNames)
{
    setTimeout(function() { _hs_onReset(form, groupNames); }, 25);
}

function _hs_onReload()
{
    var ctl;
    for (var i = 0; i < document.forms.length; i++) {
        for (var j in _hs_defaults) {
            if (ctl = _hs_findSelect(document.forms[i], j, 0)) {
                for (var k = 0; k < _hs_defaults[j].length; k++) {
                    _hs_setValue(_hs_findSelect(document.forms[i], j, k), _hs_defaults[j][k]);
                }
            }
        }
    }

    if (_hs_prevOnload) {
        _hs_prevOnload();
    }
}

var _hs_prevOnload = null;
if (window.onload) {
    _hs_prevOnload = window.onload;
}
window.onload = _hs_onReload;

var _hs_options = {};
var _hs_defaults = {};

JAVASCRIPT;
                define('HTML_QUICKFORM_HIERSELECT_EXISTS', true);
            }

            // option lists
            $jsParts = [];

            for ($i = 1; $i < $this->_nbElements; $i ++)
            {
                $jsParts[] = $this->_convertArrayToJavascript($this->_options[$i]);
            }

            $this->_js .= "\n_hs_options['" . $this->_escapeString($this->getName()) . "'] = [\n" .
                implode(",\n", $jsParts) . "\n];\n";

            // default value; if we don't actually have any values yet just use
            // the first option (for single selects) or empty array (for multiple)
            $values = [];

            foreach (array_keys($this->_elements) as $key)
            {
                $element = $this->_elements[$key];

                if (is_array($v = $element->getValue()))
                {
                    $values[] = count($v) > 1 ? $v : $v[0];
                }
                elseif ($element instanceof HTML_QuickForm_select)
                {
                    // XXX: accessing the supposedly private _options array
                    $values[] = ($element->getMultiple() || !$element->hasOptions()) ? [] :
                        $element->getOptions()[0]['attr']['value'];
                }
            }
            $this->_js .= "_hs_defaults['" . $this->_escapeString($this->getName()) . "'] = " .
                $this->_convertArrayToJavascript($values, false) . ";\n";
        }

        $renderer = new HTML_QuickForm_Renderer_Default();
        $renderer->setElementTemplate('{element}');

        parent::accept($renderer);

        if (!empty($onChange))
        {
            $keys = array_keys($this->_elements);

            for ($i = 0; $i < count($keys) - 1; $i ++)
            {
                $this->_elements[$keys[$i]]->updateAttributes(['onchange' => $onChange[$i]]);
            }
        }

        return (empty($this->_js) ? '' :
                "<script type=\"text/javascript\">\n//<![CDATA[\n" . $this->_js . "//]]>\n</script>") .
            $renderer->toHtml();
    }

}
