<?php

namespace App\Model;

use dibi;

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
        
        return dibi::getInsertId();
    }
    
    public function writeListingPostageOptions($listingID, array $postage){
        
        $postageLen = count($postage);
        $listing = array();
        
        //assemble array with listingID duplicates
        for($i=0; $i<$postageLen; $i++){
            array_push($listing, $listingID);
        }
        
        //to correctly form argument array
        $a = array (
            "listing_id" => $listing,
            "option" => $postage,
        );

        //to pass it to db layer with %m multiple insert modifier
        dibi::query('INSERT INTO [postage] %m', $a);
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
        return dibi::select('id, product_name, product_type, product_desc, price, ships_from, ships_to')
                ->from('listings')->where('id = %i', $id)->fetch();
    }
    
    public function getAuthor($id){
        return dibi::select('author')->from('listings')->where('id = %i', $id)->fetch();
    }
    
    public function getListingImages($id){
        return unserialize(dibi::select('product_images')->from('listings')->where('id = %i', $id)->fetch()['product_images']);
    }
    
    public function updateListingImages($id, $images){
        dibi::update('listings', array('product_images' => $images))->where('id = %i', $id)->execute();
    }
    
    public function setListingMainImage($id, $imgNum){
        dibi::update('listings', array('main_image' => $imgNum))->where('id = %i', $id)->execute();
    }
    
    public function getListingMainImage($id){
        return dibi::select('main_image')->from('listings')->where('id = %i', $id)->fetch();
    }

    public function getListingPrice($id){
        return dibi::select('price')->from('listings')->where('id = %i', $id)->fetch();
    }
    
    public function getPostageOptions($id){
        $q = dibi::select('option')->from('postage')->where('listing_id = %i', $id)->fetchAll(); 
        $array = array();
        
        foreach($q as $option){
            array_push($array, $option['option']);
        }
        
        return $array;
    }
}
