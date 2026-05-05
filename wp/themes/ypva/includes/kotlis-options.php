<?php
    /**
     * ReduxFramework Sample Config File
     * For full documentation, please visit: http://docs.reduxframework.com/
     */

    if ( ! class_exists( 'Redux' ) ) {
        return;
    }


    // This is your option name where all the Redux data is stored.
    $opt_name = "kotlis";

    // This line is only for altering the demo. Can be easily removed.
    $opt_name = apply_filters( 'kotlis/opt_name', $opt_name );

    /*
     *
     * --> Used within different fields. Simply examples. Search for ACTUAL DECLARATION for field examples
     *
     */

    /**
     * ---> SET ARGUMENTS
     * All the possible arguments for Redux.
     * For full documentation on arguments, please refer to: https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments
     * */

    $theme = wp_get_theme(); // For use with some settings. Not necessary.

    $args = array(
        // TYPICAL -> Change these values as you need/desire
        'opt_name'             => $opt_name,
		'class'                => 'admin-color-pimax',
        // This is where your data is stored in the database and also becomes your global variable name.
        'display_name'         => $theme->get( 'Name' ),
        // Name that appears at the top of your panel
        'display_version'      => $theme->get( 'Version' ),
        // Version that appears at the top of your panel
        'menu_type'            => 'menu',
        //Specify if the admin menu should appear or not. Options: menu or submenu (Under appearance only)
        'allow_sub_menu'       => true,
        // Show the sections below the admin menu item or not
        'menu_title'           => esc_html__( 'Kotlis Options', 'kotlis' ),
        'page_title'           => esc_html__( 'Kotlis Options', 'kotlis' ),
        // You will need to generate a Google API key to use this feature.
        // Please visit: https://developers.google.com/fonts/docs/developer_api#Auth
        'google_api_key'       => 'AIzaSyCN8bSGZHdbSOXu0HbhXf8j0SnswTmbCNw',
        // Set it you want google fonts to update weekly. A google_api_key value is required.
        'google_update_weekly' => true,
        // Must be defined to add google fonts to the typography module
        'async_typography'     => false,
        // Use a asynchronous font on the front end or font string
        //'disable_google_fonts_link' => true,                    // Disable this in case you want to create your own google fonts loader
        'admin_bar'            => true,
        // Show the panel pages on the admin bar
        'admin_bar_icon'       => 'dashicons-portfolio',
        // Choose an icon for the admin bar menu
        'admin_bar_priority'   => 50,
        // Choose an priority for the admin bar menu
        'global_variable'      => '',
        // Set a different name for your global variable other than the opt_name
        'dev_mode'             => false,
        // Show the time the page took to load, etc
        'update_notice'        => false,
        // If dev_mode is enabled, will notify developer of updated versions available in the GitHub Repo
        'customizer'           => true,
        // Enable basic customizer support
        //'open_expanded'     => true,                    // Allow you to start the panel in an expanded way initially.
        //'disable_save_warn' => true,                    // Disable the save warning when a user changes a field
        'async_typography' => false, 
        // OPTIONAL -> Give you extra features
        'page_priority'        => 90,
        // Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
        'page_parent'          => 'themes.php',
        // For a full list of options, visit: http://codex.wordpress.org/Function_Reference/add_submenu_page#Parameters
        'page_permissions'     => 'manage_options',
        // Permissions needed to access the options panel.
        'menu_icon'            => '',
        // Specify a custom URL to an icon
        'last_tab'             => '',
        // Force your panel to always open to a specific tab (by id)
        'page_icon'            => 'icon-themes',
        // Icon displayed in the admin panel next to your menu_title
        'page_slug'            => '',
        // Page slug used to denote the panel, will be based off page title then menu title then opt_name if not provided
        'save_defaults'        => true,
        // On load save the defaults to DB before user clicks save or not
        'default_show'         => false,
        // If true, shows the default value next to each field that is not the default value.
        'default_mark'         => '',
        // What to print by the field's title if the value shown is default. Suggested: *
        'show_import_export'   => true,
        // Shows the Import/Export panel when not used as a field.

        // CAREFUL -> These options are for advanced use only
        'transient_time'       => 60 * MINUTE_IN_SECONDS,
        'output'               => true,
        // Global shut-off for dynamic CSS output by the kotlis. Will also disable google fonts output
        'output_tag'           => true,
        // Allows dynamic CSS to be generated for customizer and google fonts, but stops the dynamic CSS from going to the head
        // 'footer_credit'     => '',                   // Disable the footer credit of Redux. Please leave if you can help it.

        // FUTURE -> Not in use yet, but reserved or partially implemented. Use at your own risk.
        'database'             => '',
        // possible: options, theme_mods, theme_mods_expanded, transient. Not fully functional, warning!
        'use_cdn'              => true,
        // If you prefer not to use the CDN for Select2, Ace Editor, and others, you may download the Redux Vendor Support plugin yourself and run locally or embed it in your code.

        // HINTS
        'hints'                => array(
            'icon'          => 'el el-question-sign',
            'icon_position' => 'right',
            'icon_color'    => 'lightgray',
            'icon_size'     => 'normal',
            'tip_style'     => array(
                'color'   => 'red',
                'shadow'  => true,
                'rounded' => false,
                'style'   => '',
            ),
            'tip_position'  => array(
                'my' => 'top left',
                'at' => 'bottom right',
            ),
            'tip_effect'    => array(
                'show' => array(
                    'kotlis'   => 'slide',
                    'duration' => '500',
                    'event'    => 'mouseover',
                ),
                'hide' => array(
                    'kotlis'   => 'slide',
                    'duration' => '500',
                    'event'    => 'click mouseleave',
                ),
            ),
        )
    );

    
    // Panel Intro text -> before the form
    if ( ! isset( $args['global_variable'] ) || $args['global_variable'] !== false ) {
        if ( ! empty( $args['global_variable'] ) ) {
            $v = $args['global_variable'];
        } else {
            $v = str_replace( '-', '_', $args['opt_name'] );
        }
        $args['intro_text'] = sprintf( esc_html__( '', 'kotlis' ), $v );
    } else {
        $args['intro_text'] = esc_html__( '', 'kotlis' );
    }

    // Add content after the form.
    $args['footer_text'] = esc_html__( '', 'kotlis' );

    Redux::setArgs( $opt_name, $args );

    /*
     * ---> END ARGUMENTS
     */


    /*
     * ---> START HELP TABS
     */

    $tabs = array(
        array(
            'id'      => 'redux-help-tab-1',
            'title'   => esc_html__( 'Support', 'kotlis' ),
            'content' => esc_html__( 'Send us a mail by using our item support form.', 'kotlis' )
        ),
        
    );
    Redux::set_help_tab( $opt_name, $tabs );

    // Set the help sidebar
    $content = esc_html__( 'Send us a mail by using our item support form.', 'stukram' );
    Redux::set_help_sidebar( $opt_name, $content );


    /*
     * <--- END HELP TABS
     */


    /*
     *
     * ---> START SECTIONS
     *
     */

    /*

        As of Redux 3.5+, there is an extensive API. This API can be used in a mix/match mode allowing for


     */

    // ACTUAL DECLARATION OF SECTIONS
                Redux::setSection( $opt_name, array(
                    'title'  => esc_html__( 'General Settings', 'kotlis' ),
                    'desc'   => esc_html__( '', 'kotlis' ),
                    'icon'   => 'el el-icon-home-alt',
                    // 'submenu' => false, // Setting submenu to false on a given section will hide it from the WordPress sidebar menu!
                    'fields' => array(

						array(
								'id' => 'header-gutenberg',
								'type' => 'info',
								'notice' => true,
								'style' => 'info',
								'title' => esc_attr__('Gutenberg Editor Option', 'kotlis'),
								
						),
                        array(
								'id' => 'opt_theme_gutenberg',
								'type' => 'button_set',
								'title' => esc_attr__('Gutenberg Editor', 'kotlis'),
								'subtitle' => esc_attr__('Enable/ Disable Gutenberg Editor.', 'kotlis'),
								'desc' => '',
								'options' => array(
									'st1'=> esc_html__('Disable', 'kotlis'),
									'st2' => esc_html__('Enable', 'kotlis'),
								),
								'default'  => 'st1'
						),           					
					
					array(
							'id' => 'textlogo',
							'type' => 'button_set',
							'title' => esc_html__('Select Logo Format', 'kotlis'),
							'subtitle' => esc_html__('', 'kotlis'),
							'desc' => '',
							'options' => array(
									'st1'=> esc_html__('Text Logo', 'kotlis'),
									'st2' => esc_html__('Image Logo', 'kotlis'),
									
							),
							'default'  => 'st1'
					),
					 
					array(
							'id' => 'logopic',
							'type' => 'media',
							'compiler' => 'true',
							'title' => esc_html__('Upload  Logo', 'kotlis'),
							'subtitle' => esc_html__('Image Size 110x22', 'kotlis'),
							'required' => array('textlogo', '=' , 'st2')
					),
					
					$fields = array(
						'id'       => 'opt_logo_dimensions',
						'type'     => 'dimensions',
						'units'    => array('em','px','%'),
						'output' => array('.logo-holder img'),
						'title'    => esc_html__('Logo Size', 'kotlis'),
						'subtitle' => esc_html__('.', 'kotlis'),
						'desc'     => esc_html__('Optional', 'kotlis'),
						'default'  => array(
							'Width'   => '110', 
							'Height'  => '22'
					    ),
					    'required' => array('textlogo', '=' , 'st2')
				    ),	

					array(
			                'id' => 'notice_responsive_logo_opt',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Responsive Logo Options', 'kotlis'),
			                'desc' => esc_html__('Responsive Logo Dimensions ', 'kotlis'),
							'required' => array('textlogo', '=' , 'st2')
			        ),
					
					$fields = array(
						'id'       => 'opt_logo_mobile_dimensions',
						'type'     => 'dimensions',
						'units'    => array('em','px','%'),
						'output' => array(''),
						'title'    => esc_html__('Responsive Logo Dimensions', 'kotlis'),
						'subtitle' => __('Media width 768px', 'kotlis'),
						'desc'     => __('Optional', 'kotlis'),
						'default'  => array(
							'Width'   => '137', 
							'Height'  => '25'
						),
						'required' => array('textlogo', '=' , 'st2')
					),				
					array(
							'id' => 'logotext',
							'type' => 'text',
							'title' => esc_html__('Logo Text ', 'kotlis'),
							'subtitle' => esc_html__('', 'kotlis'),
							'required' => array('textlogo', '=' , 'st1')
					
					),					
											
					array(
			                'id' => 'notice_header_theme_cursors',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Custom Cursor', 'kotlis'),
			                'desc' => esc_html__('Enable/ Disable Custom Cursor.', 'kotlis')
			        ),					
			        array(
							'id' => 'cursors',
							'type' => 'button_set',
							'title' => esc_attr__('Custom Cursor', 'kotlis'),
							'subtitle' => esc_attr__('', 'kotlis'),
							'default'  => 'no',
							'options' => array(
							        'no'=> esc_html__('Disable', 'kotlis'),
									'yes'=> esc_html__('Enable', 'kotlis'),
							),							
					),						
					array(
			                'id' => 'notice_header_menu',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Menu Options', 'kotlis'),
			                'desc' => esc_html__('Menu options of your site header.', 'kotlis')
			            ),
						
					array(
							'id' => 'menu_st_title',
							'type' => 'text',
							'compiler' => 'true',
							'title' => esc_html__('Menu Section Title', 'kotlis'),
							'subtitle' => esc_html__('E.X: Menu', 'kotlis'),
							
					),
					
					array(
			                'id' => 'notice_header_share',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Share Options', 'kotlis'),
			                'desc' => esc_html__('Share options of your site header.', 'kotlis')
			            ),
					
					array(
							'id' => 'headershare_opt',
							'type' => 'button_set',
							'title' => esc_html__('Share Option', 'kotlis'),
							'subtitle' => esc_html__('', 'kotlis'),
							'desc' => '',
							'options' => array(
									'st1'=> esc_html__('Disable', 'kotlis'),
									'st2' => esc_html__('Enable', 'kotlis'),
									
									
							),
							'default'  => 'st1'
					),
					
					array(
			                'id' => 'notice_header_share_translation',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Share Section Translation Options', 'kotlis'),
			                'desc' => esc_html__('Share Section Text Translation Options', 'kotlis'),
							'required' => array('headershare_opt', '=' , 'st2')
			            ),
					
					array(
							'id' => 'share_bt_title1',
							'type' => 'text',
							'compiler' => 'true',
							'title' => esc_html__('Share Text', 'kotlis'),
							'subtitle' => esc_html__('Replace "Share" text here.', 'kotlis'),
							'required' => array('headershare_opt', '=' , 'st2')
					),										
					array(
			                'id' => 'notice_header_search',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Search Options', 'kotlis'),
			                'desc' => esc_html__('Search options of your site menu.', 'kotlis')
			        ),
					
					array(
							'id' => 'headersearch_opt',
							'type' => 'button_set',
							'title' => esc_html__('Search Option', 'kotlis'),
							'subtitle' => esc_html__('', 'kotlis'),
							'desc' => '',
							'options' => array(
									'st1'=> esc_html__('Disable', 'kotlis'),
									'st2' => esc_html__('Enable', 'kotlis'),
									
									
							),
							'default'  => 'st1'
					),
					
					array(
			                'id' => 'notice_header_search_translation',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Search Section Translation Options', 'kotlis'),
			                'desc' => esc_html__('Search Section Text Translation Options', 'kotlis'),
							'required' => array('headersearch_opt', '=' , 'st2')
			        ),
					
					array(
							'id' => 'search_bt_title1',
							'type' => 'text',
							'compiler' => 'true',
							'title' => esc_html__('Search Text', 'kotlis'),
							'subtitle' => esc_html__('Replace "Search.." text here.', 'kotlis'),
							'required' => array('headersearch_opt', '=' , 'st2')
					),
					
				  )
               ) );
			   
			   Redux::setSection( $opt_name, array(
                    'icon'   => 'el el-icon-idea',
                    'title'  => esc_html__( 'Header Settings', 'kotlis' ),
                    'fields' => array(
					
					array(
			                'id' => 'notice_header_nav_opt',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Header Logo & Navigation', 'kotlis'),
			                'desc' => esc_html__('Header logo & navigation position controlling options.', 'kotlis'),
					),
					
					$fields = array(
						'id'       => 'opt_nav_dimensions',
						'type'     => 'dimensions',
						'units'    => array('em','px','%'),
						'output' => array('.main-header'),
						'title'    => __('Navigation Bar Height', 'kotlis'),
						'subtitle' => __('Default: 70px', 'kotlis'),
						'width' => false,
						'desc'     => __('Optional', 'kotlis'),
						'default'  => array(
							'Height'  => '90'
						),
					),
					
					$fields = array(
						'id'             => 'opt_header_logo_spacing',
						'type'           => 'spacing',
						'output'         => array('.logo-holder'),
						'mode'           => 'margin',
						'units'          => array('px', 'em'),
						'right'   => false, 
						'bottom'  => false, 
						'left'    => false,
						'units_extended' => 'false',
						'title'          => __('Logo Top Margin', 'kotlis'),
						'subtitle'       => __('Default: 0px', 'kotlis'),
						'desc'           => __('', 'kotlis'),
						'default'            => array(
							'margin-top'     => '', 
							'units'          => 'px', 
						)
					),
					
					$fields = array(
						'id'             => 'opt_header_logo_spacing_resposive',
						'type'           => 'spacing',
						'output'         => array(''),
						'mode'           => 'margin',
						'units'          => array('px', 'em'),
						'right'   => false, 
						'bottom'  => false, 
						'left'    => false,
						'units_extended' => 'false',
						'title'          => __('Responsive Logo Top Margin', 'kotlis'),
						'subtitle'       => __('Default: 0px<br>Media width 768px', 'kotlis'),
						'desc'           => __('', 'kotlis'),
						'default'            => array(
							'margin-top'     => '', 
							'units'          => 'px', 
						)
					),
					
					$fields = array(
						'id'             => 'opt_header_nav_spacing',
						'type'           => 'spacing',
						'output'         => array('.nav-holder, .header-cart_wrap'),
						'mode'           => 'margin',
						'units'          => array('px', 'em'),
						'right'   => false, 
						'bottom'  => false, 
						'left'    => false,
						'units_extended' => 'false',
						'title'          => __('Navigation Top Margin', 'kotlis'),
						'subtitle'       => __('Default: 0px', 'kotlis'),
						'desc'           => __('', 'kotlis'),
						'default'            => array(
							'margin-top'     => '', 
							'units'          => 'px', 
						)
					),
					
					$fields = array(
						'id'             => 'opt_header_share_spacing',
						'type'           => 'spacing',
						'output'         => array('.sb-button, .share-btn'),
						'mode'           => 'margin',
						'units'          => array('px', 'em'),
						'right'   => false, 
						'bottom'  => false, 
						'left'    => false,
						'units_extended' => 'false',
						'title'          => __('Header Widget & Share Button  Top Margin', 'kotlis'),
						'subtitle'       => __('Default: 0px', 'kotlis'),
						'desc'           => __('', 'kotlis'),
						'default'            => array(
							'margin-top'     => '', 
							'units'          => 'px', 
						)
					),
					
					$fields = array(
						'id'             => 'opt_header_search_spacing',
						'type'           => 'spacing',
						'output'         => array('.search-input'),
						'mode'           => 'margin',
						'units'          => array('px', 'em'),
						'right'   => false, 
						'bottom'  => false, 
						'left'    => false,
						'units_extended' => 'false',
						'title'          => __('Header Search Button Top Margin', 'kotlis'),
						'subtitle'       => __('Default: 0px', 'kotlis'),
						'desc'           => __('', 'kotlis'),
						'default'            => array(
							'margin-top'     => '', 
							'units'          => 'px', 
						)
					),

						array(
								'id'       => 'opt_header_nav_menu_sub_bg_color',
								'type'     => 'color_rgba',
								'title'    => esc_html__( 'Sub Menu Item Background Color', 'kotlis' ),
								'subtitle' => esc_html__( 'Media width 1200px.', 'kotlis' ),
								'desc'     => esc_html__( '', 'kotlis' ),
								//'regular'   => false, // Disable Regular Color
								//'hover'     => false, // Disable Hover Color
								//'active'    => false, // Disable Active Color
								//'visited'   => true,  // Enable Visited Color
						),					

                    )
                ) );
				
				 Redux::setSection( $opt_name, array(
                    'icon'   => 'el el-icon-cogs',
                    'title'  => esc_html__( 'Page Settings', 'kotlis' ),
                    'fields' => array(		
					array(
							'id' => 'header-portfolio',
							'type' => 'info',
		                    'notice' => true,
		                    'style' => 'info',
							'title' => esc_attr__('Portfolio Page Option', 'kotlis'),
							
					),	
					array(
							'id' => 'port-page-url',
							'type' => 'text',
							'title' => __('Portfolio Page URL', 'kotlis'),
							'subtitle' => __('Insert portfolio page URL here.', 'kotlis'),
							'default' => '',
							
					),	
                    array(
							'id' => 'portfolio_hover_st',
							'type' => 'button_set',
							'title' => esc_attr__('Portfolio Mouse Over', 'kotlis'),
							'subtitle' => esc_attr__('', 'kotlis'),
							'desc' => '',
							'options' => array(
									'st1'=> esc_attr__('Image Popup', 'kotlis'),
									'st2' => esc_attr__('Details URL', 'kotlis'),
							),
							'default'  => 'st1'
					),	
					array(
			                'id' => 'notice_portfolio_main_base',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Portfolio Details Base Option.', 'kotlis'),
			                'desc' => __('If you like, you may enter custom structures for your Portfolio details URLs here. For example, using topics as your portfolio base would make your portfolio details links like http://yoursiteurl/topics/sample-post/. If you leave these blank the defaults will be used.<br>After make changes save permalink settings again.', 'kotlis'),
					),
					
					array(
							'id' => 'portfolio_main_base_opt',
							'type' => 'text',
							'title' => esc_html__('Portfolio Details Base/Slug ', 'kotlis'),
							'subtitle' => esc_html__('Ex: topics', 'kotlis'),
					),
					array(
			                'id' => 'notice_portfolio_category_base',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Portfolio Category Base Option.', 'kotlis'),
			                'desc' => __('If you like, you may enter custom structures for your Portfolio Category URLs here. For example, using topics as your portfolio_category base would make your portfolio category links like http://yoursiteurl/topics/uncategorized/. If you leave these blank the defaults will be used.<br>After make changes save permalink settings again.', 'kotlis'),
					),
					
					array(
							'id' => 'portfolio_category_base_opt',
							'type' => 'text',
							'title' => esc_html__('Portfolio Category Base ', 'kotlis'),
							'subtitle' => esc_html__('Ex: topics', 'kotlis'),
					),					
					array(
							'id' => 'header-video',
							'type' => 'info',
		                    'notice' => true,
		                    'style' => 'info',
							'title' => esc_attr__('Video Page Option', 'kotlis'),
							
					),	
					array(
							'id' => 'video-page-url',
							'type' => 'text',
							'title' => __('Video Page URL', 'kotlis'),
							'subtitle' => __('Insert video page URL here.', 'kotlis'),
							'default' => '',
							
					),
                    array(
							'id' => 'video_hover_st',
							'type' => 'button_set',
							'title' => esc_attr__('Video Mouse Over', 'kotlis'),
							'subtitle' => esc_attr__('', 'kotlis'),
							'desc' => '',
							'options' => array(
									'st1'=> esc_attr__('Video Popup', 'kotlis'),
									'st2' => esc_attr__('Details URL', 'kotlis'),
							),
							'default'  => 'st1'
					),	
					array(
			                'id' => 'notice_video_main_base',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Video Details Base Option.', 'kotlis'),
			                'desc' => __('If you like, you may enter custom structures for your Video details URLs here. For example, using topics as your video base would make your video details links like http://yoursiteurl/topics/sample-post/. If you leave these blank the defaults will be used.<br>After make changes save permalink settings again.', 'kotlis'),
					),
					
					array(
							'id' => 'video_main_base_opt',
							'type' => 'text',
							'title' => esc_html__('Video Details Base/Slug ', 'kotlis'),
							'subtitle' => esc_html__('Ex: topics', 'kotlis'),
					),
					array(
			                'id' => 'notice_video_category_base',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Video Category Base Option.', 'kotlis'),
			                'desc' => __('If you like, you may enter custom structures for your Video Category URLs here. For example, using topics as your video_category base would make your video category links like http://yoursiteurl/topics/uncategorized/. If you leave these blank the defaults will be used.<br>After make changes save permalink settings again.', 'kotlis'),
					),
					
					array(
							'id' => 'video_category_base_opt',
							'type' => 'text',
							'title' => esc_html__('Video Category Base ', 'kotlis'),
							'subtitle' => esc_html__('Ex: topics', 'kotlis'),
					),						
			        array(
							'id' => 'video_scroll_swipe_show',
							'type' => 'button_set',
							'title' => esc_html__('Scroll Down', 'kotlis'),
							'subtitle' => esc_html__('Enable/Disable header section for video category & search page.', 'kotlis'),
							'default'  => 'yes',
							'options' => array(
									'yes'=> esc_html__('Enable', 'kotlis'),
									'no'=> esc_html__('Disable', 'kotlis'),
							),
					),						
					array(
			                'id' => 'notice_port_translation',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Translation Options', 'kotlis'),
			                'desc' => esc_html__('Default Text Translate Here.', 'kotlis'),
			        ),	
					array(
							'id' => 'portfolio_page_translet_scroll',
							'type' => 'text',
							'title' => __('Scroll Down Text', 'kotlis'),
							'subtitle' => __('Change/Repalce portfolio page "Scroll down to Discover" text here.', 'kotlis'),
							'default' => '',
							'required' => array('portfolio_scroll_swipe_show', '=' , 'yes')
					),						
					array(
							'id' => 'port-page-nopost',
							'type' => 'text',
							'title' => __('Back To Portfolio Text', 'kotlis'),
							'subtitle' => __('Change/Repalce portfolio post "Back To Portfolio" text here.', 'kotlis'),
							'default' => '',
							
					),	
					
					array(
							'id' => 'video-page-nopost',
							'type' => 'text',
							'title' => __('Back To Video Text', 'kotlis'),
							'subtitle' => __('Change/Repalce video post "Back To Video" text here.', 'kotlis'),
							'default' => '',
							
					),
					
					array(
							'id' => 'shop-page-nopost',
							'type' => 'text',
							'title' => __('Back To Shop Text', 'kotlis'),
							'subtitle' => __('Change/Repalce product details "Back To Shop" text here.', 'kotlis'),
							'default' => '',
							
					),
					
					array(
							'id' => 'port-page-back-home',
							'type' => 'text',
							'title' => __('Back To Home Text', 'kotlis'),
							'subtitle' => __('Change/Repalce portfolio post "Back To Home" text here.', 'kotlis'),
							'default' => '',
							
					),						
					array(
							'id' => 'header-error',
							'type' => 'info',
		                    'notice' => true,
		                    'style' => 'info',
							'title' => esc_attr__('404 Error Page Option', 'kotlis'),
							
					),	
					array(
							'id' => 'errorpic',
							'type' => 'media',
							'compiler' => 'true',
							'title' => esc_html__('Upload Background Image', 'kotlis'),
							'subtitle' => esc_html__('Upload error page background image.', 'kotlis'),
							'subtitle' => esc_html__('', 'kotlis'),
					),															
					array(
							'id' => 'error-page-title',
							'type' => 'text',
							'title' => esc_attr__('Title Text', 'kotlis'),
							'subtitle' => esc_attr__('Change/Repalce "404" text here.', 'kotlis'),
							'default' => '',
							
					),	
					array(
							'id' => 'error-page-sbtitle',
							'type' => 'text',
							'title' => esc_attr__('Subtitle Text', 'kotlis'),
							'subtitle' => esc_attr__('Change/Repalce "Page Error" text here.', 'kotlis'),
							'default' => '',
							
					),					
					array(
							'id' => 'error-page-subtitle',
							'type' => 'textarea',
							'title' => esc_attr__('Content Text', 'kotlis'),
							'subtitle' => esc_attr__('Change/Repalce "Page not Found" text here.', 'kotlis'),
							'default' => '',
							
					),					
					
                    )
                ));				   
			   
				if (class_exists('WooCommerce')) {
				Redux::setSection( $opt_name, array(
                    'icon'   => 'el el-shopping-cart-sign',
                    'title'  => esc_attr__( 'Shop Options', 'kotlis' ),
                    'fields' => array(
					
					array(
							'id' => 'wr-shop-opt',
							'type' => 'info',
		                    'notice' => true,
		                    'style' => 'info',
							'title' => esc_attr__('Shop Page Header Options', 'kotlis'),
							'desc' => esc_attr__(' ', 'kotlis')
							
					  ),

					array(
							'id' => 'shopheaderimg',
							'type' => 'media',
							'compiler' => 'true',
							'title' => esc_attr__('Upload Shop Page Header Image', 'kotlis'),
							'subtitle' => esc_attr__('', 'kotlis'),
							
					),
					
					array(
							'id' => 'shopsubtitle',
							'type' => 'textarea',
							'title' => esc_attr__('Sub Title ', 'kotlis'),
							'subtitle' => esc_attr__('Shop page sub title', 'kotlis'),
							
					),
					
					array(
							'id' => 'wr-shop-dt-opt',
							'type' => 'info',
		                    'notice' => true,
		                    'style' => 'info',
							'title' => esc_attr__('Product Details Page Options', 'kotlis'),
							'desc' => esc_attr__(' ', 'kotlis')
							
					  ),
					  
					array(
							'id' => 'shop_details_page_opt',
							'type' => 'button_set',
							'title' => esc_attr__('Details Page Style', 'kotlis'),
							'subtitle' => esc_attr__('', 'kotlis'),
							'desc' => '',
							'options' => array(
									'st1'=> esc_html__('Full Width', 'kotlis'),
									'st2' => esc_html__('Left Side Block', 'kotlis'),
									
							),
							'default'  => 'st1'
					),

					array(
							'id' => 'shopheaderimgdt',
							'type' => 'media',
							'compiler' => 'true',
							'title' => esc_attr__('Upload Product Details Page Header Image', 'kotlis'),
							'subtitle' => esc_attr__('', 'kotlis'),
							
					),
					
					array(
							'id' => 'shoptitledt',
							'type' => 'text',
							'title' => esc_attr__('Title ', 'kotlis'),
							'subtitle' => esc_attr__('Product Details PageTitle', 'kotlis'),
							
					),
					
					array(
							'id' => 'shopsubtitledt',
							'type' => 'textarea',
							'title' => esc_attr__('Sub Title ', 'kotlis'),
							'subtitle' => esc_attr__('Product Details Page Sub Title', 'kotlis'),
							'required' => array('shop_details_page_opt', '=' , 'st1')
							
					),
					
					
					
                    )
                ) );
				}
				
				
				Redux::setSection( $opt_name, array(
                    'icon'   => 'el el-icon-bullhorn',
                    'title'  => esc_html__( 'Blog Settings', 'kotlis' ),
                    'fields' => array(
					array(
							'id' => 'blogtyle',
							'type' => 'button_set',
							'title' => esc_html__('Select Blog Layout', 'kotlis'),
							'subtitle' => esc_html__('', 'kotlis'),
							'desc' => '',
							'options' => array(									
									'st2' => esc_html__('Left Sidebar', 'kotlis'),
									'st3' => esc_html__('Side Block', 'kotlis'),
									'st1'=> esc_html__('Right Sidebar', 'kotlis'),

							),
							'default'  => 'st1'
					),					

					array(
							'id' => 'blog-page-header-img',
							'type' => 'media',
							'compiler' => 'true',
							'title' => esc_html__('Header & Sideblock Image', 'kotlis'),
							'subtitle' => esc_html__('Upload header/sideblock background image for blog, archives, category, tag & search page.', 'kotlis'),
							'subtitle' => esc_html__('', 'kotlis'),
					),					
			        array(
							'id' => 'index-header-show',
							'type' => 'button_set',
							'title' => esc_html__('Blog Header Section', 'kotlis'),
							'subtitle' => esc_html__('Enable/Disable header section for blog single, archives, category, tag & search page.', 'kotlis'),
							'default'  => 'yes',
							'options' => array(
									'yes'=> esc_html__('Enable', 'kotlis'),
									'no'=> esc_html__('Disable', 'kotlis'),
							),
							
					),											
			        array(
							'id' => 'index-header-title-show',
							'type' => 'button_set',
							'title' => esc_html__('Blog Header Title', 'kotlis'),
							'subtitle' => esc_html__('Enable/Disable header section for blog single, archives, category, tag & search page.', 'kotlis'),
							'default'  => 'yes',
							'options' => array(
									'yes'=> esc_html__('Enable', 'kotlis'),
									'no'=> esc_html__('Disable', 'kotlis'),
							),
							'required' => array('index-header-show', '=' , 'yes')
					),	
					array(
							'id' => 'blog-page-title',
							'type' => 'text',
							'title' => esc_html__('Blog Title Text', 'kotlis'),
							'subtitle' => esc_html__('Insert blog page header title text here for blog single, archives, category, tag & search page.', 'kotlis'),
							'required' => array('index-header-title-show', '=' , 'yes')
							
					),					
					array(
							'id' => 'blog-page-subtitle',
							'type' => 'textarea',
							'title' => esc_html__('Blog Subtitle Text', 'kotlis'),
							'subtitle' => esc_html__('Insert blog page header subtitle text here for blog single, archives, category, tag & search page.', 'kotlis'),
							'required' => array('index-header-title-show', '=' , 'yes')
							
					),	
			        array(
							'id' => 'index_scroll_swipe_show',
							'type' => 'button_set',
							'title' => esc_html__('Scroll Down', 'kotlis'),
							'subtitle' => esc_html__('Enable/Disable header section for blog single, archives, category, tag & search page.', 'kotlis'),
							'default'  => 'yes',
							'options' => array(
									'yes'=> esc_html__('Enable', 'kotlis'),
									'no'=> esc_html__('Disable', 'kotlis'),
							),
							'required' => array('blogtyle', '=' , 'st3')
					),	
					array(
			                'id' => 'notice_blog-scroll_swipe_translation',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Translation Options', 'kotlis'),
			                'desc' => esc_html__('Default Text Translate Here.', 'kotlis'),
							'required' => array('index_scroll_swipe_show', '=' , 'yes')
			        ),					
					array(
							'id' => 'blog_page_translet_scroll',
							'type' => 'text',
							'title' => __('Scroll Down Text', 'kotlis'),
							'subtitle' => __('Change/Repalce blog page "Scroll down to Discover" text here.', 'kotlis'),
							'default' => '',
							'required' => array('index_scroll_swipe_show', '=' , 'yes')							
					),					
			        array(
							'id' => 'blog_author_info',
							'type' => 'button_set',
							'title' => esc_html__('Blog Author Info', 'kotlis'),
							'subtitle' => esc_html__('Enable/Disable author info for blog single page.', 'kotlis'),
							'default'  => 'no',
							'options' => array(
									'yes'=> esc_html__('Enable', 'kotlis'),
									'no'=> esc_html__('Disable', 'kotlis'),
							),
					),	
			        array(
							'id' => 'blog_single_page_nav',
							'type' => 'button_set',
							'title' => esc_html__('Blog Prev & Next', 'kotlis'),
							'subtitle' => esc_html__('Enable/Disable prev & next for blog single page.', 'kotlis'),
							'default'  => 'no',
							'options' => array(
									'yes'=> esc_html__('Enable', 'kotlis'),
									'no'=> esc_html__('Disable', 'kotlis'),
							),
					),	
					array(
							'id' => 'blog-page-url',
							'type' => 'text',
							'title' => __('Blog Page Link URL', 'kotlis'),
							'subtitle' => __('Insert blog page link url  here.', 'kotlis'),
							'default' => '',
							'required' => array('blog_single_page_nav', '=' , 'yes')
							
					),						
					array(
			                'id' => 'notice_blog_translation',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Translation Options', 'kotlis'),
			                'desc' => esc_html__('Default Text Translate Here.', 'kotlis'),
			        ),					
					array(
							'id' => 'blog-read-more',
							'type' => 'text',
							'title' => __('Read More Text', 'kotlis'),
							'subtitle' => __('Change/Repalce blog post "Read More" text here.', 'kotlis'),							
							'default' => '',
							
					),	
					array(
							'id' => 'blog-page-nopost',
							'type' => 'text',
							'title' => __('No Post Available Text', 'kotlis'),
							'subtitle' => __('Change/Repalce blog post "No Post Available" text here.', 'kotlis'),
							'default' => '',
							'required' => array('blog_single_page_nav', '=' , 'yes')
							
					),						
					array(
							'id' => 'arch-page-title',
							'type' => 'text',
							'title' => esc_html__('Archive Page Title', 'kotlis'),
							'subtitle' => esc_html__('Write header title for blog archive page here. Ex: Archive : ', 'kotlis'),
							'default' => '',
							'required' => array('index-header-title-show', '=' , 'yes')
					),	
					array(
							'id' => 'cat-page-title',
							'type' => 'text',
							'title' => esc_html__('Category Page Title', 'kotlis'),
							'subtitle' => esc_html__('Write header title for blog category page here. Ex: Category : ', 'kotlis'),
							'default' => '',
							'required' => array('index-header-title-show', '=' , 'yes')
					),	
	
					array(
							'id' => 'tag-page-title',
							'type' => 'text',
							'title' => esc_html__('Tag Page Title', 'kotlis'),
							'subtitle' => esc_html__('Write header title for blog tag page here. Ex: Tag : ', 'kotlis'),
							'default' => '',
							'required' => array('index-header-title-show', '=' , 'yes')
					),						

					array(
							'id' => 'src-page-title',
							'type' => 'text',
							'title' => esc_html__('Search Page Title', 'kotlis'),
							'subtitle' => esc_html__('Write header title for blog search page title here. Ex: Search Results for :', 'kotlis'),
							'default' => '',
							'required' => array('index-header-title-show', '=' , 'yes')
					),					
					array(
							'id' => 'translet_opt_6',
							'type' => 'text',
							'title' => esc_html__('Type & Hit Enter...', 'kotlis'),
							'subtitle' => esc_html__('Search Widget Placeholder Text.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_7',
							'type' => 'text',
							'title' => esc_html__('Search...', 'kotlis'),
							'subtitle' => esc_html__('Search Page Form Placeholder Text.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_8',
							'type' => 'text',
							'title' => esc_html__('Comment', 'kotlis'),
							'subtitle' => esc_html__('Post Meta.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_9',
							'type' => 'text',
							'title' => esc_html__('Comments', 'kotlis'),
							'subtitle' => esc_html__('Post Meta.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_10',
							'type' => 'text',
							'title' => esc_html__('One comment on', 'kotlis'),
							'subtitle' => esc_html__('Post Comment Section.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_11',
							'type' => 'text',
							'title' => esc_html__('Comment on', 'kotlis'),
							'subtitle' => esc_html__('Post Comment Section.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_12',
							'type' => 'text',
							'title' => esc_html__('Comments on', 'kotlis'),
							'subtitle' => esc_html__('Post Comment Section.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_13',
							'type' => 'text',
							'title' => esc_html__('Comments are closed.', 'kotlis'),
							'subtitle' => esc_html__('Post Comment Section.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_14',
							'type' => 'text',
							'title' => esc_html__('Your Name', 'kotlis'),
							'subtitle' => esc_html__('Post Comment Section Form.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_15',
							'type' => 'text',
							'title' => esc_html__('Your Email', 'kotlis'),
							'subtitle' => esc_html__('Post Comment Section Form.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_16',
							'type' => 'text',
							'title' => esc_html__('Your Comment', 'kotlis'),
							'subtitle' => esc_html__('Post Comment Section Form.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_17',
							'type' => 'text',
							'title' => esc_html__('Add Comment', 'kotlis'),
							'subtitle' => esc_html__('Post Comment Section Form.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_18',
							'type' => 'text',
							'title' => esc_html__('Prev', 'kotlis'),
							'subtitle' => esc_html__('Post & Portfolio Pagination.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_20',
							'type' => 'text',
							'title' => esc_html__('Next', 'kotlis'),
							'subtitle' => esc_html__('Post & Portfolio Pagination.', 'kotlis'),
					),

					
					array(
							'id' => 'translet_opt_23',
							'type' => 'text',
							'title' => esc_html__('No Item Found', 'kotlis'),
							'subtitle' => esc_html__('Post Search Page.', 'kotlis'),
					),
					
					array(
							'id' => 'translet_opt_24',
							'type' => 'text',
							'title' => esc_html__('Please Search Again.', 'kotlis'),
							'subtitle' => esc_html__('Post Search Page.', 'kotlis'),
					),	
					
                    )
                ) );			

            
			Redux::setSection( $opt_name, array(
                'icon'   => 'el-icon-brush',
                'title'  => esc_html__( 'Styling', 'cyberbook' ),
                'fields' => array(
						array(
			                'id' => 'noticebase_theme_color',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Theme Color Scheme Options', 'kotlis'),
			                'desc' => esc_html__('Choose theme color scheme style.', 'kotlis')
						),					
						array(
							'id' => 'colorstyle',
							'type' => 'button_set',
							'title' => esc_attr__('Color Scheme', 'kotlis'),
							'subtitle' => esc_attr__('Choose theme color scheme style.', 'kotlis'),
							'default'  => 'no',
							'options' => array(
								'no'=> esc_html__('Light Version', 'kotlis'),
								'yes'=> esc_html__('Dark Version', 'kotlis'),
							),							
						),
						array(
							'id' => 'notice_site_basecolor',
							'type' => 'info',
							'notice' => true,
							'style' => 'success',
							'title' => esc_html__('Site Base Colors.', 'kotlis'),
							'desc' => esc_html__('', 'kotlis'),
						),
						array(
							'id'       => 'opt_site_base_one',
							'type'     => 'color',
							'title'    => esc_html__( 'Base Color 1', 'kotlis' ),
							'subtitle' => esc_html__( '', 'kotlis' ),
							'desc'     => esc_html__( '', 'kotlis' ),
							'output'    => array(
								'background' => 'body, .main-header, .loader-wrap, .slider-counter_wrap, .bottom-panel, .search-input input, .scroll-nav-wrap, .fixed-bottom-content, .bottom-filter-wrap, .thumbnail-container, .thumb-img:before, .main-header .search-input form input[type="text"], .hiiden-sidebar-wrap, .share-wrapper, .home-main_container, .multi-slideshow-wrap_3, .column-wrapper, .column-notifer, .testi-item p, .author-social, .fixed-bottom-content.fbc_white, .fix-pr-det, .swiper-link, .hero-slider_details_wrap, .widget_tag_cloud a, .widget_product_tag_cloud a', 
							),
						),
						array(
							'id'       => 'opt_site_base_two',
							'type'     => 'color',
							'title'    => esc_html__( 'Base Color 2', 'kotlis' ),
							'subtitle' => esc_html__( '', 'kotlis' ),
							'desc'     => esc_html__( '', 'kotlis' ),
							'output'    => array(
								'background' => '#subscribe-button, .twitt_btn, #footer-twiit p.interact a, .slider-counter_wrap .fw-carousel-counter, .slider-counter_wrap .swiper-counter, .slider-counter_wrap .count-folio, .hs_init:before, .btn, .skillbar-bg, .serv-price, .to-top, .bottom-filter-wrap:before, .single-carousel-control .fw-carousel-counter, .fw_cb, .box-media-zoom, .hero-slider_details_url, .big-bg-container, #submit_btn, .author-social li, .ss-slider-cont', 
							),
						),
						
						array(
							'id' => 'notice_site_overlay',
							'type' => 'info',
							'notice' => true,
							'style' => 'success',
							'title' => esc_html__('Image Overlay.', 'kotlis'),
							'desc' => esc_html__('All image overlay.', 'kotlis'),
						),
						array(
							'id'       => 'opt_df_overlay',
							'type'     => 'color_rgba',
							'title'    => esc_html__( 'Image Overlay', 'kotlis' ),
							'subtitle' => esc_html__( '', 'kotlis' ),
							'desc'     => esc_html__( '', 'kotlis' ),
							'output'    => array(
								'background' => '.overlay', 
							),
						),
					)
            ) 	);
			Redux::setSection( $opt_name, array(
                    'icon'   => 'el el-icon-text-width',
                    'title'  => __( 'Typography', 'kotlis' ),
                    'fields' => array(     
						array(
                            'id'          => 'typo_body',
                            'type'        => 'typography', 
                            'title'       => __('Body', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('body'),
                            'units'       =>'px',
                            'line-height'       =>false,
                            'subtitle'    => esc_attr__('Specify the Body Text font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),
						array(
			                'id' => 'notice_critical11',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Entry Headings', 'kotlis'),
			                'desc' => __('Entry Headings in posts/pages', 'kotlis')
			            ),								
                        array(
                            'id'          => 'typography-h1',
                            'type'        => 'typography', 
                            'title'       => __('H1', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('h1'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the Heading font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
                        array(
                            'id'          => 'typography-h2',
                            'type'        => 'typography', 
                            'title'       => __('H2', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('h2'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the Heading font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),      
                        ),
                        array(
                            'id'          => 'typography-h3',
                            'type'        => 'typography', 
                            'title'       => __('H3', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('h3'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the Heading font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
                        array(
                            'id'          => 'typography-h4',
                            'type'        => 'typography', 
                            'title'       => __('H4', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('h4'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the Heading font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),                        	
                        array(
                            'id'          => 'typography-h5',
                            'type'        => 'typography', 
                            'title'       => __('H5', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('h5'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the Heading font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
                        array(
                            'id'          => 'typography-h6',
                            'type'        => 'typography', 
                            'title'       => __('H6', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('h6'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the Heading font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
						array(
			                'id' => 'notice_critical1_navmenu',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Permalink', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),
                        array(
                            'id'          => 'typography-lnurl',
                            'type'        => 'typography', 
                            'title'       => __('Link URL', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('a'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the permalink link url font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),		
						array(
                            'id'          => 'typography-a-hover',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Link URL Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('a:focus, a:hover'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the permalink font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),							
						array(
			                'id' => 'notice_critical1_button',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Button', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),						
                        array(
                            'id'          => 'typography-a-button',
                            'type'        => 'typography', 
                            'title'       => __('Button Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.btn'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the button font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),		
						array(
                            'id'          => 'typography-a-hover-button',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Button Text Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.btn:hover'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the button font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),		
						array(
			                'id' => 'notice_critical1_sideblock',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Sideblock', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),						
                        array(
                            'id'          => 'typography-sideblock-text',
                            'type'        => 'typography', 
                            'title'       => __('Sideblock Title ', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.column-title h2'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-sideblock-subtext',
                            'type'        => 'typography', 
                            'title'       => __('Sideblock Subtitle ', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.column-title h3'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),	
						array(
			                'id' => 'notice_critical1_banner',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Banner', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),						
                        array(
                            'id'          => 'typography-banner-text',
                            'type'        => 'typography', 
                            'title'       => __('Banner Title ', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.img-section-title h2'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-banner-subtext',
                            'type'        => 'typography', 
                            'title'       => __('Banner Subtitle ', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.img-section-title h3'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),							
						array(
			                'id' => 'notice_critical13',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Page', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),
                        array(
                            'id'          => 'typography-pgtl',
                            'type'        => 'typography', 
                            'title'       => __('Page Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.section-title h3'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the page title font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),						
                        array(
                            'id'          => 'typography-pgsubtl',
                            'type'        => 'typography', 
                            'title'       => __('Page Subtitle', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.section-title h4'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the page subtitle font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
                        array(
                            'id'          => 'typography-pgcontentl',
                            'type'        => 'typography', 
                            'title'       => __('Content', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.page-content p, .sec-text p, .sec-text'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the page content text font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-pg-scroll-menu',
                            'type'        => 'typography', 
                            'title'       => __('Page Scroll Menu', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.scroll-nav li a'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
                        array(
                            'id'          => 'typography-pg-scroll-menu-hover',
                            'type'        => 'typography', 
                            'title'       => __('Page Scroll Menu Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.scroll-nav li a.act-scrlink'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),							
						array(
			                'id' => 'notice_critical14',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Post', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),	
                        array(
                            'id'          => 'typography-bltl',
                            'type'        => 'typography', 
                            'title'       => __('Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.post.fw-post h2'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the blog post title font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),						
                        array(
                            'id'          => 'typography-blcon',
                            'type'        => 'typography', 
                            'title'       => __('Content', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.post-content p, .comment-text p, p.blog-text'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the blog post content font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-post-meta',
                            'type'        => 'typography', 
                            'title'       => __('Post Info', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.blog-title-opt li, .pr-tags li'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-post-meta-permalink',
                            'type'        => 'typography', 
                            'title'       => __('Post Info Permalink', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.blog-title-opt li a, .pr-tags li a'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-post-meta-permalink-hover',
                            'type'        => 'typography', 
                            'title'       => __('Post Info Permalink Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.blog-title-opt li a:hover, .pr-tags li a:hover'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-post-read-more',
                            'type'        => 'typography', 
                            'title'       => __('Read More Button', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.post .btn'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),		
                        array(
                            'id'          => 'typography-post-author-title',
                            'type'        => 'typography', 
                            'title'       => __('Author Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.author-content h5'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-post-author-bio',
                            'type'        => 'typography', 
                            'title'       => __('Author Bio Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.author-content p'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),		
                        array(
                            'id'          => 'typography-post-comment-reply-title',
                            'type'        => 'typography', 
                            'title'       => __('Comment & Reply Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('#comments-title, #reply-title'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-post-comment-meta',
                            'type'        => 'typography', 
                            'title'       => __('Comment Meta Info', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.comment-meta, .comment-meta a'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),		
                        array(
                            'id'          => 'typography-post-comment-notes',
                            'type'        => 'typography', 
                            'title'       => __('Comment Notes Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('p.comment-notes, .comment-notes, .logged-in-as, p.comment-form-cookies-consent'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),							
						
						array(
			                'id' => 'notice_critical_header_nav_section',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Header Navigation Section', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),							
						array(
                            'id'          => 'typography-a-navmenu',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Navigation Menu', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.nav-holder nav li a'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the nav link font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-a-navmenu-hover',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Navigation Menu Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
							'font-style'  => false,
							'font-family' => false,
							'font-size'   => false,
							'font-weight'   => true,
							'text-align'   => false,
                            'line-height' => false,
                            'output'      => array('.nav-holder nav li a.act-link, .nav-holder nav li a:hover'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the nav link font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
                        array(
                            'id'          => 'typo_menu_iten_active',
                            'type'        => 'typography', 
                            'title'       => esc_html__('Navigation Menu Item Active', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
							'font-style'  => false,
							'font-family' => false,
							'font-size'   => false,
							'font-weight'   => true,
							'text-align'   => false,
                            'line-height' => false,
                            'output'      => array('.nav-holder nav li.current-menu-parent > a, .nav-holder nav li.current-menu-item > a'),
                            'units'       =>'px',
                            'subtitle'    => esc_html__('Specify the Menu Item Text font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight'   => false,
                            ),
						),
						array(
                            'id'          => 'typo_menu_iten_active_border',
							'type'     => 'background',
							'title'    => esc_html__('Navigation Menu Item Active Border', 'kotlis'),
							'subtitle' => esc_html__('', 'kotlis'),
							'output'      => array('.nav-holder nav li a:before'),
							'background-color' => true,
							'background-image' => false,
							'background-position' => false,
							'background-repeat' => false,
							'background-attachment' => false,
							'background-size' => false,
							
						),							
						array(
                            'id'          => 'typography-a-navmenu-sub',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Navigation Menu Sub', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.nav-holder nav li ul a'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the nav link font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-a-navmenu-sub-hover',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Navigation Menu Sub Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
							'font-style'  => false,
							'font-family' => false,
							'font-size'   => false,
							'font-weight'   => true,
							'text-align'   => false,
                            'line-height' => false,
                            'output'      => array('.nav-holder nav .sub-menu li a.act-link, .nav-holder nav .sub-menu li a:hover'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the nav link font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
                        array(
                            'id'          => 'typo_menu_iten_sub_active',
                            'type'        => 'typography', 
                            'title'       => esc_html__('Navigation Menu Sub Item Active', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
							'font-style'  => false,
							'font-family' => false,
							'font-size'   => false,
							'font-weight'   => true,
							'text-align'   => false,
                            'line-height' => false,
                            'output'      => array('.nav-holder nav .sub-menu li.current-menu-parent > a, .nav-holder nav .sub-menu li.current-menu-item > a'),
                            'units'       =>'px',
                            'subtitle'    => esc_html__('Specify the Menu Item Text font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight'   => false,
                            ),
						),						
						array(
                            'id'          => 'typography-header-contact-title',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Contact Info Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.contact-info-btn'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),
						array(
                            'id'          => 'typography-header-contact-text-title',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Contact Info Text Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.contact-details ul li span'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-header-contact-text-subtitle',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Contact Info Text Subitle', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.contact-details ul li a'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-header-tooltip',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Header Tooltip', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.share-btn span'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
                            ),
						),
						array(
                            'id'          => 'typography-header-share',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Social Share text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.share-container a'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
						array(
			                'id' => 'notice_critical_footer_nav_section',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Footer Scroll Navigation', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),							
						array(
                            'id'          => 'typography-footer-a-navmenu',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Navigation Menu', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.scroll-nav li a'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the nav link font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-footer-a-navmenu-hover',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Navigation Menu Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
							'font-style'  => false,
							'font-family' => false,
							'font-size'   => false,
							'font-weight'   => true,
							'text-align'   => false,
                            'line-height' => false,
                            'output'      => array('.scroll-nav li a:hover'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the nav link font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
                        array(
                            'id'          => 'typo_footer_menu_iten_active',
                            'type'        => 'typography', 
                            'title'       => esc_html__('Navigation Menu Item Active', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
							'font-style'  => false,
							'font-family' => false,
							'font-size'   => false,
							'font-weight'   => true,
							'text-align'   => false,
                            'line-height' => false,
                            'output'      => array('.scroll-nav li a.act-scrlink'),
                            'units'       =>'px',
                            'subtitle'    => esc_html__('Specify the Menu Item Text font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight'   => false,
                            ),
						),
						array(
                            'id'          => 'typo_footer_menu_iten_active_border',
							'type'     => 'background',
							'title'    => esc_html__('Navigation Menu Item Active Border', 'kotlis'),
							'subtitle' => esc_html__('', 'kotlis'),
							'output'      => array('.scroll-nav li a:before'),
							'background-color' => true,
							'background-image' => false,
							'background-position' => false,
							'background-repeat' => false,
							'background-attachment' => false,
							'background-size' => false,
							
						),							
						array(
			                'id' => 'notice_critical_sidebar_section',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Click Sidebar Widgets Section', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),							
						array(
                            'id'          => 'typography-widget-title',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Widgets Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.sb-widget-wrap h3'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),
						array(
                            'id'          => 'typography-widget-content',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Widgets Content', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.sb-widget-wrap p, .sb-widget-wrap .textwidget, .widget-posts-date'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-widget-permalink',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Widgets Permalink Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.widget li a'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),						
						array(
                            'id'          => 'typography-widget-permalink-hover',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Widgets Permalink Text Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
							'font-style'  => false,
							'font-family' => false,
							'font-size'   => false,
							'font-weight'   => true,
							'text-align'   => false,
                            'line-height' => false,
                            'output'      => array('.widget li a:hover'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),						
						array(
                            'id'          => 'typography-widget-button',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Widgets Button', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.mc4wp-form input[type="submit"], .mc4wp-form button, .twitt_btn'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-widget-button-hover',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Widgets Button Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
							'font-style'  => false,
							'font-family' => false,
							'font-size'   => false,
							'font-weight'   => true,
							'text-align'   => false,
                            'line-height' => false,
                            'output'      => array('#subscribe-button:hover, .twitt_btn:hover'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-widget-social',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Widgets Social Icon', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.widget .sidebar-social li a'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-widget-social-hover',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Widgets Social Icon Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
							'font-style'  => false,
							'font-family' => false,
							'font-size'   => false,
							'font-weight'   => true,
							'text-align'   => false,
                            'line-height' => false,
                            'output'      => array('.widget .sidebar-social li a:hover '),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
						array(
                            'id'          => 'typo-widget-social-bg',
							'type'     => 'background',
							'title'    => esc_html__('Widgets Social Icon Background', 'kotlis'),
							'subtitle' => esc_html__('', 'kotlis'),
							'output'      => array('.widget .sidebar-social li a'),
							'background-color' => true,
							'background-image' => false,
							'background-position' => false,
							'background-repeat' => false,
							'background-attachment' => false,
							'background-size' => false,
							
						),						
						array(
                            'id'          => 'typography-widget-twitt',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Widgets Tweet Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('#footer-twiit p.tweet'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-widget-twitt-permalink',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Widgets Tweet Permalink Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.widget.widget-block #footer-twiit ul li a'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-widget-twitt-permalink-hover',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Widgets Tweet Permalink Text Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
							'font-style'  => false,
							'font-family' => false,
							'font-size'   => false,
							'font-weight'   => true,
							'text-align'   => false,
                            'line-height' => false,
                            'output'      => array('#footer-twiit p.tweet a:hover'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),
						),		
						array(
			                'id' => 'notice_critical1_portfolio',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Portfolio', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),	
                        array(
                            'id'          => 'typography-port-title',
                            'type'        => 'typography', 
                            'title'       => __('Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.thumb-info h3 a'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
							'font-weight' => false,
                            'font-size'   => false,
                            'line-height' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-port-title-hover',
                            'type'        => 'typography', 
                            'title'       => __('Title Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.thumb-info h3 a:hover'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),							
                        array(
                            'id'          => 'typography-port-subtitle',
                            'type'        => 'typography', 
                            'title'       => __('Subtitle', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.thumb-info p'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),		
                        array(
                            'id'          => 'typography-port-title-grid',
                            'type'        => 'typography', 
                            'title'       => __('Title Grid Style', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.vis-thumb-info .thumb-info h3, .vis-thumb-info .thumb-info h3 a'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-port-title-hover-grid',
                            'type'        => 'typography', 
                            'title'       => __('Title Grid Style Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.vis-thumb-info .thumb-info h3 a:hover'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),							
                        array(
                            'id'          => 'typography-port-subtitle-grid',
                            'type'        => 'typography', 
                            'title'       => __('Subtitle Grid Style', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.vis-thumb-info .thumb-info p'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),		
                        array(
                            'id'          => 'typography-port-content-title',
                            'type'        => 'typography', 
                            'title'       => __('Content Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.pr-det-container h2'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-port-content-text',
                            'type'        => 'typography', 
                            'title'       => __('Content Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.pr-det-container p'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),							
                        array(
                            'id'          => 'typography-port-caption-title',
                            'type'        => 'typography', 
                            'title'       => __('Caption Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.caption-wrap ul li span'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),		
                        array(
                            'id'          => 'typography-port-caption-text',
                            'type'        => 'typography', 
                            'title'       => __('Caption Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.caption-wrap ul li a'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),		
                        array(
                            'id'          => 'typography-port-caption-text-hover',
                            'type'        => 'typography', 
                            'title'       => __('Caption Text Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.caption-wrap ul li a:hover'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-port-content-nav-text',
                            'type'        => 'typography', 
                            'title'       => __('Tooltip Prev Next Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.content-nav li a.ln span.tooltip, .content-nav li a.rn span.tooltip'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-port-content-nav-text-footer',
                            'type'        => 'typography', 
                            'title'       => __('Footer Prev Next Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.content-nav-fixed li a span'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),							
                        array(
                            'id'          => 'typography-port-filter-title',
                            'type'        => 'typography', 
                            'title'       => __('Filter Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.filter-title'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),							
						),
                        array(
                            'id'          => 'typography-port-filter-cat-title',
                            'type'        => 'typography', 
                            'title'       => __('Filter Category Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.gallery-filters a'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),							
						),	
                        array(
                            'id'          => 'typography-port-filter-cat-act-title',
                            'type'        => 'typography', 
                            'title'       => __('Filter Category Active Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.gallery-filters a.gallery-filter-active'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
                            'line-height' => false,
							'font-weight' => false,
                            ),							
						),		
                        array(
                            'id'          => 'typography-port-view-title',
                            'type'        => 'typography', 
                            'title'       => __('View Details & Show Thumb Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.single-carousel-control_list li'),
                            'units'       =>'px',
                            'subtitle'    => __('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),							
						),	
						array(
			                'id' => 'notice_critical_intro_section',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Intro Section', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),	
						array(
                            'id'          => 'typography-intro-slider-follow-text',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Slideshow Follow text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.follow-wrap_title span'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),		
						array(
                            'id'          => 'typography-intro-slider-multi-title',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Slideshow Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.home-main_title_item h2'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-intro-slider-multi-subtitle',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Slideshow Subtitle', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.home-main_title_item h4'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),							
						array(
                            'id'          => 'typography-intro-slider-multi-contant',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Slideshow Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.home-main_title_item p'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),						
						array(
                            'id'          => 'typography-intro-slider-details-title',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Slider Details & Classic Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.fs-slider_align_title h2, .fs-slider_align_title h2 a'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),			
						array(
                            'id'          => 'typography-intro-slider-details-subtitle',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Slider Details & Classic Subtitle', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.fs-slider_align_title p'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-intro-slider-details-info-title',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Slider Details Info Title', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.hero-slider_details li'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),
						array(
                            'id'          => 'typography-intro-slider-details-info-subtitle',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Slider Details Info Subtitle', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.hero-slider_details li span'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),						
						array(
                            'id'          => 'typography-intro-slider-details-info-number',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Slider Details Info Number', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.hero-slider_details li:before'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),
						array(
			                'id' => 'notice_critical_services_section',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Services Section', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),	
						array(
                            'id'          => 'typography-serv-title',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Services Title Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.serv-text h4 a'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-serv-list',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Services List Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.serv-text ul li a, .serv-text ul li'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-serv-price',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Services Price Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.serv-text .serv-price'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),
						array(
                            'id'          => 'typography-serv-price2',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Services Price Currency', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.serv-text .serv-price span'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),
						array(
			                'id' => 'notice_critical_contact_section',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Contact Section', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),	
						array(
                            'id'          => 'typography-contact-title',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Contact Title Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'line-height' => true,
							'text-transform'  => true,
                            'output'      => array('.sec-contact-info .contact-details ul li span'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'text-transform' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-contact-info-text',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Contact Info Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
							'line-height' => true,
							'text-transform'  => true,
                            'output'      => array('.sec-contact-info .contact-details ul li, .sec-contact-info .contact-details ul li a, .sec-contact-info .contact-details ul li p'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
							'text-transform' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),							
						array(
			                'id' => 'notice_critical_footer_section',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => __('Footer Section', 'kotlis'),
			                'desc' => __('', 'kotlis')
			            ),	
						array(
                            'id'          => 'typography-copyright',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Copyright Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.policy-box p'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),							
						array(
                            'id'          => 'typography-scroll-down-wrap',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Scroll Down', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.scroll-down-wrap span'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-scroll-down-wrap-light',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Scroll Down', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.scroll-down-wrap.transparent_sdw span'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),							
						array(
                            'id'          => 'typography-totop',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Back To Top Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.to-top-btn'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),	
						array(
                            'id'          => 'typography-totop-hover',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Back To Top Text Hover', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.to-top-btn:hover'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),						
						array(
                            'id'          => 'typography-post-pev-next',
                            'type'        => 'typography', 
                            'title'       => esc_attr__('Previous Next Text', 'kotlis'),
                            'google'      => true, 
                            'font-backup' => false,
                            'output'      => array('.content-nav-fixed li a span'),
                            'units'       =>'px',
                            'subtitle'    => esc_attr__('Specify the font properties.', 'kotlis'),
                            'default'     => array(
                            'color'       => false,
                            'font-style'  => false,
                            'font-family' => false,
                            'google'      => true,
                            'font-size'   => false,
							'font-weight' => false,
                            'line-height' => false,
                            ),
						),							
                    )
               ) );		            
				 Redux::setSection( $opt_name, array(
                    'icon'   => 'el el-icon-th-large',
                    'title'  => esc_html__( 'Footer Settings', 'kotlis' ),
                    'fields' => array(
					
					array(
							'id' => 'theme-cus-copy',
							'type' => 'info',
		                    'notice' => true,
		                    'style' => 'info',
							'title' => esc_html__('Footer Copyright Text', 'kotlis'),
							'desc' => esc_html__('Footer copy right Text', 'kotlis')
							
					  ),
					
					array(
							'id' => 'copyright',
							'type' => 'editor',
							'wpautop'=>true,
							'compiler' => 'true',
							'title' => esc_html__('Copyright text of the Website', 'kotlis'),
							'subtitle' => esc_html__('Write a Copyright text of your WebSite. year shortcode [kotlis_year]', 'kotlis'),
							'default'          => '<span>&#169; Kotlis [kotlis_year]  |  All rights reserved. </span>',
							'args'   => array(
								'teeny'            => true,
								'textarea_rows'    => 10
							)
					),
					
					
			        array(
							'id' => 'totop',
							'type' => 'button_set',
							'title' => esc_attr__('Back To Top', 'kotlis'),
							'default'  => 'yes',
							'options' => array(
								'yes'=> esc_attr__('Enable', 'kotlis'),
								'no'=> esc_attr__('Disable', 'kotlis'),
							),
					),					
					
					array(
			                'id' => 'notice_header_totop_translation',
			                'type' => 'info',
			                'notice' => true,
			                'style' => 'success',
			                'title' => esc_html__('Back To Top Section Translation Options', 'kotlis'),
			                'desc' => esc_html__('Back To Top Section Text Translation Options', 'kotlis'),
							'required' => array('totop', '=' , 'yes')
			            ),	

					array(
							'id' => 'to-top-title',
							'type' => 'text',
							'compiler' => 'true',
							'title' => esc_html__('Back To Top Text', 'kotlis'),
							'subtitle' => esc_html__('Replace "Back To Top" text here.', 'kotlis'),
							'required' => array('totop', '=' , 'yes')
					),						
					
					)
                ) );
				
				Redux::setSection( $opt_name, array(
                    'icon'   => 'el el-icon-key',
                    'title'  => esc_html__( 'Documentation', 'kotlis' ),
                    'fields' => array(					
					
					array(
							'id' => 'docs',
							'type' => 'info',
		                    'notice' => true,
		                    'style' => 'info',
							'title' => esc_html__('Kotlis Theme Documentation', 'kotlis'),
							'desc' => __('<a href="http://webredox.net/demo/wp/kotlis/doc/documentation.html" target="_blank">Click Here</a> To get the theme documentation.', 'kotlis')
							
					),	

			
			
					)
                ));
				
				
    /*
     * <--- END SECTIONS
     */


    /*
     *
     * YOU MUST PREFIX THE FUNCTIONS BELOW AND ACTION FUNCTION CALLS OR ANY OTHER CONFIG MAY OVERRIDE YOUR CODE.
     *
     */

    /*
    *
    * --> Action hook examples
    *
    */

    // If Redux is running as a plugin, this will remove the demo notice and links
    //add_action( 'redux/loaded', 'remove_demo' );

    // Function to test the compiler hook and demo CSS output.
    // Above 10 is a priority, but 2 in necessary to include the dynamically generated CSS to be sent to the function.
    //add_filter('redux/options/' . $opt_name . '/compiler', 'compiler_action', 10, 3);

    // Change the arguments after they've been declared, but before the panel is created
    //add_filter('redux/options/' . $opt_name . '/args', 'change_arguments' );

    // Change the default value of a field after it's been set, but before it's been useds
    //add_filter('redux/options/' . $opt_name . '/defaults', 'change_defaults' );

    // Dynamically add a section. Can be also used to modify sections/fields
    //add_filter('redux/options/' . $opt_name . '/sections', 'dynamic_section');

    /**
     * This is a test function that will let you see when the compiler hook occurs.
     * It only runs if a field    set with compiler=>true is changed.
     * */
    if ( ! function_exists( 'compiler_action' ) ) {
        function compiler_action( $options, $css, $changed_values ) {
            echo '<h1>The compiler hook has run!</h1>';
            echo "<pre>";
            print_r( $changed_values ); // Values that have changed since the last save
            echo "</pre>";
            //print_r($options); //Option values
            //print_r($css); // Compiler selector CSS values  compiler => array( CSS SELECTORS )
        }
    }

    /**
     * Custom function for the callback validation referenced above
     * */
    if ( ! function_exists( 'redux_validate_callback_function' ) ) {
        function redux_validate_callback_function( $field, $value, $existing_value ) {
            $error   = false;
            $warning = false;

            //do your validation
            if ( $value == 1 ) {
                $error = true;
                $value = $existing_value;
            } elseif ( $value == 2 ) {
                $warning = true;
                $value   = $existing_value;
            }

            $return['value'] = $value;

            if ( $error == true ) {
                $return['error'] = $field;
                $field['msg']    = 'your custom error message';
            }

            if ( $warning == true ) {
                $return['warning'] = $field;
                $field['msg']      = 'your custom warning message';
            }

            return $return;
        }
    }

    /**
     * Custom function for the callback referenced above
     */
    if ( ! function_exists( 'redux_my_custom_field' ) ) {
        function redux_my_custom_field( $field, $value ) {
            print_r( $field );
            echo '<br/>';
            print_r( $value );
        }
    }

    /**
     * Custom function for filtering the sections array. Good for child themes to override or add to the sections.
     * Simply include this function in the child themes functions.php file.
     * NOTE: the defined constants for URLs, and directories will NOT be available at this point in a child theme,
     * so you must use get_template_directory_uri() if you want to use any of the built in icons
     * */
    if ( ! function_exists( 'dynamic_section' ) ) {
        function dynamic_section( $sections ) {
            //$sections = array();
            $sections[] = array(
                'title'  => esc_html__( 'Section via hook', 'kotlis' ),
                'desc'   => esc_html__( '<p class="description">This is a section created by adding a filter to the sections array. Can be used by child themes to add/remove sections from the options.</p>', 'kotlis' ),
                'icon'   => 'el el-paper-clip',
                // Leave this as a blank section, no options just some intro text set above.
                'fields' => array()
            );

            return $sections;
        }
    }

    /**
     * Filter hook for filtering the args. Good for child themes to override or add to the args array. Can also be used in other functions.
     * */
    if ( ! function_exists( 'change_arguments' ) ) {
        function change_arguments( $args ) {
            //$args['dev_mode'] = true;

            return $args;
        }
    }

    /**
     * Filter hook for filtering the default value of any given field. Very useful in development mode.
     * */
    if ( ! function_exists( 'change_defaults' ) ) {
        function change_defaults( $defaults ) {
            $defaults['str_replace'] = 'Testing filter hook!';

            return $defaults;
        }
    }

    /**
     * Removes the demo link and the notice of integrated demo from the redux-kotlis plugin
     */
    if ( ! function_exists( 'remove_demo' ) ) {
        function remove_demo() {
            // Used to hide the demo mode link from the plugin page. Only used when Redux is a plugin.
            if ( class_exists( 'ReduxFrameworkPlugin' ) ) {
                remove_filter( 'plugin_row_meta', array(
                    ReduxFrameworkPlugin::instance(),
                    'plugin_metalinks'
                ), null, 2 );

                // Used to hide the activation notice informing users of the demo panel. Only used when Redux is a plugin.
                remove_action( 'admin_notices', array( ReduxFrameworkPlugin::instance(), 'admin_notices' ) );
            }
        }
    }

