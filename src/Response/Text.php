<?php

namespace Aether\Response;

use Exception;

/**
 * Textual response
 *
 * Created: 2007-02-05
 * @author Raymond Julin
 * @package aether.lib
 */
class Text extends Response
{
    /**
     * Hold text string for output
     * @var string
     */
    private $out = '';
    private $contentType;

    /**
     * Constructor
     *
     * @access public
     * @param string $output
     */
    public function __construct($output, $contentType = null)
    {
        $this->out = $output;
        $this->contentType = $contentType;
    }

    /**
     * Draw text response. Echoes out the response
     *
     * @access public
     * @return void
     * @param \Aether\ServiceLocator $sl
     */
    public function draw($sl)
    {
        if (session_id() !== '') {
            $_SESSION['wasGoingTo'] = $_SERVER['REQUEST_URI'];
        }

        $out = $this->out;

        if ($this->contentType) {
            header("Content-Type: {$this->contentType}; charset=UTF-8");
        }
        echo $out;
    }

    /**
     * Return instead of echo
     *
     * @access public
     * @return string
     */
    public function get()
    {
        return $this->out;
    }
}
