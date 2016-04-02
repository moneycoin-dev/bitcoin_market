<?php

namespace App\Presenters;

use App\Model\Orders;

class OrdersPresenter extends ProtectedPresenter {
    
        protected $orders;
    
        public function injectOrders(Orders $o){
            $this->orders = $o;
        }

	public function beforeRender(){
            $id = $this->getUser()->getIdentity()->getId();
            $this->template->orders = $this->orders->getUserOrders($id);
	}
}