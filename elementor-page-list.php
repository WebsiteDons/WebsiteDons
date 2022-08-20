<?php

# Using WP admin_bar_menu hook 
add_action('admin_bar_menu', function($admin_bar) 
{
  // Set menu parent label
  $admin_bar->add_menu(['id'=>'pgb','title'=>'Page Builder']);

  // assign database table prefix to variable for use in loop eg: wp_
  $pre = elementorPages()->prefix;
  
  // loop the array of pages source
  $elem_menu=[];
  foreach(elementorPages()->pages as $pgb) 
  {
    $pid = $pgb->post_id;
    $pgtitle = elementorPages($pid)->title;
    
    // skip items without value
    if( empty($pgtitle) )
      continue;

    // load array variable to use solely for count to define column display
    $elem_menu[] = $pgtitle;

    // create sub menu items
    $admin_bar->add_menu([
      'parent'=>'pgb',
      'id'    => 'pagebuilder-'.$pid,
      'title' => ucfirst(truncText($pgtitle,20)),
      'href'  => admin_url('post.php?post='.$pid.'&action=elementor'),
    ]);
  }

  // set css for menu column multiplication when the elementor page count exceeds 10
  if( count((array)$elem_menu) > 10 ) 
  {
    add_action('admin_footer', function() {
      $css = '<style>
      ul#wp-admin-bar-pgb-default {
        column-count: 3;
        column-rule: 1px solid lightblue;
      }
      </style>';

      echo $css;
    });
  }
});


/*
Elementor hook to add pages menu to editor panel
*/
add_action('elementor/documents/register_controls', function($document) 
{
  if( !$document instanceof \Elementor\Core\DocumentTypes\PageBase || !$document::get_property('has_elements') ) {
    return;
  }
  
	$e = new \Elementor\Controls_Manager;
	$pgmenu=[];
			
	foreach(elementorPages()->pages as $pgb) 
	{
		$pid = $pgb->post_id;
		$ptitle = elementorPages($pid)->title;

		$url = admin_url('post.php?post='.$pid.'&action=elementor');
		if( !empty($ptitle) ) {
			$lbl = ucfirst(truncText($ptitle,30));
			$pgmenu[] = '<div><a href="'.$url.'">'.$lbl.'</a></div>';
		}
	}
	
	// pages list HTML in panel
	$document->start_controls_section('maax_document_pagelist',[
	'tab' => $e::TAB_SETTING,
	'label'=>'Pages'
	]);
		$pages = implode('',$pgmenu);
		$exit_editor = '<a href="'.get_admin_url().'" class="panelbtn">Exit Pagebuilder</a>';

		$document->add_control('pages_list',[
		'type' => $e::RAW_HTML,
		'raw'=>'<div class="panelpages">'.$pages.$exit_editor.'</div>'
		]);

	$document->end_controls_section();
  
});


/*
Truncate function
*/
function truncText($text, $charcount, $keeptag='')
{
	// strip plugin content shortcodes eg: [video]
	$text = preg_replace('#\[(.*?)\]#', '', $text);

	$text = strip_tags($text, $keeptag);// $keeptag '<a><b><strong>' etc

	if( strlen($text) > $charcount && $charcount != 0 )
	{
		$text = $text." ";
		$text = substr($text,0,$charcount);
		$text = substr($text,0,strrpos($text,' '));
		if($charcount != 0) {
		$text = $text." ...";
		}
	}
	return $text;
}

/*
Get elementor pages
*/
function elementorPages($pid='')
{
  // WP database connect global variable
  global $wpdb;

  // get active pages created with elementor
  $elementor_pages=[];
  if( empty($pid) ) {
    $elementor_pages = $wpdb->get_results('
    SELECT post_id
    FROM '.$wpdb->postmeta.'
    WHERE meta_key = "_elementor_edit_mode" 
    ORDER BY post_id
    ');
  }
  $pgtitle='';
  if( !empty($pid) ) {
    $pgtitle = $wpdb->get_var('
    SELECT post_title 
    FROM '.$wpdb->prefix.'posts 
    WHERE (ID = '.$pid.' AND post_type = "page")
    OR (ID = '.$pid.' AND post_type = "post")
    ');
  }
  
  $val = (object)[
  'pages' => $elementor_pages,
  'title' => $pgtitle
  ];
  
  return $val;
}



/** CSS to be added to admin css file

.panelpages a {
	display: block; 
	padding: 5px; 
	border-bottom: 1px solid #2b2f33;
}
.panelpages .panelbtn {
	background: #2b2f33; 
	padding: 8px 6px; 
	margin-top: 10px; 
	border-radius: 3px; 
	text-align: center;
	border: none;
}
.panelpages .panelbtn:hover {
	background: #111;
}
.panelpages h3 {
 margin-top: 30px; margin-bottom: 10px;
 }
 */
