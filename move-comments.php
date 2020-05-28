<?php
/*
Plugin Name: Comments Mover
Version: 1.0
Plugin URI: https://github.com/BRdhanani/comments-mover
Author: Brijesh Dhanani
Author URI: http://wholeblogs.com
Description: Using comments mover plugin you can move comments between posts and pages in a simple and easy way.
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include_once('update-db.php');
include_once('comments-helper.php');
include_once('comments_mover_functions.php');

class MoveComments {
	private $db;
	private $form_errors;
	private $helper;

	function __construct() {
		$this->db = new CommentsMoverdb();

		$this->helper = new commentsHelper();
		
		$this->add_Menu();


        /* Sanitize $_POST to prevent XSS. */
        if($_POST)
        {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        }

        // Validate data for processing
		if($_POST and $this->validateForm($_POST))
		{
			$this->processSubmission($_POST);
		}
	}

	function processSubmission(&$data) {
		if($data and is_array($data))
		{
			$source_post_id = (int) $data['source_post_id'];
			$target_post_id = (int) $data['target_post_id'];
			foreach($data['move_comment_id'] as $comment_id)
			{
                $comment_id = (int) $comment_id;
				$this->db->moveComment($source_post_id, $target_post_id, $comment_id);
			}
		}
        $this->helper->redirect();
	}
	
	function validateForm(&$data) {
		$validate = true;
		
		if($data['target_post_id'] == 0)
		{
			$this->form_errors['target_post_id'] = 'Please select a post';
			$validate = false;
		}
		elseif($data['target_post_id'] == $data['source_post_id'])
		{
			$this->form_errors['target_post_id'] = 'You are trying to move the comments to the same post.';
			$validate = false;
		}
		
		return $validate;
	}
	
	function add_Menu() {
		add_action('admin_menu', array(&$this, 'adminMenu'));
	}
	
	// Manage Admin Options
	function adminMenu() {
		add_submenu_page('edit-comments.php', 'Move Comments', 'Move Comments', 8, __FILE__, array(&$this, 'adminPage'));
	}	

	// Admin page
	function adminPage() {
		$html = '<div class="wrap">';
		$html .= '<h2>Move Your Comments</h2>';
		$html .= $this->commentForm();
		$html .= '</div>';
		
		print($html);
	}

    function commentForm() {
        /* Sanitize $_GET to prevent XSS. */
        if($_GET)
        {
            $_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
        }

        $action = sanitize_text_field($_SERVER['PHP_SELF']);
        $page = sanitize_text_field($_REQUEST['page']);
        $sourcePostId = sanitize_text_field($_GET['source_post_id']);

        // Display the Source Post/Page selection
		$html = $this->displaySourcePost();

		$html .= '<form name="move-comments" method="post" action="'.$action.'?page='.$page.'&source_post_id='.$sourcePostId.'">';

		if($sourcePostId and is_numeric($sourcePostId))
		{
			$html .= $this->displayComments($sourcePostId);
		}

        // Display the Destination Post/Page selection
		$html .= $this->displayDestinationPost();
		
		// Hidden input for source_post_id
		$html .= '<input type="hidden" name="source_post_id" value="'.$sourcePostId.'">';
		
		// Submit button
		$html .= '<div class="submit"><input type="submit" value="Move Comment(s)"></div>';
		$html .= '</form>';
	
		return $html;
	}

	function displaySourcePost() {
		$html = '';
		$id = (int)$_REQUEST['source_post_id'];
		$posts = $this->db->getPostsList();
		$pages = $this->db->getPageList();
		
		if(!empty($posts) || !empty($pages))
		{
			$html = 'Select post or page from which you wants to move comment(s): ';
			$html .= "<select name=\"source_post_id\" onchange=\"javascript:location.href='?page=comments-mover/move-comments.php&source_post_id='+this.options[this.selectedIndex].value;\">";

			$s = 0;
			if($id == 0)
			{
				$s = 'selected';
			}
			$html .= '<option value="0" '.$s.'>Select</option>';
			if(!empty($posts)){
				$html .= '<optgroup label="Posts">'."\n";
			}

			foreach($posts as $p)
			{
				$s = "";
				if($id == $p->ID)
				{
					$s = "selected";
				}
				$html .= "<option value=\"$p->ID\" $s>$p->post_title</option>";
			}
			if(!empty($posts)){
				$html .= '</optgroup>';
			}
			if(!empty($pages)){
				$html .= '<optgroup label="Pages">'."\n";
			}
			foreach($pages as $p)
			{
				$s = "";
				if($id == $p->ID)
				{
					$s = "selected";
				}
				$html .= "<option value=\"$p->ID\" $s>$p->post_title</option>";
			}
			if(!empty($pages)){
				$html .= '</optgroup>';
			}
			$html .= '</select>';
		}
		return $html;
	}
	
	function displayComments($post_id) {
		$comments = array();
		$html = '';
		
		if(is_numeric($post_id))
		{
			$comments = $this->db->getComments($post_id);
		}
		
		if(!empty($comments))
		{
			// List the available pages and posts in the database
			$html .= '<table id="the-list-x" width="100%" cellpadding="3" cellspacing="3">'."\n";
			$html .= '<thead><tr>'."\n";
            $html .= '<th class="column1" scope="col">Select</th>'."\n";
			$html .= '<th class="column2" scope="col">Commented By</th>'."\n";
			$html .= '<th class="column3" scope="col">Comment</th>'."\n";
			$html .= '<th class="column4" scope="col">Date</th>'."\n";

			$html .= '</tr></thead>'."\n";
			
			$checkbox_index = 0;
			foreach($comments as $comment)
			{	
			    // Row Definition
				if($this->helper->is_even($checkbox_index))
				{
					$row_class = "alternate";
				}
				else
				{
					$row_class = "";
				}
				$html .= "<tr id='comment-".$comment->comment_ID."' class=".$row_class.">\n";

                if($_POST["move_comment_id"] and $_POST["move_comment_id"][$checkbox_index] == $comment->comment_ID)
                {
                    $checked = 'checked';
                }
                else
                {
                    $checked = '';
                }

                $html .= "<td><input type=\"checkbox\" name=\"move_comment_id[$checkbox_index]\" value=\"$comment->comment_ID\" $checked /></td>\n";
				$html .= "<td>$comment->comment_author</td>\n";

				// Display a portion of the comment_content if it is too long
				$comment_body = $comment->comment_content;
				if(strlen($comment_body) > 250)
				{
					$comment_body = substr($comment->comment_content, 0, 250);
					$comment_body .= ' [&#8230;]';
				}

				$html .= "<td>$comment_body</td>\n";
				$html .= "<td>". get_comment_date( 'Y-m-d', $comment ) ."</td>\n";

				$html .= '</tr>';
				$checkbox_index++;
			}
			$html .= '</table>'."\n";
			$html .= '<br />'."\n";
		}
		return $html;
	}
	
	function displayDestinationPost()
	{
		$html = '';

        $posts = $this->db->getPosts("publish");
        $pages = $this->db->getPages("publish");
		if(!empty($posts) || !empty($pages))
		{
			$html .= 'Select post or page to which you wants to move comment(s): '."\n";
			$html .= "<select name=\"target_post_id\">\n";

			$html .= '<option value="0">Select</option>'."\n";
			if(!empty($posts)){
				$html .= '<optgroup label="Posts">'."\n";
			}
			
			foreach($posts as $post)
			{
				$sel = 0;
				if($_POST['target_post_id'] == $post->ID)
				{
					$sel = 'selected';
				}
				$html .= "<option value=\"$post->ID\" $sel>$post->post_title</option>\n";
			}
			if(!empty($posts)){
				$html .= '</optgroup>';
			}
			if(!empty($pages)){
				$html .= '<optgroup label="Pages">'."\n";
			}
			foreach($pages as $page)
			{
				$sel = 0;
				if($_POST['target_post_id'] == $page->ID)
				{
					$sel = 'selected';
				}
				$html .= "<option value=\"$page->ID\" $sel>$page->post_title</option>\n";
			}
			if(!empty($pages)){
				$html .= '</optgroup>';
			}
			$html .= '</select>'."\n";
		}
		else
        {
            $html .= 'No published page or post exists'."\n";
        }
		
		if($this->form_errors['target_post_id'])
		{
			$html .= '<strong style="color:red;">'.$this->form_errors['target_post_id'].'</strong>'."\n";
		}
		
		return $html;
	}
}

$mc = new MoveComments();
?>