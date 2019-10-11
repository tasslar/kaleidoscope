<?php 
/**************************
 Project Name	: Groupboxx
Created on		: Feb 20, 2015
Last Modified 	: Feb 20, 2015
Description		: Page contains post actions delete,like, comments
***************************/
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
error_reporting(ERROR_REPORT);
class Postactions extends CI_Controller {

 public function __construct()
 {
   parent::__construct();
   $this->authentication->user_authentication();  /* check user authentication */
   // $this->authentication->post_user_authentication(); /* Check permission for post */
   $this->user = get_user_id();
 }
 
/* Post Delete request */	
function  post_delete_reques()
 {
 	//check_ajax_request(); /* skip direct access */
    $delete_data = explode('_',decode_value($this->input->post('DelReq'))); /* Delete Request Id */
    $share_text =  $share_count = '';
    $new_share_count = 0;
     
     if(count($delete_data) == 2)
      {  
      	 $post_id = isset($delete_data[1])? addslashes($delete_data[1]) : 0;
      	 $delete_permission = $this->mydb->get_record(array('post_id','post_type','post_parent_id'),'gb_posts',array('post_id'=>$post_id,'post_user_id'=>$this->user,'post_status'=>'Yes'));       	 /*check delet permission */
      	  if(!empty($delete_permission))
      	   {
             $update_status =   $this->mydb->update('gb_posts',array('post_id'=>$delete_permission['post_id'] ),array('post_status'=>'Deleted'));
              /* Remove share count */
              if($update_status > 0 && $delete_permission['post_type']=="share"){
                $parent_post = join($this->mydb->get_record('post_share_count','gb_posts',array('post_id'=>$delete_permission['post_parent_id'])));
                $new_share_count = (int)$parent_post - 1;     
                $update_status =   $this->mydb->update('gb_posts',array('post_id'=>$delete_permission['post_parent_id']),array('post_share_count'=>$new_share_count));
              }
              
      	  	   $response = array("status"=>"ok","msg"=>"removed",'div'=>encode_value('remove_'.$delete_permission['post_id']),'share_text'=>encode_value($delete_permission['post_parent_id']).'_share_count','share_count'=>$new_share_count);
      	  	   get_content_type();
      	  	   echo  json_encode($response); exit;
      	   }
      	
      }
      $response = array("status"=>"error","msg"=>get_label('somthing_wrong'));
      get_content_type();
      echo json_encode($response); exit;
 }
 
 
 /* Post status like  */
 function  post_like_request()
 {
 	//check_ajax_request(); /* skip direct access */
 	get_content_type();  /* Json header */
 	$like_data = explode('_',decode_value($this->input->post('LikeReq'))); /* Delete Request Id */
 	$response = array("status"=>"error","msg"=>get_label('somthing_wrong'));
 	if(count($like_data) == 5)
 	{ 
 	    $group_id =  addslashes($like_data[1]);
 	    $post_id =  addslashes($like_data[2]);
 	    $element_id =  addslashes($like_data[3]);
 	    $like_type =  addslashes($like_data[4]);
 	    $current_user =  get_user_id();
 		
 	    /* Check like permission  */
 	    $permission=$post='';
 	    if($group_id!=0)
 	    {
 	    $permission= $this->mydb->get_record('join_user_id','gb_join_groups',array('join_group_id'=>$group_id,'join_user_id'=>$current_user,'join_status'=>'Yes'));
	    }  
	    else{
			
		 $post= $this->mydb->get_record(array('post_for','post_id'),'gb_posts',array('post_id'=>$post_id));
		 //$query="select post_for,post_id from gb_post where post_id=$post_id";
		//echo $query;
		// $post=$this->mydb->custom_query_single($query);
		 
		}
		
 		if( (!empty($permission)) || ( (!empty($post)) && ($post['post_for']=='follower') ) )
 		{
 			
 		    $like_status = $this->mydb->get_record('post_like_user_id','gb_post_likes',array('post_like_user_id'=>$current_user,'post_like_post_id'=>$post_id,'post_like_element_id'=>$element_id,'post_like_type'=>$like_type));
 		
 		    if(empty($like_status))
 		     {
 		     	$this->mydb->insert('gb_post_likes',array('post_like_post_id'=>$post_id,'post_like_user_id'=>$current_user,'post_like_status'=>'Like','post_like_created_on'=>get_post_date(),'post_like_creatd_ip'=>get_ip(),'post_like_element_id'=>$element_id,'post_like_type'=>$like_type));
 		     	$response = array("status"=>"ok","msg"=>'Unlike');
 		     }
 		   else 
 		    {
 		   	   $this->mydb->delete('gb_post_likes',array('post_like_user_id'=>$current_user,'post_like_post_id'=>$post_id));
 		   	   $response = array("status"=>"ok","msg"=>'Like');
 		    } 
 		    
 		   $lik_qry = " SELECT count(post_like_post_id) as ccd FROM gb_post_likes WHERE post_like_post_id = $post_id AND post_like_element_id = '".$element_id."' AND post_like_type = '".$like_type."' ";
 		   $lik_result = $this->mydb->custom_query_single($lik_qry);
 		   $like_count = (isset($lik_result['ccd']))? $lik_result['ccd'] : 0;
 		   
 		   $response = array_merge($response,array('like_count'=>$like_count));
 		   
  		}
 		 
 	}

 	get_content_type();
 	echo json_encode($response); exit;
 }
 
 
 
  /* Post status like for follower  */
 function  post_like_request_follower()
 {
 	//check_ajax_request(); /* skip direct access */
 	get_content_type();  /* Json header */
 	$like_data = explode('_',decode_value($this->input->post('LikeReq'))); /* Delete Request Id */
 	$response = array("status"=>"error","msg"=>get_label('somthing_wrong'));
 	if(count($like_data) == 4)
 	{ 
 	   
 	    $post_id =  addslashes($like_data[1]);
 	    $element_id =  addslashes($like_data[2]);
 	    $like_type =  addslashes($like_data[3]);
 	    $current_user =  get_user_id();
 		
 	    /* Check like permission  */
 	    //$permission= $this->mydb->get_record('join_user_id','gb_join_groups',array('join_group_id'=>$group_id,'join_user_id'=>$current_user,'join_status'=>'Yes'));
 	 	 
 		/*if(!empty($permission))
 		{*/
 			
 		    $like_status = $this->mydb->get_record('post_like_user_id','gb_post_likes',array('post_like_user_id'=>$current_user,'post_like_post_id'=>$post_id,'post_like_element_id'=>$element_id,'post_like_type'=>$like_type));
 		
 		    if(empty($like_status))
 		     {
 		     	$this->mydb->insert('gb_post_likes',array('post_like_post_id'=>$post_id,'post_like_user_id'=>$current_user,'post_like_status'=>'Like','post_like_created_on'=>get_post_date(),'post_like_creatd_ip'=>get_ip(),'post_like_element_id'=>$element_id,'post_like_type'=>$like_type));
 		     	$response = array("status"=>"ok","msg"=>'Unlike');
 		     }
 		   else 
 		    {
 		   	   $this->mydb->delete('gb_post_likes',array('post_like_user_id'=>$current_user,'post_like_post_id'=>$post_id));
 		   	   $response = array("status"=>"ok","msg"=>'Like');
 		    } 
 		    
 		   $lik_qry = " SELECT count(post_like_post_id) as ccd FROM gb_post_likes WHERE post_like_post_id = $post_id AND post_like_element_id = '".$element_id."' AND post_like_type = '".$like_type."' ";
 		   $lik_result = $this->mydb->custom_query_single($lik_qry);
 		   $like_count = (isset($lik_result['ccd']))? $lik_result['ccd'] : 0;
 		   
 		   $response = array_merge($response,array('like_count'=>$like_count));
 		   
  		/*}*/
 		 
 	}

 	get_content_type();
 	echo json_encode($response); exit;
 }
 

 } /* End of file postactions.php */


