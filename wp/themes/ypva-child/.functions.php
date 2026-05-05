<?php
add_action( 'wp_enqueue_scripts', 'kotlis_add_stylesheet' );
function kotlis_add_stylesheet() {
    wp_enqueue_style( 'kotlis-child-style', get_stylesheet_directory_uri() . '/style.css', false, '1.0', 'all' );
}


add_action('wp_head', 'add_googleanalytics');
function add_googleanalytics() { ?>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-RLCYB0EFS5"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-7Z64J0BX75');
</script>

<?php } 

function serve_webp_images($content) {
    return preg_replace('/\.(jpg|png)/', '.webp', $content);
}
add_filter('the_content', 'serve_webp_images');

function disable_unused_wp_features() {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    //remove_action('wp_head', 'print_emoji_detection_script', 7);
    //remove_action('wp_print_styles', 'print_emoji_styles');
}
add_action('init', 'disable_unused_wp_features');


function fix_kotlis_gallery() {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof galleryInit === "function") {
                galleryInit();
            }
        });


		document.addEventListener("DOMContentLoaded", function() {
			let gallery = document.querySelector(".gallery");
			if (gallery) {
				new Flickity(gallery, {
					cellAlign: "center",
					wrapAround: true,
					freeScroll: true,
					autoPlay: 4000,
					lazyLoad: 2
				});
			}
		});


    </script>';
}
add_action('wp_footer', 'fix_kotlis_gallery');

function add_social_sharing_buttons($content) {
    if (is_singular('portfolio')) {
        $image_url = get_the_post_thumbnail_url();
        if ($image_url) {
            $content .= '<p>
                <a href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode($image_url) . '" target="_blank">📘 Compartir en Facebook</a> | 
                <a href="https://twitter.com/intent/tweet?url=' . urlencode($image_url) . '" target="_blank">🐦 Compartir en Twitter</a>
            </p>';
        }
    }
    return $content;
}
add_filter('the_content', 'add_social_sharing_buttons');

function enqueue_flickity() {
    if (is_singular('portfolio')) {
        wp_enqueue_script('flickity', 'https://cdnjs.cloudflare.com/ajax/libs/flickity/2.3.0/flickity.pkgd.min.js', [], null, true);
        wp_enqueue_style('flickity-css', 'https://cdnjs.cloudflare.com/ajax/libs/flickity/2.3.0/flickity.min.css');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_flickity');


function optimize_kotlis_scripts() {
    if (!is_admin()) {
        wp_deregister_script('jquery');
        wp_register_script('jquery', includes_url('/js/jquery/jquery.js'), false, null, true);
        wp_enqueue_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'optimize_kotlis_scripts');



function add_image_loader() {
    echo '<style>
        .lazyload { opacity: 0; transition: opacity 0.5s ease-in-out; }
        .lazyload.loaded { opacity: 1; }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let images = document.querySelectorAll("img.lazyload");
            images.forEach(img => {
                img.onload = () => img.classList.add("loaded");
            });
        });
    </script>';
}
add_action('wp_footer', 'add_image_loader');

function banner_cookies() {
    echo '<link rel="stylesheet" href="'.get_stylesheet_directory_uri().'/cookies/pdcc.min.css">
<script charset="utf-8" src="'.get_stylesheet_directory_uri().'/cookies/pdcc.min.js"></script>
<script type="text/javascript">
    PDCookieConsent.config({
      "cookiePolicyLink": "",
      "hideModalIn": [""],
      "styles": {
        "primaryButton": {
          "bgColor" : "#A1FFA1",
          "txtColor": "#036900"
        },
        "secondaryButton": {
          "bgColor" : "#EEEEEE",
          "txtColor": "#333333"
        }
      }
    });
	PDCookieConsent.blockList([
		 
]);

  </script>
';
}

function add_lightbox_to_portfolio() {
    if (is_singular('portfolio')) {
        wp_enqueue_script('lightbox2', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js', [], null, true);
        wp_enqueue_style('lightbox2-css', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css');
    }
}
add_action('wp_enqueue_scripts', 'add_lightbox_to_portfolio');



function custom_lazy_load() {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            let images = document.querySelectorAll("img.lazyload");
            let observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        let img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove("lazyload");
                        observer.unobserve(img);
                    }
                });
            });
            images.forEach(img => observer.observe(img));
        });
    </script>';
}
add_action('wp_footer', 'custom_lazy_load');

// Add hook for front-end <head></head>
//add_action( 'wp_head', 'banner_cookies',1 );

function address_mobile_address_bar() {
	$color = "#161616";
	//this is for Chrome, Firefox OS, Opera and Vivaldi
	echo '<meta name="theme-color" content="'.$color.'">';
	//Windows Phone **
	echo '<meta name="msapplication-navbutton-color" content="'.$color.'">';
	// iOS Safari
	echo '<meta name="apple-mobile-web-app-capable" content="yes">';
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';
}
add_action( 'wp_head', 'address_mobile_address_bar' );
add_filter('autoptimize_filter_cachecheck_frequency','monthly');

add_filter( 'mwl_img_title', 'my_filter_title', 10, 3 );

function my_filter_title( $title, $id, $meta ) {
  return $title;
}

add_filter( 'mwl_img_description', 'my_filter_description', 10, 3 );

function my_filter_description( $title, $id, $meta ) {
  return $title;
}

function pantallaCompleta() {
  wp_enqueue_script( 'js-file', get_stylesheet_directory_uri() . '/js/goToFullscreen.js');
}
add_action('wp_enqueue_scripts','pantallaCompleta');


// This will load the CropGuide service script
/* function cropguide_scripts() {
     wp_enqueue_script( 'cropguide', get_stylesheet_directory_uri() . '/crop/my_l.js');
	  wp_enqueue_script( 'cropguidel', get_stylesheet_directory_uri() . '/crop/l.js');
	 // wp_enqueue_script( 'cropguidees', get_stylesheet_directory_uri() . '/crop/es.js');
	  wp_enqueue_style( 'cropguidestyle', get_stylesheet_directory_uri() . '/crop/l.css');
}

add_action('wp_enqueue_scripts', 'cropguide_scripts');
add_action('admin_enqueue_scripts', 'cropguide_scripts'); */