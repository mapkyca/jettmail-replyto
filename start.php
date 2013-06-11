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
    
    // Kludge, make sure we have object context
    elgg_register_plugin_hook_handler('notify:entity:message', 'object',
            function ($hook, $type, $message, $params) {
                
                global $__jettmail_replyto_objects;
                if (!isset($__jettmail_replyto_objects))
                    $__jettmail_replyto_objects = array();
                
                if ($entity = $params['entity'])
                        $__jettmail_replyto_objects[] = $entity;
        
            }
    );
    
    elgg_register_plugin_hook_handler('notify:annotation:message', 'all',
            function ($hook, $type, $message, $params) {
                
                global $__jettmail_replyto_annotations;
                if (!isset($__jettmail_replyto_annotations))
                    $__jettmail_replyto_annotations = array();
                
                if ($annotation = $params['annotation'])
                        $__jettmail_replyto_annotations[] = $annotation;
        
            }
    );
    
    // Hook into from hook
    elgg_register_plugin_hook_handler('jettmail:from:email', 'none', function($hook, $entity_type, $returnvalue, $params){
        global $__jettmail_replyto_objects;
        global $__jettmail_replyto_annotations;
        
        $to = $params['to'];
        $subject = $params['subject'];
        $notifications = $params['notifications'];
        
        if ($notifications) {
            
            // Get notifications
            foreach ($notifications as $guid => $details) {
                
                // See if we have an object context
                foreach ($__jettmail_replyto_objects as $entity) {
                    
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
                        $reply_email = $email_generator->generateEmailAddress($reply_action , $entity->guid, $to);
                        
                        if ($reply_email) {
                            error_log("JETTMAIL-REPLYTO: Generating email address $reply_email for action $reply_action");
                            return $reply_email;
                        }
                        else
                            error_log("JETTMAIL-REPLYTO: Could not generate email address");
                    }
                    // This is a message
                    else if (in_array($subtype, array("messages"))) {
                        $reply_action = 'create.messages';
                        
                        $email_generator = new EmailAddressGenerator();
                        $reply_email = $email_generator->generateEmailAddress($reply_action , $entity->fromId, $to);
                        
                        if ($reply_email) {
                            error_log("JETTMAIL-REPLYTO: Generating email address $reply_email for action $reply_action");
                            return $reply_email;
                        }
                        else
                            error_log("JETTMAIL-REPLYTO: Could not generate email address");
                    }
                    else
                        error_log("JETTMAIL-REPLYTO: $subtype is not something we can reply to.");
                }
                
                // See if we have an annotation context
                foreach ($__jettmail_replyto_annotations as $annotation) {
                    
                    // Get parent object
                    $entity = $annotation->getEntity();
                    
                    // Get annotation subtype
                    $subtype = $annotation->getSubtype();

                    // Default action
                    $reply_action = 'create.generic_comment';
                    
                    // See if we need another action
                    if (in_array($subtype, array("group_topic_post"))) {
                        $reply_action = 'create.group_topic_post';
                    }
                    
                    $email_generator = new EmailAddressGenerator();
                    $reply_email = $email_generator->generateEmailAddress($reply_action , $entity->guid, $to);

                    if ($reply_email) {
                        error_log("JETTMAIL-REPLYTO: Generating email address $reply_email for action $reply_action annotating {$entity->guid}");
                        return $reply_email;
                    }
                    else
                        error_log("JETTMAIL-REPLYTO: Could not generate email address for annotated object");
                    
                }
                    
            }
        }
        else
            error_log("JETTMAIL-REPLYTO: No notifications found.");
    });
    
});