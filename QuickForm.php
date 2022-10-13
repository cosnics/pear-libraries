<?php
/**
 * +----------------------------------------------------------------------+
 * | PHP version 4.0                                                      |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2003 The PHP Group                                |
 * +----------------------------------------------------------------------+
 * | This source file is subject to version 2.0 of the PHP license,       |
 * | that is bundled with this package in the file LICENSE, and is        |
 * | available at through the world-wide-web at                           |
 * | http://www.php.net/license/2_02.txt.                                 |
 * | If you did not receive a copy of the PHP license and are unable to   |
 * | obtain it through the world-wide-web, please send a note to          |
 * | license@php.net so we can mail you a copy immediately.               |
 * +----------------------------------------------------------------------+
 * | Authors: Adam Daniel <adaniel1@eesus.jnj.com>                        |
 * |          Bertrand Mansion <bmansion@mamasam.com>                     |
 * +----------------------------------------------------------------------+
 */

$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES'] = [
    'group' => 'HTML_QuickForm_group',
    'hidden' => 'HTML_QuickForm_hidden',
    'reset' => 'HTML_QuickForm_reset',
    'checkbox' => 'HTML_QuickForm_checkbox',
    'file' => 'HTML_QuickForm_file',
    'image' => 'HTML_QuickForm_image',
    'password' => 'HTML_QuickForm_password',
    'radio' => 'HTML_QuickForm_radio',
    'button' => 'HTML_QuickForm_button',
    'submit' => 'HTML_QuickForm_submit',
    'select' => 'HTML_QuickForm_select',
    'hiddenselect' => 'HTML_QuickForm_hiddenselect',
    'text' => 'HTML_QuickForm_text',
    'textarea' => 'HTML_QuickForm_textarea',
    'link' => 'HTML_QuickForm_link',
    'advcheckbox' => 'HTML_QuickForm_advcheckbox',
    'date' => 'HTML_QuickForm_date',
    'static' => 'HTML_QuickForm_static',
    'header' => 'HTML_QuickForm_header',
    'html' => 'HTML_QuickForm_html',
    'hierselect' => 'HTML_QuickForm_hierselect',
    'autocomplete' => 'HTML_QuickForm_autocomplete',
    'xbutton' => 'HTML_QuickForm_xbutton'
];

$GLOBALS['_HTML_QuickForm_registered_rules'] = [
    'required' => ['HTML_QuickForm_Rule_Required', 'HTML/QuickForm/Rule/Required.php'],
    'maxlength' => ['HTML_QuickForm_Rule_Range', 'HTML/QuickForm/Rule/Range.php'],
    'minlength' => ['HTML_QuickForm_Rule_Range', 'HTML/QuickForm/Rule/Range.php'],
    'rangelength' => ['HTML_QuickForm_Rule_Range', 'HTML/QuickForm/Rule/Range.php'],
    'email' => ['HTML_QuickForm_Rule_Email', 'HTML/QuickForm/Rule/Email.php'],
    'regex' => ['HTML_QuickForm_Rule_Regex', 'HTML/QuickForm/Rule/Regex.php'],
    'lettersonly' => ['HTML_QuickForm_Rule_Regex', 'HTML/QuickForm/Rule/Regex.php'],
    'alphanumeric' => ['HTML_QuickForm_Rule_Regex', 'HTML/QuickForm/Rule/Regex.php'],
    'numeric' => ['HTML_QuickForm_Rule_Regex', 'HTML/QuickForm/Rule/Regex.php'],
    'nopunctuation' => ['HTML_QuickForm_Rule_Regex', 'HTML/QuickForm/Rule/Regex.php'],
    'nonzero' => ['HTML_QuickForm_Rule_Regex', 'HTML/QuickForm/Rule/Regex.php'],
    'callback' => ['HTML_QuickForm_Rule_Callback', 'HTML/QuickForm/Rule/Callback.php'],
    'compare' => ['HTML_QuickForm_Rule_Compare', 'HTML/QuickForm/Rule/Compare.php']
];

/*
 * Error codes for the QuickForm interface, which will be mapped to textual messages
 * in the QuickForm::errorMessage() function.  If you are to add a new error code, be
 * sure to add the textual messages to the QuickForm::errorMessage() function as well
 */

const QUICKFORM_OK = 1;
const QUICKFORM_ERROR = - 1;
const QUICKFORM_INVALID_RULE = - 2;
const QUICKFORM_NONEXIST_ELEMENT = - 3;
const QUICKFORM_INVALID_FILTER = - 4;
const QUICKFORM_UNREGISTERED_ELEMENT = - 5;
const QUICKFORM_INVALID_ELEMENT_NAME = - 6;
const QUICKFORM_INVALID_PROCESS = - 7;
const QUICKFORM_DEPRECATED = - 8;
const QUICKFORM_INVALID_DATASOURCE = - 9;

/**
 * Create, validate and process HTML forms
 *
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     2.0
 */
class HTML_QuickForm extends HTML_Common
{
    /**
     * Postfix message in javascript alert if error
     *
     * @var  string
     */
    public $_jsPostfix = 'Please correct these fields.';

    /**
     * Prefix message in javascript alert if error
     *
     * @var  string
     */
    public $_jsPrefix = 'Invalid information entered.';

    /**
     * Value for maxfilesize hidden element if form contains file input
     *
     * @var  int
     */
    public $_maxFileSize = 1048576;

    /**
     * Array of submitted form files
     *
     * @var  int
     */
    public $_submitFiles = [];

    /**
     * Array of constant form values
     *
     * @var  array
     */
    protected $_constantValues = [];

    /**
     * Datasource object implementing the informal
     * datasource protocol
     *
     * @var  object
     */
    protected $_datasource;

    /**
     * Array of default form values
     *
     * @var  array
     */
    protected $_defaultValues = [];

    /**
     * Array containing indexes of duplicate elements
     *
     * @var  array
     */
    protected $_duplicateIndex = [];

    /**
     * Array containing element name to index map
     *
     * @var  array
     */
    protected $_elementIndex = [];

    /**
     * Array containing the form fields
     *
     * @var  array
     */
    protected $_elements = [];

    /**
     * Array containing the validation errors
     *
     * @var  array
     */
    protected $_errors = [];

    /**
     * Whether the form was submitted
     *
     * @var       bool
     */
    protected $_flagSubmitted = false; // 1 Mb = 1048576

    /**
     * Form rules, global variety
     *
     * @var     array
     */
    protected $_formRules = [];

    /**
     * Flag to know if all fields are frozen
     *
     * @var  bool
     */
    protected $_freezeAll = false;

    /**
     * Array containing required field IDs
     *
     * @var  array
     */
    protected $_required = [];

    /**
     * Note for required fields in the form
     *
     * @var       string
     */
    protected $_requiredNote = '<span style="font-size:80%; color:#ff0000;">*</span><span style="font-size:80%;"> denotes required field</span>';

    /**
     * Array containing the form rules
     *
     * @var  array
     */
    protected $_rules = [];

    /**
     * Array of submitted form values
     *
     * @var  array
     */
    protected $_submitValues = [];

    /**
     * Class constructor
     *
     * @param string $formName  Form's name.
     * @param string $method    (optional)Form's method defaults to 'POST'
     * @param string $action    (optional)Form's action
     * @param string $target    (optional)Form's target defaults to '_self'
     * @param mixed $attributes (optional)Extra attributes for <form> tag
     * @param bool $trackSubmit (optional)Whether to track if the form was submitted by adding a special hidden field
     *
     * @throws \QuickformException
     */
    public function __construct(
        $formName = '', $method = 'post', $action = '', $target = '', $attributes = null, $trackSubmit = false
    )
    {
        parent::__construct($attributes);
        $method = (strtoupper($method) == 'GET') ? 'get' : 'post';
        $action = ($action == '') ? $_SERVER['PHP_SELF'] : $action;
        $target = empty($target) ? [] : ['target' => $target];
        $attributes = ['action' => $action, 'method' => $method, 'name' => $formName, 'id' => $formName] + $target;
        $this->updateAttributes($attributes);
        if (!$trackSubmit || isset($_REQUEST['_qf__' . $formName]))
        {
            $this->_submitValues = 'get' == $method ? $_GET : $_POST;
            $this->_submitFiles = $_FILES;
            $this->_flagSubmitted = count($this->_submitValues) > 0 || count($this->_submitFiles) > 0;
        }
        if ($trackSubmit)
        {
            unset($this->_submitValues['_qf__' . $formName]);
            $this->addElement('hidden', '_qf__' . $formName, null);
        }
        if (preg_match('/^([0-9]+)([a-zA-Z]*)$/', ini_get('upload_max_filesize'), $matches))
        {
            // see http://www.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
            switch (strtoupper($matches['2']))
            {
                case 'G':
                    $this->_maxFileSize = $matches['1'] * 1073741824;
                    break;
                case 'M':
                    $this->_maxFileSize = $matches['1'] * 1048576;
                    break;
                case 'K':
                    $this->_maxFileSize = $matches['1'] * 1024;
                    break;
                default:
                    $this->_maxFileSize = $matches['1'];
            }
        }
    } // end constructor

    /**
     * Returns a form element of the given type
     *
     * @param string $event event to send to newly created element ('createElement' or 'addElement')
     * @param string $type  element type
     * @param array $args   arguments for event
     *
     * @return    object    a new element
     * @throws    QuickformException
     */
    protected function &_loadElement($event, $type, $args)
    {
        $type = strtolower($type);
        if (!HTML_QuickForm::isTypeRegistered($type))
        {
            throw new QuickformException("Element '$type' does not exist in HTML_QuickForm::_loadElement()");
        }
        $className = $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES'][$type];
        $elementObject = new $className(); //Moodle: PHP 5.3 compatibility
        for ($i = 0; $i < 5; $i ++)
        {
            if (!isset($args[$i]))
            {
                $args[$i] = null;
            }
        }
        $err = $elementObject->onQuickFormEvent($event, $args, $this);
        if ($err !== true)
        {
            return $err;
        }

        return $elementObject;
    }

    /**
     * Recursively apply a filter function
     *
     * @param string $filter filter to apply
     * @param mixed $value   submitted values
     *
     * @return    cleaned values
     */
    protected function _recursiveFilter($filter, $value)
    {
        if (is_array($value))
        {
            $cleanValues = [];
            foreach ($value as $k => $v)
            {
                $cleanValues[$k] = $this->_recursiveFilter($filter, $v);
            }

            return $cleanValues;
        }
        else
        {
            return call_user_func($filter, $value);
        }
    }

    /**
     * A helper function to change the indexes in $_FILES array
     *
     * @param mixed   Some value from the $_FILES array
     * @param string  The key from the $_FILES array that should be appended
     *
     * @return array
     */
    public function _reindexFiles($value, $key)
    {
        if (!is_array($value))
        {
            return [$key => $value];
        }
        else
        {
            $ret = [];
            foreach ($value as $k => $v)
            {
                $ret[$k] = $this->_reindexFiles($v, $key);
            }

            return $ret;
        }
    }

    /**
     * Accepts a renderer
     *
     * @param object     An HTML_QuickForm_Renderer object
     *
     * @return void
     */
    public function accept(&$renderer)
    {
        $renderer->startForm($this);
        foreach (array_keys($this->_elements) as $key)
        {
            $element =& $this->_elements[$key];
            $elementName = $element->getName();
            $required = ($this->isElementRequired($elementName) && !$element->isFrozen());
            $error = $this->getElementError($elementName);
            $element->accept($renderer, $required, $error);
        }
        $renderer->finishForm($this);
    }

    /**
     * Adds an element into the form
     * If $element is a string representing element type, then this
     * method accepts variable number of parameters, their meaning
     * and count depending on $element
     *
     * @param mixed $element element object or type of element to add (text, textarea, file...)
     *
     * @return   object     reference to element
     * @throws   QuickformException
     */
    public function &addElement($element)
    {
        if (is_object($element) && is_subclass_of($element, 'html_quickform_element'))
        {
            $elementObject = &$element;
            $elementObject->onQuickFormEvent('updateValue', null, $this);
        }
        else
        {
            $args = func_get_args();
            $elementObject =& $this->_loadElement('addElement', $element, array_slice($args, 1));
        }
        $elementName = $elementObject->getName();

        // Add the element if it is not an incompatible duplicate
        if (!empty($elementName) && isset($this->_elementIndex[$elementName]))
        {
            if ($this->_elements[$this->_elementIndex[$elementName]]->getType() == $elementObject->getType())
            {
                $this->_elements[] =& $elementObject;
                $elKeys = array_keys($this->_elements);
                $this->_duplicateIndex[$elementName][] = end($elKeys);
            }
            else
            {
                throw new QuickformException("Element '$elementName' already exists in HTML_QuickForm::addElement()");
            }
        }
        else
        {
            $this->_elements[] =& $elementObject;
            $elKeys = array_keys($this->_elements);
            $this->_elementIndex[$elementName] = end($elKeys);
        }
        if ($this->_freezeAll)
        {
            $elementObject->freeze();
        }

        return $elementObject;
    }

    /**
     * Adds a global validation rule
     * This should be used when for a rule involving several fields or if
     * you want to use some completely custom validation for your form.
     * The rule function/method should return true in case of successful
     * validation and array('element name' => 'error') when there were errors.
     *
     * @param mixed   Callback, either function name or array(&$object, 'method')
     *
     * @throws   QuickformException
     */
    public function addFormRule($rule)
    {
        if (!is_callable($rule))
        {
            throw new QuickformException('Callback function does not exist in HTML_QuickForm::addFormRule()');
        }
        $this->_formRules[] = $rule;
    }

    /**
     * Adds an element group
     *
     * @param array $elements               array of elements composing the group
     * @param string $name                  (optional)group name
     * @param string $groupLabel            (optional)group label
     * @param string $separator             (optional)string to separate elements
     * @param string $appendName            (optional)specify whether the group name should be
     *                                      used in the form element name ex: group[element]
     *
     * @return   object     reference to added group of elements
     * @throws   QuickformException
     */
    public function &addGroup($elements, $name = null, $groupLabel = '', $separator = null, $appendName = true)
    {
        static $anonGroups = 1;

        if (0 == strlen($name))
        {
            $name = 'qf_group_' . $anonGroups ++;
            $appendName = false;
        }
        $group =& $this->addElement('group', $name, $groupLabel, $elements, $separator, $appendName);

        return $group;
    }

    /**
     * Adds a validation rule for the given group of elements
     * Only groups with a name can be assigned a validation rule
     * Use addGroupRule when you need to validate elements inside the group.
     * Use addRule if you need to validate the group as a whole. In this case,
     * the same rule will be applied to all elements in the group.
     * Use addRule if you need to validate the group against a function.
     *
     * @param string $group      Form group name
     * @param mixed $arg1        Array for multiple elements or error message string for one element
     * @param string $type       (optional)Rule type use getRegisteredRules() to get types
     * @param string $format     (optional)Required for extra rule data
     * @param int $howmany       (optional)How many valid elements should be in the group
     * @param string $validation (optional)Where to perform validation: "server", "client"
     * @param bool $reset        Client-side: whether to reset the element's value to its original state if validation
     *                           failed.
     *
     * @throws   QuickformException
     */
    public function addGroupRule(
        $group, $arg1, $type = '', $format = null, $howmany = 0, $validation = 'server', $reset = false
    )
    {
        if (!$this->elementExists($group))
        {
            throw new QuickformException("Group '$group' does not exist in HTML_QuickForm::addGroupRule()");
        }

        $groupObj =& $this->getElement($group);
        if (is_array($arg1))
        {
            $required = 0;
            foreach ($arg1 as $elementIndex => $rules)
            {
                $elementName = $groupObj->getElementName($elementIndex);
                foreach ($rules as $rule)
                {
                    $format = (isset($rule[2])) ? $rule[2] : null;
                    $validation = (isset($rule[3]) && 'client' == $rule[3]) ? 'client' : 'server';
                    $reset = isset($rule[4]) && $rule[4];
                    $type = $rule[1];
                    if (false === ($newName = $this->isRuleRegistered($type, true)))
                    {
                        throw new QuickformException(
                            "Rule '$type' is not registered in HTML_QuickForm::addGroupRule()"
                        );
                    }
                    elseif (is_string($newName))
                    {
                        $type = $newName;
                    }

                    $this->_rules[$elementName][] = [
                        'type' => $type,
                        'format' => $format,
                        'message' => $rule[0],
                        'validation' => $validation,
                        'reset' => $reset,
                        'group' => $group
                    ];

                    if ('required' == $type || 'uploadedfile' == $type)
                    {
                        $groupObj->_required[] = $elementName;
                        $this->_required[] = $elementName;
                        $required ++;
                    }
                }
            }
            if ($required > 0 && count($groupObj->getElements()) == $required)
            {
                $this->_required[] = $group;
            }
        }
        elseif (is_string($arg1))
        {
            if (false === ($newName = $this->isRuleRegistered($type, true)))
            {
                throw new QuickformException("Rule '$type' is not registered in HTML_QuickForm::addGroupRule()");
            }
            elseif (is_string($newName))
            {
                $type = $newName;
            }

            // addGroupRule() should also handle <select multiple>
            if (is_a($groupObj, 'html_quickform_group'))
            {
                // Radios need to be handled differently when required
                if ($type == 'required' && $groupObj->getGroupType() == 'radio')
                {
                    $howmany = ($howmany == 0) ? 1 : $howmany;
                }
                else
                {
                    $howmany = ($howmany == 0) ? count($groupObj->getElements()) : $howmany;
                }
            }

            $this->_rules[$group][] = [
                'type' => $type,
                'format' => $format,
                'message' => $arg1,
                'validation' => $validation,
                'howmany' => $howmany,
                'reset' => $reset
            ];
            if ($type == 'required')
            {
                $this->_required[] = $group;
            }
        }
    }

    /**
     * Adds a validation rule for the given field
     * If the element is in fact a group, it will be considered as a whole.
     * To validate grouped elements as separated entities,
     * use addGroupRule instead of addRule.
     *
     * @param string $element    Form element name
     * @param string $message    Message to display for invalid data
     * @param string $type       Rule type, use getRegisteredRules() to get types
     * @param string $format     (optional)Required for extra rule data
     * @param string $validation (optional)Where to perform validation: "server", "client"
     * @param bool $reset        Client-side validation: reset the form element to its original value if there is an
     *                           error?
     * @param bool $force        Force the rule to be applied, even if the target form element does not exist
     *
     * @throws   QuickformException
     */
    public function addRule(
        $element, $message, $type, $format = null, $validation = 'server', $reset = false, $force = false
    )
    {
        if (!$force)
        {
            if (!is_array($element) && !$this->elementExists($element))
            {
                throw new QuickformException("Element '$element' does not exist in HTML_QuickForm::addRule()");
            }
            elseif (is_array($element))
            {
                foreach ($element as $el)
                {
                    if (!$this->elementExists($el))
                    {
                        throw new QuickformException(
                            "Element '$el' does not exist in HTML_QuickForm::addRule()", 'HTML_QuickForm_Error'
                        );
                    }
                }
            }
        }
        if (false === ($newName = $this->isRuleRegistered($type, true)))
        {
            throw new QuickformException("Rule '$type' is not registered in HTML_QuickForm::addRule()");
        }
        elseif (is_string($newName))
        {
            $type = $newName;
        }
        if (is_array($element))
        {
            $dependent = $element;
            $element = array_shift($dependent);
        }
        else
        {
            $dependent = null;
        }
        if ($type == 'required' || $type == 'uploadedfile')
        {
            $this->_required[] = $element;
        }
        if (!isset($this->_rules[$element]))
        {
            $this->_rules[$element] = [];
        }
        $this->_rules[$element][] = [
            'type' => $type,
            'format' => $format,
            'message' => $message,
            'validation' => $validation,
            'reset' => $reset,
            'dependent' => $dependent
        ];
    }

    /**
     * Applies a data filter for the given field(s)
     *
     * @param mixed $element Form element name or array of such names
     * @param mixed $filter  Callback, either function name or array(&$object, 'method')
     *
     * @throws \QuickformException
     */
    public function applyFilter($element, $filter)
    {
        if (!is_callable($filter))
        {
            throw new QuickformException('Callback function does not exist in QuickForm::applyFilter()');
        }
        if ($element == '__ALL__')
        {
            $this->_submitValues = $this->_recursiveFilter($filter, $this->_submitValues);
        }
        else
        {
            if (!is_array($element))
            {
                $element = [$element];
            }
            foreach ($element as $elName)
            {
                $value = $this->getSubmitValue($elName);
                if (null !== $value)
                {
                    if (false === strpos($elName, '['))
                    {
                        $this->_submitValues[$elName] = $this->_recursiveFilter($filter, $value);
                    }
                    else
                    {
                        $idx = "['" . str_replace([']', '['], ['', "']['"], $elName) . "']";
                        eval("\$this->_submitValues$idx = \$this->_recursiveFilter(\$filter, \$value);");
                    }
                }
            }
        }
    }

    /**
     * Merges two arrays
     * Merges two array like the PHP function array_merge but recursively.
     * The main difference is that existing keys will not be renumbered
     * if they are integers.
     *
     * @access   puplic
     *
     * @param array $a original array
     * @param array $b array which will be merged into first one
     *
     * @return   array   merged array
     */
    public static function arrayMerge($a, $b)
    {
        if (is_null($a))
        {
            $a = [];
        }
        if (is_null($b))
        {
            $b = [];
        }
        foreach ($b as $k => $v)
        {
            if (is_array($v))
            {
                if (isset($a[$k]) && !is_array($a[$k]))
                {
                    $a[$k] = $v;
                }
                else
                {
                    if (!isset($a[$k]))
                    {
                        $a[$k] = [];
                    }
                    $a[$k] = HTML_QuickForm::arrayMerge($a[$k], $v);
                }
            }
            else
            {
                $a[$k] = $v;
            }
        }

        return $a;
    }

    /**
     * Creates a new form element of the given type.
     * This method accepts variable number of parameters, their
     * meaning and count depending on $elementType
     *
     * @param string $elementType type of element to add (text, textarea, file...)
     *
     * @return    object extended class of HTML_element
     * @throws    QuickformException
     */
    public function &createElement($elementType)
    {
        $args = func_get_args();
        $element =& HTML_QuickForm::_loadElement('createElement', $elementType, array_slice($args, 1));

        return $element;
    }

    /**
     * Returns a reference to default renderer object
     *
     * @return object a default renderer object
     */
    public function &defaultRenderer()
    {
        if (!isset($GLOBALS['_HTML_QuickForm_default_renderer']))
        {
            $GLOBALS['_HTML_QuickForm_default_renderer'] =
                new HTML_QuickForm_Renderer_Default(); //Moodle: PHP 5.3 compatibility
        }

        return $GLOBALS['_HTML_QuickForm_default_renderer'];
    }

    /**
     * Returns true if element is in the form
     *
     * @param string $element form name of element to check
     *
     * @return    bool
     */
    public function elementExists($element = null)
    {
        return isset($this->_elementIndex[$element]);
    }

    /**
     * Return a textual error message for an QuickForm error code
     *
     * @param int     error code
     *
     * @return  string  error message
     */
    public static function errorMessage($value)
    {
        // make the variable static so that it only has to do the defining on the first call
        static $errorMessages;

        // define the varies error messages
        if (!isset($errorMessages))
        {
            $errorMessages = [
                QUICKFORM_OK => 'no error',
                QUICKFORM_ERROR => 'unknown error',
                QUICKFORM_INVALID_RULE => 'the rule does not exist as a registered rule',
                QUICKFORM_NONEXIST_ELEMENT => 'nonexistent html element',
                QUICKFORM_INVALID_FILTER => 'invalid filter',
                QUICKFORM_UNREGISTERED_ELEMENT => 'unregistered element',
                QUICKFORM_INVALID_ELEMENT_NAME => 'element already exists',
                QUICKFORM_INVALID_PROCESS => 'process callback does not exist',
                QUICKFORM_DEPRECATED => 'method is deprecated',
                QUICKFORM_INVALID_DATASOURCE => 'datasource is not an object'
            ];
        }

        // If this is an error object, then grab the corresponding error code
        if (HTML_QuickForm::isError($value))
        {
            $value = $value->getCode();
        }

        // return the textual error message corresponding to the code
        return $errorMessages[$value] ?? $errorMessages[QUICKFORM_ERROR];
    }

    /**
     * Returns a 'safe' element's value
     * This method first tries to find a cleaned-up submitted value,
     * it will return a value set by setValue()/setDefaults()/setConstants()
     * if submitted value does not exist for the given element.
     *
     * @param string   Name of an element
     *
     * @return mixed
     * @throws \QuickformException
     */
    public function exportValue($element)
    {
        if (!isset($this->_elementIndex[$element]))
        {
            throw new QuickformException("Element '$element' does not exist in HTML_QuickForm::getElementValue()");
        }
        $value = $this->_elements[$this->_elementIndex[$element]]->exportValue($this->_submitValues, false);
        if (isset($this->_duplicateIndex[$element]))
        {
            foreach ($this->_duplicateIndex[$element] as $index)
            {
                if (null !== ($v = $this->_elements[$index]->exportValue($this->_submitValues, false)))
                {
                    if (is_array($value))
                    {
                        $value[] = $v;
                    }
                    else
                    {
                        $value = (null === $value) ? $v : [$value, $v];
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Returns 'safe' elements' values
     * Unlike getSubmitValues(), this will return only the values
     * corresponding to the elements present in the form.
     *
     * @param mixed   Array/string of element names, whose values we want. If not set then return all elements.
     *
     * @return  array   An assoc array of elements' values
     * @throws  QuickformException
     */
    public function exportValues($elementList = null)
    {
        $values = [];
        if (null === $elementList)
        {
            // iterate over all elements, calling their exportValue() methods
            foreach (array_keys($this->_elements) as $key)
            {
                $value = $this->_elements[$key]->exportValue($this->_submitValues, true);
                if (is_array($value))
                {
                    // This shit throws a bogus warning in PHP 4.3.x
                    $values = HTML_QuickForm::arrayMerge($values, $value);
                }
            }
        }
        else
        {
            if (!is_array($elementList))
            {
                $elementList = array_map('trim', explode(',', $elementList));
            }
            foreach ($elementList as $elementName)
            {
                $value = $this->exportValue($elementName);
                $values[$elementName] = $value;
            }
        }

        return $values;
    }

    /**
     * Displays elements without HTML input tags
     *
     * @param mixed $elementList array or string of element(s) to be frozen
     *
     * @throws   QuickformException
     */
    public function freeze($elementList = null)
    {
        if (!isset($elementList))
        {
            $this->_freezeAll = true;
            $elementList = [];
        }
        else
        {
            if (!is_array($elementList))
            {
                $elementList = preg_split('/[ ]*,[ ]*/', $elementList);
            }
            $elementList = array_flip($elementList);
        }

        foreach (array_keys($this->_elements) as $key)
        {
            $name = $this->_elements[$key]->getName();
            if ($this->_freezeAll || isset($elementList[$name]))
            {
                $this->_elements[$key]->freeze();
                unset($elementList[$name]);
            }
        }

        if (!empty($elementList))
        {
            throw new QuickformException(
                "Nonexistant element(s): '" . implode("', '", array_keys($elementList)) .
                "' in HTML_QuickForm::freeze()"
            );
        }

        return true;
    }

    /**
     * Returns a reference to the element
     *
     * @param string $element Element name
     *
     * @return    object     reference to element
     * @throws    QuickformException
     */
    public function &getElement($element)
    {
        if (isset($this->_elementIndex[$element]))
        {
            return $this->_elements[$this->_elementIndex[$element]];
        }
        else
        {
            throw new QuickformException("Element '$element' does not exist in HTML_QuickForm::getElement()");
        }
    }

    /**
     * Returns error corresponding to validated element
     *
     * @param string $element Name of form element to check
     *
     * @return    string    error message corresponding to checked element
     */
    public function getElementError($element)
    {
        if (isset($this->_errors[$element]))
        {
            return $this->_errors[$element];
        }
    }

    /**
     * Returns the type of the given element
     *
     * @param string $element Name of form element
     *
     * @return     string    Type of the element, false if the element is not found
     */
    public function getElementType($element)
    {
        if (isset($this->_elementIndex[$element]))
        {
            return $this->_elements[$this->_elementIndex[$element]]->getType();
        }

        return false;
    }

    /**
     * Returns the element's raw value
     * This returns the value as submitted by the form (not filtered)
     * or set via setDefaults() or setConstants()
     *
     * @param string $element Element name
     *
     * @return    mixed     element value
     * @throws    QuickformException
     */
    public function &getElementValue($element)
    {
        if (!isset($this->_elementIndex[$element]))
        {
            throw new QuickformException("Element '$element' does not exist in HTML_QuickForm::getElementValue()");
        }
        $value = $this->_elements[$this->_elementIndex[$element]]->getValue();
        if (isset($this->_duplicateIndex[$element]))
        {
            foreach ($this->_duplicateIndex[$element] as $index)
            {
                if (null !== ($v = $this->_elements[$index]->getValue()))
                {
                    if (is_array($value))
                    {
                        $value[] = $v;
                    }
                    else
                    {
                        $value = (null === $value) ? $v : [$value, $v];
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Returns the value of MAX_FILE_SIZE hidden element
     *
     * @return    int   max file size in bytes
     */
    public function getMaxFileSize()
    {
        return $this->_maxFileSize;
    }

    /**
     * Sets the value of MAX_FILE_SIZE hidden element
     *
     * @param int $bytes Size in bytes
     *
     * @return    void
     * @throws \QuickformException
     */
    public function setMaxFileSize($bytes = 0)
    {
        if ($bytes > 0)
        {
            $this->_maxFileSize = $bytes;
        }
        if (!$this->elementExists('MAX_FILE_SIZE'))
        {
            $this->addElement('hidden', 'MAX_FILE_SIZE', $this->_maxFileSize);
        }
        else
        {
            $el =& $this->getElement('MAX_FILE_SIZE');
            $el->updateAttributes(['value' => $this->_maxFileSize]);
        }
    }

    /**
     * Returns an array of registered validation rules
     *
     * @return    array
     */
    public function getRegisteredRules()
    {
        return array_keys($GLOBALS['_HTML_QuickForm_registered_rules']);
    }

    /**
     * Returns an array of registered element types
     *
     * @return    array
     */
    public function getRegisteredTypes()
    {
        return array_keys($GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']);
    }

    /**
     * Returns the required note
     *
     * @return    string
     */
    public function getRequiredNote()
    {
        return $this->_requiredNote;
    }

    /**
     * Sets required-note
     *
     * @param string $note Message indicating some elements are required
     *
     * @return    void
     */
    public function setRequiredNote($note)
    {
        $this->_requiredNote = $note;
    }

    /**
     * Returns the elements value after submit and filter
     *
     * @param string     Element name
     *
     * @return    mixed     submitted element value or null if not set
     * @throws \QuickformException
     */
    public function getSubmitValue($elementName)
    {
        $value = null;
        if (isset($this->_submitValues[$elementName]) || isset($this->_submitFiles[$elementName]))
        {
            $value = $this->_submitValues[$elementName] ?? [];
            if (is_array($value) && isset($this->_submitFiles[$elementName]))
            {
                foreach ($this->_submitFiles[$elementName] as $k => $v)
                {
                    $value = HTML_QuickForm::arrayMerge(
                        $value, $this->_reindexFiles($this->_submitFiles[$elementName][$k], $k)
                    );
                }
            }
        }
        elseif ('file' == $this->getElementType($elementName))
        {
            return $this->getElementValue($elementName);
        }
        elseif (false !== ($pos = strpos($elementName, '[')))
        {
            $base = substr($elementName, 0, $pos);
            $idx = "['" . str_replace([']', '['], ['', "']['"], substr($elementName, $pos + 1, - 1)) . "']";
            if (isset($this->_submitValues[$base]))
            {
                $value =
                    eval("return (isset(\$this->_submitValues['$base']$idx)) ? \$this->_submitValues['$base']$idx : null;");
            }

            if ((is_array($value) || null === $value) && isset($this->_submitFiles[$base]))
            {
                $props = ['name', 'type', 'size', 'tmp_name', 'error'];
                $code =
                    "if (!isset(\$this->_submitFiles['$base']['name']$idx)) {\n" . "    return null;\n" . "} else {\n" .
                    "    \$v = array();\n";
                foreach ($props as $prop)
                {
                    $code .= "    \$v = HTML_QuickForm::arrayMerge(\$v, \$this->_reindexFiles(\$this->_submitFiles['$base']['$prop']$idx, '$prop'));\n";
                }
                $fileValue = eval($code . "    return \$v;\n}\n");
                if (null !== $fileValue)
                {
                    $value = null === $value ? $fileValue : HTML_QuickForm::arrayMerge($value, $fileValue);
                }
            }
        }

        // This is only supposed to work for groups with appendName = false
        if (null === $value && 'group' == $this->getElementType($elementName))
        {
            $group =& $this->getElement($elementName);
            $elements =& $group->getElements();
            foreach (array_keys($elements) as $key)
            {
                $name = $group->getElementName($key);
                // prevent endless recursion in case of radios and such
                if ($name != $elementName)
                {
                    if (null !== ($v = $this->getSubmitValue($name)))
                    {
                        $value[$name] = $v;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Returns the values submitted by the form
     *
     * @param bool      Whether uploaded files should be returned too
     *
     * @return    array
     */
    public function getSubmitValues($mergeFiles = false)
    {
        return $mergeFiles ? HTML_QuickForm::arrayMerge($this->_submitValues, $this->_submitFiles) :
            $this->_submitValues;
    }

    /**
     * Returns the client side validation script
     *
     * @return    string    Javascript to perform validation, empty string if no 'client' rules were added
     * @throws \QuickformException
     */
    public function getValidationScript()
    {
        if (empty($this->_rules) || empty($this->_attributes['onsubmit']))
        {
            return '';
        }

        $registry =& HTML_QuickForm_RuleRegistry::singleton();
        $test = [];
        $js_escape = [
            "\r" => '\r',
            "\n" => '\n',
            "\t" => '\t',
            "'" => "\\'",
            '"' => '\"',
            '\\' => '\\\\'
        ];

        foreach ($this->_rules as $elementName => $rules)
        {
            foreach ($rules as $rule)
            {
                if ('client' == $rule['validation'])
                {
                    unset($element);

                    $dependent = isset($rule['dependent']) && is_array($rule['dependent']);
                    $rule['message'] = strtr($rule['message'], $js_escape);

                    if (isset($rule['group']))
                    {
                        $group =& $this->getElement($rule['group']);
                        // No JavaScript validation for frozen elements
                        if ($group->isFrozen())
                        {
                            continue 2;
                        }
                        $elements =& $group->getElements();
                        foreach (array_keys($elements) as $key)
                        {
                            if ($elementName == $group->getElementName($key))
                            {
                                $element =& $elements[$key];
                                break;
                            }
                        }
                    }
                    elseif ($dependent)
                    {
                        $element = [];
                        $element[] =& $this->getElement($elementName);
                        foreach ($rule['dependent'] as $elName)
                        {
                            $element[] =& $this->getElement($elName);
                        }
                    }
                    else
                    {
                        $element =& $this->getElement($elementName);
                    }
                    // No JavaScript validation for frozen elements
                    if (is_object($element) && $element->isFrozen())
                    {
                        continue 2;
                    }
                    elseif (is_array($element))
                    {
                        foreach (array_keys($element) as $key)
                        {
                            if ($element[$key]->isFrozen())
                            {
                                continue 3;
                            }
                        }
                    }

                    $test[] = $registry->getValidationScript($element, $elementName, $rule);
                }
            }
        }
        if (count($test) > 0)
        {
            return "\n<script type=\"text/javascript\">\n" . "//<![CDATA[\n" . 'function validate_' .
                $this->_attributes['id'] . "(frm) {\n" . "  var value = '';\n" . "  var errFlag = new Array();\n" .
                "  var _qfGroups = {};\n" . "  _qfMsg = '';\n\n" . join("\n", $test) . "\n  if (_qfMsg != '') {\n" .
                "    _qfMsg = '" . strtr($this->_jsPrefix, $js_escape) . "' + _qfMsg;\n" .
                "    _qfMsg = _qfMsg + '\\n" . strtr($this->_jsPostfix, $js_escape) . "';\n" . "    alert(_qfMsg);\n" .
                "    return false;\n" . "  }\n" . "  return true;\n" . "}\n" . "//]]>\n" . '</script>';
        }

        return '';
    }

    /**
     * Inserts a new element right before the other element
     * Warning: it is not possible to check whether the $element is already
     * added to the form, therefore if you want to move the existing form
     * element to a new position, you'll have to use removeElement():
     * $form->insertElementBefore($form->removeElement('foo', false), 'bar');
     *
     * @param object  HTML_QuickForm_element  Element to insert
     * @param string  Name of the element before which the new one is inserted
     *
     * @return   object  HTML_QuickForm_element  reference to inserted element
     * @throws   QuickformException
     */
    public function &insertElementBefore(&$element, $nameAfter)
    {
        if (!empty($this->_duplicateIndex[$nameAfter]))
        {
            throw new QuickformException(
                'Several elements named "' . $nameAfter . '" exist in HTML_QuickForm::insertElementBefore().'
            );
        }
        elseif (!$this->elementExists($nameAfter))
        {
            throw new QuickformException(
                "Element '$nameAfter' does not exist in HTML_QuickForm::insertElementBefore()"
            );
        }
        $elementName = $element->getName();
        $targetIdx = $this->_elementIndex[$nameAfter];
        $duplicate = false;
        // Like in addElement(), check that it's not an incompatible duplicate
        if (!empty($elementName) && isset($this->_elementIndex[$elementName]))
        {
            if ($this->_elements[$this->_elementIndex[$elementName]]->getType() != $element->getType())
            {
                throw new QuickformException(
                    "Element '$elementName' already exists in HTML_QuickForm::insertElementBefore()"
                );
            }
            $duplicate = true;
        }
        // Move all the elements after added back one place, reindex _elementIndex and/or _duplicateIndex
        $elKeys = array_keys($this->_elements);
        for ($i = end($elKeys); $i >= $targetIdx; $i --)
        {
            if (isset($this->_elements[$i]))
            {
                $currentName = $this->_elements[$i]->getName();
                $this->_elements[$i + 1] =& $this->_elements[$i];
                if ($this->_elementIndex[$currentName] == $i)
                {
                    $this->_elementIndex[$currentName] = $i + 1;
                }
                else
                {
                    if (!empty($currentName))
                    {
                        $dupIdx = array_search($i, $this->_duplicateIndex[$currentName]);
                        $this->_duplicateIndex[$currentName][$dupIdx] = $i + 1;
                    }
                }
                unset($this->_elements[$i]);
            }
        }
        // Put the element in place finally
        $this->_elements[$targetIdx] =& $element;
        if (!$duplicate)
        {
            $this->_elementIndex[$elementName] = $targetIdx;
        }
        else
        {
            $this->_duplicateIndex[$elementName][] = $targetIdx;
        }
        $element->onQuickFormEvent('updateValue', null, $this);
        if ($this->_freezeAll)
        {
            $element->freeze();
        }
        // If not done, the elements will appear in reverse order
        ksort($this->_elements);

        return $element;
    }

    /**
     * Returns whether or not the form element is frozen
     *
     * @param string $element Form element name
     *
     * @return    bool
     */
    public function isElementFrozen($element)
    {
        if (isset($this->_elementIndex[$element]))
        {
            return $this->_elements[$this->_elementIndex[$element]]->isFrozen();
        }

        return false;
    }

    /**
     * Returns whether or not the form element is required
     *
     * @param string $element Form element name
     *
     * @return    bool
     */
    public function isElementRequired($element)
    {
        return in_array($element, $this->_required, true);
    }

    /**
     * Tell whether a result from a QuickForm method is an error (an instance of HTML_QuickForm_Error)
     *
     * @param mixed     result code
     *
     * @return bool     whether $value is an error
     */
    public static function isError($value)
    {
        return (is_object($value) && is_a($value, 'html_quickform_error'));
    }

    /**
     * Returns whether or not the whole form is frozen
     *
     * @return    bool
     */
    public function isFrozen()
    {
        return $this->_freezeAll;
    }

    /**
     * Returns whether or not the given rule is supported
     *
     * @param string $name Validation rule name
     * @param bool     Whether to automatically register subclasses of HTML_QuickForm_Rule
     *
     * @return    mixed    true if previously registered, false if not, new rule name if auto-registering worked
     */
    public function isRuleRegistered($name, $autoRegister = false)
    {
        if (is_scalar($name) && isset($GLOBALS['_HTML_QuickForm_registered_rules'][$name]))
        {
            return true;
        }
        elseif (!$autoRegister)
        {
            return false;
        }
        // automatically register the rule if requested
        $ruleName = false;
        if (is_object($name) && is_a($name, 'html_quickform_rule'))
        {
            $ruleName = !empty($name->name) ? $name->name : strtolower(get_class($name));
        }
        elseif (is_string($name) && class_exists($name))
        {
            $parent = strtolower($name);
            do
            {
                if ('html_quickform_rule' == strtolower($parent))
                {
                    $ruleName = strtolower($name);
                    break;
                }
            }
            while ($parent = get_parent_class($parent));
        }
        if ($ruleName)
        {
            $registry =& HTML_QuickForm_RuleRegistry::singleton();
            $registry->registerRule($ruleName, null, $name);
        }

        return $ruleName;
    }

    /**
     * Tells whether the form was already submitted
     * This is useful since the _submitFiles and _submitValues arrays
     * may be completely empty after the trackSubmit value is removed.
     *
     * @return bool
     */
    public function isSubmitted()
    {
        return $this->_flagSubmitted;
    }

    /**
     * Returns whether or not the form element type is supported
     *
     * @param string $type Form element type
     *
     * @return    bool
     */
    public function isTypeRegistered($type)
    {
        return isset($GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES'][strtolower($type)]);
    }

    /**
     * Performs the form data processing
     *
     * @param mixed $callback  Callback, either function name or array(&$object, 'method')
     * @param bool $mergeFiles Whether uploaded files should be processed too
     *
     * @throws   QuickformException
     */
    public function process($callback, $mergeFiles = true)
    {
        if (!is_callable($callback))
        {
            throw new QuickformException('Callback function does not exist in QuickForm::process()');
        }
        $values = ($mergeFiles === true) ? HTML_QuickForm::arrayMerge($this->_submitValues, $this->_submitFiles) :
            $this->_submitValues;

        return call_user_func($callback, $values);
    }

    /**
     * Registers a new element type
     *
     * @param string $typeName  Name of element type
     * @param string $className Element class name
     *
     * @return    void
     */
    public static function registerElementType($typeName, $className)
    {
        $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES'][strtolower($typeName)] = $className;
    }

    /**
     * Registers a new validation rule
     *
     * @param string $ruleName Name of validation rule
     * @param string $type     Either: 'regex', 'function' or 'rule' for an HTML_QuickForm_Rule object
     * @param string $data1    Name of function, regular expression or HTML_QuickForm_Rule classname
     * @param string $data2    Object parent of above function or HTML_QuickForm_Rule file path
     *
     * @return    void
     */
    public static function registerRule($ruleName, $type, $data1, $data2 = null)
    {
        $registry =& HTML_QuickForm_RuleRegistry::singleton();
        $registry->registerRule($ruleName, $type, $data1, $data2);
    }

    /**
     * Removes an element
     * The method "unlinks" an element from the form, returning the reference
     * to the element object. If several elements named $elementName exist,
     * it removes the first one, leaving the others intact.
     *
     * @param string $elementName The element name
     * @param bool $removeRules   True if rules for this element are to be removed too
     *
     * @return object HTML_QuickForm_element    a reference to the removed element
     * @throws QuickformException
     */
    public function &removeElement($elementName, $removeRules = true)
    {
        if (!isset($this->_elementIndex[$elementName]))
        {
            throw new QuickformException("Element '$elementName' does not exist in HTML_QuickForm::removeElement()");
        }
        $el =& $this->_elements[$this->_elementIndex[$elementName]];
        unset($this->_elements[$this->_elementIndex[$elementName]]);
        if (empty($this->_duplicateIndex[$elementName]))
        {
            unset($this->_elementIndex[$elementName]);
        }
        else
        {
            $this->_elementIndex[$elementName] = array_shift($this->_duplicateIndex[$elementName]);
        }
        if ($removeRules)
        {
            unset($this->_rules[$elementName], $this->_errors[$elementName]);
        }

        return $el;
    }

    /**
     * Initializes constant form values.
     * These values won't get overridden by POST or GET vars
     *
     * @param array $constantValues values used to fill the form
     * @param mixed $filter         (optional) filter(s) to apply to all default values
     *
     * @return    void
     * @throws \QuickformException
     */
    public function setConstants($constantValues = null, $filter = null)
    {
        if (is_array($constantValues))
        {
            if (isset($filter))
            {
                if (is_array($filter) && (2 != count($filter) || !is_callable($filter)))
                {
                    foreach ($filter as $val)
                    {
                        if (!is_callable($val))
                        {
                            throw new QuickformException(
                                'Callback function does not exist in QuickForm::setConstants()'
                            );
                        }
                        else
                        {
                            $constantValues = $this->_recursiveFilter($val, $constantValues);
                        }
                    }
                }
                elseif (!is_callable($filter))
                {
                    throw new QuickformException('Callback function does not exist in QuickForm::setConstants()');
                }
                else
                {
                    $constantValues = $this->_recursiveFilter($filter, $constantValues);
                }
            }
            $this->_constantValues = HTML_QuickForm::arrayMerge($this->_constantValues, $constantValues);
            foreach (array_keys($this->_elements) as $key)
            {
                $this->_elements[$key]->onQuickFormEvent('updateValue', null, $this);
            }
        }
    }

    /**
     * Sets a datasource object for this form object
     * Datasource default and constant values will feed the QuickForm object if
     * the datasource implements defaultValues() and constantValues() methods.
     *
     * @param object $datasource     datasource object implementing the informal datasource protocol
     * @param mixed $defaultsFilter  string or array of filter(s) to apply to default values
     * @param mixed $constantsFilter string or array of filter(s) to apply to constants values
     *
     * @return    void
     * @throws \QuickformException
     */
    public function setDatasource(&$datasource, $defaultsFilter = null, $constantsFilter = null)
    {
        if (is_object($datasource))
        {
            $this->_datasource =& $datasource;
            if (is_callable([$datasource, 'defaultValues']))
            {
                $this->setDefaults($datasource->defaultValues($this), $defaultsFilter);
            }
            if (is_callable([$datasource, 'constantValues']))
            {
                $this->setConstants($datasource->constantValues($this), $constantsFilter);
            }
        }
        else
        {
            throw new QuickformException('Datasource is not an object in QuickForm::setDatasource()');
        }
    }

    /**
     * Initializes default form values
     *
     * @param array $defaultValues values used to fill the form
     * @param mixed $filter        (optional) filter(s) to apply to all default values
     *
     * @return    void
     * @throws \QuickformException
     */
    public function setDefaults($defaultValues = [], $filter = null)
    {
        if (is_array($defaultValues))
        {
            if (isset($filter))
            {
                if (is_array($filter) && (2 != count($filter) || !is_callable($filter)))
                {
                    foreach ($filter as $val)
                    {
                        if (!is_callable($val))
                        {
                            throw new QuickformException(
                                'Callback function does not exist in QuickForm::setDefaults()'
                            );
                        }
                        else
                        {
                            $defaultValues = $this->_recursiveFilter($val, $defaultValues);
                        }
                    }
                }
                elseif (!is_callable($filter))
                {
                    throw new QuickformException('Callback function does not exist in QuickForm::setDefaults()');
                }
                else
                {
                    $defaultValues = $this->_recursiveFilter($filter, $defaultValues);
                }
            }
            $this->_defaultValues = HTML_QuickForm::arrayMerge($this->_defaultValues, $defaultValues);
            foreach (array_keys($this->_elements) as $key)
            {
                $this->_elements[$key]->onQuickFormEvent('updateValue', null, $this);
            }
        }
    }

    /**
     * Set error message for a form element
     *
     * @param string $element Name of form element to set error for
     * @param string $message Error message, if empty then removes the current error message
     *
     * @return    void
     */
    public function setElementError($element, $message = null)
    {
        if (!empty($message))
        {
            $this->_errors[$element] = $message;
        }
        else
        {
            unset($this->_errors[$element]);
        }
    }

    /**
     * Sets JavaScript warning messages
     *
     * @param string $pref Prefix warning
     * @param string $post Postfix warning
     *
     * @return    void
     */
    public function setJsWarnings($pref, $post)
    {
        $this->_jsPrefix = $pref;
        $this->_jsPostfix = $post;
    }

    /**
     * Returns an HTML version of the form
     *
     * @param string $in_data (optional) Any extra data to insert right
     *                        before form is rendered.  Useful when using templates.
     *
     * @return   string     Html version of the form
     * @throws \QuickformException
     */
    public function toHtml($in_data = null)
    {
        if (!is_null($in_data))
        {
            $this->addElement('html', $in_data);
        }
        $renderer =& $this->defaultRenderer();
        $this->accept($renderer);

        return $renderer->toHtml();
    }

    /**
     * Updates Attributes for one or more elements
     *
     * @param mixed $elements Array of element names/objects or string of elements to be updated
     * @param mixed $attrs    Array or sting of html attributes
     *
     * @return     void
     */
    public function updateElementAttr($elements, $attrs)
    {
        if (is_string($elements))
        {
            $elements = preg_split('/[ ]?,[ ]?/', $elements);
        }
        foreach (array_keys($elements) as $key)
        {
            if (is_object($elements[$key]) && is_a($elements[$key], 'HTML_QuickForm_element'))
            {
                $elements[$key]->updateAttributes($attrs);
            }
            elseif (isset($this->_elementIndex[$elements[$key]]))
            {
                $this->_elements[$this->_elementIndex[$elements[$key]]]->updateAttributes($attrs);
                if (isset($this->_duplicateIndex[$elements[$key]]))
                {
                    foreach ($this->_duplicateIndex[$elements[$key]] as $index)
                    {
                        $this->_elements[$index]->updateAttributes($attrs);
                    }
                }
            }
        }
    }

    /**
     * Performs the server side validation
     *
     * @return    bool   true if no error found
     * @throws \QuickformException
     */
    public function validate()
    {
        if (count($this->_rules) == 0 && count($this->_formRules) == 0 && $this->isSubmitted())
        {
            return (0 == count($this->_errors));
        }
        elseif (!$this->isSubmitted())
        {
            return false;
        }

        $registry =& HTML_QuickForm_RuleRegistry::singleton();

        foreach ($this->_rules as $target => $rules)
        {
            $submitValue = $this->getSubmitValue($target);

            foreach ($rules as $rule)
            {
                if ((isset($rule['group']) && isset($this->_errors[$rule['group']])) || isset($this->_errors[$target]))
                {
                    continue 2;
                }
                // If element is not required and is empty, we shouldn't validate it
                if (!$this->isElementRequired($target))
                {
                    if (!isset($submitValue) || '' == $submitValue)
                    {
                        continue 2;
                        // Fix for bug #3501: we shouldn't validate not uploaded files, either.
                        // Unfortunately, we can't just use $element->isUploadedFile() since
                        // the element in question can be buried in group. Thus this hack.
                    }
                    elseif (is_array($submitValue))
                    {
                        if (false === ($pos = strpos($target, '[')))
                        {
                            $isUpload = !empty($this->_submitFiles[$target]);
                        }
                        else
                        {
                            $base = substr($target, 0, $pos);
                            $idx = "['" . str_replace([']', '['], ['', "']['"], substr($target, $pos + 1, - 1)) . "']";
                            eval("\$isUpload = isset(\$this->_submitFiles['$base']['name']$idx);");
                        }
                        if ($isUpload && (!isset($submitValue['error']) || 0 != $submitValue['error']))
                        {
                            continue 2;
                        }
                    }
                }
                if (isset($rule['dependent']) && is_array($rule['dependent']))
                {
                    $values = [$submitValue];
                    foreach ($rule['dependent'] as $elName)
                    {
                        $values[] = $this->getSubmitValue($elName);
                    }
                    $result = $registry->validate($rule['type'], $values, $rule['format'], true);
                }
                elseif (is_array($submitValue) && !isset($rule['howmany']))
                {
                    $result = $registry->validate($rule['type'], $submitValue, $rule['format'], true);
                }
                else
                {
                    $result = $registry->validate($rule['type'], $submitValue, $rule['format'], false);
                }

                if (!$result || (!empty($rule['howmany']) && $rule['howmany'] > (int) $result))
                {
                    if (isset($rule['group']))
                    {
                        $this->_errors[$rule['group']] = $rule['message'];
                    }
                    else
                    {
                        $this->_errors[$target] = $rule['message'];
                    }
                }
            }
        }

        // process the global rules now
        foreach ($this->_formRules as $rule)
        {
            if (true !== ($res = call_user_func($rule, $this->_submitValues, $this->_submitFiles)))
            {
                if (is_array($res))
                {
                    $this->_errors += $res;
                }
                else
                {
                    throw new QuickformException(
                        'Form rule callback returned invalid value in HTML_QuickForm::validate()'
                    );
                }
            }
        }

        return (0 == count($this->_errors));
    }

} // end class HTML_QuickForm