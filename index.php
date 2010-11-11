<?php

/* 
Bookmarks - Main page.
Copyright (C) 2010 Aleksi Räsänen <aleksi.rasanen@runosydan.net>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

	session_start();

	require 'CHTML/CHTML.php';
	require 'CUsers/CUsers.php';
	require 'CForm/CForm.php';
	require 'CGeneral/CGeneral.php';
	require 'CSQLite/CSQLite.php';

	// **************************************************
	//	logged_in
	/*!
		@brief Check if user is logged in.

		@return True if user is logged in, false if not.
	*/
	// **************************************************
	function logged_in()
	{
		if( isset( $_SESSION['bookmarks']['username'] ) )
			return true;

		return false;
	}

	// **************************************************
	//	create_login_form
	/*!
		@brief Create login form.

		@param $html CHTML class instance. We use it to
		  show potential message.
	*/
	// **************************************************
	function create_login_form( $html )
	{
		// Create form 
		$form = new CForm( 'index.php', 'post' );
		$form->addTextField( 'Username: ', 'username' );
		$form->addPasswordField( 'Password: ', 'password' );
		$form->addSubmit( 'Login', 'submit' );
		$form_out = $form->createForm();

		// Create <h2> and show Message
		$h = $html->createH( '2', 'My Bookmarks - Login' );
		$msg = $html->showMessage();

		// Create link for registeration
		$link = $html->createLink( 'register.php', 'Create it' );
		$p = $html->createP( 'No account? ' . $link );

		// Create div and put above content in it.
		$html->setExtraParams( array( 'id' => 'login' ) );
		echo $html->createSiteTop( 'My Bookmarks - Login page' );
		echo $html->createDiv( $h . $msg . $form_out . $p );
	}

	// **************************************************
	//	handle_post_values
	/*!
		@brief Check if there is POST values and if so,
		  do something, depending on values.
	*/
	// **************************************************
	function handle_post_values( $html )
	{
		if(! isset( $_POST ) )
			return;
		
		$gen = new CGeneral();
		$p = $_POST;
		$required = array( 'username', 'password' );

		// Try to login if required fields are set.
		if( $gen->checkRequiredFields( $p, $required ) )
		{
			// NOTE! We use users.db database to read user
			// informations, not 'bookmarks.db' what is used
			// in every other stuff. We want to have general
			// users database so same acocunt can be used in multiple
			// services without any new registerations.
			$db = new CSQLite();
			$db->connect( 'users.db', false );

			// Try to login.
			$auth = new CUsers( $db );
			$ret = $auth->checkLogin( $p['username'], $p['password'] );

			// If we get array, then login was OK.
			if( is_array($ret ) )
			{
				$_SESSION['bookmarks']['username'] = $ret[0]['username'];
				$_SESSION['bookmarks']['id'] = $ret[0]['id'];
			}
			else if( $ret == -1 )
			{
				$html->setMessage( 'User ' . $p['username'] 
					. ' not found.');
			}
			else if( $ret == -2)
			{
				$html->setMessage( 'Invalid password!');
			}
		}
	}

	// **************************************************
	//	logout
	/*!
		@brief Log out if we are logged in.

		@return None.
	*/
	// **************************************************
	function logout()
	{
		if( isset( $_SESSION['bookmarks']['username'] ) )
			unset( $_SESSION['bookmarks']['username'] );
		
		if( isset( $_SESSION['bookmarks']['id'] ) )
			unset( $_SESSION['bookmarks']['id'] );
	}

	// **************************************************
	//	handle_get_values
	/*!
		@brief This will process GET values and call
		  correct functions if needed.

		@param $db CSQLite database connection. Connection
		  must be opened!

		@return True if "own page" much be created, false if not.
	*/
	// **************************************************
	function handle_get_values( $db, $html )
	{
		if(! isset( $_GET ) )
			return;
		
		$g = $_GET;

		// Logout
		if( isset( $g['action'] ) && $g['action'] == 'logout' )
			logout();

		// If user has given GET value "page", we must check that
		// someone is logged in before we do anything else.
		if( isset( $g['page'] ) && logged_in() )
		{
			if( $g['page'] == 'add' )
			{
				echo $html->createSiteTop( 'Bookmarks');
				create_bookmark_adding_form();
				return false;
			}
			else if( $g['page'] == 'edit' )
			{
				echo $html->createSiteTop( 'Bookmarks');
				create_edit_bookmarks_form( $db );
				return false;
			}
			else if( $g['page'] == 'remove' )
			{
				remove_bookmarks( $db, $html );
				return false;
			}
		}

		return true;
	}

	// **************************************************
	//	get_all_bookmarks_for_logged_user
	/*!
		@brief Get all user bookmarks.

		@return Array.
	*/
	// **************************************************
	function get_all_bookmarks_for_logged_user()
	{
		$db = new CSQLite();

		// Logged user id is in session variable!
		$uid = $_SESSION['bookmarks']['id'];

		try {
			$db->connect( 'bookmarks.db', false );
		} catch( Exception $e ) {
			die( 'Error: ' . $e->getMessage() );
		}

		$q = 'SELECT * FROM bookmarks WHERE user_id=' . $uid
			. ' ORDER BY added DESC';
		return $db->queryAndAssoc( $q );
	}

	// **************************************************
	//	remove_bookmarks
	/*!
		@brief This will remove bookmark if there is GET parameter
		  with confirmation. If not, then we show confirmation
		  dialog first here.

		@param $db CSQLite database connection.

		@param $html CHTML class instance.
	*/
	// **************************************************
	function remove_bookmarks( $db, $html )
	{
		if(! isset( $_GET['id'] ) )
			return;

		// Get user bookmarks
		$ret = get_all_bookmarks_for_logged_user();
		$max = count( $ret );
		$ok = false;

		// Does this user have this ID?
		for( $i=0; $i < $max; $i++ )
		{
			if( $ret[$i]['id'] == $_GET['id'] )
			{
				$ok = true;
				break;
			}
		}

		if(! $ok )
		{
			header( 'Location: index.php' );
			die();
		}

		// If we have already been here and we have answerred Yes
		// to confirmation, then we just delete it now.
		if( isset( $_GET['confirm'] ) && $_GET['confirm'] == '1' )
		{
			$q = 'DELETE FROM bookmarks WHERE id=' . $_GET['id'];
			$db->query( $q );
			header( 'Location: index.php' );
			die();
		}
		else
		{
			$q = 'SELECT * FROM bookmarks WHERE id=' . $_GET['id'];
			$ret = $db->queryAndAssoc( $q );
			$img_url = 'icons/nuvola/32x32/actions/';

			// Create logout-button
			$logout = create_logout_button( $html );

			// Create two images, "Ok" and "Cancel"
			$ok = $html->createImg( $img_url . 'apply.png' );
			$cancel = $html->createImg( $img_url . 'cancel.png' );

			// Create link for "Remove"
			$html->setExtraParams( array( 'title' => 'Yes, remove it' ) );
			$link_remove = $html->createLink( 'index.php?page=remove&id=' 
				. $_GET['id'] . '&confirm=1', $ok );

			// Create link for "Don't remove"
			$html->setExtraParams( array( 
				'title'=> 'No, don\'t remove it' ) );
			$link_dont = $html->createLink( 'index.php', $cancel );

			// Header and text inside div
			$h = $html->createH( '2', 'Delete bookmark' );
			$text = 'Are you sure that you want to delete bookmark "'
				. $ret[0]['description'] . '"?<br /><br />';

			// Separator span
			$html->setExtraParams( array( 'class' =>'separator' ) );
			$span = $html->createSpan( '&nbsp;' );

			// Back to bookmarks -link
			$html->setExtraParams( array( 'class' => 'back' ) );
			$btm = $html->createLink( 'index.php', 
				'Back to bookmarks list' );

			echo $html->createSiteTop( 'Bookmarks');
			$html->setExtraParams( array( 'id' => 'confirmation' ) );

			// Create our div
			echo $html->createDiv( $logout . $h . $text . $link_remove 
				. $span .  $link_dont . $btm );
			echo $html->createSiteBottom();

			//echo '<span class="separator"></span>';

			//create_div_bottom();
		}
	}

	// **************************************************
	//	create_edit_bookmarks_form
	/*!
		@brief Create a HTML table where are our bookmarks

		@param $db CSQLite database class instance.
	*/
	// **************************************************
	function create_edit_bookmarks_form( $db )
	{
		if(! isset( $_GET['id'] ) )
			return;

		$html = new CHTML();
		$form = new CForm( 'edit.php', 'post' );
		$form_out = '';

		$q = 'SELECT * FROM bookmarks WHERE id=' . $_GET['id'];
		$ret = $db->queryAndAssoc( $q );

		// Logout-button
		$logout = create_logout_button( $html );

		// Header
		$h = $html->createH( '2', 'Edit bookmarks' );

		// Back to bookmarks -link
		$html->setExtraParams( array( 'class' => 'back' ) );
		$btm = $html->createLink( 'index.php', 
			'Back to bookmarks list' );

		// Make sure that we can edit only our own bookmarks.
		if( $ret[0]['user_id'] == $_SESSION['bookmarks']['id'] )
		{
			$form->setFormName( 'edit_bookmark' );

			$form->addTextField( 'URL: ', 'url', 
				'', $ret[0]['url'] );

			$form->addTextField( 'Description: ', 'description', 
				'', $ret[0]['description'] );
			
			$form->addHiddenField( 'bookmark_id', $ret[0]['id'] );
			$form->addSubmit( 'Update bookmark', 'submit' );

			$form_out = $form->createForm();
		}
		
		$html->setExtraParams( array( 'class' => 'link_handling' ) );
		echo $html->createDiv( $logout . $h . $form_out . $btm );

		//create_div_bottom();
	}

	// **************************************************
	// create_bookmark_adding_form
	/*!
		@brief Create a form where we can create new bookmark.
	*/
	// **************************************************
	function create_bookmark_adding_form()
	{
		$html = new CHTML();
		$form = new CForm( 'add.php', 'post' );

		// Craete form
		$form->addTextField( 'Link URL: ', 'url' );
		$form->addTextField( 'Description: ', 'description' );
		$form->addSubmit( 'Save new bookmark', 'submit' );
		$form_out = $form->createForm();

		// Create logout-button
		$logout = create_logout_button( $html );

		// Header of page
		$h = $html->createH( '2', 'Create new bookmark' );

		// Create link 'Back to bookmarks'
		$html->setExtraParams( array( 'class' => 'back' ) );
		$btm = $html->createLink( 'index.php', 'Back to bookmarks list' );

		// Create DIV with above elements
		$html->setExtraParams( array( 'class' => 'link_handling' ) );
		echo $html->createDiv( $logout . $h . $form_out . $btm );
	}

	// **************************************************
	//	show_all_bookmarks
	/*!
		@brief This will create list of bookmarks what
		  currently logged user have. This will be called
		  from create_own_page function!
	*/
	// **************************************************
	function show_all_bookmarks()
	{
		$html = new CHTML();
		$ret = get_all_bookmarks_for_logged_user();
		$img_url = 'icons/nuvola/32x32/actions/';

		// There were items! Show them here now.
		if( $ret != -1 )
		{
			$num = count( $ret );

			echo '<div class="bookmark">';
			for( $i=0; $i < $num; $i++ )
			{
				$url = $ret[$i]['url'];
				$id = $ret[$i]['id'];
				$desc = $ret[$i]['description'];

				// Down-arrow.
				$html->setExtraParams( array( 
					'title' => 'Click to see options',
					'id' => 'img_' . $id ) );
				echo $html->createImg( $img_url . '1downarrow.png' );

				// Link itself
				$html->setExtraParams( array( 'id' => 'url_ '. $id ) );
				echo $html->createLink( $url, $desc );

				echo '<br />';
				echo '<div class="edit_form" id="ef_' . $id . '">';
				echo $url . '<br />';

				// Edit-link
				echo $html->createLink( 'index.php?page=edit&id=' . $id, 
					'Edit' );

				echo '<span class="separator">&nbsp;</span>';

				// Delete-link
				echo $html->createLink( 'index.php?page=remove&id=' . $id, 
					'Delete' );

				echo '</div>';
			}
			echo '</div>';
		}
		else
		{
			echo '<div id="no_bookmarks"">';
			echo 'You have no any bookmarks stored. '
				. 'Add new bookmark by pressing + icon on the top.';
			echo '</div>';
		}
		echo '</div>';
	}

	// **************************************************
	//	create_logout_button
	/*!
		@brief Create logout-button inside logout-div

		@param $html CHTML class instance

		@return String
	*/
	// **************************************************
	function create_logout_button( $html )
	{
		// Icons path
		$img_url = 'icons/nuvola/48x48/actions/';

		// Create image what is link to 'logout.php'
		$html->setExtraParams( array( 'title' => 'Log out' ) );
		$logout = $html->createImg( $img_url . 'exit.png' );
		$link = $html->createLink( 'index.php?action=logout', $logout );

		// Create DIV.
		$html->setExtraParams( array( 'id' => 'logout' ) );
		return $html->createDiv( $link );
	}

	// **************************************************
	//	create_own_page
	/*!
		@brief This will create page what will be shown to user
		  after login is done succesfully.
		
		@param $db Database class.
	*/
	// **************************************************
	function create_own_page( $html )
	{
		echo $html->createSiteTop( 'Bookmarks' );
		echo create_logout_button( $html );

		echo '<div id="ownpage">';
		echo '<h2>My Bookmarks</h2>';
		echo '<div id="ownpage_inner">';

		$html->setExtraParams( array(
			'title' => 'Add new bookmark' ) );

		$img_url = 'icons/nuvola/32x32/actions/';
		$img = $html->createImg( $img_url . 'edit_add.png' );
		echo $html->createLink( 'index.php?page=add', $img );

		echo '<span class="separator"></span>';
		echo '<br />';

		show_all_bookmarks();
		
		echo '<br />';

		echo '</div>';
		include 'edit_bookmarks.js';
	}

	// **************************************************
	//	main
	/*!
		@brief Main function.
	*/
	// **************************************************
	function main()
	{
		$html = new CHTML();
		$db = new CSQLite();
		$db->connect( 'bookmarks.db' );

		// Include JQuery and CSS file and create HTML headers.
		$html->setJS( 'jquery/jquery-1.4.3.js' );
		$html->setCSS( 'bookmarks.css' );

		// Check if there is POST-values (eg. login information)
		handle_post_values( $html );

		// This will return true if we need to create own page.
		// False will be set if we do not want to show own page.
		$ret = handle_get_values( $db, $html );

		// If we are not logged in, create login form.
		// If we are logged in and handle_get_values returned true,
		// then we create own page. 
		if(! logged_in() )
			create_login_form( $html );
		else if( $ret == true )
			create_own_page( $html );

		echo $html->createSiteBottom();
	}

	main();

?>
