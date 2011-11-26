<?php
# Copyright (c) 2000-2002 dev/consulting GmbH
# Copyright (c) 2011 Sven Michael Klose <pixel@copei.de>
#
# Licensed under the MIT, BSD and GPL licenses.


require_once PATH_TO_CAROSHI . '/dbi/dbi.class.php';
require_once PATH_TO_CAROSHI . '/dbi/dbsession.class.php';
require_once PATH_TO_CAROSHI . '/dbi/dbtoken.class.php';
require_once PATH_TO_CAROSHI . '/object/is_a.php';
require_once PATH_TO_CAROSHI . '/proc/debug_dump.php';
require_once PATH_TO_CAROSHI . '/proc/event.class.php';
require_once PATH_TO_CAROSHI . '/proc/_subsession.class.php';

# Flags to application::arg().
define ('ARG_OPTIONAL', 1);
define ('ARG_SUB', 2);

/**
 * Application base class
 *
 * @access public
 * @package Application server
 * @author Sven Michael Klose <pixel@copei.de>
 */
class application {
    # Public.
    public $db;		# dbi.class instance.
    public $session;	# dbsession.class instance.

    # Private. Hands off.
    private $_subargs;  # Current subsession arguments.
    private $_event;    # Current function's event object.
    private $_handlers; # Array of event handler keyed by name.
    private $_null_handler = 'defaultview'; # Name of null event handler.
    private $_types;
    private $_tokens;   # Reference to dbtoken.class instance.

    /**
     * Execute application.
     *
     * @access public
     * @returns void This function never returns.
     */
    function run ()
    {
        global $debug;

        $this->_application_init ();
        $tokens =& $this->_tokens;

        # Call null event handler if there's no event.
        if (!isset ($this->_event)) {
	    if ($debug)
	        echo 'No event.<BR>';
	    $this->_event = new event ($this->_null_handler);
            $sub = new _application_subsession;
            $this->_event->subsession = $tokens->create ($sub);
        }

        # Dump arguments in debug mode if there's no event handler to invoke.
        $handler = $this->_event->name;
        if (!isset ($this->_handlers[$handler]))
            if (isset ($this->debug) && $this->debug)
	        $this->_application_dump ();

        $this->call ($this->_event);

        application::close ();
    }

    /**
     * Shutdown application.
     *
     * @access public
     */
    function close ()
    {
        # Give derived class a chance to shutdown everything properly.
        $this->close ();

        # Writing cached tokens to the database may take a while, so flush
        # the output buffer right now.
        flush ();

        # Write cached tokens back to the database.
        $this->_tokens->close ();
    }

    /**
     * Return reference to last triggered event.
     *
     * @access public
     * @returns object event
     */
    function event ()
    {
        return $this->_event;
    }

    /**
     * Call single event.
     *
     * Even if a next event is defined by use of event::set_caller() this
     * function will ignore it. Use call() instead.
     *
     * @access public
     * @param object event $e Event to trigger.
     */
    function call_single (&$e)
    {
        if (!is_a ($e, 'event'))
            die ('application::call_single(): Parameter is not an event.');

        $tokens =& $this->_tokens;

        # Save current event and subsession.
        $oldevent =& $this->_event;
        $oldsub =& $this->_subargs;

        $handler = $e->name;

        # Set new event and subsession information.
        $this->_event =& $e;
        if (!isset ($e->subsession) || !$e->subsession)
            $e->subsession = $oldevent->subsession;
        $sub = $tokens->get ($e->subsession);
        $this->_subargs =& $sub->args;

        # Dump arguments in debug mode.
        if ($this->debug) {
            echo "<hr><b>Call to event handler '$handler':</b>";
	    $this->_application_dump ();
        }

        # Don't output header or footer for internal functions.
        $f = $handler != 'return2caller' && $handler != '__call_sub' ?
             true : false;
 
        # Call header function if so desired.
        if ($f && method_exists ($this, 'start_view'))
            $this->start_view ();

        # Call event handler.
        if (($obj =& $this->_handlers[$handler]))
	    $ret =& $obj->$handler ($this);
        else
	    $ret =& $handler ($this);

        if ($this->debug) {
            echo "<b>Return value of event handler '$handler':</b>";
            debug_dump ($ret);
        }

        # Call footer function if so desired.
        if ($f && method_exists ($this, 'end_view'))
            $this->end_view ();

        # Save subsession.
        if ($e->subsession)
            $tokens->write ($e->subsession, $sub);

        # Restore former event object and subsession.
        $this->_event =& $oldevent;
        $this->_subargs =& $oldsub;

        if (isset ($ret))
            return $ret;
    }

    /**
     * Call batch of events.
     *
     * @access public
     * @param object event Event to trigger.
     */
    function call ($e)
    {
        global $debug;

        if (is_string ($e)) {
            $e = new event ($e);
            $e->subsession = $this->_event->subsession;
        } else if (!is_a ($e, 'event')) {
            debug_dump ($e);
            die ('application::call(): Argument is not an event object.');
        }

        $handler = $e->name;
        do {
	    # Check if event handler is registered.
            if (!isset ($this->_handlers[$handler])) {
	        if ($handler)
	            echo "Unknown event handler '$handler'.<BR>";
                $e = new event ($this->_null_handler);
            }

            # Detect infinite loops.
            static $called = array ();
            $sv = serialize ($e);
            if (isset ($called[$sv]))
                die ("application::call (): Infinite loop detected before call to event handler '$handler'.");
            $called[$sv] = true;

	    $ret =& $this->call_single ($e);

            $e = $e->next;
	    if (!is_a ($e, 'event'))
	        break;

	    if ($debug)
	        echo '<B>Calling next event handler:</B>';
        } while ($e);

        if (isset ($ret))
            return $ret;
    }

    /**
     * Register event handler.
     *
     * @access public
     * @param string $handler Name of event handler.
     * @param integer $token_type Token type to use for links to the function.
     */
    function add_function ($handler, $token_type = TOKEN_DEFAULT)
    {
        if (!is_string ($handler))
            die ('application::add_function(): Handler name is not a string.');

        $this->_set_type ($handler, $token_type);
        $this->_handlers[$handler] = false;
    }

    /**
     * Register event handler method in particular object..
     *
     * @access public
     * @param string $method Name of event handler.
     * @param object $object Reference to object.
     * @param integer $token_type Token type to use for links to the method.
     */
    function add_method ($method, &$object, $token_type = TOKEN_DEFAULT)
    {
        if (!is_string ($method))
            die ('application::add_method(): Method name is not a string.');
        if (!is_object ($object))
            die ('application::add_method(): Method name is not a string.');
        if (!is_int ($token_type))
            die ('application::add_method(): Token type is not an integer.');

        $this->_set_type ($method, $token_type);
        $this->_handlers[$method] =& $object;
    }

    /**
     * Create URL containing an event that is triggered when the URL is
     * requested.
     *
     * @access public
     * @param object event $e Event object.
     * @returns string URL to trigger event.
     */
    function link ($e)
    {
        $tokens =& $this->_tokens;
        $te =& $this->_event;

        # Make event object from event handler name or check its class.
        if (is_string ($e))
            $e = new event ($e);
        else if (!is_a ($e, 'event'))
            die ('application::link(): Argument is not an event object.');

        # Die if the event handler doesn't exist.
        $handler = $e->name;
        if (!isset ($this->_handlers[$handler]))
            return "No such event handler '$handler'.";

        # Add session key.
        $e->session_key = $this->session->key ();

        # Add current subsession if the event doesn't have one.
        if (!isset ($e->subsession) || !$e->subsession) {
            if ($te->subsession)
	        $e->subsession = $te->subsession;
            else
                die ('application::link(): Internal error - no subsession in current event.');
        }

        # Store event object in token.
        $token = $tokens->create ($e, $this->_types[$handler]);

        # Create directory part of the URL.
        return $_SERVER['SCRIPT_NAME'] . "/$token/";
    }

    /**
     * Return from a subsession.
     *
     * @access public
     */
    function return2caller ()
    {
        if ($this->debug)
            echo "<b>Returning from session.</b><br>";

        # Get parent event including subsession.
        $s =& $this->_tokens->get ($this->_event->subsession);
        $p =& $s->parent;

        if (!$p)
	    die ('application::return2caller(): No session to return to.');

        # Return to previous subsession.
        $this->call ($p);
    }

    /**
     * Get an argument from current event.
     *
     * @access public
     * @param string $name Name of argument.
     * @param integer $flags Type of argument (TOKEN_SUB | TOKEN_OPTIONAL).
     */
    function arg ($name, $flags = 0)
    { 
        $e =& $this->_event;
        $func = $e->name;
        $arg =& $e->arg ($name);
        $sub =& $this->_subargs[$name];

        if (!is_string ($name))
            die ('application::arg(): Argument name is not a string.');

        # Load session argument with argument of the same name.
        if ($flags & ARG_SUB && isset ($arg))
            $sub = $arg;

        # Check if queried type of argument exists if non-optional.
        if (($flags & ARG_OPTIONAL) == false) {
            if ($flags & ARG_SUB) {
                if (!isset ($sub))
                    die ("$func(): subsession argument $name: not set");
            } else if (!isset ($arg))
                die ("$func(): argument '$name': not set");
        }

        # Return argument of specified type.
        if (($flags & ARG_SUB) && isset ($sub))
            return $sub;
        if (isset ($arg))
            return $arg;
    }

    /**
     * Get all arguments.
     *
     * @access public
     * @returns array Array of arguments keyed by name.
     */
    function args ()
    {
        return $this->_event->args;
    }

    /**
     * Define tables for application class.
     *
     * @access public
     */
    function application_define_database ()
    {
        if (!$this->session)
            die ('Authorisation required.');

        $def =& $this->db->def;
        $this->session->define_tables ();
        $this->_tokens->define_database ($def);
    }

    /**
     * Set time to live until auto-logout.
     *
     * @access public
     * @param integer $seconds Number of seconds which must be >1.
     */
    function set_timeout ($seconds)
    {
        if (!is_int ($seconds))
            die ('application::set_timeout(): Argument is not an integer.');

        $this->session->set_timeout ($seconds);
        $this->_tokens->set_timeout ($seconds);
    }

    /**
     * Set handler name for null event.
     *
     * Use this method before run().
     *
     * @access public
     * @param string $handler
     * @see run()
     */
    function set_null_handler ($handler)
    {
        if (!is_string ($handler))
            die ('application::set_null_handler(): Handler name is not a string.');

        $this->_null_handler = $handler;
    }

    #######################
    ### Private section ###
    #######################

    /**
     * Set token type for event for a particular event handler.
     *
     * @access private
     * @param string $name Name of the event handler.
     * @param integer $t Token type.
     */
    function _set_type ($name, $t)
    {
        if ($t != TOKEN_DEFAULT && $t != TOKEN_ONETIME && $t != TOKEN_REUSE)
            die ("application::add_function(), add_method(): Unknown token type $t for event handler $name.");
        $this->_types[$name] = $t;
    }

    /**
     * Create new subsession for next event.
     *
     * Argument 'caller' contains the event object of the caller. It is
     * stored in a new subsession object and called by return2caller().
     *
     * @access private
     */
    function __call_sub ()
    {
        $e = $this->arg ('caller');
        $s = new _application_subsession ($e);
        $s->args = $this->_subargs;
        $token = $this->_tokens->create ($s);
        $this->_event->next->subsession = $token;
    }

    function _application_dump ()
    {
        echo '<HR><B>Function object:</B><BR>';
        debug_dump ($this->_event);
        echo '<HR><B>Session object:</B><BR>';
        $tmp = $this->_tokens->get ($this->_event->subsession);
        debug_dump ($tmp);
        echo '<HR>';
    }

    /**
     * Get event object from URL.
     *
     * @access private
     */
    function _url2event ()
    {
        $url = $_SERVER['PATH_INFO'];
        $tokens =& $this->_tokens;
        $session =& $this->session;

        if (!$url)
            return;
        $path = explode ('/', $url);
        if (!$path[1])
	    return;
        $e = $tokens->get ($path[1]);
        if (!is_a ($e, 'event'))
            return;
        $session->read_id ($e->session_key);
        $session->force_key ();
        return $e;
    }

    /**
     * Install sql tables.
     *
     * @access private
     */
    function _application_install ()
    {
        global $SCRIPT_NAME;
        $db =& $this->db;

        $this->application_define_database ();
        $db->create_tables ();
        if ($err = $db->error ())
            die ("<b>application.class install failed: $err</b><br>" .
                 "Please check file 'config.php' and try again.<br>" .
                 "Bitte &Uuml;berpr&uuml;fen Sie die Eintr&auml;ge in der " .
                 "Datei .dbi.conf und versuchen Sie es erneut.");
        die ("<font color=\"green\"><b>application base installed - <a href=\"$SCRIPT_NAME\">Please reload</a>.</b>");
    }

    /**
     * Initialize everything.
     *
     * @access private
     */
    function _application_init ()
    {
        global $debug;

        if ($debug)
            debug_env_dump ();

        # Connect to database specified in .dbi.conf.php.
        if (!file_exists ('config.php'))
	    die ('Can\'t find file .dbi.conf.php - stop.<br>Kann Datei .dbi.conf.php nicht finden - stop.');
        include 'config.php';
        $this->db = new DBI ($dbidatabase, $dbiserver, $dbiuser, $dbipwd);
        $db =& $this->db;

        # Get session.
        $this->session = new DBSESSION ($this->db);
        if (isset ($application_session_table))
             $this->session->set_table ($application_session_table);

        # Force new session if there's none.
        $this->session->force_key ();

        # Create tokenizer.
        $this->_tokens = new dbtoken ($db, $this->session);
        if (isset ($application_token_table))
           $this->_tokens->set_table ($application_token_table);
        else
           $application_token_table = 'tokens';

        # Invoke init in derived class.
        # All event handlers and database tables must be registered in there.
        $this->init ();

        # Check if tables are installed and install them if not.
        $db->select ('COUNT(1)', $application_token_table);
        if ($db->error ())
            $this->_application_install ();

        # Register default page functions.
        $this->add_method ('defaultview', $this);
        $this->add_method ('return2caller', $this);
        $this->add_method ('__call_sub', $this);

        # Get event object from URL.
        $this->_event = $this->_url2event ();
    }
}
?>
