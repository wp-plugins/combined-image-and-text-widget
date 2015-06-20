<?php
/*
Plugin Name: Combined Image and Text Widget
Plugin URI: http://www.nadavr.com/
Author URI: http://www.nadavr.com/
Description: A widget for text and image combinations, with multilingual support.
Author: Nadav Rotchild
Version: 1
*/

class Combined_Image_Text_Widget extends WP_Widget {

	private $isMultilingual = FALSE; //Is this site multilingual?
	private $isDefaultLanguage = TRUE; //Is this the default language for this website?
	private $urlSchema = FALSE; // Use the automatic or manual url schema? (multilanguage only)
	
	function __construct() 
	{
		parent::__construct(
			'combined_image_text_widget', // Base ID
			__( 'Combined Image & Text', 'citw' ), // Name
			array( 'description' => __( 'A widget for multilingual text and image combinations.', 'citw' ), ) // Args
		);

		//If WPML is active and was setup to have more than one language this website is multilingual.
		if ( function_exists('icl_get_default_language') && (icl_get_default_language()) )
		{
			$this->isMultilingual = TRUE;
			$this->isDefaultLanguage = (icl_get_default_language() == ICL_LANGUAGE_CODE)? TRUE:FALSE;
			$this->urlSchema = get_option('citw_url_schema', 'auto');
		}

		if ( is_admin() === TRUE )
		{
			if ( $this->isMultilingual )
			{
				add_action('admin_init', array($this, 'register_settings_options') );
				add_action('admin_menu', array($this, 'add_settings_item') );	
			}
			
			add_action('admin_enqueue_scripts', array($this, 'enqueue_backend_scripts') );
		}
		else add_action('wp_head', array($this, 'add_widget_css'));
	}


	public function enqueue_backend_scripts()
	{
		wp_enqueue_media(); //Enable the WP media uploader
		wp_register_script( 'citw_admin', plugin_dir_url( __FILE__ ) . 'citw.js', array('jquery'), FALSE, TRUE);
		wp_enqueue_script( 'citw_admin' );	
	}


	public function register_settings_options()
	{
		register_setting( 'citw-settings', 'citw_url_schema');
	}


	public function add_settings_item()
	{
		 add_options_page('Combined Image & Text Widget Settings', 'CITW', 'manage_options', 'citw-settings', array($this, 'add_settings_item_view') );
	}

	public function add_settings_item_view()
	{
		ob_start();
		 ?>
		 	<div class="wrap">
				<h2><?php _e('Combined Image & Text Widget Settings', 'citw'); ?></h2>
				<form method="post" action="options.php">
				    <?php settings_fields( 'citw-settings' ); ?>
					<table class="form-table">
					<tr valign="top">
					<th scope="row"><?php _e('Multilingual Url Schema', 'citw'); ?> </th>
					<td>
						<fieldset><legend class="screen-reader-text"><span><?php _e('multilingual url schema', 'citw'); ?></span></legend>
						
						<p><label title="auto">
							<input type="radio" name="citw_url_schema" value="auto" <?php checked('auto', get_option('citw_url_schema', 'auto')); ?> />
							<span><?php _e('Automatically format the widget link url to the current language.', 'citw'); ?></span>
						</p></label>

						<p><label title="manual">
							<input type="radio" name="citw_url_schema" value="manual" <?php checked('manual', get_option('citw_url_schema', 'auto')); ?> />
							<span><?php _e('Allow me to manually input the url for each language.', 'citw'); ?></span>
						</p></label>
						</fieldset>
					</td>
					</tr>
					</table>
					<p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Save Changes', 'citw'); ?>" /></p>
				</form>
			</div>
		<?php
		echo ob_get_clean();
	}


	/**
	* Add some basic CSS styling to the header on all pages.
	* @param NULL
	* @return NULL
	**/
	public function add_widget_css()
	{
	 ?>
		<style>
			.citw_image_container{ position: relative;}
			.citw_inner_widget_text{ 
				display: block; 
				position: absolute; 
				top: 0; 
				left: 0; 
			}
		</style>
	 <?php
	}


	/**
    * Format a url before it is passed to be saved. Removes the www. prefix as well as any WPML language url denominator.
    * @param String $url The url to filter.
    * @return String $url The url after filteration.
    */
    public function strip_languages_from_url($url)
    {
        if ( !function_exists('icl_get_default_language') ) return $url;

        //Checking for when WPML uses different language directories.
        $languages = icl_get_languages('skip_missing=0&orderby=code');
        foreach ($languages as $languageCode => $value) 
        { 
            if ( strpos($url, '/'.$languageCode.'/') !== FALSE ) 
            {
                $url = implode('', explode('/'.$languageCode, $url));
                break; 
            }
        }

        //Checking for when WPML adds the language as a GET parameter.
        if ( (strpos($url, '/?lang=') !== FALSE) )
        {
            $url = explode('/?lang=', $url);
            array_pop($url);
            if ( count($url) > 1 ) $url = implode('', $url);
            else $url = $url[0];
        }

        return $url;
    }


    /**
    *	Add the language parameter to a url if this website i multilangual and the current language is not the default language and the url is internal.
    *	@param string $url The url to change.
    *	@return string The url after its language parameter was added (if needed) 
    **/
    public function add_language_to_url($url)
    {
    	if ( function_exists('icl_get_default_language') && (icl_get_default_language() != ICL_LANGUAGE_CODE) && (strpos($url, get_site_url()) !== FALSE) )
    	{
            $wpmlLanguage = get_option('icl_sitepress_settings');
            $wpmlLanguage = $wpmlLanguage['language_negotiation_type'];

            /* WPML language index:
            * 1 - WPML uses different language directories.
            * 2 - WPML redirect to a different website (not relevant to this plugin).
            * 3 - WPML adds the language as a GET parameter.
            */

            switch ( (int)$wpmlLanguage ) 
            {
                case 1:
                    $url = explode(get_site_url(), $url);
                    if ( count($url) > 1 )	$url = get_site_url() . '/' . ICL_LANGUAGE_CODE . $url[1];
                    else $url = get_site_url() . '/' . ICL_LANGUAGE_CODE . $url[0];
                    break;
                
                case 3:
                    $url = untrailingslashit($url);
                    $url = $url . '/?lang=' . ICL_LANGUAGE_CODE;
            }
         }

         return $url;
    }
    

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) 
	{
		if ( $this->isMultilingual )
		{
			$title_name = 'title_' .ICL_LANGUAGE_CODE;
		 	$text_name = 'text_'.ICL_LANGUAGE_CODE;
		 	$image_name = 'image_' . ICL_LANGUAGE_CODE;
		 	$url_name = 'url_' . ICL_LANGUAGE_CODE;

		 	if ( $this->urlSchema == 'auto' && isset($instance['widgetLink']) && $instance['widgetLink'] != '' ) $widgetLink = $this->add_language_to_url($instance['widgetLink']);
		 	else if ( isset($instance[$url_name]) && ($instance[$url_name] != '') ) $widgetLink = $instance[$url_name];
		 	else $widgetLink = FALSE;
		}
		else 
		{
			$title_name = 'title';
			$text_name = 'text';
			$image_name = 'image';
			$widgetLink = ( isset($instance['widgetLink']) && ($instance['widgetLink'] != '') )? $instance['widgetLink'] : FALSE;
		}

		$widgetId = ( (isset($instance['widgetId'])) && (!empty($instance['widgetId'])) && ($instance['widgetId'] != '') )? $instance['widgetId']:$args['widget_id'];
		$widgetImg = ( (isset($instance[$image_name])) && (!empty($instance[$image_name])) )? '<img src="' . $instance[$image_name] . '">':'';
		$title = ( (isset($instance[$title_name])) && (!empty($instance[$title_name])) )? $instance[$title_name]:FALSE; 
		$text = ( (isset($instance[$text_name])) && (!empty($instance[$text_name])) )? $instance[$text_name] : '';

		if ( ( isset($instance['widgetClasses']) ) && ( !empty($instance['widgetClasses']) ) )
		{
			$widgetClasses = explode(',', $instance['widgetClasses']); 
			if ( $this->isMultilingual ) $widgetClasses[] = 'citw_' . ICL_LANGUAGE_CODE;
			$widgetClasses[] = 'citw_image_container';
			$widgetClasses = implode(' ', $widgetClasses);
		}
		else
		{
			if ( $this->isMultilingual ) $widgetClasses = 'citw_' . ICL_LANGUAGE_CODE . ' citw_image_container';
			else $widgetClasses = 'citw_image_container';
		}

		$html = '<section class="widget citw_widget">';
		if ( $title ) $html.= '<h3 class="widget-title">' . $title . '</h3>';
		
		if ( $widgetLink ) 
			$html.= '<div id="' . $widgetId . '" class="' . $widgetClasses . '"><a href="' . $widgetLink . '" >' . $widgetImg . '<span class="citw_inner_widget_text">' . $text . '</span></a></div>';
		else
			$html.= '<div id="' . $widgetId . '" class="' . $widgetClasses . '">' . $widgetImg . '<span class="citw_inner_widget_text">' . $text . '</span></div>';
		
		$html.= '</section>';
		echo $html;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) 
	{
		//Widget title and image input
		if ( $this->isMultilingual )
		{
			global $sitepress;
	 	    $active_languages = $sitepress->get_active_languages();
	    	foreach ($active_languages as $lang):
	    		$title_name = 'title_' . $lang['code'];
	    		$title = ( (isset($instance[$title_name])) && (!empty($instance[$title_name])) )? $instance[$title_name] : '';
				$text_name = 'text_' . $lang['code'];
				$text = ( (isset($instance[$text_name])) && (!empty($instance[$text_name])) )? $instance[$text_name] : '';
				$image_name = 'image_' . $lang['code'];
				$image = ( (isset( $instance[$image_name])) && (!empty($instance[$image_name])) )? $instance[$image_name] : '';

				if ( $this->urlSchema == 'manual' )
				{
					$url_name = 'url_' . $lang['code'];
					$url = ( (isset($instance[$url_name])) && (!empty($instance[$url_name])) )? $instance[$url_name] : '';
				}
			?>

				<p>
		        <label for="<?php echo $this->get_field_id( $title_name ); ?>"><?php echo $lang['display_name'] . ' Title:'; ?></label> 
		        <input class="widefat" id="<?php echo $this->get_field_id( $title_name ); ?>" name="<?php echo $this->get_field_name( $title_name ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		        </p>
				
				<p>
		        <label for="<?php echo $this->get_field_id( $text_name ); ?>"><?php echo $lang['display_name'] . ' Text:'; ?></label> 
		        <input class="widefat" id="<?php echo $this->get_field_id( $text_name ); ?>" name="<?php echo $this->get_field_name( $text_name ); ?>" type="text" value="<?php echo esc_attr( $text ); ?>">
		        </p>

		        <?php if ( $this->urlSchema == 'manual' ): ?>
				<p>
		        <label for="<?php echo $this->get_field_id( $url_name ); ?>"><?php echo $lang['display_name'] . ' Url:'; ?></label> 
		        <input class="widefat" id="<?php echo $this->get_field_id( $url_name ); ?>" name="<?php echo $this->get_field_name( $url_name ); ?>" type="text" value="<?php echo esc_attr( $url ); ?>">
		        </p>
		        <?php endif; ?>

		        <p>
			      <label for="<?php echo $this->get_field_id($image_name); ?>"><?php echo $lang['display_name']; ?> Image:</label><br />
			        <img class="citw_media_image" src="<?php if(!empty($instance[$image_name])){echo $instance[$image_name];} ?>" style="max-width:400px;" />
			        <input type="hidden" class="widefat citw_media_url" name="<?php echo $this->get_field_name($image_name); ?>" id="<?php echo $this->get_field_id($image_name); ?>" value="<?php echo $image; ?>">
			        <a href="#" class="button citw_media_upload"><?php _e('Upload', 'citw'); ?></a>
			        <a href="#" class="button citw_media_upload_delete"><?php _e('Delete', 'citw'); ?></a>
			    </p>

			<?php 
			 	endforeach;
				if ( $this->urlSchema != 'manual' ):
					// Generate widget link input
					$widgetLink = ((isset($instance['widgetLink'])) && (!empty($instance['widgetLink'])))? $instance['widgetLink'] : '';
				?>
				<p>
					<label for="<?php echo $this->get_field_id( 'widgetLink' ); ?>"><?php _e( 'Widget Link: (will dynamically change by language)', 'citw' ); ?></label> 
					<input class="widefat" id="<?php echo $this->get_field_id( 'widgetLink' ); ?>" name="<?php echo $this->get_field_name( 'widgetLink' ); ?>" type="text" value="<?php echo esc_attr( $widgetLink ); ?>">
				</p>	
			<?php endif;
		}
		else
		{ 
			$title_name = 'title';
			$title = ( (isset($instance[$title_name])) && (!empty( $instance[$title_name])) )? $instance[$title_name] : '';
			$text_name = 'text';
			$text = ( (isset($instance[$text_name])) && (!empty($instance[$text_name])) )? $instance[$text_name] : '';
			$image_name = 'image';
			$image = ( (isset($instance[$image_name])) && (!empty($instance[$image_name])) )? $instance[$image_name] : '';

			?>

			<p>
		        <label for="<?php echo $this->get_field_id( $title_name ); ?>">Title:</label> 
		        <input class="widefat" id="<?php echo $this->get_field_id( $title_name ); ?>" name="<?php echo $this->get_field_name( $title_name ); ?>" type="text" value="<?php echo esc_attr( $text ); ?>">
		    </p>
			
			<p>
		        <label for="<?php echo $this->get_field_id( $text_name ); ?>">Text:</label> 
		        <input class="widefat" id="<?php echo $this->get_field_id( $text_name ); ?>" name="<?php echo $this->get_field_name( $text_name ); ?>" type="text" value="<?php echo esc_attr( $text ); ?>">
		    </p>

		    <p>
		      <label for="<?php echo $this->get_field_id($image_name); ?>">Image:</label><br />
		        <img class="citw_media_image" src="<?php if(!empty($instance[$image_name])){echo $instance[$image_name];} ?>" style="" />
		        <input type="text" class="widefat citw_media_url" name="<?php echo $this->get_field_name($image_name); ?>" id="<?php echo $this->get_field_id($image_name); ?>" value="<?php echo $image; ?>">
		        <a href="#" class="button citw_media_upload"><?php _e('Upload', 'citw'); ?></a>
		    </p>

			<?php 
				// Generate widget link input
				$widgetLink = ((isset($instance['widgetLink'])) && (!empty($instance['widgetLink'])))? $instance['widgetLink'] : '';
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'widgetLink' ); ?>"><?php _e( 'Widget Link:', 'citw' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'widgetLink' ); ?>" name="<?php echo $this->get_field_name( 'widgetLink' ); ?>" type="text" value="<?php echo esc_attr( $widgetLink ); ?>">
			</p>	
			<?php

		}

		//Widget ID input
		$widgetId = ( (isset($instance['widgetId'])) && (!empty($instance['widgetId'])) )? $instance['widgetId'] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'widgetId' ); ?>"><?php _e( 'Widget ID:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'widgetId' ); ?>" name="<?php echo $this->get_field_name( 'widgetId' ); ?>" type="text" value="<?php echo esc_attr( $widgetId ); ?>">
		</p>
		<?php

		//Widget classes input
		$widgetClasses = ( (isset($instance['widgetClasses'])) && (!empty($instance['widgetClasses'])) )? $instance['widgetClasses'] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'widgetClasses' ); ?>"><?php _e( 'Widget Classes: (separate with a dash)', 'citw' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'widgetClasses' ); ?>" name="<?php echo $this->get_field_name( 'widgetClasses' ); ?>" type="text" value="<?php echo esc_attr( $widgetClasses ); ?>">
		</p>
		<?php

	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) 
	{

		if ( !empty($new_instance['widgetId']))
		{
			$new_instance['widgetId'] = preg_replace("/[^0-9A-Za-z_]/", "", $new_instance['widgetId']);	
		}

		if ( !empty($new_instance['widgetClasses']))
		{
			//Remove illegal characters
			$widgetClasses = preg_replace("/[^0-9A-Za-z,_]/", "", $new_instance['widgetClasses']); 

			//Make sure all class names are valid
			$widgetClasses = explode(',', $widgetClasses);
			foreach ($widgetClasses as $key => $val) 
			{
				if ( is_numeric(substr($val, 0, 1)) ) unset($widgetClasses[$key]);
			}
			$widgetClasses = implode(',', $widgetClasses);
			$new_instance['widgetClasses'] = $widgetClasses;
		}

		if ( ($this->urlSchema == 'auto') && (!empty($new_instance['widgetLink'])) )
		{
			//Escape the link and remove any language parameters
			$new_instance['widgetLink'] = esc_url( $new_instance['widgetLink'] );	
			$new_instance['widgetLink'] = $this->strip_languages_from_url($new_instance['widgetLink']);
		}
		else if ($this->urlSchema == 'manual')
		{
			global $sitepress;
			foreach ($active_languages as $lang):
				$url_name = 'url_' . $lang['code'];
				$new_instance[$url_name] = esc_url( $new_instance[$url_name] );	
			endforeach;	
		}

		return $new_instance;
	}
}

add_action( 'widgets_init', function(){
     register_widget( 'combined_image_text_widget' );
});?>