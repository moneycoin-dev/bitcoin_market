<?php

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;

class VendorNotesFactory extends Nette\Object
{

	/**
	 * @return Form
	 */
	public function create()
	{
        $notesForm = new \Nette\Application\UI\Form;
        $notesForm->addTextarea("notes", "Vendor Notes:");
        $notesForm->addCheckBox("check", "Šifrovat PGP klíčem vendora.");
        $notesForm->addSubmit("odeslat", "Pokračovat");
        $notesForm->addSubmit("zrusit", "Zrusit");
        
        return $notesForm;
	}
}