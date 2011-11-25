#!/bin/sh

# $Id: mk.FreeBSD.docproj.sh,v 1.1 2001/09/26 23:53:00 sven Exp $
#
# This script needs textproc/docproj installed. It creates HTML pages for
# each chapter.

jade -c /usr/local/share/sgml/docbook/dsssl/modular/catalog \
     -c /usr/local/share/sgml/docbook/catalog \
     -c /usr/local/share/sgml/jade/catalog \
     -d /usr/local/share/sgml/docbook/dsssl/modular/html/docbook.dsl \
     -t sgml manual.xml
