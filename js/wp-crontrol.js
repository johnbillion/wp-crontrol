
const header = document.getElementById('crontrol-header');
const wpbody = document.getElementById('wpbody-content');

if ( header && wpbody ) {
	wpbody.prepend( header );
}
