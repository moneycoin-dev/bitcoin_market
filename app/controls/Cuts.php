<?php

namespace App\Controls;

class Cuts { 
	
	public static function callback($callback, $m = NULL)
	{
		return new \Nette\Utils\Callback($callback, $m);
	}

}
