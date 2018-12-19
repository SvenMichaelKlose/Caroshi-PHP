<?php
/**
 * RFC 2110 "MIME E-mail Encapsulation of Aggregate Documents, such as HTML"
 * compliant mail attachment creation.
 *
 * @access public
 * @module rfc2110
 * @package Network functions
 */

# Copyright (c) 2000-2001 dev/consulting GmbH
# Copyright (c) 2011,2018 Sven Michael Klose <pixel@hugbox.org>
#
# Licensed under the MIT, BSD and GPL licenses.


# Example:
/*
  mail (
      "sven@devcon.net",
      "ILOVEYOU",
      rfc2110_attachment (
        "Worx.\n", "text/plain; charset=UTF-8"
      ) .
      rfc2110_attachment_file (
        "some.gif", "image/gif"
      ) .
      rfc2110_tail (), 
      rfc2110_header ("centralservices@devcon.net")
    ); # sends some text and some.gif to sven@devcon.net
*/

$__rfc2110boundary = uniqid (rand ());

/**
 * Common boundary string.
 *
 * @access public
 * @returns string
 */
function rfc2110_boundary ()
{
    global $__rfc2110boundary;

    return "rfc2110_boundary_cs$__rfc2110boundary";
}

/**
 * Initialise mail.
 *
 * @access public
 */
function rfc2110_init ()
{
    $GLOBALS['_rfc2110comment'] = FALSE;
}

/**
 * Create multipart header extension.
 *
 * @access public
 * @returns string
 */
function rfc2110_header ($fromemail)
{
    return "From: $fromemail\n" .
	   "Reply-To: $fromemail\n" .
	   "X-Mailer: central services rfc2110 support.\n" .
    	   "Mime-Version: 1.0\n" .
	   "Content-Type: Multipart/mixed; boundary=\"".
	   rfc2110_boundary () . "\"";
}

/**
 * Create attachment
 *
 * @access public
 * @param string $content Data to attach.
 * @param string $type MIME type of the data.
 * @param string $encoding Encoding type.
 * @param string $contentid Content-ID
 * @returns string
 */
function rfc2110_attachment ($content, $type, $encoding = 0, $contentid = 0)
{
    global $_rfc2110comment;

    if (!$_rfc2110comment) {
        $_rfc2110comment = TRUE;
        $header = "  This is a multipart mime message.\n\n";
    }
    $header .= "--" . rfc2110_boundary () . "\n";
    if ($contentid)
        $header .= "Content-ID: $contentid\n";
    if ($type)
        $header .= "Content-Type: $type\n";
    if ($encoding)
        $header .= "Content-Transfer-Encoding: $encoding\n";

    return "$header\n$content";
}

/**
 * Create base64 encoded file attachment
 *
 * @access public
 * @param string $filename Path to file.
 * @param string $type MIME type of file.
 * @returns string
 */
function rfc2110_attachment_file ($filename, $type)
{
    # Read in file, encode base64
    $fd = fopen ($filename, "r");
    $image = base64_encode ($bin = fread ($fd, filesize ($filename)));
    fclose ($fd);

    # Make 64 char wide strips from encoded file
    for ($i = 0; $i < strlen ($image); $i += 64)
        $attachment .= substr ($image, $i, 64) . "\n";

    # Create attachment with md5 checksum
    return rfc2110_attachment (
        $attachment,
        $type . "; name=\"$filename\"\n" .
	    "Content-Disposition: inline; filename=\"$filename\"\n" .
	    "Content-MD5: ". md5 ($bin),
        "base64"
    );
}

/**
 * End of message body.
 *
 * @access public
 * @returns string
 */
function rfc2110_tail ()
{
    return "--" . rfc2110_boundary () . "--\n";
}
?>
