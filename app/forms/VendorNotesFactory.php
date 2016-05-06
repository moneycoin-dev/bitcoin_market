<?php

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;

/**
 * 
 * @what Vendor notes submission Form factory
 * @author Tomáš Keske a.k.a клустерфцк
 * @copyright 2015-2016
 * 
 */

class VendorNotesFactory extends Nette\Object
{
    public function create()
    {
        $notesForm = new Form();
        $notesForm->addTextarea("notes", "Vendor Notes:");
        $notesForm->addCheckBox("check", "Šifrovat PGP klíčem vendora.");
        $notesForm->addSubmit("odeslat", "Pokračovat");
        $notesForm->addSubmit("zrusit", "Zrusit");

        return $notesForm;
    }
}