<?php

/* 
Bookmarks - Edit old bookmark.
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
require 'CSQLite/CSQLite.php';
require 'CHTML/CHTML.php';
require 'CGeneral/CGeneral.php';

// **************************************************
//	main
/*!
	@brief Main function.

	@param $data POST-data
*/
// **************************************************
function main( $data )
{
	$html = new CHTML();
	$gen = new CGeneral();

	// rf = Required Fields
	$rf = array( 'url', 'description', 'bookmark_id' );

	if(! $gen->checkRequiredFields( $data, $rf ) )
	{
		header( 'Location: index.php' );
		die();
	}

	$db = new CSQLite();
	$db->connect( 'bookmarks.db', false );

	// Make sure that we edit only our own bookmarks.
	$id = $db->makeSafeForDb( $data['bookmark_id'] );
	$q = 'SELECT user_id FROM bookmarks WHERE id=' . $id;
	$ret = $db->queryAndAssoc( $q );

	// If logged user ID is different than user id in database with
	// given item ID, then somebody is trying to haxhaxhax. We just
	// forward to index.php.
	if( $ret[0]['user_id'] != $_SESSION['bookmarks']['id'] )
		header( 'Location: index.php' );

	// Try to prevent possible SQL injections
	$desc = $db->makeSafeForDb( $data['description'] );
	$url = $db->makeSafeForDb( $data['url'] );

	// If description is empty, use URL as a text.
	if( trim( $desc ) == '' )
		$desc = $url;

	// Update value from database.
	$q = 'UPDATE bookmarks SET description="' . $desc . '", url="'
		. $url . '" WHERE id="' . $id . '"';
	$db->query( $q );

	header( 'Location: index.php' );
}

main( $_POST );

?>
