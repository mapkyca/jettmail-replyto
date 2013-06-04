<?php

/**
 * Reply-To extension to JettMail
 * 
 * @see Readme.md for details
 *
 * @licence GNU Public License version 2
 * @link http://www.marcus-povey.co.uk
 * @link https://github.com/jumbojett/jettmail
 * @author Marcus Povey <marcus@marcus-povey.co.uk>
 */
elgg_register_event_handler('init', 'system', function() {
    
    // Hook into from hook
    elgg_register_plugin_hook_handler('jettmail:from:email', 'none', function($hook, $entity_type, $returnvalue, $params){
        
        $to = $params['to'];
        $subject = $params['subject'];
        $notifications = $params['notifications'];
        
        if ($notifications) {
            
            // Get notifications
            foreach ($notifications as $guid => $details) {
                
                // See if the object is retrievable
                if ($entity = get_entity($guid)) {
                    
                    // Get subtype
                    $subtype = $entity->getSubtype();
                    
                    // Default action
                    $reply_action = 'create.generic_comment';
                    
                    // See if we need another action
                    if (in_array($subtype, array("groupforumtopic"))) {
                        $reply_action = 'create.group_topic_post';
                    }
                 
                    // Generate email address
                    if (in_array($subtype, array("blog", "page_top", "page", "groupforumtopic", "file", "album"))) {

                        $email_generator = new EmailAddressGenerator();
                        $reply_email = $email_generator->generateEmailAddress($reply_action , $guid, $to);
                        
                        if ($reply_email)
                            return $reply_email;
                        
                    }
                
                }
            }
        }
    });
    
});