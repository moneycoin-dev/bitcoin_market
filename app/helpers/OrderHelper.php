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
        $paginator = $this->base->paginatorSetup($page, 5);

        //get paginated data
        $orders = $this->orders->getOrders($this->base->logn(),
                  $type, $paginator, $sales);

        $pgcount = $paginator->getPageCount();
        $this->base->paginatorTemplate($type, $orders, $pgcount, $page);
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
        $totalsCZK = $this->orders->getTotals($login,"czk",$status,$sales);
        $totalsBTC = $this->orders->getTotals($login,"btc",$status, $sales);
        $totalSumCZK = $this->orders->getTotals($login,"czk",NULL,$sales);
        $totalSumBTC = $this->orders->getTotals($login,"btc",NULL,$sales);
        $template = $this->base->pres()->template;
        $template->totalSumCZK = $totalSumCZK;
        $template->totalSumBTC = $totalSumBTC;
        $template->totalsCZK = $totalsCZK;
        $template->totalsBTC = $totalsBTC;
    }
}
