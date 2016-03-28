<?php

namespace App\Model;

use dibi;

class Messages extends \DibiRow
{       
        public function recipientExists($recipient){
            
            $rec = dibi::select('count(id)')->as('recip')->select('id')
                    ->as('recipid')->select(dibi::select('count(*)')
                    ->from('messages'))->as('npm')->from('users')
                    ->where('login = %s', $recipient)->fetch();
            
            if($rec['recip'] == 1){
                return $rec;
            }
            
            throw new messageException('Neexistujici prijemce.');          
        }
        
        public function verifySelfMessage($rec, $senderid){
            
            if($rec['recipid']!=$senderid){
                 return TRUE;
            } 
            
            throw new messageException('Nemuzete odeslat zprávu sami sobě.');
        }
        
        public function sendMessage($recipient, $title, $msg, $senderid){
           
            $rec = $this->recipientExists($recipient);
        
            if($this->verifySelfMessage($rec, $senderid)){
                 
                $id = $rec['npm']+1; 
               
                dibi::insert('messages', array('id' => $id, 'id2' => '1',
                    'title' => $title, 'user1' => $senderid, 'user2' => $rec['recipid'],
                    'message' => $msg, 'timestamp' => time(),
                    'msg_read1' => 'yes', 'msg_read2' => 'no',
                    'sender_deleted' => 'no', 'receiver_deleted' => 'no'))->execute();
            }       
        }
        
        public function unreadMessages($senderid){
         return dibi::fetchAll('select m1.id, m1.title, m1.timestamp, count(m2.id) as reps, users.id as userid, users.login from messages as m1, messages as m2,users where ((m1.user1="'.$senderid.'" and m1.msg_read1="no" and users.id=m1.user2) or (m1.user2="'.$senderid.'" and m1.msg_read2="no" and users.id=m1.user1)) and m1.id2="1" and m2.id=m1.id group by m1.id order by m1.id desc');
        }
        
        public function readMessages($senderid){
            return dibi::fetchAll('select m1.id, m1.title, m1.timestamp, m1.receiver_deleted, count(m2.id) as reps, users.id as userid, users.login from messages as m1, messages as m2, users where ((m1.user1="'.$senderid.'" and m1.msg_read1="no" and users.id=m1.user2 ) or (m1.user2="'.$senderid.'" and m1.msg_read2="yes" and users.id=m1.user1 and m1.receiver_deleted="no")) and m1.id2="1" and m2.id=m1.id group by m1.id order by m1.id desc');
        }
        
        public function sentMessages($senderid){
          return dibi::fetchAll('select  * from messages where user1="'.$senderid.'" and sender_deleted="no"');
        }
        
        public function getNarrators($id){
            return dibi::fetch('select title, user1, user2, sender_deleted, receiver_deleted from messages where id="'.$id.'" and id2="1"');
        }
        
        public function userRead($id, $flag){     
            
            if ($flag == 1){

                $msgRead = dibi::select('msg_read2')->from('messages')->where('id = %i', $id)->fetch();                
                $msgAlreadyRead = $msgRead['msg_read2'];
                
                //vykonat update pouze v pripade ze zprava nebyla jeste zobrazena
                if (!($msgAlreadyRead == "yes")){
                
                    dibi::update('messages', array('msg_read2' => 'yes'))
                    ->where('id = %i', $id)->and('id2="1"')->execute();           
                }
                              
            } else {
               
                dibi::update('messages', array('msg_read1' => 'yes'))
                    ->where('id = %i', $id)->and('id2="1"')->execute();
            }
        }
        
        public function getListOfMessages($id){
            return dibi::fetch('select messages.timestamp, messages.message, messages.id, messages.title, users.id as userid, users.login from messages, users where messages.id="'.$id.'" and users.id=messages.user1 order by messages.id2');
        }
        
        public function receiverDeleteMessage($id){
             dibi::update('messages', array('receiver_deleted' => 'yes'))
                    ->where('id = %i', $id)->and('id2="1"')->execute();
        }
        
        public function senderDeleteMessage($id){
            dibi::update('messages', array('sender_deleted' => 'yes'))
                    ->where('id = %i', $id)->and('id2="1"')->execute();
        }
        
        public function getReplyRecipient($id){
            return dibi::select('login')->from('users')->where('id = %i', $id)->fetch();
        }
}

class messageException extends \Exception
{}
