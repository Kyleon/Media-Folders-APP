<?php
function kotlis_pagination($pages = '', $range = 2)
{ 
 $showitems = ($range * 2)+1;  

     global $paged;
     if(empty($paged)) $paged = 1;

     if($pages == '')
     {
         global $wp_query;
         $pages = $wp_query->max_num_pages;
         if(!$pages)
         {
             $pages = 1;
         }
     }   

     if(1 != $pages)
     {
         echo "<div class='big-bg-container fl-wrap'><div class='pagination-container fl-wrap'>";
          echo "<a class='prevposts-link' href='".get_pagenum_link(1)."'><i class='fal fa-chevron-left'></i></a>";
         

         for ($i=1; $i <= $pages; $i++)
         {
             if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems ))
             {
                 echo esc_attr(($paged == $i))? "<a class='current-page'>".$i."</a>":"<a class='blog-page' href='".get_pagenum_link($i)."'>".$i."</a>";
             }
         }

         
          echo "<a class='nextposts-link' href='".get_pagenum_link($pages)."'><i class='fal fa-chevron-right'></i></a>";
         echo "</div></div>\n";
     }
}
?>