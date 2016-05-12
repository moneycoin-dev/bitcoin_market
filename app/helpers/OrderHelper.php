<?php

namespace App\Helpers;

use Nette;
use Nette\Utils\Paginator;
use App\Helpers\BaseHelper;
use App\Model\Orders;

class OrderHelper extends Nette\Object {
    
    const ITM_PER_PAGE = 4;
    
    protected $orders, $base;
    
    public function injectBase(BaseHelper $bh){
        $this->base = $bh;
    }
    
    public function injectOrders(Orders $o){
        $this->orders = $o;
    }
        
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
}
