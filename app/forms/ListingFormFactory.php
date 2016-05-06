<?php

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;

/**
 * 
 * @what Factory for Listing creation & edit Form
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class ListingFormFactory extends Nette\Object
{
    public function create()
    {
        $form = new Form();

        $form->addText('product_name', 'Product name:');
        $form->addTextArea('product_desc', 'Product description:');
        
        $form->addSelect('ships_from', 'Ships from:',
                array("Czech", "Germany", "Italy"));
        
        $form->addSelect('ships_to', 'Ships to:', array("Europe", "Usa", "Etc"));
        
        $form->addSelect('product_type', 'Product type:', 
                array("Physical package", "Digital Package"));
        
        $form->addText('price', 'Price:');
        $form->addUpload('image', 'Product image:', TRUE);

        return $form;
    }
}