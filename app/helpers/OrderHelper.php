<?php

namespace App\Helpers;

use Nette;
use Nette\Utils\Paginator;
use App\Helpers\BaseHelper;
use App\Model\Orders;

/**
 * 
 * @what Order & Sales presenters helper
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class OrderHelper extends Nette\Object {
    
    const ITM_PER_PAGE = 4;
    
    /** @var App\Model\Orders */
    protected $orders;
    
    /** @var App\Helpers\BaseHelper */
    protected $base;
    
    /**
     * Dependency Injection
     * @param BaseHelper $bh
     */
    public function injectBase(BaseHelper $bh){
        $this->base = $bh;
    }
    
    /**
     * Dependency Injection
     * @param Orders $o
     */
    public function injectOrders(Orders $o){
        $this->orders = $o;
    }
    
    /**
     * Takes care of rendering paginated
     * orders & sales
     * 
     * @param int $page
     * @param string $type - "pending", "closed", "dispute"
     * @param bool $sales
     */
    public function ordersRenderer($page, $type, $sales = NULL){
        //start paginator construction
        $paginator = new Paginator();
        $paginator->setItemsPerPage(self::ITM_PER_PAGE);
        $paginator->setPage($page);

        //get paginated data
        $orders = $this->orders->getOrders($this->base->logn(),
                  $type, $paginator, $sales);

        //finally render template variables
        $this->base->pres()->template->$type = TRUE;
        $this->base->pres()->template->orders = $orders; 
        $this->base->pres()->template->totalOrders = $paginator->getPageCount();                  
        $this->base->pres()->template->page = $page;
    }
    
    /**
     * Renders sums of:
     *  -> User's total orders
     *  -> Vendor's total sales
     * 
     * @param string $status - "pending", "closed", "dispute"
     * @param bool $sales
     */
    public function totalsRenderer($status, $sales = NULL){
        $login = $this->base->logn();
        $totals = $this->orders->getTotals($login, $status, $sales);
        $this->base->pres()->template->totals = $totals;
    }
}
