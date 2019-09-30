<?php

class Pagination
{
    private $currentPage;

    private $nextPage;

    private $previousPage;

    private $firstPage;

    private $lastPage;

    private $results;

    private $pageRange = 10;

    /**
     * Pagination constructor.
     *
     * @param think\db\Query $query
     * @param int            $page
     * @param int            $limit
     *
     * @return $this
     */
    public function __construct($query, $page, $limit)
    {
        // 统计总行数
        $cloneQuery = clone $query;
        try {
            $count = $cloneQuery->count();
        } catch (\Exception $exception) {
            $count = 0;
        }
        // 计算最后一页
        $lastPage = 0;
        if ($count > $limit) {
            if (($count % $limit) == 0) {
                $lastPage = (int)($count / $limit) - 1;
            } else {
                $lastPage = (int)($count / $limit);
            }
        }
        $this->lastPage = $lastPage;
        // 获取分页结果
        try {
            $offset = $page * $limit;
            $length = $limit;
            $results = $query->limit($offset, $length)->select()->toArray();
        } catch (\Exception $exception) {
            $results = [];
        }
        $this->results = $results;
        // 设置页码
        $this->currentPage = $page;
        $this->nextPage = $page + 1;
        $this->previousPage = $page - 1;
        $this->firstPage = 0;
        return $this;
    }

    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    public function getNextPage()
    {
        return $this->nextPage;
    }

    public function getPreviousPage()
    {
        return $this->previousPage;
    }

    public function getFirstPage()
    {
        return $this->firstPage;
    }

    public function getLastPage()
    {
        return $this->lastPage;
    }

    public function isFirstPage()
    {
        return $this->currentPage == $this->firstPage;
    }

    public function isLastPage()
    {
        return $this->currentPage == $this->lastPage;
    }

    public function getLinks()
    {
        $links = [];
        array_push($links, $this->currentPage);
        // 返回分页范围
        $i = 1;
        while (true) {
            if (count($links) > $this->pageRange || count($links) >= ($this->lastPage + 1)) {
                break;
            }
            $page = $this->currentPage - $i;
            if ($page >= 0 && $page <= $this->lastPage) {
                array_push($links, $page);
            }
            $page = $this->currentPage + $i;
            if ($page >= 0 && $page <= $this->lastPage) {
                array_push($links, $page);
            }
            $i++;
        }
        // 排序
        asort($links);
        return $links;
    }

    public function getResults()
    {
        return $this->results;
    }
}