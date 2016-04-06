<?php

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;

class ListingFormFactory extends Nette\Object
{

	/**
	 * @return Form
	 */
	public function create()
	{
		$form = new Nette\Application\UI\Form;
        
        $form->addText('product_name', 'Product name:');
        $form->addTextArea('product_desc', 'Product description:');
        $form->addSelect('ships_from', 'Ships from:', array("Czech", "Germany", "Italy"));
        $form->addSelect('ships_to', 'Ships to:', array("Europe", "Usa", "Etc"));
        $form->addSelect('product_type', 'Product type:', array("Physical package", "Digital Package"));
        $form->addText('postage', 'Doprava');
        $form->addText('price', 'Price:');
        $form->addUpload('image', 'Product image:', TRUE);

        return $form;
	}
}