<?php

/**
 * PEAR::HTML_Table makes the design of HTML tables easy, flexible, reusable and
 * efficient.
 * The PEAR::HTML_Table package provides methods for easy and efficient design
 * of HTML tables.
 * - Lots of customization options.
 * - Tables can be modified at any time.
 * - The logic is the same as standard HTML editors.
 * - Handles col and rowspans.
 * - PHP code is shorter, easier to read and to maintain.
 * - Tables options can be reused.
 * For auto filling of data and such then check out
 * http://pear.php.net/package/HTML_Table_Matrix
 * PHP versions 4 and 5
 * LICENSE:
 * Copyright (c) 2005-2007, Adam Daniel <adaniel1@eesus.jnj.com>,
 *Bertrand Mansion <bmansion@mamasam.com>,
 *Mark Wiesemann <wiesemann@php.net>
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 ** Redistributions of source code must retain the above copyright
 *notice, this list of conditions and the following disclaimer.
 ** Redistributions in binary form must reproduce the above copyright
 *notice, this list of conditions and the following disclaimer in the
 *documentation and/or other materials provided with the distribution.
 ** The names of the authors may not be used to endorse or promote products
 *derived from this software without specific prior written permission.
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
 * @category   HTML
 * @packageHTML_Table
 * @author     Adam Daniel <adaniel1@eesus.jnj.com>
 * @author     Bertrand Mansion <bmansion@mamasam.com>
 * @licensehttp://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://pear.php.net/package/HTML_Table
 */
class HTML_Table extends HTML_Common
{

    /**
     * Value to insert into empty cells. This is used as a default for newly-created tbodies.
     */
    protected string $_autoFill = '&nbsp;';

    /**
     * Automatically adds a new row, column, or body if a given row, column, or body index does not exist. This is used
     * as a default for newly-created tbodies.
     */
    protected bool $_autoGrow = true;

    /**
     * Array containing the table caption
     */
    protected array $_caption = [];

    /**
     * Array containing the table column group specifications
     *
     * @authorLaurent Laville (pear at laurent-laville dot org)
     */
    protected array $_colgroup = [];

    /**
     * HTML_Table_Storage object for the (t)body of the table
     *
     * @var \HTML_Table_Storage[]
     */
    protected array $_tbodies = [];

    protected int $_tbodyCount = 0;

    protected ?HTML_Table_Storage $_tfoot = null;

    protected ?HTML_Table_Storage $_thead = null;

    /**
     * Whether to use <thead>, <tfoot> and <tbody> or not
     */
    protected bool $_useTGroups;

    /**
     * @param ?array $attributes Associative array of table tag attributes
     * @param bool $useTGroups   Whether to use <thead>, <tfoot> and <tbody> or not
     */
    public function __construct(?array $attributes = null, int $tabOffset = 0, bool $useTGroups = false)
    {
        parent::__construct($attributes, $tabOffset);
        $this->_useTGroups = $useTGroups;
        $this->addBody();
        if ($this->_useTGroups)
        {
            $this->_thead = new HTML_Table_Storage($tabOffset, $this->_useTGroups);
            $this->_tfoot = new HTML_Table_Storage($tabOffset, $this->_useTGroups);
        }
    }

    /**
     * Returns the table structure as HTML
     *
     * @throws \TableException
     */
    public function __toString(): string
    {
        return $this->toHtml();
    }

    /**
     * Adjusts the number of bodies
     *
     * @throws \TableException
     */
    protected function _adjustTbodyCount(int $body, string $method)
    {
        if ($this->_autoGrow)
        {
            while ($this->_tbodyCount <= $body)
            {
                $this->addBody();
            }
        }
        else
        {
            throw new TableException(
                'Invalid body reference[' . $body . '] in HTML_Table::' . $method
            );
        }
    }

    /**
     * Adds a table body and returns the body identifier
     *
     * @param ?array|?string $attributes Associative array or string of table body attributes
     */
    public function addBody($attributes = null): int
    {
        if (!$this->_useTGroups && $this->_tbodyCount > 0)
        {
            for ($i = 0; $i < $this->_tbodyCount; $i ++)
            {
                $this->_tbodies[$i]->setUseTGroups(true);
            }
            $this->_useTGroups = true;
        }

        $body = $this->_tbodyCount ++;
        $this->_tbodies[$body] = new HTML_Table_Storage(
            $this->getTabOffset(), $this->_useTGroups
        );
        $this->_tbodies[$body]->setAutoFill($this->_autoFill);
        $this->_tbodies[$body]->setAttributes($attributes);

        return $body;
    }

    /**
     * Adds a table column and returns the column identifier
     *
     * @param ?array $contents           Must be a indexed array of valid cell contents
     * @param ?array|?string $attributes Associative array or string of table row attributes
     * @param string $type               Cell type either 'th' or 'td'
     *
     * @throws \TableException
     */
    public function addCol(?array $contents = null, $attributes = null, string $type = 'td', int $body = 0): int
    {
        $this->_adjustTbodyCount($body, 'addCol');

        return $this->_tbodies[$body]->addCol($contents, $attributes, $type);
    }

    /**
     * Adds a table row and returns the row identifier
     *
     * @param ?array $contents  Must be a indexed array of valid cell contents
     * @param mixed $attributes Associative array or string of table row attributes. This can also be an
     *                          array of attributes, in which case the attributes will be repeated in a loop.
     * @param string $type      Cell type either 'th' or 'td'
     * @param bool $inTR        false if attributes are to be applied in TD tags; true if attributes are to be applied
     *                          in TR tag
     *
     * @throws \TableException
     */
    public function addRow(
        ?array $contents = null, $attributes = null, string $type = 'td', bool $inTR = false, int $body = 0
    ): int
    {
        $this->_adjustTbodyCount($body, 'addRow');

        return $this->_tbodies[$body]->addRow($contents, $attributes, $type, $inTR);
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
        int $start, $attributes1, $attributes2, bool $inTR = false, int $firstAttributes = 1, ?int $body = null
    )
    {
        if (!is_null($body))
        {
            $this->_adjustTbodyCount($body, 'altRowAttributes');
            $this->_tbodies[$body]->altRowAttributes(
                $start, $attributes1, $attributes2, $inTR, $firstAttributes
            );
        }
        else
        {
            for ($i = 0; $i < $this->_tbodyCount; $i ++)
            {
                $this->_tbodies[$i]->altRowAttributes(
                    $start, $attributes1, $attributes2, $inTR, $firstAttributes
                );
                // if the tbody's row count is odd, toggle $firstAttributes to
                // prevent the next tbody's first row from having the same
                // attributes as this tbody's last row.
                if ($this->_tbodies[$i]->getRowCount() % 2)
                {
                    $firstAttributes ^= 3;
                }
            }
        }
    }

    /**
     * Returns the autoFill value
     *
     * @param ?int $body The index of the body to get. Pass null to get the default for new bodies.
     *
     * @throws \TableException
     */
    public function getAutoFill(?int $body = null): string
    {
        if (!is_null($body))
        {
            $this->_adjustTbodyCount($body, 'getAutoFill');

            return $this->_tbodies[$body]->getAutoFill();
        }
        else
        {
            return $this->_autoFill;
        }
    }

    /**
     * Sets the autoFill value
     *
     * @param string $fill Whether autoFill should be enabled or not
     * @param ?int $body   The index of the body to set. Pass null to set for all bodies.
     *
     * @throws \TableException
     */
    public function setAutoFill(string $fill, ?int $body = null)
    {
        if (!is_null($body))
        {
            $this->_adjustTbodyCount($body, 'setAutoFill');
            $this->_tbodies[$body]->setAutoFill($fill);
        }
        else
        {
            $this->_autoFill = $fill;
            for ($i = 0; $i < $this->_tbodyCount; $i ++)
            {
                $this->_tbodies[$i]->setAutoFill($fill);
            }
        }
    }

    /**
     * Returns the autoGrow value
     *
     * @param ?int $body The index of the body to get. Pass null to get the default for new bodies.
     *
     * @throws \TableException
     */
    public function getAutoGrow(?int $body = null): bool
    {
        if (!is_null($body))
        {
            $this->_adjustTbodyCount($body, 'getAutoGrow');

            return $this->_tbodies[$body]->getAutoGrow();
        }
        else
        {
            return $this->_autoGrow;
        }
    }

    /**
     * Sets the autoGrow value
     *
     * @param bool $grow Whether autoGrow should be enabled or not
     * @param ?int $body The index of the body to set. Pass null to set for all bodies.
     *
     * @throws \TableException
     */
    public function setAutoGrow(bool $grow, ?int $body = null)
    {
        if (!is_null($body))
        {
            $this->_adjustTbodyCount($body, 'setAutoGrow');
            $this->_tbodies[$body]->setAutoGrow($grow);
        }
        else
        {
            $this->_autoGrow = $grow;
            for ($i = 0; $i < $this->_tbodyCount; $i ++)
            {
                $this->_tbodies[$i]->setAutoGrow($grow);
            }
        }
    }

    /**
     * Returns the HTML_Table_Storage object for the specified <tbody> (or the whole table if <t{head|foot|body}> is
     * not used)
     *
     * @throws \TableException
     */
    public function getBody(int $body = 0): ?HTML_Table_Storage
    {
        $this->_adjustTbodyCount($body, 'getBody');

        return $this->_tbodies[$body];
    }

    /**
     * Returns the attributes for a given cell
     *
     * @param ?int $body The index of the body to get.
     *
     * @throws \TableException
     */
    public function getCellAttributes(int $row, int $col, int $body = 0): array
    {
        $this->_adjustTbodyCount($body, 'getCellAttributes');

        return $this->_tbodies[$body]->getCellAttributes($row, $col);
    }

    /**
     * Returns the cell contents for an existing cell
     *
     * @return mixed
     * @throws \TableException
     */
    public function getCellContents(int $row, int $col, int $body = 0)
    {
        $this->_adjustTbodyCount($body, 'getCellContents');

        return $this->_tbodies[$body]->getCellContents($row, $col);
    }

    /**
     * Gets the number of columns in the table. If a row index is specified, the count will not take the spanned cells
     * into account in the return value.
     *
     * @throws \TableException
     */
    public function getColCount(?int $row = null, int $body = 0): int
    {
        $this->_adjustTbodyCount($body, 'getColCount');

        return $this->_tbodies[$body]->getColCount($row);
    }

    public function getFooter(): ?HTML_Table_Storage
    {
        if (is_null($this->_tfoot))
        {
            $this->_useTGroups = true;
            $this->_tfoot = new HTML_Table_Storage(
                $this->getTabOffset(), $this->_useTGroups
            );
            for ($i = 0; $i < $this->_tbodyCount; $i ++)
            {
                $this->_tbodies[$i]->setUseTGroups(true);
            }
        }

        return $this->_tfoot;
    }

    public function getHeader(): ?HTML_Table_Storage
    {
        if (is_null($this->_thead))
        {
            $this->_useTGroups = true;
            $this->_thead = new HTML_Table_Storage(
                $this->getTabOffset(), $this->_useTGroups
            );
            for ($i = 0; $i < $this->_tbodyCount; $i ++)
            {
                $this->_tbodies[$i]->setUseTGroups(true);
            }
        }

        return $this->_thead;
    }

    /**
     * Returns the attributes for a given row as contained in the TR tag
     *
     * @throws \TableException
     */
    public function getRowAttributes(int $row, int $body = 0): array
    {
        $this->_adjustTbodyCount($body, 'getRowAttributes');

        return $this->_tbodies[$body]->getRowAttributes($row);
    }

    /**
     * Returns the number of rows in the table
     *
     * @param ?int $body The index of the body to get. Pass null to get the total number of rows in all bodies.
     *
     * @throws \TableException
     */
    public function getRowCount(?int $body = null): int
    {
        if (!is_null($body))
        {
            $this->_adjustTbodyCount($body, 'getRowCount');

            return $this->_tbodies[$body]->getRowCount();
        }
        else
        {
            $rowCount = 0;
            for ($i = 0; $i < $this->_tbodyCount; $i ++)
            {
                $rowCount += $this->_tbodies[$i]->getRowCount();
            }

            return $rowCount;
        }
    }

    /**
     * Sets the attributes for all cells
     *
     * @param ?array|?string $attributes Associative array or string of table row attributes
     * @param ?int $body                 The index of the body to set. Pass null to set for all bodies.
     *
     * @throws \TableException
     */
    public function setAllAttributes($attributes = null, ?int $body = null)
    {
        if (!is_null($body))
        {
            $this->_adjustTbodyCount($body, 'setAllAttributes');
            $this->_tbodies[$body]->setAllAttributes($attributes);
        }
        else
        {
            for ($i = 0; $i < $this->_tbodyCount; $i ++)
            {
                $this->_tbodies[$i]->setAllAttributes($attributes);
            }
        }
    }

    /**
     * Sets the table caption
     *
     * @param ?array|?string $attributes Associative array or string of
     *                                   table row attributes
     */
    public function setCaption(string $caption, $attributes = null)
    {
        $attributes = $this->_parseAttributes($attributes);
        $this->_caption = ['attr' => $attributes, 'contents' => $caption];
    }

    /**
     * Sets the cell attributes for an existing cell.
     * If the given indices do not exist and autoGrow is true then the given
     * row and/or col is automatically added.If autoGrow is false then an
     * error is returned.
     *
     * @param ?array|?string $attributes Associative array or string of
     *                                   table row attributes
     *
     * @throws \TableException
     */
    public function setCellAttributes(int $row, int $col, $attributes, int $body = 0)
    {
        $this->_adjustTbodyCount($body, 'setCellAttributes');
        $this->_tbodies[$body]->setCellAttributes($row, $col, $attributes);
    }

    /**
     * Sets the cell contents for an existing cell
     * If the given indices do not exist and autoGrow is true then the given
     * row and/or col is automatically added.If autoGrow is false then an
     * error is returned.
     *
     * @param mixed $contents May contain html or any object with a
     *                        toHTML() method; it is an array (with
     *                        strings and/or objects), $col will be
     *                        used as start offset and the array
     *                        elements will be set to this and the
     *                        following columns in $row
     * @param string $type    Cell type either 'TH' or 'TD'
     *
     * @throws \TableException
     */
    public function setCellContents(int $row, int $col, $contents, string $type = 'TD', int $body = 0)
    {
        $this->_adjustTbodyCount($body, 'setCellContents');
        $this->_tbodies[$body]->setCellContents($row, $col, $contents, $type);
    }

    /**
     * Sets the column attributes for an existing column
     *
     * @param ?array|?string $attributes Associative array or string of table row attributes
     * @param ?int $body                 The index of the body to set. Pass null to set for all bodies.
     *
     * @throws \TableException
     */
    public function setColAttributes(int $col, $attributes = null, ?int $body = null)
    {
        if (!is_null($body))
        {
            $this->_adjustTbodyCount($body, 'setColAttributes');
            $this->_tbodies[$body]->setColAttributes($col, $attributes);
        }
        else
        {
            for ($i = 0; $i < $this->_tbodyCount; $i ++)
            {
                $this->_tbodies[$i]->setColAttributes($col, $attributes);
            }
        }
    }

    /**
     * Sets the number of columns in the table
     *
     * @throws \TableException
     */
    public function setColCount(int $cols, int $body = 0)
    {
        $this->_adjustTbodyCount($body, 'setColCount');
        $this->_tbodies[$body]->setColCount($cols);
    }

    /**
     * Sets the table columns group specifications, or removes existing ones.
     *
     * @param mixed $colgroup   Columns attributes
     * @param mixed $attributes Associative array or string
     *                          of table row attributes
     *
     * @author Laurent Laville (pear at laurent-laville dot org)
     */
    public function setColGroup($colgroup = null, $attributes = null)
    {
        if (isset($colgroup))
        {
            $attributes = $this->_parseAttributes($attributes);
            $this->_colgroup[] = [
                'attr' => $attributes,
                'contents' => $colgroup
            ];
        }
        else
        {
            $this->_colgroup = [];
        }
    }

    /**
     * Sets a columns type 'TH' or 'TD'
     *
     * @param string $type 'TH' or 'TD'
     * @param ?int $body   The index of the body to set.
     *                     Pass null to set for all bodies.
     *
     * @throws \TableException
     */
    public function setColType(int $col, string $type, ?int $body = null)
    {
        if (!is_null($body))
        {
            $this->_adjustTbodyCount($body, 'setColType');
            $this->_tbodies[$body]->setColType($col, $type);
        }
        else
        {
            for ($i = 0; $i < $this->_tbodyCount; $i ++)
            {
                $this->_tbodies[$i]->setColType($col, $type);
            }
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
    public function setHeaderContents(
        int $row, int $col, $contents, $attributes = null, int $body = 0
    )
    {
        $this->_adjustTbodyCount($body, 'setHeaderContents');
        $this->_tbodies[$body]->setHeaderContents($row, $col, $contents, $attributes);
    }

    /**
     * Sets the row attributes for an existing row
     *
     * @param ?array|?string $attributes Associative array or string of table row
     *                                   attributes. This can also be an array of
     *                                   attributes, in which case the attributes
     *                                   will be repeated in a loop.
     * @param bool $inTR                 false if attributes are to be applied in
     *                                   TD tags; true if attributes are to be
     *                                   applied in TR tag
     *
     * @throws \TableException
     */
    public function setRowAttributes(int $row, $attributes, bool $inTR = false, int $body = 0)
    {
        $this->_adjustTbodyCount($body, 'setRowAttributes');
        $this->_tbodies[$body]->setRowAttributes($row, $attributes, $inTR);
    }

    /**
     * @throws \TableException
     */
    public function setRowCount(int $rows, int $body = 0)
    {
        $this->_adjustTbodyCount($body, 'setRowCount');
        $this->_tbodies[$body]->setRowCount($rows);
    }

    /**
     * Sets a rows type 'TH' or 'TD'
     *
     * @param string $type 'TH' or 'TD'
     *
     * @throws \TableException
     */
    public function setRowType(int $row, string $type, int $body = 0)
    {
        $this->_adjustTbodyCount($body, 'setRowType');
        $this->_tbodies[$body]->setRowType($row, $type);
    }

    /**
     * @throws \TableException
     */
    public function toHtml(): string
    {
        $strHtml = '';
        $tabs = $this->_getTabs();
        $tab = $this->_getTab();
        $lnEnd = $this->_getLineEnd();
        $tBodyColCounts = [];
        for ($i = 0; $i < $this->_tbodyCount; $i ++)
        {
            $tBodyColCounts[] = $this->_tbodies[$i]->getColCount();
        }
        $tBodyMaxColCount = 0;
        if (count($tBodyColCounts) > 0)
        {
            $tBodyMaxColCount = max($tBodyColCounts);
        }
        if ($this->_comment)
        {
            $strHtml .= $tabs . "<!-- $this->_comment -->" . $lnEnd;
        }
        if ($this->getRowCount() > 0 && $tBodyMaxColCount > 0)
        {
            $strHtml .= $tabs . '<table' . $this->_getAttrString($this->_attributes) . '>' . $lnEnd;
            if (!empty($this->_caption))
            {
                $attr = $this->_caption['attr'];
                $contents = $this->_caption['contents'];
                $strHtml .= $tabs . $tab . '<caption' . $this->_getAttrString($attr) . '>';
                if (is_array($contents))
                {
                    $contents = implode(', ', $contents);
                }
                $strHtml .= $contents;
                $strHtml .= '</caption>' . $lnEnd;
            }
            if (!empty($this->_colgroup))
            {
                foreach ($this->_colgroup as $g => $col)
                {
                    $attr = $this->_colgroup[$g]['attr'];
                    $contents = $this->_colgroup[$g]['contents'];
                    $strHtml .= $tabs . $tab . '<colgroup' . $this->_getAttrString($attr) . '>';
                    if (!empty($contents))
                    {
                        $strHtml .= $lnEnd;
                        if (!is_array($contents))
                        {
                            $contents = [$contents];
                        }
                        foreach ($contents as $colAttr)
                        {
                            $attr = $this->_parseAttributes($colAttr);
                            $strHtml .= $tabs . $tab . $tab . '<col' . $this->_getAttrString($attr) . ' />' . $lnEnd;
                        }
                        $strHtml .= $tabs . $tab;
                    }
                    $strHtml .= '</colgroup>' . $lnEnd;
                }
            }
            if ($this->_useTGroups)
            {
                $tHeadColCount = 0;
                if ($this->_thead !== null)
                {
                    $tHeadColCount = $this->_thead->getColCount();
                }
                $tFootColCount = 0;
                if ($this->_tfoot !== null)
                {
                    $tFootColCount = $this->_tfoot->getColCount();
                }
                $maxColCount = max($tHeadColCount, $tFootColCount, $tBodyMaxColCount);
                if ($this->_thead !== null)
                {
                    $this->_thead->setColCount($maxColCount);
                    if ($this->_thead->getRowCount() > 0)
                    {
                        $strHtml .= $tabs . $tab . '<thead' . $this->_getAttrString($this->_thead->_attributes) . '>' .
                            $lnEnd;
                        $strHtml .= $this->_thead->toHtml($tabs, $tab);
                        $strHtml .= $tabs . $tab . '</thead>' . $lnEnd;
                    }
                }
                if ($this->_tfoot !== null)
                {
                    $this->_tfoot->setColCount($maxColCount);
                    if ($this->_tfoot->getRowCount() > 0)
                    {
                        $strHtml .= $tabs . $tab . '<tfoot' . $this->_getAttrString($this->_tfoot->_attributes) . '>' .
                            $lnEnd;
                        $strHtml .= $this->_tfoot->toHtml($tabs, $tab);
                        $strHtml .= $tabs . $tab . '</tfoot>' . $lnEnd;
                    }
                }
                for ($i = 0; $i < $this->_tbodyCount; $i ++)
                {
                    $this->_tbodies[$i]->setColCount($maxColCount);
                    if ($this->_tbodies[$i]->getRowCount() > 0)
                    {
                        $strHtml .= $tabs . $tab . '<tbody' . $this->_getAttrString($this->_tbodies[$i]->_attributes) .
                            '>' . $lnEnd;
                        $strHtml .= $this->_tbodies[$i]->toHtml($tabs, $tab);
                        $strHtml .= $tabs . $tab . '</tbody>' . $lnEnd;
                    }
                }
            }
            else
            {
                for ($i = 0; $i < $this->_tbodyCount; $i ++)
                {
                    $strHtml .= $this->_tbodies[$i]->toHtml($tabs, $tab);
                }
            }
            $strHtml .= $tabs . '</table>' . $lnEnd;
        }

        return $strHtml;
    }

    /**
     * Updates the attributes for all cells
     *
     * @param ?array|?string $attributes Associative array or string
     *                                   of table row attributes
     * @param ?int $body                 The index of the body to set.
     *                                   Pass null to set for all bodies.
     *
     * @throws \TableException
     */
    public function updateAllAttributes($attributes = null, ?int $body = null)
    {
        if (!is_null($body))
        {
            $this->_adjustTbodyCount($body, 'updateAllAttributes');
            $this->_tbodies[$body]->updateAllAttributes($attributes);
        }
        else
        {
            for ($i = 0; $i < $this->_tbodyCount; $i ++)
            {
                $this->_tbodies[$i]->updateAllAttributes($attributes);
            }
        }
    }

    /**
     * Updates the cell attributes passed but leaves other existing attributes
     * intact
     *
     * @param ?array|?string $attributes Associative array or string of table row
     *                                   attributes
     *
     * @throws \TableException
     */
    public function updateCellAttributes(int $row, int $col, $attributes, int $body = 0)
    {
        $this->_adjustTbodyCount($body, 'updateCellAttributes');
        $this->_tbodies[$body]->updateCellAttributes($row, $col, $attributes);
    }

    /**
     * Updates the column attributes for an existing column
     *
     * @param ?array|?string $attributes Associative array or
     *                                   string of table row attributes
     * @param ?int $body                 The index of the body to set.
     *                                   Pass null to set for all bodies.
     *
     * @throws \TableException
     */
    public function updateColAttributes(int $col, $attributes = null, ?int $body = null)
    {
        if (!is_null($body))
        {
            $this->_adjustTbodyCount($body, 'updateColAttributes');
            $this->_tbodies[$body]->updateColAttributes($col, $attributes);
        }
        else
        {
            for ($i = 0; $i < $this->_tbodyCount; $i ++)
            {
                $this->_tbodies[$i]->updateColAttributes($col, $attributes);
            }
        }
    }

    /**
     * Updates the row attributes for an existing row
     *
     * @param ?array|?string $attributes Associative array or string of table row
     *                                   attributes
     * @param bool $inTR                 false if attributes are to be applied in
     *                                   TD tags; true if attributes are to be
     *                                   applied in TR tag
     *
     * @throws \TableException
     */
    public function updateRowAttributes(
        int $row, $attributes = null, bool $inTR = false, int $body = 0
    )
    {
        $this->_adjustTbodyCount($body, 'updateRowAttributes');
        $this->_tbodies[$body]->updateRowAttributes($row, $attributes, $inTR);
    }
}