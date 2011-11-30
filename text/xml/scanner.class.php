<?php

# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.

# About this file:
#
# This is two-pass XML scanner supporting block and inline elements.
# Before anything can be scanned, the tags must be defined.
#
# Scanning a document:
#
# Before a document can be processed, it must be scanned and validated using
# the scan() function. Along with this process a document tree of element
# nodes is set up which can be saved and reused to save scanning time.
#
# Walking a document:
#
# exec() walks a document tree and calls the right tag handler for
# each element, passing it attributes and/or the document branch of
# blocks they might enclose.
# Tag names imply the names of the tg handlers that return information an
# element is replaced by.
# Functions for all tags start with 'tag_', name space dependend functions
# start with 'dirtag_'. Please use alphanumerical characters for tag
# names only.

/**
 * XML scanner
 *
 * @access public
 * @module xml_scanner
 * @package Text functions
 */
class XML_SCANNER {
    var $classes; # Class ids keyed by name.
    var $dirtypes; # Class ids keyed by name.

    ###############
    ### Context ###
    ###############

    var $context = '';
    var $dirtype = '';
    var $parent_context = '';
    var $parent_dirtype = '';

    # Internal context stack.
    var $context_stack;
    var $dirtype_stack;
    var $_block_stack = array ();

    # Calculated information
    var $_tag;		# Commands used with all tags
    var $_dirtag;	# Commands used with certain directory types.

    # Reference to pass to tag handlers.
    var $_ref = 0;

    # Reference to context function.
    var $_context_func = false;

    # Original input sent through scan(). Needed for showing the position
    # of errors.
    var $_in;

    ###################
    ### Definitions ###
    ###################

    /**
     * Define an element.
     *
     * @access public
     * @param string $tagname Element name.
     * @param integer $id_class Element id.
     */
    function assoc ($tagname, $id_class)
    {
        $this->classes[$tagname] = $id_class;
        $this->dirtypes[$id_class] = $tagname;
    }

    /**
     * Register list of handlers that work with all element types.
     *
     * @access public
     * @param string $names Space separated list of tag names.
     */
    function tags ($names)
    {
        $names = ereg_replace ('-', '_', $names);
        $a = explode (' ', $names);
        foreach ($a as $v)
            $this->_tag[strtolower ($v)] = true;
    }

    /**
     * Register directory type dependend functions.
     *
     * @access public
     * @param string $ns Name space.
     * @param string $names Space separated list of tag names.
     */
    function dirtag ($ns, $names)
    {
        $ns = strtolower ($ns);
        $names = ereg_replace ('-', '_', $names);
        $a = explode (' ', $names);
        foreach ($a as $v)
            $this->_dirtag[$ns][strtolower ($v)] = true;
    }

    /**
     * Set object of element handlers.
     *
     * @access public
     * @param object any $objref
     */
    function set_ref (&$objref)
    {
        $this->_ref =& $objref;
    }

    /**
     * Register context function.
     *
     * @access public
     * @param object any $objref
     */
    function set_context_func (&$context_func)
    {
        $this->_context_func =& $context_func;
    }

    /**
     * Push context on stack.
     *
     * @access public
     */
    function push_context ()
    {
        if (!isset ($this->context_stack)) {
	    $this->context_stack = array ();
	    $this->dirtype_stack = array ();
        }

        array_push ($this->context_stack, $this->parent_context);
        array_push ($this->dirtype_stack, $this->parent_dirtype);

        $this->parent_context = $this->context;
        $this->parent_dirtype = $this->dirtype;
    }

    /**
     * Pop context from stack.
     *
     * @access public
     */
    function pop_context ()
    {
        if (!sizeof($this->context_stack))
            die_traced ('Context stack underflow.');

        $this->context = $this->parent_context;
        $this->dirtype = $this->parent_dirtype;

        $this->parent_context = array_pop ($this->context_stack);
        $this->parent_dirtype = array_pop ($this->dirtype_stack);
    }

    ############################
    ### Scanning & execution ###
    ############################

    /**
     * Call handler for an element.
     *
     * @param array $node Node of element in document tree.
     * @param integer $id
     * @param array $con Initial context.
     * @returns string Result from handler.
     */
    function &_exec_tag (&$node, $id = 0, $con = '')
    {
        # Return #PCDATA sections as they are.
        if (isset ($node['pcdata']))
            return $node['pcdata'];

        $ns = $node['ns'];
        $name = $node['name'];
        isset ($node['attr']) ? $args = $node['attr'] : $args = array ();
        $args['_'] =& $node['_'];

        $out = '';

        # Save and create context for element.
        $this->push_context ();
        if ($this->_context_func) {
            $this->_context_func ($ns, $id);
            $type = $this->dirtype;
        } else
            $type = $ns;
        if ($con && !$this->context)
            $this->context = $con;

        # Execute tag dependend function, global function otherwise
        # and echo the returned data.
        # XXX In XML this would be the default name space.
        if (isset ($this->_dirtag[$type][$name]) && $this->_dirtag[$type][$name]) {
            $func = "dirtag_$type_$name";
            $out .= ($this->_ref) ? $func ($this->_ref, $args) : $func ($args);  
        } else if (isset ($this->_tag[$name])) {
            $func = "tag_$name";
            $out .= ($this->_ref) ? $func ($this->_ref, $args) : $func ($args);  
        } else
            $out .= "<b>&lt;$ns:$name ... &gt;</b>";

        # Back to former context.
        $this->pop_context ();

        return $out;
    }

    /**
     * Exec a document branch.
     *
     * This function iterates over a document tree and calls the tag handler
     * defined by assoc(), tag() and/or dirtag().
     *
     * @access public
     * @param array $branch Root node of a branch or tree.
     * @param integer $id
     * @param array $con Initial context.
     * @returns string Result from handler.
     */
    function &exec (&$branch, $id = 0, $con = 0)
    {
        if (!is_array ($branch))
            return;

        $out = '';
        foreach ($branch as $node)
            $out .= $this->_exec_tag ($node, $id, $con);

        return $out;
    }

    function _err ($msg)
    {
        echo "<b>Error in XML syntax:</b><hr><pre>" . htmlentities ($this->_in) . "<font color='red'>$msg</font></pre>";
        die_traced ();
    }

    function _tags2tree (&$data, $ptr = 0)
    {
        global $debug;

        $len = strlen ($data);
        $out = '';
        $node = array ();
        while ($data && ($p = strpos ($data, '<', $ptr)) !== false) {
            $ending = $inline = '';

            # Get everything before the tag seems to start.
            $tmp = substr ($data, $ptr, $p - $ptr);
            $out .= $tmp;
            $this->_in .= $tmp;

	    # Jump to first character after '<'.
            $ptr = $p + 1;

            # Check if this is an ending tag.
            if (($ending = substr ($data, $ptr, 1)) == '/')
                $ptr++;
            else
                $ending = '';

	    # Get tag end.
            $p = strpos ($data, '>', $ptr);

	    # Choke if there's no end.
	    if ($p === false) {
                $this->_in .= '<';
	        $this->_err ('Runaway tag.' . substr ($data, $ptr));
            }

            $t = strpos ($data, '<', $ptr);
            if ($t !== false && $t < $p) {
                $out .= '<';
                $this->_in .= '<';
                continue;
            }

	    # Get tag contents.
            $tag = substr ($data, $ptr, $p - $ptr);
            $this->_in .= "<$ending$tag$inline>";

            # Check if its an inline tag without child block.
            if (($inline = substr ($tag, strlen ($tag) - 1)) == '/')
                $tag = substr ($tag, 0, strlen ($tag) - 1);
            else
                $inline = '';

	    # Jump to first character after tag end.
            $ptr = $p + 1;

	    # Check if tag has a name space.
	    if (($s = strpos ($tag, ':')) === false) {
	        $tmp = "<$ending$tag$inline>"; # No known tag.
                $out .= $tmp;
	        continue;
	    }

	    # Split into name space and command part.
	    $ns = strtolower (substr ($tag, 0, $s));
	    $cmdarg = substr ($tag, $s + 1);

	    # Check for arguments.
	    if (($b = strpos ($cmdarg, ' ')) !== false) {
	        $cmd = substr ($cmdarg, 0, $b);
	        $arg = substr ($cmdarg, $b + 1);
	    } else {
	        $cmd = $cmdarg;
	        $arg = '';
	    }

	    # Split command and argument part.
            $cmd = strtolower (ereg_replace ('-', '_', trim ($cmd)));
	    $arg = trim ($arg);
	    if (!$cmd) {
	        $tmp = "<$ending$tag$inline>";
                $out .= $tmp;
                $this->_in = $tmp;
	        continue;
	    }

            $ap = 0;
            $args = array ();
            while (($a = strpos ($arg, '=', $ap)) !== false) {
                $n = trim (substr ($arg, $ap, $a - $ap));
                if (($t = strpos ($arg, '"', $a + 2)) === false)
                    break;
                $args[$n] = substr ($arg, $a + 2, $t - $a - 2);
                $ap = $t + 1;
            }

            # If there's plain data, append it before the tags node.
            if ($out) {
                $node[] = array ('pcdata' => $out);
                $out = '';
            }

            if ($ending) {
                # Check if were in a block of the elemt type to close.
                $n = array_pop ($this->_block_stack);
                if (!$n)
                    $this->_err ('No open element to be closed.');
                if ($n != $cmd)
                    $this->_err ("Element '$n' needs to be closed instead of '$cmd'.");

                return array ($node, $ptr);
            }

            $n = array ('ns' => $ns, 'name' => $cmd);
            if ($args)
                $n['attr'] = $args;

            if (!$inline) {
                # Push element type to block stack.
                array_push ($this->_block_stack, $cmd);

                # Scan block inside tag for child list.
                list ($childs, $ptr) = $this->_tags2tree ($data, $ptr);
                $n['_'] = $childs;
            }

            # Append new node to list.
            $node[] = $n;
        }

        # Append everything that's left.
        $tmp = substr ($data, $ptr);
        $out .= $tmp;
        $this->_in .= $tmp;
        if ($out)
            $node[] = array ('pcdata' => $out);

        return array ($node, strlen ($data));
    }

    /**
     * Create document tree from XML document.
     *
     * A document tree is a nested array containing the following fields:
     * 'name': The tag name (non-optional). For a text block the name is
     * #PCDATA, 'ns': the namespace, 'args': an array of arguments keyed by
     * name, '_': an array of child blocks (more arrays of this type).
     * Use exec() to execute a document tree.
     *
     * @access public
     * @param string $data XML document.
     * @returns array Document tree.
     * @see exec()
     */
    function &scan (&$data)
    {
        type_string ($data);

        $this->_in = '';
        $this->_block_stack = array ();
        list ($tree, $ptr) = $this->_tags2tree ($data);

        return $tree;
    }
}

?>
