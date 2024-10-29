<?php

/*
	Plugin Name: Artists ilike
	Plugin URI: http://www.hamstar.co.nz/?page_id=104
	Version: 1.3.4
	Author: <a href="http://www.hamstar.co.nz/">Robert McLeod</a>
	Description: Gets all the artists from your profile on ilike and displays them on a page of your choice.
*/

/*  Copyright 2008 Robert McLeod  (email : hamstar@telescum.co.nz)

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

function get_image($file, $local_path, $newfilename) {
	# From http://www.weberdev.com/get_example-4009.html (modified slightly)
    $err_msg = '';
    $out = fopen($local_path.$newfilename, 'wb');
    if ($out == FALSE){
      # Opening the file failed
      return false;
      exit;
    }
   
    $ch = curl_init();
           
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_FILE, $out);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $file);
               
    curl_exec($ch);
    if(curl_error ($ch)) {
		return false;
		exit;
	}
    //fclose($handle);
   
    curl_close($ch);
    
    return true;

}//end function

if (!class_exists("Artists_ilikePlugin")) {
	class Artists_ilikePlugin {
		var $adminOptionsName = 'ArtistsilikeAdminOptions';
		function Artists_ilikePlugin() { //constructor

		}
		
		function generateArtistsilike($type='') {
			# Include our classes
			include_once('php/simple_html_dom.php');
			include_once('php/curl.php');

			# Set some variables
			$user = get_option("aiUser");
			$url = "http://ilike.com/user/$user/fan_of";
			$artists = '';
			$eof = 0;
			$count = 1;
			
			if($user) {
				# Create a new curl object
				$curl = new curl();
				if(empty($curl)) { echo 'curl is empty'; }
				# Recurse through all the pages
				while($eof == 0) {
					
					# Get the url with curl
					$response = $curl->get("$url?page=$count");

					# Make a html_dom object from our curl response
					$html = str_get_html($response->body);

					# Check that there are artists to get
					if(strstr($html,'hasn\'t told us which artists they like yet.')) {
						$eof = 1;
					} else {
						# Search the html for ul
						foreach($html->find('ul') as $elem) {
							# The artists are kept in the artist_list class
							if($elem->class == 'artist_list') {
								# Add them to the artists string
								$artists .= $elem->innertext;
							}
						}
					}
					
					# Increment the counter
					$count++;
				}

				# Need to modify the a element
				$artists = str_get_html($artists);
				foreach($artists->find('a') as $a) {
					# Turn all the links to ilike into absolute
					$href=$a->href;
					$a->href = "http://ilike.com$href";
					
					# Try and download the image to the local server
					# Get the remote image url from the style
					$style = $a->style;
					preg_match('/http.+jpg/',$style,$matches);
					$url = $matches[0];
					$filename = basename($url);
					$saveto = dirname(__FILE__).'/thumbs/';
					
					# Try to download the file
					if(!file_exists($saveto.$filename)) {
						$result = get_image($url,$saveto,$filename);
					} else {
						$result = true;
					}
					
					# If the download worked, try to change the image link in style
					# to the one on the local server, otherwise leave it as it was
					if($result) {
						# Get the web-relative location for the thumbs folder
						preg_match('/(\/wp-content.+)/',dirname(__FILE__),$matches);
						$fullname = get_option('home') . $matches[1] . "/thumbs/$filename";
						# Replace the link
						$newstyle = preg_replace('/http.+jpg/',$fullname ,$style);
						$a->style = $newstyle;
					}
					
					if($type == 'collage') {
						$a->style = '';
						$a->title = strip_tags($a->innertext);
						$a->innertext = "<img src='$fullname' border='0' alt='{$a->innerHTML}'/>";
					}
				}

				# Remove the clear left div
				str_replace('<div style="clear:left;"></div></ul>','',$artists);
				
				# Print the artists and the ul
				$artists = '<ul id="artist_list">'. $artists . '<div style="clear:left;"></div></ul>';
			} else {
				$artists = 'No username set yet.';
			}
		
			#$str = '<div style="display: none;">';
			#$str .= '';
			#$str .= '</div>';

			return $artists;# . $str;
		} // End the generateArtistsilike function
		
		// Main function to do the stuff
		function printArtistsilike($content) {
		
			$artists = get_option('aiArtistMap');
			
			//$artists = $user .' '. $url;
			$content = str_ireplace('<!-- artists ilike -->',$artists,$content);
			return $content;

		} // End printArtists() function
		
		// Add the stylesheet in
		function addHeaderCode() {
			if(!(get_option('aiCollage'))) {
				echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('wpurl')
					. '/wp-content/plugins/artists_ilike/css/artists_ilike.css" />'
					. "\n";
			}
		}
		
		// Initialise the plugin
		function init() {
			if(!get_option('aiUser')) {
				update_option('aiUser','');
			}
			
			if(!get_option('aiArtistMap')) {
				update_option('aiArtistMap','');
			}
			
			if(!get_option('aiCollage')) {
				update_option('aiCollage','');
			}
		}
		
		//Prints out the admin page
		function printAdminPage() {
			$user = get_option('aiUser');

			if (isset($_POST['update_Artists_ilikePluginSettings'])||isset($_POST['update_ArtistMap'])) {
			
				// Update the submitted username to the db
				// (if there is a profile for the username at ilike)
				if (isset($_POST['aiUser'])) {
				
					# Test here for existance of user at ilike
					#include_once('php/curl.php');
					#$curl = new curl();
					#$response = $curl->get("http://ilike.com/user/{$_POST['aiUser']}/");
					#if(stristr("Profile not found",$response)) {
				
					if(get_option('aiUser') != $_POST['aiUser']) {
						update_option('aiUser', $_POST['aiUser']);

						echo '<div class="updated"><p><strong>';
						_e("Username Updated.","Artists_ilikePlugin");
						echo '</strong></p></div>';
					}
					#} else {
						#echo '<div class="updated"><p><strong>';
						#_e("No such user at ilike.  Please check your username.","Artists_ilikePlugin");
						#echo '</strong></p></div>';
					#}
				}

				// Get the artists and update the artist map in the database
				// (if username set)
				if (isset($_POST['update_ArtistMap'])) {
					$user = get_option('aiUser');
					if(empty($user)) {
						echo '<div class="updated"><p><strong>';
						_e("Please set a username first.","Artists_ilikePlugin");
						echo '</strong></p></div>';
					} else {
						echo '<div class="updated"><p><strong>';
						_e("Please wait... ","Artists_ilikePlugin");
						$type = ($_POST['aiCollage']=='on') ? 'collage' : '';
						$map = $this->generateArtistsilike($type);
						($_POST['aiCollage']) ? update_option('aiCollage','checked') : update_option('aiCollage','');
						update_option('aiArtistMap',$map);
						update_option('aiLastMapGenerated',time());
						_e("Artist Map Regenerated","Artists_ilikePlugin");
						echo '</strong></p></div>';
					}
				}
			}
			
			// Print the admin page
			
			$map = get_option('aiArtistMap');
			$button_str = (empty($map)) ? 'Generate Artist Map' : 'Regenerate Artist Map';
			$time = get_option('aiLastMapGenerated');
			$time_str = (empty($time)) ? 'Click Generate Artist Map to generate your map' : 'Last generated: <em>'.date('l, d F \a\t g:ia',$time).'</em>';
			
			?>
			<div class=wrap>
				<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
					<h2>Artists ilike Settings</h2>

					<h3>ilike username</h3>
					<p>The username of your ilike account</p>
					<p><label for="aiUser"><input type="text" id="aiUser" name="aiUser" value="<?=$user;?>"/></label></p>
					<p><label for="aiCollage"><input type="checkbox" name="aiCollage" id="aiCollage" <?=get_option('aiCollage');?>/> Collage mode</label></p>
					<div class="updated"><p><?=$time_str;?></p></div>
					<div class="submit">
						<input type="submit" name="update_ArtistMap" value="<?php _e($button_str, 'Artists_ilikePlugin') ?>" />
						<input type="submit" name="update_Artists_ilikePluginSettings" value="<?php _e('Update Settings', 'Artists_ilikePlugin') ?>" />
					</div>
				</form>
			</div>
			<?php

		}//End function printAdminPage()


	}
} //End Class Artists_ilikePlugin

if (class_exists("Artists_ilikePlugin")) {
	$ai_plugin = new Artists_ilikePlugin();
}


//Actions and Filters
if (isset($ai_plugin)) {
	//Actions
	//Initialize the admin panel
	if (!function_exists("Artist_ilikePlugin_ap")) {
		function Artist_ilikePlugin_ap() {
			global $ai_plugin;
			if (!isset($ai_plugin)) {
				return;
			}
			if (function_exists('add_options_page')) {
				add_options_page('Artist ilike Plugin', 'Artist ilike Plugin', 9, basename(__FILE__), array(&$ai_plugin, 'printAdminPage'));
			}
		}
	}
	add_action('wp_head', array(&$ai_plugin, 'addHeaderCode'), 1);
	add_action('activate_artists_ilike-1.0/artists_ilike-1.0.php',array(&$ai_plugin, 'init'));
	add_action('admin_menu', 'Artist_ilikePlugin_ap');
	//Filters
	add_filter('the_content',array(&$ai_plugin,'printArtistsilike'));
	
}

?>
