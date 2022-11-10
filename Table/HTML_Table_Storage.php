<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Storage class for HTML::Table data
 * This class stores data for tables built with HTML_Table. When having
 * more than one instance, it can be used for grouping the table into the
 * parts <thead>...</thead>, <tfoot>...</tfoot> and <tbody>...</tbody>.
 * PHP versions 4 and 5
 * LICENSE:
 * Copyright (c) 2005-2007, Adam Daniel <adaniel1@eesus.jnj.com>,
 *          Bertrand Mansion <bmansion@mamasam.com>,
 *          Mark Wiesemann <wiesemann@php.net>
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *  * Redistributions of source code must retain the above copyright
 *  notice, this list of conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright
 *  notice, this list of conditions and the following disclaimer in the
 *  documentation and/or other materials provided with the distribution.
 *  * The names of the authors may not be used to endorse or promote products
 *  derived from this software without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category HTML
 * @package  HTML_Table
 * @author   Adam Daniel <adaniel1@eesus.jnj.com>
 * @author   Bertrand Mansion <bmansion@mamasam.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     http://pear.php.net/package/HTML_Table
 */

/**
 * Storage class for HTML::Table data
 * This class stores data for tables built with HTML_Table. When having more than one instance, it can be used for
 * grouping the table into the parts <thead>...</thead>, <tfoot>...</tfoot> and <tbody>...</tbody>.
 *
 * @category   HTML
 * @package    HTML_Table
 * @author     Adam Daniel <adaniel1@eesus.jnj.com>
 * @author     Bertrand Mansion <bmansion@mamasam.com>
 * @author     Mark Wiesemann <wiesemann@php.net>
 * @copyright  2005-2006 The PHP Group
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://pear.php.net/package/HTML_Table
 */
class HTML_Table_Storage extends HTML_Common
{

    /**
     * Value to insert into empty cells
     */
    protected string $_autoFill = '&nbsp;';

    /**
     * Automatically adds a new row or column if a given row or column index does not exist
     */
    protected bool $_autoGrow = true;

    protected int $_cols = 0;

    protected int $_nestLevel = 0;

    protected int $_rows = 0;

    /**
     * Array containing the table structure
     */
    protected array $_structure = [];

    /**
     * Whether to use <thead>, <tfoot> and <tbody> or not
     */
    protected bool $_useTGroups;

    /**
     * @param bool $useTGroups Whether to use <thead>, <tfoot> and
     *                         <tbody> or not
     */
    public function __construct(int $tabOffset = 0, bool $useTGroups = false)
    {
        parent::__construct(null, $tabOffset);
        $this->_useTGroups = $useTGroups;
    }

    /**
     * Adjusts ends (total number of rows and columns)
     *
     * @param string $method    Method name of caller. Used to populate \Exception if thrown.
     * @param array $attributes Assoc array of attributes. Default is an empty array.
     *
     * @throws \TableException
     */
    protected function _adjustEnds(int $row, int $col, string $method, array $attributes = [])
    {
        $colspan = $attributes['colspan'] ?? 1;
        $rowspan = $attributes['rowspan'] ?? 1;

        if (($row + $rowspan - 1) >= $this->_rows)
        {
            if ($this->_autoGrow)
            {
                $this->_rows = $row + $rowspan;
            }
            else
            {
                throw new TableException(
                    'Invalid table row reference[' . $row . '] in HTML_Table::' . $method
                );
            }
        }

        if (($col + $colspan - 1) >= $this->_cols)
        {
            if ($this->_autoGrow)
            {
                $this->_cols = $col + $colspan;
            }
            else
            {
                throw new TableException(
                    'Invalid table column reference[' . $col . '] in HTML_Table::' . $method
                );
            }
        }
    }

    /**
     * Tells if the parameter is an array of attribute arrays/strings
     */
    protected function _isAttributesArray($attributes): bool
    {
        if (isset($attributes[0]))
        {
            if (is_array($attributes[0]) || (is_string($attributes[0]) && count($attributes) > 1))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets the cell contents for a single existing cell. If the given indices do not exist and autoGrow is true then
     * the given row and/or col is automatically added.  If autoGrow is false then an error is returned.
     *
     * @param mixed $contents May contain html or any object with a toHTML() method; if it is an array (with strings
     *                        and/or objects), $col will be used as start offset and the array elements will be set to
     *                        this and the following columns in $row
     * @param string $type    Cell type either 'TH' or 'TD'
     *
     * @throws \TableException
     */
    protected function _setSingleCellContents(int $row, int $col, $contents, string $type = 'TD')
    {
        if (isset($this->_structure[$row][$col]) && $this->_structure[$row][$col] == '__SPANNED__')
        {
            return;
        }

        $this->_adjustEnds($row, $col, 'setCellContents');
        $this->_structure[$row][$col]['contents'] = $contents;
        $this->_structure[$row][$col]['type'] = $type;
    }

    /**
     * Checks if rows or columns are spanned
     */
    protected function _updateSpanGrid(int $row, int $col)
    {
        if (isset($this->_structure[$row][$col]['attr']['colspan']))
        {
            $colspan = $this->_structure[$row][$col]['attr']['colspan'];
        }

        if (isset($this->_structure[$row][$col]['attr']['rowspan']))
        {
            $rowspan = $this->_structure[$row][$col]['attr']['rowspan'];
        }

        if (isset($colspan))
        {
            for ($j = $col + 1; (($j < $this->_cols) && ($j <= ($col + $colspan - 1))); $j ++)
            {
                $this->_structure[$row][$j] = '__SPANNED__';
            }
        }

        if (isset($rowspan))
        {
            for ($i = $row + 1; (($i < $this->_rows) && ($i <= ($row + $rowspan - 1))); $i ++)
            {
                $this->_structure[$i][$col] = '__SPANNED__';
            }
        }

        if (isset($colspan) && isset($rowspan))
        {
            for ($i = $row + 1; (($i < $this->_rows) && ($i <= ($row + $rowspan - 1))); $i ++)
            {
                for ($j = $col + 1; (($j <= $this->_cols) && ($j <= ($col + $colspan - 1))); $j ++)
                {
                    $this->_structure[$i][$j] = '__SPANNED__';
                }
            }
        }
    }

    /**
     * Adds a table column and returns the column identifier
     *
     * @param ?array $contents           Must be a indexed array of valid cell contents
     * @param ?array|?string $attributes Associative array or string of table row attributes
     * @param string $type               Cell type either 'th' or 'td'
     *
     * @return int
     * @throws \TableException
     */
    public function addCol(?array $contents = null, $attributes = null, string $type = 'td'): int
    {
        if (isset($contents) && !is_array($contents))
        {
            throw new TableException(
                'First parameter to HTML_Table::addCol ' . 'must be an array'
            );
        }

        if (is_null($contents))
        {
            $contents = [];
        }

        $type = strtolower($type);
        $col = $this->_cols ++;

        foreach ($contents as $row => $content)
        {
            if ($type == 'td')
            {
                $this->setCellContents($row, $col, $content);
            }
            elseif ($type == 'th')
            {
                $this->setHeaderContents($row, $col, $content);
            }
        }

        $this->setColAttributes($col, $attributes);

        return $col;
    }

    /**
     * Adds a table row and returns the row identifier
     *
     * @param ?array $contents           Must be a indexed array of valid cell contents
     * @param ?array|?string $attributes Associative array or string of table row attributes. This can also be an array
     *                                   of attributes, in which case the attributes will be repeated in a loop.
     * @param string $type               Cell type either 'th' or 'td'
     * @param bool $inTR                 false if attributes are to be applied in TD tags; true if attributes are to be
     *                                   applied in TR tag
     *
     * @return int
     * @throws \TableException
     */
    public function addRow(
        ?array $contents = null, $attributes = null, string $type = 'td', bool $inTR = false
    ): int
    {
        if (isset($contents) && !is_array($contents))
        {
            throw new TableException(
                'First parameter to HTML_Table::addRow ' . 'must be an array'
            );
        }

        if (is_null($contents))
        {
            $contents = [];
        }

        $type = strtolower($type);
        $row = $this->_rows ++;

        foreach ($contents as $col => $content)
        {
            if ($type == 'td')
            {
                $this->setCellContents($row, $col, $content);
            }
            elseif ($type == 'th')
            {
                $this->setHeaderContents($row, $col, $content);
            }
        }

        $this->setRowAttributes($row, $attributes, $inTR);

        return $row;
    }

    /**
     * Alternates the row attributes starting at $start
     *
     * @param ?array|?string $attributes1 Associative array or string of table row attributes
     * @param ?array|?string $attributes2 Associative array or string of table row attributes
     * @param bool $inTR                  false if attributes are to be applied in TD tags; true if attributes are to
     *                                    be applied in TR tag
     * @param int $firstAttributes        Which attributes should be applied to the first row, 1 or 2.
     *
     * @throws \TableException
     */
    public function altRowAttributes(
        int $start, $attributes1, $attributes2, bool $inTR = false, int $firstAttributes = 1
    )
    {
        for ($row = $start; $row < $this->_rows; $row ++)
        {
            if (($row + $start + ($firstAttributes - 1)) % 2 == 0)
            {
                $attributes = $attributes1;
            }
            else
            {
                $attributes = $attributes2;
            }
            $this->updateRowAttributes($row, $attributes, $inTR);
        }
    }

    public function getAutoFill(): string
    {
        return $this->_autoFill;
    }

    public function setAutoFill(string $fill)
    {
        $this->_autoFill = $fill;
    }

    public function getAutoGrow(): bool
    {
        return $this->_autoGrow;
    }

    public function setAutoGrow(bool $grow)
    {
        $this->_autoGrow = $grow;
    }

    /**
     * Returns the attributes for a given cell
     *
     * @throws \TableException
     */
    public function getCellAttributes(int $row, int $col): array
    {
        if (isset($this->_structure[$row][$col]) && $this->_structure[$row][$col] != '__SPANNED__')
        {
            return $this->_structure[$row][$col]['attr'];
        }
        elseif (!isset($this->_structure[$row][$col]))
        {
            throw new TableException(
                'Invalid table cell reference[' . $row . '][' . $col . '] in HTML_Table::getCellAttributes'
            );
        }

        return [];
    }

    /**
     * Returns the cell contents for an existing cell
     *
     * @return ?mixed
     * @throws \TableException
     */
    public function getCellContents(int $row, int $col)
    {
        if (isset($this->_structure[$row][$col]) && $this->_structure[$row][$col] == '__SPANNED__')
        {
            return null;
        }

        if (!isset($this->_structure[$row][$col]))
        {
            throw new TableException(
                'Invalid table cell reference[' . $row . '][' . $col . '] in HTML_Table::getCellContents'
            );
        }

        return $this->_structure[$row][$col]['contents'];
    }

    /**
     * Gets the number of columns in the table. If a row index is specified, the count will not take the spanned cells
     * into account in the return value.
     */
    public function getColCount(?int $row = null): int
    {
        if (!is_null($row))
        {
            $count = 0;

            foreach ($this->_structure[$row] as $cell)
            {
                if (is_array($cell))
                {
                    $count ++;
                }
            }

            return $count;
        }

        return $this->_cols;
    }

    /**
     * Returns the attributes for a given row as contained in the TR tag
     */
    public function getRowAttributes(int $row): array
    {
        if (isset($this->_structure[$row]['attr']))
        {
            return $this->_structure[$row]['attr'];
        }

        return [];
    }

    public function getRowCount(): int
    {
        return $this->_rows;
    }

    public function getUseTGroups(): bool
    {
        return $this->_useTGroups;
    }

    public function setUseTGroups(bool $useTGroups)
    {
        $this->_useTGroups = $useTGroups;
    }

    /**
     * Sets the attributes for all cells
     *
     * @param mixed $attributes Associative array or string of table row attributes
     *
     * @throws \TableException
     */
    public function setAllAttributes($attributes = null)
    {
        for ($i = 0; $i < $this->_rows; $i ++)
        {
            $this->setRowAttributes($i, $attributes);
        }
    }

    /**
     * Sets the cell attributes for an existing cell. If the given indices do not exist and autoGrow is true then the
     * given row and/or col is automatically added.  If autoGrow is false then an error is returned.
     *
     * @param ?array|?string $attributes Associative array or string of table row attributes
     *
     * @throws \TableException
     */
    public function setCellAttributes(int $row, int $col, $attributes)
    {
        if (isset($this->_structure[$row][$col]) && $this->_structure[$row][$col] == '__SPANNED__')
        {
            return;
        }

        $attributes = $this->_parseAttributes($attributes);
        $this->_adjustEnds($row, $col, 'setCellAttributes', $attributes);
        $this->_structure[$row][$col]['attr'] = $attributes;
        $this->_updateSpanGrid($row, $col);
    }

    /**
     * Sets the cell contents for an existing cell. If the given indices do not exist and autoGrow is true then the
     * given row and/or col is automatically added.  If autoGrow is false then an error is returned.
     *
     * @param mixed $contents May contain html or any object with a toHTML() method; if it is an array (with strings
     *                        and/or objects), $col will be used as start offset and the array elements will be set to
     *                        this and the following columns in $row
     * @param string $type    Cell type either 'TH' or 'TD'
     *
     * @throws \TableException
     */
    public function setCellContents(int $row, int $col, $contents, string $type = 'TD')
    {
        if (is_array($contents))
        {
            foreach ($contents as $singleContent)
            {
                $this->_setSingleCellContents(
                    $row, $col, $singleContent, $type
                );
                $col ++;
            }
        }
        else
        {
            $this->_setSingleCellContents($row, $col, $contents, $type);
        }
    }

    /**
     * Sets the column attributes for an existing column
     *
     * @param ?array|?string $attributes Associative array or string
     *                                   of table row attributes
     *
     * @throws \TableException
     */
    public function setColAttributes(int $col, $attributes = null)
    {
        $multiAttr = $this->_isAttributesArray($attributes);

        for ($i = 0; $i < $this->_rows; $i ++)
        {
            if ($multiAttr)
            {
                $this->setCellAttributes(
                    $i, $col, $attributes[$i - ((ceil(($i + 1) / count($attributes))) - 1) * count($attributes)]
                );
            }
            else
            {
                $this->setCellAttributes($i, $col, $attributes);
            }
        }
    }

    /**
     * Sets the number of columns in the table
     */
    public function setColCount(int $cols)
    {
        $this->_cols = $cols;
    }

    /**
     * Sets a columns type 'TH' or 'TD'
     *
     * @param string $type 'TH' or 'TD'
     */
    public function setColType(int $col, string $type)
    {
        for ($counter = 0; $counter < $this->_rows; $counter ++)
        {
            $this->_structure[$counter][$col]['type'] = $type;
        }
    }

    /**
     * Sets the contents of a header cell
     *
     * @param mixed $contents
     * @param ?array|?string $attributes Associative array or string of table row attributes
     *
     * @throws \TableException
     */
    public function setHeaderContents(int $row, int $col, $contents, $attributes = null)
    {
        $this->setCellContents($row, $col, $contents, 'TH');

        if (!is_null($attributes))
        {
            $this->updateCellAttributes($row, $col, $attributes);
        }
    }

    /**
     * Sets the row attributes for an existing row
     *
     * @param ?array|?string $attributes Associative array or string of table row attributes. This can also be an array
     *                                   of attributes, in which case the attributes will be repeated in a loop.
     * @param bool $inTR                 false if attributes are to be applied in TD tags; true if attributes are to be
     *                                   applied in TR tag
     *
     * @throws \TableException
     */
    public function setRowAttributes(int $row, $attributes, bool $inTR = false)
    {
        if (!$inTR)
        {
            $multiAttr = $this->_isAttributesArray($attributes);

            for ($i = 0; $i < $this->_cols; $i ++)
            {
                if ($multiAttr)
                {
                    $this->setCellAttributes(
                        $row, $i, $attributes[$i - ((ceil(($i + 1) / count($attributes))) - 1) * count($attributes)]
                    );
                }
                else
                {
                    $this->setCellAttributes($row, $i, $attributes);
                }
            }
        }
        else
        {
            $attributes = $this->_parseAttributes($attributes);
            $this->_adjustEnds($row, 0, 'setRowAttributes', $attributes);
            $this->_structure[$row]['attr'] = $attributes;
        }
    }

    /**
     * Sets the number of rows in the table
     */
    public function setRowCount(int $rows)
    {
        $this->_rows = $rows;
    }

    /**
     * Sets a rows type 'TH' or 'TD'
     *
     * @param string $type 'TH' or 'TD'
     */
    public function setRowType(int $row, string $type)
    {
        for ($counter = 0; $counter < $this->_cols; $counter ++)
        {
            $this->_structure[$row][$counter]['type'] = $type;
        }
    }

    public function toHtml($tabs = null, $tab = null): string
    {
        $strHtml = '';

        if (is_null($tabs))
        {
            $tabs = $this->_getTabs();
        }

        if (is_null($tab))
        {
            $tab = $this->_getTab();
        }

        $lnEnd = $this->_getLineEnd();

        if ($this->_useTGroups)
        {
            $extraTab = $tab;
        }
        else
        {
            $extraTab = '';
        }

        if ($this->_cols > 0)
        {
            for ($i = 0; $i < $this->_rows; $i ++)
            {
                $attr = '';

                if (isset($this->_structure[$i]['attr']))
                {
                    $attr = $this->_getAttrString($this->_structure[$i]['attr']);
                }

                $strHtml .= $tabs . $tab . $extraTab . '<tr' . $attr . '>' . $lnEnd;

                for ($j = 0; $j < $this->_cols; $j ++)
                {
                    $attr = '';
                    $contents = '';
                    $type = 'td';

                    if (isset($this->_structure[$i][$j]) && $this->_structure[$i][$j] == '__SPANNED__')
                    {
                        continue;
                    }

                    if (isset($this->_structure[$i][$j]['type']))
                    {
                        $type = (strtolower($this->_structure[$i][$j]['type']) == 'th' ? 'th' : 'td');
                    }

                    if (isset($this->_structure[$i][$j]['attr']))
                    {
                        $attr = $this->_structure[$i][$j]['attr'];
                    }

                    if (isset($this->_structure[$i][$j]['contents']))
                    {
                        $contents = $this->_structure[$i][$j]['contents'];
                    }

                    $strHtml .= $tabs . $tab . $tab . $extraTab . "<$type" . $this->_getAttrString($attr) . '>';

                    if (is_object($contents))
                    {
                        // changes indent and line end settings on nested tables
                        if ($contents instanceof HTML_Table_Storage)
                        {
                            $contents->setTab($tab . $extraTab);
                            $contents->setTabOffset($this->_tabOffset + 3);
                            $contents->_nestLevel = $this->_nestLevel + 1;
                            $contents->setLineEnd($this->_getLineEnd());
                        }

                        if (method_exists($contents, 'toHtml'))
                        {
                            $contents = $contents->toHtml();
                        }
                        elseif (method_exists($contents, 'toString'))
                        {
                            $contents = $contents->toString();
                        }
                    }

                    if (is_array($contents))
                    {
                        $contents = implode(', ', $contents);
                    }

                    if (isset($this->_autoFill) && $contents === '')
                    {
                        $contents = $this->_autoFill;
                    }

                    $strHtml .= $contents;
                    $strHtml .= "</$type>" . $lnEnd;
                }

                $strHtml .= $tabs . $tab . $extraTab . '</tr>' . $lnEnd;
            }
        }

        return $strHtml;
    }

    /**
     * Updates the attributes for all cells
     *
     * @param mixed $attributes Associative array or string of table row attributes
     *
     * @throws \TableException
     */
    public function updateAllAttributes($attributes = null)
    {
        for ($i = 0; $i < $this->_rows; $i ++)
        {
            $this->updateRowAttributes($i, $attributes);
        }
    }

    /**
     * Updates the cell attributes passed but leaves other existing attributes
     * intact
     *
     * @param ?array|?string $attributes Associative array or string of table row attributes
     *
     * @throws \TableException
     */
    public function updateCellAttributes(int $row, int $col, $attributes)
    {
        if (isset($this->_structure[$row][$col]) && $this->_structure[$row][$col] == '__SPANNED__')
        {
            return;
        }

        $attributes = $this->_parseAttributes($attributes);
        $this->_adjustEnds($row, $col, 'updateCellAttributes', $attributes);

        if(!isset($this->_structure[$row][$col]['attr']))
        {
            $this->_structure[$row][$col]['attr'] = [];
        }

        $this->_updateAttrArray($this->_structure[$row][$col]['attr'], $attributes);
        $this->_updateSpanGrid($row, $col);
    }

    /**
     * Updates the column attributes for an existing column
     *
     * @param ?array|?string $attributes Associative array or string of table row attributes
     *
     * @throws \TableException
     */
    public function updateColAttributes(int $col, $attributes = null)
    {
        $multiAttr = $this->_isAttributesArray($attributes);

        for ($i = 0; $i < $this->_rows; $i ++)
        {
            if ($multiAttr)
            {
                $this->updateCellAttributes(
                    $i, $col, $attributes[$i - ((ceil(($i + 1) / count($attributes))) - 1) * count($attributes)]
                );
            }
            else
            {
                $this->updateCellAttributes($i, $col, $attributes);
            }
        }
    }

    /**
     * Updates the row attributes for an existing row
     *
     * @param ?string|?array $attributes Associative array or string of table row attributes
     * @param bool $inTR                 false if attributes are to be applied in TD tags; true if attributes are to be
     *                                   applied in TR tag
     *
     * @throws \TableException
     */
    public function updateRowAttributes(int $row, $attributes = null, bool $inTR = false)
    {
        if (!$inTR)
        {
            $multiAttr = $this->_isAttributesArray($attributes);

            for ($i = 0; $i < $this->_cols; $i ++)
            {
                if ($multiAttr)
                {
                    $this->updateCellAttributes(
                        $row, $i, $attributes[$i - ((ceil(($i + 1) / count($attributes))) - 1) * count($attributes)]
                    );
                }
                else
                {
                    $this->updateCellAttributes($row, $i, $attributes);
                }
            }
        }
        else
        {
            $attributes = $this->_parseAttributes($attributes);
            $this->_adjustEnds($row, 0, 'updateRowAttributes', $attributes);
            $this->_updateAttrArray($this->_structure[$row]['attr'], $attributes);
        }
    }

}