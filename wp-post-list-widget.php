<?php

/*

Plugin Name: WP Post List Widget
Plugin URI: http://wordpress.org/plugins/wp-post-list-widget/
Description: This creates a list of posts of custom type.
Author: Al Stern
Version: 1.0
*/

class Post_List_Widget extends WP_Widget {

			public function __construct() {
				parent::__construct('post_list_widget','Post List'); // Name
				//	array( 'description' => __( 'List of posts of a custom type', 'text_domain' ), ) // Args
				
			}
			public function widget( $args, $instance ) {
				extract( $args );
				$title = apply_filters( 'widget_title', $instance['title'] );
				
				
				echo $before_widget;


				if ( ! empty( $title ) && $instance['hide_title']!="1") echo $before_title . $title . $after_title;



				foreach (get_post_types('','names') as $post_type ) if ($instance['check_type_'.$post_type] == "1") $selected_types[]=$post_type;

				if (count($selected_types)==0) {
					echo $after_widget;
					return;
				}
				
				$args1 = array( 'post_type' => $selected_types, 'posts_per_page'=>-1);

				if ($instance['query_args']!="") $args1=array_merge($args1,json_decode($instance['query_args'],true));
//				pre($args1);

				global $post;
				$original_post = $post;


				$output="";
				$query = new WP_Query( $args1 );
				if ( $query->have_posts() ) {
				  if ($instance['use_template']=="1" && !$instance['script']) $output.=$instance['before_template'];
				  $i=0;
					while ( $query->have_posts() ) {
						$query->the_post();

						$template= ($instance['use_template']=="1")?$instance['template']:""; // put default template here!
						if ($instance['script'] && $instance['script_var']!="") $arr[]=$this->getArrItem($template); else $output.=$this->subst($template);

					}
					if ($instance['script'] && $instance['script_var']!="") echo "<script> var ".$instance['script_var']."=".json_encode($arr)."</script>";
				  if ($instance['use_template']=="1" && !$instance['script']) $output.=$instance['after_template'];
				} else {
					// no posts found
				}

				$post = $original_post;
				setup_postdata($post);
				
				echo apply_filters( 'widget_text', $output);
				echo $after_widget;


			}

			private function getArrItem ($str) {
				global $post;
				$common=$this->get_common_fields();
				$custom=get_post_custom_keys($post->ID);
                $taxonomies=get_object_taxonomies($post,'names');
				foreach ($common as $k=>$v){
					if (strpos($str,"{{".$k."}}")!==false){
						if (isset($v[1])) {
								if ($v[1]=="id") $item[$k]=call_user_func($v[0],$post->ID);
						} else $item[$k]=call_user_func($v[0]);
					}
				}
				foreach ($custom as $k){
					if (strpos($str,"{{".$k."}}")!==false){
						$item[$k]=implode(",",get_post_custom_values($k));
					}
				}
                if (count($taxonomies)) foreach ($taxonomies as $t){
                    if (strpos($str,"{{".$t."}}")!==false){
    					$terms=get_the_terms($post->ID,$t); $s="";
                        if ($terms) foreach ($terms as $tr) $s.=$tr->slug.',';
        				$item[$t].=substr($s,0,-1);
					}
                }
					
				return $item;
  		    }

		
			private function subst ($str) {
				global $post;
				$common=$this->get_common_fields();
				$custom=get_post_custom_keys($post->ID);
                $taxonomies=get_object_taxonomies($post,'names');
				//echo implode(",",$taxonomies);

				$str = preg_replace_callback("/\{\{(.*)\}\}/Usi",function($matches)use($common,$custom,$taxonomies,$post){
					if (array_key_exists($matches[1],$common)){
						if (isset($common[$matches[1]][1])) {
							if ($common[$matches[1]][1]=="id") return call_user_func($common[$matches[1]][0],$post->ID);
						} else return call_user_func($common[$matches[1]][0]);
    				} else if (count($custom) && in_array($matches[1],$custom)){
						return implode(",",get_post_custom_values($matches[1]));
    				} else if (count($taxonomies) && in_array($matches[1],$taxonomies)){
                        $terms=get_the_terms($post->ID,$matches[1]); $s="";
                        if ($terms) foreach ($terms as $t) $s.=$t->slug.',';
						return substr($s,0,-1);
					} else {
                        // !!!!!!!!!!!!!!!!!!!!!!!!!!!! returns empty string if {{..}} not found
                        return "";
                        // return $matches[0];
					}
				},$str);
				return $str;
			}
					
			private function get_common_fields(){
				$get_the_fields="ID,title,content,excerpt,author,post_thumbnail,category,author_posts,tag_list,time,modified_time";
				//$a=array();
				foreach(explode(",",$get_the_fields) as $f)$a[$f]=array("get_the_".$f);
			    $a["type"]=array("get_post_type","id");
			    $a["permalink"]=array("get_permalink","id");
				return $a;
			}
		
			private function get_wp_types_fields(){
				
				global $post;
				$tmp_post = $post;

				$q = new WP_Query( array( 'post_type' => 'wp-types-group', 'posts_per_page'=>-1));
				$a=array();
				foreach($q->posts as $p) {
					$meta = get_post_meta($p->ID);
//					pre($meta);
					foreach (explode(",",$meta['_wp_types_group_post_types'][0]) as $t){
						if ($t=="")continue;
						if (array_search($t,$a)==false)$a[$t]=array();
						foreach (explode(",",$meta['_wp_types_group_fields'][0]) as $f) {
							if (array_search($f,$a[$t])==false)if($f!="")$a[$t][]=$f;
						}
					}
				}
				$post = $tmp_post;
				setup_postdata($post);				
				return $a;
			}		
		
		
		 	public function form( $instance ) {

				if ( isset( $instance[ 'title' ] ) ) {$title = $instance[ 'title' ];}	else {$title = __( 'New title', 'text_domain' );}				
				if ( isset( $instance[ 'hide_title' ] ) ) $hide_title = $instance[ 'hide_title' ];	else $hide_title ="1";				

				?>
				<div class="post_list_widget_form_container">
				<p>	<label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /><br/>
				<input  id="<?php echo $this->get_field_id( 'hide_title' ); ?>" name="<?php echo $this->get_field_name( 'hide_title' ); ?>" type="checkbox" value="1" <?php if ($hide_title=="1") echo "checked"; ?> /> Hide title
				</p>
				<p> Post types in the list:<ul class="post_list_widget_form_post_list">
				<?php 

  			$post_types=get_post_types('','names');
  			$fields=$this->get_wp_types_fields();
				foreach ($post_types as $post_type ) {
					 echo '<li><input  id="'.$this->get_field_id( 'check_type_'.$post_type ).'" name="'.$this->get_field_name( 'check_type_'.$post_type ).'" type="checkbox" value="1"'; if ($instance['check_type_'.$post_type]=="1") echo "checked"; echo " /> <b>".$post_type."</b>";
					 if($fields[$post_type]){
		 			   echo '<div class="post_list_widget_form_fields_btns"';
		 			   if ($instance['check_type_'.$post_type]!="1") echo 'style="display:none"';
		 			   echo '>fields: ';
    				 foreach ($fields[$post_type] as $f) echo ' <span>{{wpcf-'.$f.'}}</span>';
    				 echo '</div>';
					 }
           
           $taxonomies=get_object_taxonomies($post_type,'names');
    			 if(count($taxonomies)){
		 			   echo '<div class="post_list_widget_form_taxonomies_btns"';
		 			   if ($instance['check_type_'.$post_type]!="1") echo 'style="display:none"';
		 			   echo '>taxonomies: ';
             foreach ($taxonomies as $t ) echo '<span>{{'.$t.'}}</span>';
    				 echo '</div>';
    			 }
    			 echo "</li>";
				}

  			echo '</ul></p>Query arguments (json):<br/><textarea id="'.$this->get_field_id( 'query_args' ).'" name="'.$this->get_field_name( 'query_args' ).'" rows=3>'.$instance['query_args'].'</textarea>';
				


  			echo '<br/><input  id="'.$this->get_field_id( 'script' ).'" name="'.$this->get_field_name( 'script' ).'" type="checkbox" value="1"'; if ($instance['script']=="1") echo "checked"; echo " /> Print as Javascript array (invisible)<br/>";
				?>
				<p>	<label for="<?php echo $this->get_field_name( 'script_var' ); ?>"><?php _e( 'Script variable:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'script_var' ); ?>" name="<?php echo $this->get_field_name( 'script_var' ); ?>" type="text" value="<?php echo $instance['script_var']; ?>" /><br/>
				<?php

  			echo '<br/><input  id="'.$this->get_field_id( 'use_template' ).'" name="'.$this->get_field_name( 'use_template' ).'" type="checkbox" value="1"'; if ($instance['use_template']=="1" || !isset($instance['use_template'])) echo "checked"; echo " />Use template<br/>";
  			echo '<br/>Before:<br/><textarea id="'.$this->get_field_id( 'before_template' ).'" name="'.$this->get_field_name( 'before_template' ).'" rows=1>'.$instance['before_template'].'</textarea>';
  			echo '<br/>Template:<br/><textarea id="'.$this->get_field_id( 'template' ).'" name="'.$this->get_field_name( 'template' ).'" class="post_list_widget_template_textarea" rows=4>'.$instance['template'].'</textarea><br/>';

				$common=$this->get_common_fields();
				echo '<div class="post_list_widget_form_fields_btns">';
				foreach ($common as $c=>$v) echo ' <span>{{'.$c.'}}</span>';
				echo '</div>';


  			echo '<br/>After:<br/><textarea id="'.$this->get_field_id( 'after_template' ).'" name="'.$this->get_field_name( 'after_template' ).'" rows=1>'.$instance['after_template'].'</textarea></div>';

			}
		
			public function update( $new_instance, $old_instance ) {
				$instance = array();
				$instance['title'] = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
				$instance['hide_title'] = $new_instance['hide_title'];
  			$post_types=get_post_types('','names');
				foreach ($post_types as $post_type ) $instance['check_type_'.$post_type] = $new_instance['check_type_'.$post_type];
				$instance['query_args'] = $new_instance['query_args'];
				$instance['script'] = $new_instance['script'];
				$instance['script_var'] = $new_instance['script_var'];
				$instance['use_template'] = $new_instance['use_template'];
				$instance['before_template'] = $new_instance['before_template'];
				$instance['template'] = $new_instance['template'];
				$instance['after_template'] = $new_instance['after_template'];
				
				return $instance;			
			}

		}

add_shortcode ('post_list_widget','post_list_widget_shortcode');
function post_list_widget_shortcode($atts){
	ob_start();
	if (empty($atts['types']))return;
	$t=explode(',',$atts['types']);
	foreach($t as $k)$atts['check_type_'.$k]="1";
	the_widget( 'Post_List_Widget', $atts, $args ); 
	$output = ob_get_clean();
	return $output;
}

add_action( 'widgets_init', function(){
     register_widget( 'Post_List_Widget' );
});



add_action( 'admin_init', 'post_list_admin_init' );
   
function post_list_admin_init() {
    wp_enqueue_style( 'PostListStylesheet', plugins_url('style.css', __FILE__) );
	 	wp_enqueue_script( 'PostListScript', plugins_url('script.js', __FILE__));
}

?>
