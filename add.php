<?php

/* 
add.php - Add item to database. Part of Bookmarks-project.
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


// **************************************************
//	check_is_all_ok
/*!
	@brief Check if required session variables
	  and POST values is set.
*/
// **************************************************
function check_is_all_ok()
{
	// User must be logged in to use this.
	if(! isset( $_SESSION['bookmarks']['username'] ) )
	{
		header( 'Location: index.php' );
		die();
	}

	// There must be url in POST-values.
	if(! isset( $_POST['url'] ) )
	{
		header( 'Location: index.php' );
		die();
	}

	// URL cannot be empty.
	if( trim( $_POST['url'] ) == '' )
	{
		header( 'Location: index.php' );
		die();
	}
}

// **************************************************
//	main
/*!
	@brief Application main function.

	@param $p POST-values array.
*/
// **************************************************
function main( $p )
{
	// Check if all POST and SESSION variables are OK.
	check_is_all_ok();

	require 'CSQLite/CSQLite.php';
	require 'CHTML/CHTML.php';

	$db = new CSQLite();
	$html = new CHTML();

	// Try to open database connection.
	try {
		$db->connect( 'bookmarks.db', false );
	} catch( Exception $e ) {
		die( 'Error in database connection!' );
	}

	$p['url'] = $db->makeSafeForDB( $p['url'] );

	// Description is optional, so make sure that we do
	// not try to use it if does not exists. Make it, if there
	// is no index already.
	if(! isset( $p['description'] ) 
		|| trim( strlen( $p['description'] ) ) == 0 )
		$p['description'] = $p['url'];
	else
		$p['description'] = $db->makeSafeForDB( $p['description'] );

	// Logged user ID from sessions.
	$uid = $_SESSION['bookmarks']['id'];

	// Create INSERT query where we add new bookmark
	$q = 'INSERT INTO bookmarks VALUES( NULL, "'
		. $uid . '", "' . $p['url'] . '", "'
		. $p['description'] . '", "' 
		. date( 'Y-m-d H:i:s' ) . '")';

	try {
		$db->query( $q );
	} catch( Exception $e ) {
		die( 'Error: ' . $e->getMessage() );
	}

	// Home sweet home. After successfully query, go back to mainpage.
	header( 'Location: index.php' );
}

main( $_POST );
?>
