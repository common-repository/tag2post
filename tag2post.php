<?php
/*
Plugin Name: Tag to Post
Plugin URI: http://www.internetgeneration.org
Description: Ties a Tag to a specfic Post or Page ID
Version: 1.0.1
Author: Diogo Assumpcao
Author URI: http://www.internetgeneration.org
*/
/*  Copyright 2009  DIOGO ASSUMPCAO  (email : diogo@confrade.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
class Tag2Post {

	private static $wpdb;
	private static $info;
	
	/**
	 * Função de inicialização, centraliza a definição de filtros/ações
	 *
	 */
	public static function inicializar(){
		global $wpdb;
		
		add_filter("tag_link", array("Tag2Post","tag2post_mod_links"),1,2);
		add_filter("wp_generate_tag_cloud", array("Tag2Post","tag2post_mod_links_cloud"),1,3);
		add_action('edit_tag_form_pre', array('Tag2Post','tag2post_add_form'));
		add_action('edit_term', array('Tag2Post','tag2post_update_db'),10,2);
		
		//Mapear objetos WP
		Tag2Post::$wpdb = $wpdb;
		//Outros mapeamentos
		Tag2Post::$info['plugin_fpath'] = dirname(__FILE__);
	
	}
	
	/**
	 * Funcao de instalacao do plugin
	 *
	 */
	public static function tag2post_install() {
		if ( is_null(Tag2Post::$wpdb) ) Tag2Post::inicializar();
		$table_name = Tag2Post::$wpdb->prefix . "terms";
				
	$sql = "CREATE TABLE ".$table_name." (
	  term_id bigint(20) NOT NULL auto_increment,
	  name varchar(200) NOT NULL default '',
	  slug varchar(200) NOT NULL default '',
	  term_group bigint(10) NOT NULL default '0',
	  term_post_id bigint(20) NOT NULL default '0',
	  PRIMARY KEY  (term_id),
	  UNIQUE KEY slug (slug),
	);"; 
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

		
		
	}
	/**
	 * Insere um novo campo no formulário para editar Tags
	 *
	 */
	public static function tag2post_add_form($tag) {
		global $wp_query;
		global $posts;
	?>
		<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php _e('Edit Tag'); ?></h2>
		<div id="ajax-response"></div>
		<form name="edittag" id="edittag" method="post" action="edit-tags.php" class="validate">
        <input type="hidden" name="action" value="editedtag" />
		<input type="hidden" name="tag_ID" value="<?php echo $tag->term_id ?>" />
		<?php wp_original_referer_field(true, 'previous'); wp_nonce_field('update-tag_' . $tag->term_id	); ?>
			<table class="form-table">
				<tr class="form-field form-required">
					<th scope="row" valign="top"><label for="name"><?php _e('Tag name') ?></label></th>
					<td><input name="name" id="name" type="text" value="<?php if ( isset( $tag->name ) ) echo attribute_escape($tag->name); ?>" size="40" aria-required="true" />
					<p><?php _e('The name is how the tag appears on your site.'); ?></p></td>
				</tr>
				<tr class="form-field">
					<th scope="row" valign="top"><label for="slug"><?php _e('Tag slug') ?></label></th>
					<td><input name="slug" id="slug" type="text" value="<?php if ( isset( $tag->slug ) ) echo attribute_escape(apply_filters('editable_slug', $tag->slug)); ?>" size="40" />
					<p><?php _e('The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.'); ?></p></td>
				</tr>
				<tr class="form-field">
					<th scope="row" valign="top"><label for="term_post_id"><?php _e('PostID') ?></label></th>
					<td><input name="term_post_id" id="term_post_id" type="text" value="<?php if ( isset( $tag->term_post_id ) ) echo $tag->term_post_id; ?>" size="40" />
					<p><?php _e('&Eacute; o ID do Post que deseja conectar a Tag'); ?></p></td>
				</tr>
			</table>
		<p class="submit"><input type="submit" class="button-primary" name="submit" value="<?php _e('Update Tag'); ?>" /></p>
		<?php do_action('edit_tag_form', $tag); ?>
		</form>
		</div>
        <?php global $post;
             $myposts = get_posts();
		?>
    <?php screen_icon(); ?>
    <h2><?php _e('Lista de Posts'); ?></h2>	 
    <table class="widefat post fixed" cellspacing="0">
     <thead>
        <tr>
        <th scope="col" id="title" class="manage-column column-title" style="">Post</th>
        <th scope="col" id="author" class="manage-column column-author" style="">ID</th>
        </tr>
     </thead>

    
     <tfoot>
        <tr>
        <th scope="col" id="title" class="manage-column column-title" style="">Post</th>
        <th scope="col" id="author" class="manage-column column-author" style="">ID</th>
        </tr>
     </tfoot>
    
        <tbody>
        <?php
            global $post;
		 	if ( !isset( $_GET['paged'] ) )
			$_GET['paged'] = 1;
			$offset = 0;
			if ($_GET['paged'] > 1 ) $offset = ( $_GET['paged'] - 1 ) * 15;
			$myposts = get_posts('numberposts=15&offset='.$offset);
            foreach($myposts as $post) :
        ?>
        <tr id='post-<?php echo $post->ID; ?>' class='alternate author-self status-publish iedit' valign="top">
		<td class="post-title column-title"><strong><a class="row-title" href="#" title="<?php echo $post->post_title; ?>" onclick="document.edittag.term_post_id.value = '<?php echo $post->ID; ?>'"><?php echo $post->post_title; ?></a></strong>
	</td>
	<td class="author column-author"><strong><a href="#" onclick="document.edittag.term_post_id.value = '<?php echo $post->ID; ?>'"><?php echo $post->ID; ?></a></strong></td>
    </tr>
             
        <?php endforeach; ?>
		</tbody>
    </table>
    <?php
    $count_posts = wp_count_posts();
	$page_links = paginate_links( array(
	'base' => add_query_arg( 'paged', '%#%' ),
	'format' => '',
	'prev_text' => __('&laquo;'),
	'next_text' => __('&raquo;'),
	'total' => ceil($count_posts->publish/15),
	'current' => $_GET['paged']
	));
    if ( $page_links ) { ?>
	<div class="tablenav">
    <div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
	number_format_i18n( ( $_GET['paged'] - 1 ) * 15 + 1 ),
	number_format_i18n( min( $_GET['paged'] * 15 , $count_posts->publish ) ),
	number_format_i18n( $count_posts->publish ),
	$page_links
	); echo $page_links_text; ?></div>
	</div>
    <br class="clear" />
	</div>
	<br class="clear" />
	</div>
	<?php } ?>
	<?php
	exit;
	}
	/**
	 * Altera o tabela terms para incluir o postID
	 *
	 */
	public static function tag2post_update_db($term_id) {
		if ( is_null(Tag2Post::$wpdb) ) Tag2Post::inicializar();
		echo $sql = "UPDATE ".Tag2Post::$wpdb->prefix."terms SET term_post_id = ".$_POST['term_post_id']." WHERE term_id = ".$term_id;
		Tag2Post::$wpdb->query($sql);
	
	}
	/**
	 * Funcao wp_generate_tag_cloud, retorna apenas a nuvem de tags
	 *
	 */
	public static function pseudo_wp_generate_tag_cloud( $tags, $args = '' ) {
		global $wp_rewrite;
	$defaults = array(
		'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 0,
		'format' => 'flat', 'orderby' => 'name', 'order' => 'ASC',
		'topic_count_text_callback' => 'default_topic_count_text',
	);

	if ( !isset( $args['topic_count_text_callback'] ) && isset( $args['single_text'] ) && isset( $args['multiple_text'] ) ) {
		$body = 'return sprintf (
			__ngettext('.var_export($args['single_text'], true).', '.var_export($args['multiple_text'], true).', $count),
			number_format_i18n( $count ));';
		$args['topic_count_text_callback'] = create_function('$count', $body);
	}

	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	if ( empty( $tags ) )
		return;

	// SQL cannot save you; this is a second (potentially different) sort on a subset of data.
	if ( 'name' == $orderby )
		uasort( $tags, create_function('$a, $b', 'return strnatcasecmp($a->name, $b->name);') );
	else
		uasort( $tags, create_function('$a, $b', 'return ($a->count < $b->count);') );

	if ( 'DESC' == $order )
		$tags = array_reverse( $tags, true );
	elseif ( 'RAND' == $order ) {
		$keys = array_rand( $tags, count( $tags ) );
		foreach ( $keys as $key )
			$temp[$key] = $tags[$key];
		$tags = $temp;
		unset( $temp );
	}

	if ( $number > 0 )
		$tags = array_slice($tags, 0, $number);

	$counts = array();
	foreach ( (array) $tags as $key => $tag )
		$counts[ $key ] = $tag->count;

	$min_count = min( $counts );
	$spread = max( $counts ) - $min_count;
	if ( $spread <= 0 )
		$spread = 1;
	$font_spread = $largest - $smallest;
	if ( $font_spread < 0 )
		$font_spread = 1;
	$font_step = $font_spread / $spread;

	$a = array();

	$rel = ( is_object( $wp_rewrite ) && $wp_rewrite->using_permalinks() ) ? ' rel="tag"' : '';

	foreach ( $tags as $key => $tag ) {
		$count = $counts[ $key ];
		$tag_link = '#' != $tag->link ? clean_url( $tag->link ) : '#';
		$tag_id = isset($tags[ $key ]->id) ? $tags[ $key ]->id : $key;
		$tag_name = $tags[ $key ]->name;
		$a[] = "<a href='$tag_link' class='tag-link-$tag_id' title='" . attribute_escape( $topic_count_text_callback( $count ) ) . "'$rel style='font-size: " .
			( $smallest + ( ( $count - $min_count ) * $font_step ) )
			. "$unit;'>$tag_name</a>";
	}

	switch ( $format ) :
	case 'array' :
		$return =& $a;
		break;
	case 'list' :
		$return = "<ul class='wp-tag-cloud'>\n\t<li>";
		$return .= join( "</li>\n\t<li>", $a );
		$return .= "</li>\n</ul>\n";
		break;
	default :
		$return = join( "\n", $a );
		break;
	endswitch;
	return $return;
	}
	/**
	 * Modifica os links quando as Tags estão em listagem simples
	 *
	 */
	public static function tag2post_mod_links($taglink, $tag_id) {
	
		if ( is_null(Tag2Post::$wpdb) ) Tag2Post::inicializar();
			if (is_object($tag_id)) {
				
				$tagid = $tag_id->term_id;
							
				$query_term_post_id = "SELECT term_post_id FROM ".Tag2Post::$wpdb->prefix."terms WHERE term_id='$tagid'";
		
				$term_post_ID =  tag2post::$wpdb->get_var($query_term_post_id);
				
				if ($term_post_ID != 0 ) {
					return $taglink = get_permalink($term_post_ID);
				} else {
					return $taglink;
				}
				} else {
					return $taglink;
				} 
	}
	/**
	 * Modifica os links quando as Tags estão em nuvem
	 *
	 */
	public static function tag2post_mod_links_cloud($return, $tags, $args) {
	
		foreach ( $tags as $key => $tag ) {
		
			if ($tag->term_post_id != 0) {
		
				$tags[$key]->link = get_permalink($tag->term_post_id);
		
			} 
			
		}
	
		return Tag2Post::pseudo_wp_generate_tag_cloud($tags,$args);
	}
}
$mppPluginFile = substr(strrchr(dirname(__FILE__),DIRECTORY_SEPARATOR),1).DIRECTORY_SEPARATOR.basename(__FILE__);
/** Funcao de instalacao */
register_activation_hook($mppPluginFile,array('Tag2Post','tag2post_install'));
/** Funcao de inicializacao */
add_filter('init', array('Tag2Post','inicializar'));

?>