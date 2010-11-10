	<?php

	require 'CSQLite/CSQLite.php';
	require 'CHTML/CHTML.php';
	require 'CForm/CForm.php';
	require 'CGeneral/CGeneral.php';
	require 'CUsers/CUsers.php';

	// **************************************************
	//	register
	/*!
		@brief Try to register new user.

		@param $db CSQLite database class instance.

		@return 0 if success, -1 if user already exists
	*/
	// **************************************************
	function register( $db )
	{
		$users = new CUsers( $db );
		$p = $_POST;

		// Return 0 if success.
		if( $users->registerNew( $p['username'], $p['password'] ) == 0 )
			return 0;

		// User already exists
		return -1;
	}

	// **************************************************
	//	handle_post_values
	/*!
		@brief Check if there is POST values to handle
		  and call correct functions if needed.
	
		@param $html CHTML Class instance

		@param $db CSQLite database class instance

		@return -1 = No all required field set for registeration.
		  -2 Passwords did not match
		  -3 User already exists.
		  -4 Username cannot be empty.
		  0 User registered.
	*/
	// **************************************************
	function handle_post_values( $html, $db )
	{
		$gen = new CGeneral();

		// rf = Required Fields
		$rf = array( 'username', 'password', 'password_again' );

		// If all required fields are not set, return -1
		if(! $gen->checkRequiredFields( $_POST, $rf ) )
			return -1;

		// Make sure that there is no empty usernames, eg.
		// no username at all or only spaces.
		if( strlen( trim( $_POST['username'] ) ) == 0 )
		{
			$html->setMessage( 'Username cannot be empty.' );
			return -4;
		}

		// Is password field value same than password_again?
		if( $_POST['password'] != $_POST['password_again'] )
		{
			$html->setMessage( 'Passwords did not match.' );
			return -2;
		}

		// Try to register user.
		$ret = register( $db );

		// User already exists?
		if( $ret == -1 )
		{
			$html->setMessage( 'User ' . $_POST['username'] 
				. ' already exists!' );
			return -3;
		}

		// New user registered.
		return 0;
	}

	// **************************************************
	//	create_register_form
	/*!
		@brief Create registeration form.

		@retun None.
	*/
	// **************************************************
	function create_register_form( $html )
	{
		$frm = new CForm( 'register.php', 'post' );

		echo '<div id="register">';
		echo '<h2>Register</h2>';
		echo $html->showMessage();
		$frm->addTextField( 'Username: ', 'username' );
		$frm->addPasswordField( 'Password: ', 'password' );
		$frm->addPasswordField( 'Password again: ', 'password_again' );
		$frm->addSubmit( 'Register', 'submit' );
		echo $frm->createForm();

		echo '<div class="back">';
		echo $html->createLink( 'index.php', 'Back to mainpage' );
		echo '</div>';
		echo '</div>';
	}
	
	// **************************************************
	//	show_registeration_ok
	/*!
		@brief Show message after registeration

		@param $html CHTML class.
	*/
	// **************************************************
	function show_registeration_ok( $html )
	{
		echo '<div id="register_ok">';
		echo '<h2>Register ok!</h2>';
		echo 'You are now registered. You can go now back to '
			. 'mainpage and login.<br><br>';
		echo $html->createLink( 'index.php', 'Back to mainpage' );
		echo '</div>';
	}

	// **************************************************
	//	main
	/*!
		@brief Application main function.
	*/
	// **************************************************
	function main()
	{
		$db = new CSQLite();
		$db->connect( 'users.db', false );

		$html = new CHTML();
		$html->setCSS( 'bookmarks.css' );

		echo $html->createSiteTop( 'Bookmarks - Registeration' );

		$ret = handle_post_values( $html, $db );

		if( $ret != 0 )
			create_register_form( $html );
		else
			show_registeration_ok( $html );

		echo $html->createSiteBottom();
	}

	main();

?>
