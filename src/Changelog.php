<?php

namespace Changelogger;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Class used for objects representing changelog files.
 */
class Changelog
{
    /**
     * The changelog title.
     *
     * @var string
     */
    private $title;

    /**
     * The versions in the file.
     *
     * @var array
     */
    public $versions = [];

    /**
     * The git repository service.
     *
     * @var string
     */
    public $service;

    /**
     * The URL references.
     *
     * @var array
     */
    private $references = [];


    /**
     * Constructs the Changelog object.
     *
     * @param string $git_url
     * @param string $service
     */
    public function __construct($service)
    {
        $this->service = $service;
    }

    /**
     * Translates object into a string format.
     *
     * @return string
     */
    public function __toString()
    {
        $versions = join("\n", $this->versions);
        krsort($this->references, SORT_NUMERIC);

        $references = join("\n", array_map(function ($link, $reference_id) {
            return "[#$reference_id]: $link";
        }, $this->references, array_keys($this->references)));

        return <<<CL
        # {$this->title}

        All notable changes to this project will be documented in this file.

        The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
        and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


        {$versions}
        {$references}

        CL;
    }

    /**
     * Sets the changelog title.
     *
     * @param string $title
     *   The changelog title.
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Adds the version.
     *
     * @param Version $version
     *   A version object.
     */
    public function addVersion(Version $version)
    {
        $this->versions[] = $version;
    }

    /**
     * Adds version object data before other versions.
     *
     * This is useful for adding a temporary "Unreleases", "master", or "*-dev"
     * version.
     *
     * @param Version $version
     *   A version object.
     */
    public function prependVersion(Version $version)
    {
        array_unshift($this->versions, $version);
    }

    /**
     * Finds the "Unreleased" version or creates it if non-existent.
     *
     * @param string $git_url
     *   The base git repository URL.
     */
    public function findUnreleased($git_url)
    {
        foreach ($this->versions as $version) {
            if ($version->getVersion() === 'Unreleased') {
                return $version;
            }
        }

        $version = new Version($git_url, $this->service);
        $version->setVersion('Unreleased');
        $version->setPrevious($this->findLatest()->getVersion());
        $this->prependVersion($version);

        return $version;
    }

    /**
     * Find the latest released version.
     *
     * @return Version
     *   A version object.
     */
    public function findLatest()
    {
        foreach ($this->versions as $version) {
            if ($version->getVersion() === 'Unreleased') {
                continue;
            }

            return $version;
        }
    }

    /**
     * Set issue references.
     *
     * @param array $references
     *   An array of issue references.
     */
    public function setReferences(array $references)
    {
        $this->references = $references;
    }

    /**
     * Add an issue reference.
     *
     * @param integer $reference_id
     *   The ID of the issue reference.
     * @param string $url
     *   The URL of the issue.
     */
    public function addReference(int $reference_id, string $url)
    {
        $this->references[$reference_id] = $url;
    }

    /**
     * Attempts to get a default Git URL.
     *
     * @return string
     *   The discovered git URL.
     */
    public static function getDefaultGitUrl() {
        $default = '';
        try {
            $process = Process::fromShellCommandline('git remote get-url origin');
            $default = $process->mustRun()->getOutput();
            $default = trim($default);
        } catch (RuntimeException $e) {}

        if (preg_match('/.git$/', $default)) {
            $default = preg_replace('/^git@/', '', $default);
            $default = preg_replace('/.git$/', '', $default);
            $default = 'https://' . str_replace(':', '/', $default);
        }

        return $default;
    }

    /**
     * Returns default changelog file contents as a starting point.
     *
     * @param string $git_url
     *   The base git repository URL.
     *
     * @return string
     *   The default file contents.
     */
    public static function default($git_url)
    {
        return <<<CL
        # Changelog

        All notable changes to this project will be documented in this file.

        The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
        and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


        ## [Unreleased]
        [Unreleased]: {$git_url}

        CL;
    }
}
