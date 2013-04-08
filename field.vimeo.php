<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PyroStreams Vimeo Field Type
 *
 * Uses the Vimeo API to get Vimeo video data based on an ID.
 *
 * For use with PyroStreams for PyroCMS
 *
 * @package		PyroStreams Vimeo Field Type
 * @author		Adam Fairholm
 * @copyright	Copyright (c) 2011-2013, Adam Fairholm
 * @link		https://github.com/adamfairholm/PyroStreams-Vimeo-Field-Type
 */
class Field_vimeo
{
	/**
	 * Field Type Slug
	 *
	 * @var 	string
	 */
	public $field_type_slug			= 'vimeo';
	
	/**
	 * Db Column Type
	 *
	 * We are saving the Vimeo ID
	 * in this case in a varchar.
	 *
	 * @var 	string
	 */
	public $db_col_type				= 'varchar';

	/**
	 * Version
	 *
	 * @var 	string
	 */
	public $version					= '1.2.0';

	/**
	 * Author
	 *
	 * @var 	array
	 */
	public $author					= array('name' => 'Adam Fairholm', 'url' => 'http://www.adamfairholm.com');
	
	/**
	 * Cache Time
	 *
	 * In Seconds
	 *
	 * @var 	int
	 */
	public $cacheTime 				= 9000;

	/**
	 * Output form input
	 *
	 * @param	array $data
	 * @return	string
	 */
	public function form_output($data)
	{
		$options = array(
			'name'	=> $data['form_slug'],
			'id'	=> $data['form_slug'],
			'value'	=> $data['value']
		);
		
		return form_input($options);
	}
	
	/**
	 * Pre Save
	 *
	 * Turn the URL into a Vimeo ID if need be
	 *
	 * @param 	string $input the input value
	 * @return  mixed string or null
	 */
	public function pre_save($input)
	{
		// Did they just give the ID? Cool. Our work here is done.
		// Vimeo IDs are numeric.
		if (is_numeric($input)) {
			return $input;
		}

		// Find and return the URL:
		$url = parse_url($input);
	
		if (isset($url['path'])) {

			$segs = explode('/', $url['path']);
		
			if (is_numeric($segs[1]))
			{
				return $segs[1];
			}
		} else {
			return null;
		}
	}

	/**
	 * Process before outputting for the plugin
	 *
	 * This creates an array of data to be merged with the
	 * tag array so relationship data can be called with
	 * a {{ field:column }} syntax
	 *
	 * @param	string
	 * @param	string
	 * @param	array
	 * @return	array
	 */
	public function pre_output_plugin($input, $params)
	{
		if ( ! $input) {
			return null;
		}

		// --------------------------------
		// Cache
		// --------------------------------

		// Should we be writing the cache?
		$write_cache = false;

		if (is_numeric($this->cacheTime))
		{
			// For the cache hash, we'll just use the
			// Vimeo ID that we have.
			$cache_hash = md5($input);

			// Cache path. Follows the convention of:
			// fieldtypes/field_type_slug/*cache files*
			$cache_file = 'fieldtypes'.DIRECTORY_SEPARATOR.'vimeo'.DIRECTORY_SEPARATOR.$cache_hash;

			// Now matter what, we'll need to write the
			// cache if the situation arises.
			$write_cache = true;

			if ($tag_cache_content = $this->CI->pyrocache->get($cache_file)) {
				return (array)json_decode($tag_cache_content);
			}			
		}

		// --------------------------------
		// Pull data from the API
		// --------------------------------

		$choices = array();
		
		$url = "http://vimeo.com/api/v2/video/{$input}.php";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$data = curl_exec($ch);
		curl_close($ch);
		
		$xml_data = unserialize($data);

		$choices = array();
		
		if ( !is_array($xml_data) or ! isset($xml_data[0])) {
			return $choices['title'] = 'Video Not Found';
		}
		
		$video_data = $xml_data[0];
		
		$choices['vimeo_id'] 				= $video_data['id'];
		$choices['title'] 					= $video_data['title'];
		$choices['url_title'] 				= url_title($video_data['title'], 'dash', TRUE);
		$choices['description'] 			= $video_data['description'];
		$choices['desc_no_html'] 			= strip_tags($video_data['description']);
		$choices['vimeo_url'] 				= $video_data['url'];
		$choices['thumb_small'] 			= $video_data['thumbnail_small'];
		$choices['thumb_medium'] 			= $video_data['thumbnail_medium'];
		$choices['thumb_large'] 			= $video_data['thumbnail_large'];
        $choices['user_name'] 				= $video_data['user_name'];
        $choices['user_url'] 				= $video_data['user_url'];
        $choices['user_portrait_small'] 	= $video_data['user_portrait_small'];
       	$choices['user_portrait_medium'] 	= $video_data['user_portrait_medium'];
        $choices['user_portrait_large'] 	= $video_data['user_portrait_large'];
        $choices['user_portrait_huge'] 		= $video_data['user_portrait_huge'];
        $choices['number_of_likes'] 		= $video_data['stats_number_of_likes'];
        $choices['number_of_plays'] 		= $video_data['stats_number_of_plays'];
       	$choices['number_of_comments'] 		= $video_data['stats_number_of_comments'];
       	$choices['duration'] 				= $this->format_duration($video_data['duration']);
        $choices['width'] 					= $video_data['width'];
        $choices['height'] 					= $video_data['height'];
        $choices['tags_string']				= $video_data['tags'];

        if ($video_data['tags']) {
        	$tags = explode(',', $video_data['tags']);

        	foreach ($tags as $tag)
        	{
        		$choices['tags'][] = array('tag' => trim($tag));
        	}
        } else {
        	$choices['tags'] = array();
        }

        // Write cache
        if ($write_cache) {
			$this->CI->pyrocache->write(json_encode($choices), $cache_file, $this->cacheTime);
        }

		return $choices;
	}

	/**
	 * Format seconds to time duration.
	 *
	 * http://stackoverflow.com/questions/3856293/how-to-convert-seconds-to-time-format
	 *
	 * @param 	int $seconds
	 * @return 	string formatted time
	 */
	private function format_duration($seconds)
	{
		$minutes = floor($seconds/60);

		$seconds = $seconds%($minutes*60);

		if (strlen($seconds) == 1) {
			$seconds = '0'.$seconds;
		}

		return $minutes.':'.$seconds;
	}

}