<script type="text/javascript">
$(document).ready( function() 
{
	$( ".bookmark img" ).click( function()
	{
		// Get item ID number, from 4th char to end of the string.
		// First four is for text img_
		item_id = this.id.substr( 4, this.id.length );

		// Show or hide edit form for this item.
		$( "#ef_" + item_id ).toggle();

		img_path = 'icons/nuvola/32x32/actions/';

		// This will update icon to down or up arrow.
		if( $( this ).attr( "src" ) == img_path + '1downarrow.png' )
			$( this ).attr( "src", img_path + '1uparrow.png' );
		else
			$( this ).attr( "src", img_path + '1downarrow.png' );
	});

	$( ".bookmark a:link" ).hover( function()
	{
		item_id = this.id.substr( 4, this.id.length );
		$( "img_" + item_id ).toggle();
	});

} );
</script>
