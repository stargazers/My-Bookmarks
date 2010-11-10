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
		$form = new CForm( 'index.php', 'post' );

		echo '<div id="login">';
		echo '<h2>My Bookmarks - Login</h2>';
		echo $html->showMessage();
		$form->addTextField( 'Username: ', 'username' );
		$form->addPasswordField( 'Password: ', 'password' );
		$form->addSubmit( 'Login', 'submit' );
		echo $form->createForm();

		echo '<p id="text_create_account">No account? ';
		echo $html->createLink( 'register.php', 'Create it', false );
		echo '</p>';
		echo '</div>';
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
	function handle_get_values( $db )
	{
		if(! isset( $_GET ) )
			return;
		
		$g = $_GET;
		$html = new CHTML();

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
				remove_bookmarks( $db );
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
	//	create_div_bottom
	/*!
		@brief Create bottom for divs used when creating, 
		  editing or deleting bookmark. Eg. creates link where
		  we can go back to listing or logout.
	*/
	// **************************************************
	function create_div_bottom()
	{
		$html = new CHTML();
		echo $html->createLink( 'index.php', 'Back to bookmarks list' );
		echo '</div>';
	}

	// **************************************************
	//	remove_bookmarks
	/*!
		@brief This will remove bookmark if there is GET parameter
		  with confirmation. If not, then we show confirmation
		  dialog first here.

		@param $db CSQLite database connection.
	*/
	// **************************************************
	function remove_bookmarks( $db )
	{
		if(! isset( $_GET['id'] ) )
			return;

		$html = new CHTML();

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

			echo $html->createSiteTop( 'Bookmarks');
			create_logout_button( $html );

			echo '<div id="confirmation">';
			echo '<h2>Delete bookmark</h2>';

			echo 'Are you sure that you want delete bookmark "'
				. $ret[0]['description'] . '"?<br /><br />';

			// Create two images
			$img_url = 'icons/nuvola/32x32/actions/';
			$ok = $html->createImg( $img_url . 'apply.png' );
			$cancel = $html->createImg( $img_url . 'cancel.png' );

			// Remove it imagelink
			$html->setExtraParams( array( 'title' => 'Yes, remove it' ) );
			echo $html->createLink( 'index.php?page=remove&id=' 
				. $_GET['id'] . '&confirm=1', $ok );

			echo '<span class="separator"></span>';

			// Don't remove it -imagelink
			$html->setExtraParams( array( 
				'title'=> 'No, don\'t remove it' ) );
			echo $html->createLink( 'index.php', $cancel );

			echo '<br /><br />';
			create_div_bottom();
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

		$form = new CForm( 'edit.php', 'post' );
		$q = 'SELECT * FROM bookmarks WHERE id=' . $_GET['id'];
		$ret = $db->queryAndAssoc( $q );

		$html = new CHTML();
		create_logout_button( $html );

		echo '<div class="link_handling">';
		echo '<h2>Edit bookmarks</h2>';

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

			echo $form->createForm();
		}

		create_div_bottom();
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
		$form->addTextField( 'Link URL: ', 'url' );
		$form->addTextField( 'Description: ', 'description' );
		$form->addSubmit( 'Save new bookmark', 'submit' );

		create_logout_button( $html );
		echo '<div class="link_handling">';
		echo '<h2>Create new bookmark</h2>';
		echo $form->createForm();
		echo '<br />';

		create_div_bottom();
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

	function create_logout_button( $html )
	{
		echo '<div id="logout">';
		$img_url = 'icons/nuvola/48x48/actions/';
		$html->setExtraParams( array( 'title' => 'Log out' ) );
		$logout = $html->createImg( $img_url . 'exit.png' );
		echo $html->createLink( 'index.php?action=logout', $logout );
		echo '</div>';
	}

	// **************************************************
	//	create_own_page
	/*!
		@brief This will create page what will be shown to user
		  after login is done succesfully.
		
		@param $db Database class.
	*/
	// **************************************************
	function create_own_page()
	{
		$html = new CHTML();

		create_logout_button( $html );

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

		// Check if there is POST-values (eg. login information)
		handle_post_values( $html );

		// This will return true if we need to create own page.
		// False will be set if we do not want to show own page.
		$ret = handle_get_values( $db );

		// Include JQuery and CSS file and create HTML headers.
		$html->setJS( 'jquery/jquery-1.4.3.js' );
		$html->setCSS( 'bookmarks.css' );
		echo $html->createSiteTop( 'Bookmarks');

		// If we are not logged in, create login form.
		// If we are logged in and handle_get_values returned true,
		// then we create own page. 
		if(! logged_in() )
			create_login_form( $html );
		else if( $ret == true )
			create_own_page();

		echo $html->createSiteBottom();
	}

	main();

?>
