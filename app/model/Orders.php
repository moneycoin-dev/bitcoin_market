<?php


namespace App\Model;

use dibi;

class Orders extends \DibiRow {

	public function getUserOrders($id){
		return dibi::select('*')->from('orders')->where('id = %i', $id)->fetchAll();
	}

	public function writeOrderToDb(array $arguments){
		dibi::insert('orders', $arguments)->execute();
	}
}