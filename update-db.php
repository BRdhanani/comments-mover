<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

include_once('comments-helper.php');

class CommentsMoverdb {
    public function getPosts($postStatus="publish") {
        $postStatus = htmlentities($postStatus);
        $args = array(
			    'post_status' 	=> $postStatus
			);
		$data = get_posts( $args );
		return $data;
    }

    public function getPages($postStatus="publish") {
        $postStatus = htmlentities($postStatus);
        $args = array(
        		'post_type' 	=> 'page',
			    'post_status' 	=> $postStatus
			);
		$data = get_posts( $args );
		return $data;
    }

    /**
     * @return array|null|object
     */
    public function getPostsList() {
		$args = array(
			    'post_type' 	=> 'post',
			    'post_status' 	=> $postStatus,
			    'comment_count' => array(
					'compare' => '>',
					'value' => '0',
				),
			);
		$data = get_posts( $args );
		return $data;
	}

	/**
     * @return array|null|object
     */
    public function getPageList() {
		$args = array(
			    'post_type' 	=> 'page',
			    'post_status' 	=> $postStatus,
			    'comment_count' => array(
					'compare' => '>',
					'value' => '0',
				),
			);
		$data = get_posts( $args );
		return $data;
	}

	public function getPostTitle($id) {
		if(is_numeric($id))
		{		
			$data = get_the_title($id);
		}
		return $data;
	}

	public function getComments($id) {
		if(is_numeric($id))
		{		
			$args = array(
			    'post_id' => $id
			);
			$data = get_comments( $args );
		}
		return $data;
	}

	public function moveComment($source_post_id, $target_post_id, $comment_id) {
		global $wpdb;
		
		// update the comment_post_id to $target_post_id
		$data[] = "update {$wpdb->comments}
				set comment_post_id = $target_post_id
				where comment_id = $comment_id";

		//Decrement the comment_count in the $source_post_id
		$data[] = "update {$wpdb->posts}
				set comment_count = comment_count-1
				where id = $source_post_id";
				
		// Increment the comment_count in the $target_post_id
		$data[] = "update {$wpdb->posts}
				set comment_count = comment_count+1
				where id = $target_post_id";
		
		foreach($data as $query)
		{
			$wpdb->query($query);
		}
	}
	
}
?>