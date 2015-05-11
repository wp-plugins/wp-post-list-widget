<?php

/*

Plugin Name: WP Post List Widget
Plugin URI: http://wordpress.org/plugins/wp-post-list-widget/
Description: This creates a list of posts of custom type.
Author: Al Stern
Version: 1.3
*/

class Post_List_Widget extends WP_Widget {
	
			public function __construct() {
				parent::__construct('post_list_widget','Post List'); // Name
				//	array( 'description' => __( 'List of posts of a custom type', 'text_domain' ), ) // Args
				
			}
			
			public function widget( $widget_args, $instance ) {
				extract( $widget_args );
				$title = apply_filters( 'widget_title', $instance['title'] );
				
				
				echo $before_widget;


				if ( ! empty( $title ) && $instance['hide_title']!="1") echo $before_title . $title . $after_title;



				foreach (get_post_types('','names') as $post_type ) if ($instance['check_type_'.$post_type] == "1") $selected_types[]=$post_type;

				if (count($selected_types)==0) {
					echo $after_widget;
					return;
				}
				

				// executing user's code that cand contain array  $args
				if ($instance['use_custom_args']=="1") {
					eval ($instance['custom_args']);
				}
				
				if (isset($args) && is_array($args)){
					if (!isset($args['post_type']))$args['post_type']=$selected_types;
					foreach ($this->getDefaultArgs() as $k=>$v) if (!isset($args[$k]))$args[$k]=$v;
				} else {
					$args = $this->getDefaultArgs();
					$args['post_type']=$selected_types;
				}

				
				global $post;
				$original_post = $post;


				$output="";
				$query = new WP_Query( $args );
				if ( $query->have_posts() ) {
					if (!$instance['script'] && $instance['use_template']!="1"){
						$default=$this->getDefaultTemplate();
						$instance['before_template']=$default["before"];
						$instance['template']=$default["template"];
						$instance['after_template']=$default["after"];
					}
				  if (!$instance['script']) $output.=$this->subst($instance['before_template'],$args);
				  $i=0;
					while ( $query->have_posts() ) {
						$query->the_post();
						$template=$instance['template'];
						if ($instance['script'] && $instance['script_var']!="") $arr[]=$this->getArrItem($template); else $output.=$this->subst($template,$args);
					}
					if ($instance['script'] && $instance['script_var']!="") echo "<script> var ".$instance['script_var']."=".json_encode($arr)."</script>";
				  if (!$instance['script']) $output.=$this->subst($instance['after_template'],$args);
				} else {
					$output.=$this->subst($instance['no_posts'],$args);
				}

				$post = $original_post;
				setup_postdata($post);
				
				echo apply_filters( 'widget_text', $output);
				echo $after_widget;


			}

			private function getDefaultArgs(){
				return array( 
					'posts_per_page'=>-1
				);
			}

			private function getDefaultTemplate(){
				return array( 
					'before'=>'<ul>',
					'template'=>'<li><h4>{{title}}</h4><p>{{content}}</p></li>',
					'after'=>'</ul>'		
				);
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

            const MY_EXCERPT_DEAFULT_LENGTH = 20;
            const MY_EXCERPT_DEAFULT_MORE = '[...]';
            const MY_EXCERPT_DEAFULT_TAGS = '<p><strong><b><br>';

            function my_excerpt($args){
                $args=explode('|',$args);
                if (!isset($args[0]) || !is_numeric($args[0])) $args[0]=self::MY_EXCERPT_DEAFULT_LENGTH;
                if (!isset($args[1])) $args[1]=self::MY_EXCERPT_DEAFULT_MORE;
                if (!isset($args[2])) $args[2]=self::MY_EXCERPT_DEAFULT_TAGS;
                global $post;
                if ( '' == $text ) {
                        $text = get_the_content('');
                        $text = apply_filters('the_content', $text);
                        $text = str_replace('\]\]\>', ']]&gt;', $text);
                        $text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
                        $text = strip_tags($text, $args[2]);
                        $words = explode(' ', $text, $args[0] + 1);
                        if (count($words)> $args[0]) {
                                array_pop($words);
                                array_push($words, $args[1]);
                                $text = implode(' ', $words);
                        }
                }
                return $text;
            }
		
			private function subst ($str,$args) {
				global $post;
				$common=$this->get_common_fields();
				$custom=get_post_custom_keys($post->ID);
                $taxonomies=get_object_taxonomies($post,'names');
				//echo implode(",",$taxonomies);

				// replacing query string arguments

				$str = preg_replace_callback("/\{\{PHP_QUERY\}\}/Usi",function($m){
					if ($_SERVER["QUERY_STRING"]!="")return $_SERVER["QUERY_STRING"];
					 else return '""';},$str
				);


				$str = preg_replace_callback("/\{\{PHP_QUERY\|(.*)\}\}/Usi",function($m){
					if ($_SERVER["QUERY_STRING"]!="")return $_SERVER["QUERY_STRING"];
					 else {
					 	if ($m[1]!="") return $m[1]; else return '""';
					 }
					},$str
				);
				
				$str = preg_replace_callback("/\{\{PHP_(.*)\|(.*)\}\}/Usi",function($m){
					if (isset($_GET[$m[1]])){
						if ($_GET[$m[1]]!="") return $_GET[$m[1]]; else return '""';
					}
					 else {
					 	if ($m[2]!="") return $m[2]; else return '""';
					 }
					},$str
				);

				$str = preg_replace_callback("/\{\{PHP_(.*)\}\}/Usi",function($m){
					if (isset($_GET[$m[1]])){
						if ($_GET[$m[1]]!="") return $_GET[$m[1]]; else return '""';
					} else return '""';
					 
					},$str
				);
				
				$str = preg_replace_callback("/\{\{PHP_(.*)\}\}/Usi",function($m){return $_GET[$m[1]];},$str);

//				$str = preg_replace_callback("/\{\{(\$args\[.*\])\}\}/Usi",function($m){return $$m[1];},$str);

				$str = preg_replace_callback("/\{\{(args.*)\}\}/Usi",function($m)use($args){
				    eval ('$s= $'.$m[1].";");
				    return $s;
				    },$str);


                // improved excerpt subst:
                $that=$this;
				$str = preg_replace_callback("/\{\{excerpt\|(.*)\}\}/Usi",function($m)use($that){return $that->my_excerpt($m[1]);},$str);


				$str = preg_replace_callback("/\{\{(.*)\}\}/Usi",function($matches)use($common,$custom,$taxonomies,$post){
				    $matches=explode("|",$matches[1]);
					if (array_key_exists($matches[0],$common)){
						if (isset($common[$matches[0]][1])) {
							if ($common[$matches[0]][1]=="id") return call_user_func($common[$matches[0]][0],$post->ID);
						} else return call_user_func($common[$matches[0]][0]);
    				} else if (count($custom) && in_array($matches[0],$custom)){  
    				    switch (count($matches)){
    				        case 1:	return implode(',',get_post_custom_values($matches[0])); break;     // without parameters separated by ,
    				        case 2: // one parameter
    				            if (is_numeric($matches[1])){  // if number, return one value
    				                if ($matches[1]<0) $matches[1]=count(get_post_custom_values($matches[0]))+$matches[1];
    				                $values=get_post_custom_values($matches[0]);
    				            	return $values[$matches[1]];
    				            } else return implode($matches[1],get_post_custom_values($matches[0])); // if not a numer, return all 
    				        break;
    				        case 3: // two parameters 
    				            return $matches[1].implode($matches[2].$matches[1],get_post_custom_values($matches[0])).$matches[2]; // returns all embraced with parameters
    				        break;
    				        case 4: // three parameters 
    				            if (is_numeric($matches[1])){
    				                if (is_numeric($matches[2])) 
    				                    return implode($matches[3], array_slice(get_post_custom_values($matches[0]),$matches[1],$matches[2]) ); // returns a subset joined by the last parameter
    				                else return implode($matches[3], array_slice(get_post_custom_values($matches[0]),$matches[1]) );  // same, but without one argument
    				            }
    				        break;
    				        case 5: // four parameters 
    				            if (is_numeric($matches[1])){
    				                if (is_numeric($matches[2])) 
    				                    return $matches[3].implode($matches[4].$matches[3], array_slice(get_post_custom_values($matches[0]),$matches[1],$matches[2]) ).$matches[4]; // returns a subset embraced with parameters
    				                else return $matches[3].implode($matches[4].$matches[3], array_slice(get_post_custom_values($matches[0]),$matches[1]) ).$matches[4];  // same, but without one argument
    				            }
    				        break;
    				        
    				    }
    				} else if (count($taxonomies) && in_array($matches[0],$taxonomies)){
                        $terms=get_the_terms($post->ID,$matches[0]); $s="";
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
				$get_the_fields="ID,title,content,excerpt,author,post_thumbnail,category,author_posts,tag_list,date,time,modified_time";
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



  			echo '</ul></p>';
  			
  			
  			echo '<div><input  id="'.$this->get_field_id( 'use_custom_args' ).'" name="'.$this->get_field_name( 'use_custom_args' ).'" class="toggle-master" type="checkbox" value="1"'; if ($instance['use_custom_args']=="1" ) echo "checked"; echo " />Use custom arguments ";
  			$this->get_help('args');
  			echo '<textarea class="toggle-slave" ';
  			if ($instance['use_custom_args']!="1") echo 'style="display:none" ';
  			echo 'id="'.$this->get_field_id( 'custom_args' ).'" name="'.$this->get_field_name( 'custom_args' ).'" rows=3>'.$instance['custom_args'].'</textarea></div>';
				


  			echo '<div><input  id="'.$this->get_field_id( 'script' ).'" name="'.$this->get_field_name( 'script' ).'" class="toggle-master" type="checkbox" value="1"'; if ($instance['script']=="1") echo "checked"; echo " />Print as Javascript array (invisible)";
  			$this->get_help('javascript');
				?>
				<textarea class="toggle-slave" <?php if ($instance['script
					']!="1") echo 'style="display:none" ';?> id="<?php echo $this->get_field_id( 'script_var' ); ?>" name="<?php echo $this->get_field_name( 'script_var' ); ?>" rows=1><?php echo $instance['script_var']; ?></textarea></div>
				<?php

  			echo '<div><input class="toggle-master" id="'.$this->get_field_id( 'use_template' ).'" name="'.$this->get_field_name( 'use_template' ).'" type="checkbox" value="1"'; if ($instance['use_template']=="1" || !isset($instance['use_template'])) echo "checked"; echo " />Use template";
  			$this->get_help('template');
  			echo '<div ';
  			if ($instance['use_template']!="1") echo 'style="display:none" ';
  			echo 'class="toggle-slave">Before:<br/><textarea id="'.$this->get_field_id( 'before_template' ).'" name="'.$this->get_field_name( 'before_template' ).'" rows=1>'.$instance['before_template'].'</textarea>';
  			echo '<br/>Template:<br/><textarea id="'.$this->get_field_id( 'template' ).'" name="'.$this->get_field_name( 'template' ).'" class="post_list_widget_template_textarea" rows=4>'.$instance['template'].'</textarea><br/>';

				$common=$this->get_common_fields();
				echo '<div class="post_list_widget_form_fields_btns">';
				foreach ($common as $c=>$v) echo ' <span>{{'.$c.'}}</span>';
				echo '</div>';
            
            $this->get_help('common-fields');

  			echo '<br/>After:<br/><textarea id="'.$this->get_field_id( 'after_template' ).'" name="'.$this->get_field_name( 'after_template' ).'" rows=1>'.$instance['after_template'].'</textarea></div></div>';
  			echo '<br/>To print if the list is empty:<br/><textarea id="'.$this->get_field_id( 'no_posts' ).'" name="'.$this->get_field_name( 'no_posts' ).'" rows=1>'.$instance['no_posts'].'</textarea></div>';

			}
		
			public function update( $new_instance, $old_instance ) {
				$instance = array();
				$instance['title'] = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
				$instance['hide_title'] = $new_instance['hide_title'];
  			$post_types=get_post_types('','names');
				foreach ($post_types as $post_type ) $instance['check_type_'.$post_type] = $new_instance['check_type_'.$post_type];
				$instance['use_custom_args'] = $new_instance['use_custom_args'];
				$instance['custom_args'] = $new_instance['custom_args'];
				$instance['script'] = $new_instance['script'];
				$instance['script_var'] = $new_instance['script_var'];
				$instance['use_template'] = $new_instance['use_template'];
				$instance['before_template'] = $new_instance['before_template'];
				$instance['template'] = $new_instance['template'];
				$instance['after_template'] = $new_instance['after_template'];
				$instance['no_posts'] = $new_instance['no_posts'];
				
				return $instance;			
			}

			function get_help($str){
				echo '<div class="post_list_widget_form_help"><img src="'.plugins_url('img/help.png', __FILE__) .'"><div>';
				include 'help/'.$str.'.php';
				echo '</div></div>';
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
