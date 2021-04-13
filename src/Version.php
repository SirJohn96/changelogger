<?php

namespace Changelogger;

/**
 * Class representing version and changes corresponding to a version.
 */
class Version
{
    /**
     * The version string.
     *
     * @var string
     */
    private $version;

    /**
     * The previous version.
     *
     * @var string
     */
    private $previous;

    /**
     * Git repository url.
     *
     * @var string
     */
    private $gitUrl;

    /**
     * A date formatted in YYYY-MM-DD.
     *
     * @var string
     */
    private $date;

    /**
     * Items representing additions.
     *
     * @var array
     */
    private $added;

    /**
     * Items representing changes.
     *
     * @var array
     */
    private $changed;

    /**
     * Items representing deprecations.
     *
     * @var array
     */
    private $deprecated;

    /**
     * Items representing fixes.
     *
     * @var array
     */
    private $fixed;

    /**
     * Items representing removals.
     *
     * @var array
     */
    private $removed;

    /**
     * Items representing security fixes.
     *
     * @var array
     */
    private $security;

    /**
     * Constructs a Version object.
     *
     * @param string $git_url
     *   The base git repository URL.
     */
    public function __construct($git_url)
    {
        $this->gitUrl = $git_url;
    }

    /**
     * Trnslates object into a string format.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->previous) {
            $reference = "[$this->version]: $this->gitUrl/compare/$this->previous...$this->version";
        } elseif (!$this->previous && $this->version !== 'Unreleased') {
            $reference = "[$this->version]: $this->gitUrl/releases/tag/$this->version";
        } else {
            $reference = "[$this->version]: $this->gitUrl";
        }

        $format_entry = function (Item $item) {
            return "- $item";
        };

        $added = '';
        if (!empty($this->added)) {
            $added = implode("\n", array_map($format_entry, $this->added));
            $added = "### Added\n$added\n";
        }

        $changed = '';
        if (!empty($this->changed)) {
            $changed = implode("\n", array_map($format_entry, $this->changed));
            $changed = "### Changed\n$changed\n";
        }

        $deprecated = '';
        if (!empty($this->deprecated)) {
            $deprecated = implode("\n", array_map($format_entry, $this->deprecated));
            $deprecated = "### Deprecated\n$deprecated\n";
        }

        $fixed = '';
        if (!empty($this->fixed)) {
            $fixed = implode("\n", array_map($format_entry, $this->fixed));
            $fixed = "### Fixed\n$fixed\n";
        }

        $removed = '';
        if (!empty($this->removed)) {
            $removed = implode("\n", array_map($format_entry, $this->removed));
            $removed = "### Removed\n$removed\n";
        }

        $security = '';
        if (!empty($this->security)) {
            $security = implode("\n", array_map($format_entry, $this->security));
            $security = "### Security\n$security\n";
        }

        return <<<V
        ## [{$this->version}] - {$this->date}
        $reference

        {$added}{$changed}{$deprecated}{$fixed}{$removed}{$security}

        V;
    }

    /**
     * Gets the version string.
     *
     * @return string
     *   The version string.
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Sets the version string.
     *
     * @param string $version
     *   The version string.
     * @param string $date
     *   The date in string format.
     */
    public function setVersion(string $version, $date = null)
    {
        $this->version = $version;
        $this->date = $date;
    }

    /**
     * Sets the previous version string.
     *
     * @param string $previous
     *   The previous version.
     */
    public function setPrevious(string $previous)
    {
        $this->previous = $previous;
    }

    /**
     * Sets update description.
     *
     * @param string $type
     *   The update type.
     * @param array $value
     *   The update value.
     */
    public function setUpdate($type, array $value)
    {
        $this->{strtolower($type)} = $value;
    }

    /**
     * Adds update description.
     *
     * @param string $type
     *   The update type.
     * @param string $value
     *   The update value.
     */
    public function addUpdate($type, Item $value)
    {
        $this->{strtolower($type)}[] = $value;
    }
}
