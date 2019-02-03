<?php /*

 conposr_plus_plus
 Copyright (c) ocProducts, 2004-2019

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    conposr_plus_plus
 */

class PaginationPage extends PHPBeanPage
{
    protected $totalPages = 0; // integer
    protected $currentPage = 1; // integer
    protected $orderByField = ''; // string
    protected $totalRows = 0; // integer
    protected $fromRow = 0; // integer
    protected $toRow = 0; // integer
    protected $pageSize = 50; // integer
    protected $columnSort = ''; // string
    protected $sortFlag = 'ASC'; // string

    public function __construct()
    {
        $this->setPageSize((new ApplicationDataService())->rowsPerPage());

        parent::__construct();

        $this->currentPage = max(1, $this->currentPage);
    }

    public function getTotalPages()
    {
        return $this->totalPages;
    }

    public function setTotalPages($totalPages) // [integer totalPages]
    {
        $this->totalPages = $totalPages;
    }

    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    public function setCurrentPage($currentPage) // [integer currentPage]
    {
        $this->currentPage = $currentPage;
    }

    public function getOrderByField()
    {
        return $this->orderByField;
    }

    public function setOrderByField($orderByField) // [string orderByField]
    {
        $this->orderByField = $orderByField;
    }

    public function getTotalRows()
    {
        return $this->totalRows;
    }

    public function setTotalRows($totalRows) // [integer totalRows]
    {
        $this->totalRows = $totalRows;
    }

    public function getFromRow()
    {
        return $this->fromRow;
    }

    public function setFromRow($fromRow) // [integer fromRow]
    {
        $this->fromRow = $fromRow;
    }

    public function getToRow()
    {
        return $this->toRow;
    }

    public function setToRow($toRow) // [integer toRow]
    {
        $this->toRow = $toRow;
    }

    public function getPageSize()
    {
        return $this->pageSize;
    }

    public function setPageSize($pageSize) // [integer pageSize]
    {
        $this->pageSize = $pageSize;
    }

    public function getColumnSort()
    {
        return $this->columnSort;
    }

    public function setColumnSort($columnSort) // [string columnSort]
    {
        $this->columnSort = $columnSort;
    }

    public function getSortFlag()
    {
        return $this->sortFlag;
    }

    public function setSortFlag($sortFlag) // [string sortFlag]
    {
        $this->sortFlag = $sortFlag;
    }

    public function calculatePaginationValues($totalRows)
    {
        $rowsPerPage = $this->getPageSize();
        $currentPage = $this->getCurrentPage();

        list($totalPages, $offset, $fromRow, $toRow) = SortOrderUtil::calculatePaginationValues($totalRows, $rowsPerPage, $currentPage);

        $this->setTotalRows($totalRows);
        $this->setTotalPages($totalPages);
        $this->setFromRow($fromRow);
        $this->setToRow($toRow);

        return $offset;
    }
}
