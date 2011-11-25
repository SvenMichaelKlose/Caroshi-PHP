<?php
  /**
   * RFC 2110 "MIME E-mail Encapsulation of Aggregate Documents, such as HTML"
   * compliant mail attachment creation.
   *
   * @access public
   * @module rfc2110
   * @package Network functions
   */

  # $Id: rfc2110.inc.php,v 1.3 2002/06/01 04:45:34 sven Exp $
  #
  # Copyright (c) 2000-2001 dev/consulting GmbH
  #                         Sven Michael Klose <sven@devcon.net>
  #
  # This library is free software; you can redistribute it and/or
  # modify it under the terms of the GNU Lesser General Public
  # License as published by the Free Software Foundation; either
  # version 2.1 of the License, or (at your option) any later version.
  #
  # This library is distributed in the hope that it will be useful,
  # but WITHOUT ANY WARRANTY; without even the implied warranty of
  # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
  # Lesser General Public License for more details.
  #
  # You should have received a copy of the GNU Lesser General Public
  # License along with this library; if not, write to the Free Software
  # Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

  # Example:
/*
    mail (
      "sven@devcon.net",
      "ILOVEYOU",
      rfc2110_attachment (
        "Worx.\n", "text/plain; charset=ISO-8859-1"
      ) .
      rfc2110_file (
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
    return "rfc2110_boundary_cs" . $__rfc2110boundary;
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
   * End of message body.
   *
   * @access public
   * @returns string
   */
  function rfc2110_tail ()
  {
    return "--" . rfc2110_boundary () . "--\n";
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
      $_rfc2110comment = true;
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
  function rfc2110_file ($filename, $type)
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
?>
