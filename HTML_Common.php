<?php

/**
 * Base class for all HTML classes
 * PHP versions 4 and 5
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category    HTML
 * @package     HTML_Common
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id$
 * @link        http://pear.php.net/package/HTML_Common/
 */
abstract class HTML_Common
{

    public string $_tab = "\11";

    protected array $_attributes = [];

    protected string $_comment = '';

    protected string $_lineEnd = "\12";

    protected int $_tabOffset = 0;

    /**
     * @param ?array|?string $attributes Associative array or name="value" pairs
     */
    public function __construct($attributes = null, int $tabOffset = 0)
    {
        $this->setAttributes($attributes);
        $this->setTabOffset($tabOffset);
    }

    /**
     * Returns the array key for the given non-name-value pair attribute
     */
    protected function _getAttrKey(string $attr, array $attributes): ?bool
    {
        if (isset($attributes[strtolower($attr)]))
        {
            return true;
        }
        else
        {
            return null;
        }
    }

    /**
     * Returns an HTML formatted attribute string
     *
     * @param array|string $attributes
     */
    protected function _getAttrString($attributes): string
    {
        $strAttr = '';

        if (is_array($attributes))
        {
            foreach ($attributes as $key => $value)
            {
                $strAttr .= ' ' . $key . '="' . htmlspecialchars($value, ENT_COMPAT) . '"';
            }
        }

        return $strAttr;
    }

    protected function _getLineEnd(): string
    {
        return $this->_lineEnd;
    }

    /**
     * Sets the line end style to Windows, Mac, Unix or a custom string.
     *
     * @param string $style "win", "mac", "unix" or custom string.
     */
    public function setLineEnd(string $style)
    {
        switch ($style)
        {
            case 'win':
                $this->_lineEnd = "\15\12";
                break;
            case 'unix':
                $this->_lineEnd = "\12";
                break;
            case 'mac':
                $this->_lineEnd = "\15";
                break;
            default:
                $this->_lineEnd = $style;
        }
    }

    /**
     * Returns a string containing the unit for indenting HTML
     */
    protected function _getTab(): string
    {
        return $this->_tab;
    }

    /**
     * Sets the string used to indent HTML
     *
     * @param string $string String used to indent ("\11", "\t", '  ', etc.).
     */
    public function setTab(string $string)
    {
        $this->_tab = $string;
    }

    /**
     * Returns a string containing the offset for the whole HTML code
     */
    protected function _getTabs(): string
    {
        return str_repeat($this->_getTab(), $this->_tabOffset);
    }

    /**
     * Returns a valid atrributes array from either a string or array
     *
     * @param array|string $attributes Either a typical HTML attribute string or an associative array
     */
    protected function _parseAttributes($attributes): array
    {
        if (is_array($attributes))
        {
            $ret = [];

            foreach ($attributes as $key => $value)
            {
                if (is_int($key))
                {
                    $key = $value = strtolower($value);
                }
                else
                {
                    $key = strtolower($key);
                }

                $ret[$key] = $value;
            }

            return $ret;
        }
        elseif (is_string($attributes))
        {
            $preg = "/(([A-Za-z_:]|[^\\x00-\\x7F])([A-Za-z0-9_:.-]|[^\\x00-\\x7F])*)" .
                "([ \\n\\t\\r]+)?(=([ \\n\\t\\r]+)?(\"[^\"]*\"|'[^']*'|[^ \\n\\t\\r]*))?/";

            if (preg_match_all($preg, $attributes, $regs))
            {
                $arrAttr = [];

                for ($counter = 0; $counter < count($regs[1]); $counter ++)
                {
                    $name = $regs[1][$counter];
                    $check = $regs[0][$counter];
                    $value = $regs[7][$counter];

                    if (trim($name) == trim($check))
                    {
                        $arrAttr[strtolower(trim($name))] = strtolower(trim($name));
                    }
                    else
                    {
                        if (substr($value, 0, 1) == "\"" || substr($value, 0, 1) == "'")
                        {
                            $value = substr($value, 1, - 1);
                        }

                        $arrAttr[strtolower(trim($name))] = trim($value);
                    }
                }

                return $arrAttr;
            }
        }

        return [];
    }

    protected function _removeAttr(string $attr, array $attributes)
    {
        $attr = strtolower($attr);

        if (isset($attributes[$attr]))
        {
            unset($attributes[$attr]);
        }
    }

    /**
     * Updates the attributes in $attr1 with the values in $attr2 without changing the other existing attributes
     *
     * @param array $attr1 Original attributes array
     * @param array $attr2 New attributes array
     */
    protected function _updateAttrArray(array &$attr1, array $attr2)
    {
        foreach ($attr2 as $key => $value)
        {
            $attr1[$key] = $value;
        }
    }

    public function getAttribute(string $attr): ?string
    {
        $attr = strtolower($attr);
        if (isset($this->_attributes[$attr]))
        {
            return $this->_attributes[$attr];
        }

        return null;
    }

    /**
     * @return array|string
     */
    public function getAttributes(bool $asString = false)
    {
        if ($asString)
        {
            return $this->_getAttrString($this->_attributes);
        }
        else
        {
            return $this->_attributes;
        }
    }

    /**
     * @param array|string $attributes Either a typical HTML attribute string or an associative array
     */
    public function setAttributes($attributes)
    {
        $this->_attributes = $this->_parseAttributes($attributes);
    }

    public function getComment(): string
    {
        return $this->_comment;
    }

    public function setComment(string $comment)
    {
        $this->_comment = $comment;
    }

    public function getTabOffset(): int
    {
        return $this->_tabOffset;
    }

    public function setTabOffset(int $offset)
    {
        $this->_tabOffset = $offset;
    }

    public function removeAttribute(string $attr)
    {
        $this->_removeAttr($attr, $this->_attributes);
    }

    public function setAttribute(string $name, ?string $value = null)
    {
        $name = strtolower($name);

        if (is_null($value))
        {
            $value = $name;
        }

        $this->_attributes[$name] = $value;
    }

    abstract public function toHtml(): string;

    /**
     * Updates the passed attributes without changing the other existing attributes
     *
     * @param mixed $attributes Either a typical HTML attribute string or an associative array
     */
    public function updateAttributes($attributes)
    {
        $this->_updateAttrArray($this->_attributes, $this->_parseAttributes($attributes));
    }
}