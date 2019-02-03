<?php /*

 conposr_plus_plus
 Copyright (c) ocProducts, 2004-2019

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    conposr_plus_plus
 */

class SortOrderUtil
{
    const ASCENDING_ORDER = 'ASC';
    const DESCENDING_ORDER = 'DESC';

    public static function calculatePaginationValues($totalRows, $rowsPerPage, $currentPage) // [int $totalRows, int $rowsPerPage, int $currentPage]
    {
        $totalPages = intval(ceil((float)$totalRows / (float)$rowsPerPage));

        $offset = $rowsPerPage * ($currentPage - 1);
        $fromRow = $offset + 1;

        $toRow = $rowsPerPage * $currentPage;

        return array($totalPages, $offset, $fromRow, $toRow);
    }
}
