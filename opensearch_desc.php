<?php
/**
 * The web entry point for generating an OpenSearch description document.
 *
 * See <http://www.opensearch.org/> for the specification of the OpenSearch
 * "description" document. In a nut shell, this tells browsers how and where
 * to submit submit search queries to get a search results page back,
 * as well as how to get typeahead suggestions (see ApiOpenSearch).
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup entrypoint
 */

// This endpoint is supposed to be independent of request cookies and other
// details of the session. Enforce this constraint with respect to session use.
define( 'MW_NO_SESSION', 1 );

define( 'MW_ENTRY_POINT', 'opensearch_desc' );

require_once __DIR__ . '/includes/WebStart.php';

if ( $wgRequest->getVal( 'ctype' ) == 'application/xml' ) {
	// Makes testing tweaks about a billion times easier
	$ctype = 'application/xml';
} else {
	$ctype = 'application/opensearchdescription+xml';
}

$response = $wgRequest->response();
$response->header( "Content-type: $ctype" );

// Set an Expires header so that CDN can cache it for a short time
// Short enough so that the sysadmin barely notices when $wgSitename is changed
$expiryTime = 600; # 10 minutes
$response->header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $expiryTime ) . ' GMT' );
$response->header( 'Cache-control: max-age=600' );

print '<?xml version="1.0"?>';
print Xml::openElement( 'OpenSearchDescription',
	[
		'xmlns' => 'http://a9.com/-/spec/opensearch/1.1/',
		'xmlns:moz' => 'http://www.mozilla.org/2006/browser/search/' ] );

// The spec says the ShortName must be no longer than 16 characters,
// but 16 is *realllly* short. In practice, browsers don't appear to care
// when we give them a longer string, so we're no longer attempting to trim.
//
// Note: ShortName and the <link title=""> need to match; they are used as
// a key for identifying if the search engine has been added already, *and*
// as the display name presented to the end-user.
//
// Behavior seems about the same between Firefox and IE 7/8 here.
// 'Description' doesn't appear to be used by either.
$fullName = wfMessage( 'opensearch-desc' )->inContentLanguage()->text();
print Xml::element( 'ShortName', null, $fullName );
print Xml::element( 'Description', null, $fullName );

// By default we'll use the site favicon.
// Double-check if IE supports this properly?
print Xml::element( 'Image',
	[
		'height' => 16,
		'width' => 16,
		'type' => 'image/x-icon' ],
	wfExpandUrl( $wgFavicon, PROTO_CURRENT ) );

$urls = [];

// General search template. Given an input term, this should bring up
// search results or a specific found page.
// At least Firefox and IE 7 support this.
$searchPage = SpecialPage::getTitleFor( 'Search' );
$urls[] = [
	'type' => 'text/html',
	'method' => 'get',
	'template' => $searchPage->getCanonicalURL( 'search={searchTerms}' ) ];

foreach ( $wgOpenSearchTemplates as $type => $template ) {
	if ( !$template ) {
		$template = ApiOpenSearch::getOpenSearchTemplate( $type );
	}

	if ( $template ) {
		$urls[] = [
			'type' => $type,
			'method' => 'get',
			'template' => $template,
		];
	}
}

// Allow hooks to override the suggestion URL settings in a more
// general way than overriding the whole search engine...
Hooks::runner()->onOpenSearchUrls( $urls );

foreach ( $urls as $attribs ) {
	print Xml::element( 'Url', $attribs );
}

// And for good measure, add a link to the straight search form.
// This is a custom format extension for Firefox, which otherwise
// sends you to the domain root if you hit "enter" with an empty
// search box.
print Xml::element( 'moz:SearchForm', null,
	$searchPage->getCanonicalURL() );

print '</OpenSearchDescription>';
