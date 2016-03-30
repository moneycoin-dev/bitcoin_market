<?php

namespace App\Model;

use dibi;
use App\BitcoindAuth as BTCAuth;
use Nbobtc\Command\Command;

class Listings extends \DibiRow {
    
        public function isVendor($id){
           $q = dibi::select('vendor')->from('users')->where('id = %i', $id)->fetch();
           
           if ($q['vendor'] == "yes"){
               return TRUE;
           }
           
           return FALSE;
        }
	
	public function becomeVendor($id){
            dibi::update('users', array('vendor' => 'yes'))->where('author = %i', $id)->execute();
	}
        
        public function createListing($id, array $values, $imageLocations){
            dibi::insert('listings', array('author' => $id,  'product_name' => $values['product_name'],
                'product_desc' => $values['product_desc'],'ships_from' => $values['ships_from'],
                'ships_to' => $values['ships_to'], 'product_type' => $values['product_type'],
                'product_images' => $imageLocations, 'price' => $values['price']))->execute();
        }
        
        public function getListings($author){
            return dibi::select('id, product_name')->from('listings')->where('author = %s', $author)->fetchAll();
        }
        
        public function editListing($id, $values){
            return dibi::update('listings', array('product_name' => $values['product_name'], 'product_desc' => $values['product_desc'],
                'ships_from' => $values['ships_from'], 'ships_to' => $values['ships_to'], 'product_type' => $values['product_type'],
                'price' => $values['price']))->where('id = %i', $id)->execute();
        }
        
        public function deleteListing($id){
            return dibi::delete('listings')->where('id = %i', $id)->execute();
        }
        
        public function getActualListingValues($id){
            return dibi::select('*')->from('listings')->where('id = %i', $id)->fetchAll();
        }
        
        public function getAuthor($id){
            return dibi::select('author')->from('listings')->where('id = %i', $id)->fetch();
        }
        
        public function getListingImages($id){
            return dibi::select('product_images')->from('listings')->where('id = %i', $id)->fetchAll();
        }
        
        public function updateListingImages($id, $images){
            dibi::update('listings', array('product_images' => $images))->where('id = %i', $id)->execute();
        }
        
        public function viewListing($id){
            return dibi::select('*')->from('listings')->where('id = %i', $id)->execute();
        }
}
