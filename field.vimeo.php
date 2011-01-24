<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Vimeo Field Type
 *
 * Uses the Vimeo API to get Vimeo video data based on an ID.
 *
 * For use with PyroStreams for PyroCMS
 *
 * @package		PyroStreams Vimeo Field Type
 * @author		Addict Add-ons Dev Team
 * @copyright	Copyright (c) 2011, Addict Add-ons
 * @link		http://addictaddons.com
 */
class Field_vimeo
{
	var $field_type_name 			= 'Vimeo Video';
	
	var $field_type_slug			= 'vimeo';
	
	var $db_col_type				= 'varchar';
	
	// --------------------------------------------------------------------------

	/**
	 * Output form input
	 *
	 * @access	public
	 * @param	array
	 * @param	array
	 * @return	string
	 */
	public function form_output( $data )
	{
		$options['name'] 	= $data['form_slug'];
		$options['id']		= $data['form_slug'];
		$options['value']	= $data['value'];
		
		return form_input( $options );
	}

	// --------------------------------------------------------------------------

	/**
	 * Process before outputting
	 *
	 * @access	public
	 * @param	array
	 * @return	string
	 */
	public function pre_output( $input, $data )
	{
		return $input;
	}

	// --------------------------------------------------------------------------

	/**
	 * Process before outputting for the plugin
	 *
	 * This creates an array of data to be merged with the
	 * tag array so relationship data can be called with
	 * a {field.column} syntax
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	array
	 * @return	array
	 */
	function pre_output_plugin( $prefix, $input, $params )
	{
		$choices = array();
		
		$xml_data = unserialize(file_get_contents("http://vimeo.com/api/v2/video/$input.php"));
		
		if( !is_array($xml_data) || !isset($xml_data[0])):
		
			return $choices[$prefix.'title'] = 'Video Not Found';
		
		endif;
		
		$video_data = $xml_data[0];
		
		$choices[$prefix.'vimeo_id'] 				= $video_data['id'];
		$choices[$prefix.'title'] 					= $video_data['title'];
		$choices[$prefix.'url_title'] 				= url_title($video_data['title'], 'dash', TRUE);
		$choices[$prefix.'description'] 			= $video_data['description'];
		$choices[$prefix.'desc_no_html'] 			= strip_tags($video_data['description']);
		$choices[$prefix.'vimeo_url'] 				= $video_data['url'];
		$choices[$prefix.'thumb_small'] 			= $video_data['thumbnail_small'];
		$choices[$prefix.'thumb_medium'] 			= $video_data['thumbnail_medium'];
		$choices[$prefix.'thumb_large'] 			= $video_data['thumbnail_large'];
        $choices[$prefix.'user_name'] 				= $video_data['user_name'];
        $choices[$prefix.'user_url'] 				= $video_data['user_url'];
        $choices[$prefix.'user_portrait_small'] 	= $video_data['user_portrait_small'];
       	$choices[$prefix.'user_portrait_medium'] 	= $video_data['user_portrait_medium'];
        $choices[$prefix.'user_portrait_large'] 	= $video_data['user_portrait_large'];
        $choices[$prefix.'user_portrait_huge'] 		= $video_data['user_portrait_huge'];
        $choices[$prefix.'number_of_likes'] 		= $video_data['stats_number_of_likes'];
        $choices[$prefix.'number_of_plays'] 		= $video_data['stats_number_of_plays'];
       	$choices[$prefix.'number_of_comments'] 		= $video_data['stats_number_of_comments'];
       	$choices[$prefix.'duration'] 				= $video_data['duration'];
        $choices[$prefix.'width'] 					= $video_data['width'];
        $choices[$prefix.'height'] 					= $video_data['height'];
        $choices[$prefix.'tags'] 					= $video_data['tags']; 
		
		return $choices;
	}

}

/* End of file field.vimeo.php */