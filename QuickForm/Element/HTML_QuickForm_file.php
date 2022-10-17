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

// register file-related rules
if (class_exists('HTML_QuickForm'))
{
    HTML_QuickForm::registerRule('uploadedfile', 'callback', '_ruleIsUploadedFile', 'HTML_QuickForm_file');
    HTML_QuickForm::registerRule('maxfilesize', 'callback', '_ruleCheckMaxFileSize', 'HTML_QuickForm_file');
    HTML_QuickForm::registerRule('mimetype', 'callback', '_ruleCheckMimeType', 'HTML_QuickForm_file');
    HTML_QuickForm::registerRule('filename', 'callback', '_ruleCheckFileName', 'HTML_QuickForm_file');
}

/**
 * HTML class for a file type element
 *
 * @author       Adam Daniel <adaniel1@eesus.jnj.com>
 * @author       Bertrand Mansion <bmansion@mamasam.com>
 */
class HTML_QuickForm_file extends HTML_QuickForm_input
{

    public ?array $_value = null;

    /**
     * @param ?array|?string $attributes Associative array of tag attributes or HTML attributes name="value" pairs
     */
    public function __construct(?string $elementName = null, ?string $elementLabel = null, $attributes = null)
    {
        parent::__construct($elementName, $elementLabel, $attributes);

        $this->setType('file');
    }

    /**
     * Tries to find the element value from the values array
     * Needs to be redefined here as $_FILES is populated differently from
     * other arrays when element name is of the form foo[bar]
     *
     * @return    mixed
     */
    protected function _findValue($values)
    {
        if (empty($_FILES))
        {
            return null;
        }

        $elementName = $this->getName();

        if (isset($_FILES[$elementName]))
        {
            return $_FILES[$elementName];
        }
        elseif (false !== ($pos = strpos($elementName, '[')))
        {
            $base = substr($elementName, 0, $pos);
            $idx = "['" . str_replace([']', '['], ['', "']['"], substr($elementName, $pos + 1, - 1)) . "']";
            $props = ['name', 'type', 'size', 'tmp_name', 'error'];
            $code =
                "if (!isset(\$_FILES['" . $base . "']['name']" . $idx . ")) {\n" . "    return null;\n" . "} else {\n" .
                "    \$value = array();\n";

            foreach ($props as $prop)
            {
                $code .= "    \$value['" . $prop . "'] = \$_FILES['" . $base . "']['" . $prop . "']" . $idx . ";\n";
            }

            return eval($code . "    return \$value;\n}\n");
        }
        else
        {
            return null;
        }
    }

    /**
     * Checks if the given element contains an uploaded file of the filename regex
     *
     * @param array $elementValue Uploaded file info (from $_FILES)
     * @param string $regex       Regular expression
     *
     * @return bool true if name matches regex, false otherwise
     */
    protected function _ruleCheckFileName(array $elementValue, string $regex): bool
    {
        if (!HTML_QuickForm_file::_ruleIsUploadedFile($elementValue))
        {
            return true;
        }

        return preg_match($regex, $elementValue['name']);
    }

    /**
     * Checks that the file does not exceed the max file size
     *
     * @param array $elementValue Uploaded file info (from $_FILES)
     * @param int $maxSize        Max file size
     *
     * @return bool true if filesize is lower than maxsize, false otherwise
     */
    protected function _ruleCheckMaxFileSize(array $elementValue, int $maxSize): bool
    {
        if (!empty($elementValue['error']) &&
            (UPLOAD_ERR_FORM_SIZE == $elementValue['error'] || UPLOAD_ERR_INI_SIZE == $elementValue['error']))
        {
            return false;
        }

        if (!HTML_QuickForm_file::_ruleIsUploadedFile($elementValue))
        {
            return true;
        }

        return ($maxSize >= filesize($elementValue['tmp_name']));
    }

    /**
     * Checks if the given element contains an uploaded file of the right mime type
     *
     * @param array $elementValue Uploaded file info (from $_FILES)
     * @param mixed $mimeType     Mime Type (can be an array of allowed types)
     *
     * @return bool true if mimetype is correct, false otherwise
     */
    protected function _ruleCheckMimeType(array $elementValue, $mimeType): bool
    {
        if (!HTML_QuickForm_file::_ruleIsUploadedFile($elementValue))
        {
            return true;
        }

        if (is_array($mimeType))
        {
            return in_array($elementValue['type'], $mimeType);
        }

        return $elementValue['type'] == $mimeType;
    }

    /**
     * Checks if the given element contains an uploaded file
     *
     * @param array $elementValue Uploaded file info (from $_FILES)
     *
     * @return    bool      true if file has been uploaded, false otherwise
     */
    protected static function _ruleIsUploadedFile(array $elementValue): bool
    {
        if ((isset($elementValue['error']) && $elementValue['error'] == 0) ||
            (!empty($elementValue['tmp_name']) && $elementValue['tmp_name'] != 'none'))
        {
            return is_uploaded_file($elementValue['tmp_name']);
        }
        else
        {
            return false;
        }
    }

    public function freeze()
    {
    }

    public function getSize(): int
    {
        return $this->getAttribute('size');
    }

    public function getValue(): array
    {
        return $this->_value;
    }

    /**
     * Sets value for file element.
     * Actually this does nothing. The function is defined here to override
     * HTML_Quickform_input's behaviour of setting the 'value' attribute. As
     * no sane user-agent uses <input type="file">'s value for anything
     * (because of security implications) we implement file's value as a
     * read-only property with a special meaning.
     *
     * @param mixed $value Value for file element
     */
    public function setValue($value)
    {
    }

    /**
     * Checks if the element contains an uploaded file
     *
     * @return bool true if file has been uploaded, false otherwise
     */
    public function isUploadedFile(): bool
    {
        return self::_ruleIsUploadedFile($this->_value);
    }

    /**
     * Moves an uploaded file into the destination
     *
     * @param string $dest     Destination directory path
     * @param string $fileName New file name
     *
     * @return bool Whether the file was moved successfully
     */
    public function moveUploadedFile(string $dest, string $fileName = ''): bool
    {
        if ($dest != '' && substr($dest, - 1) != '/')
        {
            $dest .= '/';
        }

        $fileName = ($fileName != '') ? $fileName : basename($this->_value['name']);

        if (move_uploaded_file($this->_value['tmp_name'], $dest . $fileName))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param string $event           Name of event
     * @param mixed $arg              event arguments
     * @param ?HTML_QuickForm $caller calling object
     *
     * @throws \QuickformException
     */
    public function onQuickFormEvent(string $event, $arg, ?HTML_QuickForm $caller = null): bool
    {
        switch ($event)
        {
            case 'updateValue':
                if ($caller->getAttribute('method') == 'get')
                {
                    throw new QuickformException('Cannot add a file upload field to a GET method form');
                }

                $values = [];
                $this->_value = $this->_findValue($values);
                $caller->updateAttributes(['enctype' => 'multipart/form-data']);
                $caller->setMaxFileSize();
                break;
            case 'addElement':
                $this->onQuickFormEvent('createElement', $arg, $caller);

                return $this->onQuickFormEvent('updateValue', null, $caller);
            case 'createElement':
                static::__construct($arg[0], $arg[1], $arg[2]);
                break;
        }

        return true;
    }

    public function setSize(?int $size)
    {
        $this->updateAttributes(['size' => $size]);
    }

}

