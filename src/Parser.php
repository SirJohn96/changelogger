<?php

namespace Changelogger;

/**
 * Undocumented class
 */
class Parser
{
    /**
     * An array of lines in the received file.
     *
     * @var array
     */
    private $tokens;

    /**
     * Git repository url.
     *
     * @var string
     */
    private $gitUrl;

    /**
     * The types of updates.
     *
     * @var array
     */
    private $updateTypes;

    /**
     * An array of error output code lines.
     *
     * @var array
     */
    private $span;

    /**
     * The current line number.
     *
     * @var integer
     */
    private $lineNumber = 0;

    /**
     * Constructs the parser object.
     *
     * @param string $changelog
     * @param string $git_url
     * @param string $service
     * @param array $update_types
     */
    public function __construct(string $changelog, string $git_url, string $service, array $update_types)
    {
        $this->tokens = array_map('trim', explode("\n", $changelog));
        $this->gitUrl = $git_url;
        $this->service = $service;
        $this->updateTypes = $update_types;
    }

    /**
     * Parses the changelog file.
     *
     * @return Changelog
     *   The changelog object.
     */
    public function parse()
    {
        $changelog = $this->parseTitle();

        $this->acceptEmptyLine();
        $this->acceptTextLine('All notable changes to this project will be documented in this file.');
        $this->acceptEmptyLine();
        $this->acceptTextLine('The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),');
        $this->acceptTextLine('and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).');
        $this->acceptEmptyLine();
        $this->acceptEmptyLine();

        while ($this->isVersionString($this->current())) {
            $version = $this->parseVersion();
            $changelog->addVersion($version);
        }

        $references = $this->parseReferences();
        $changelog->setReferences($references);

        $this->acceptEmptyLine();

        return $changelog;
    }

    /**
     * Parses the changelog title.
     *
     * @return Changelog
     *   The changelog object.
     */
    private function parseTitle()
    {
        if (preg_match('/# (.+)/', $this->next(), $matches)) {
            $changelog = new Changelog();
            $changelog->setTitle($matches[1]);

            return $changelog;
        }

        throw $this->error('Expected a title');
    }

    /**
     * Parses the update information for the current version.
     *
     * @return Version
     *   The version object.
     */
    private function parseVersion()
    {
        if ($this->isVersionString($this->next(), $matches)) {
            $version = new Version($this->gitUrl, $this->service);
            if (isset($matches[4])) {
                $version->setVersion($current = $matches[1], $matches[4]);
            } else {
                $version->setVersion($current = $matches[1]);
            }

            $compareLink = $this->next();
            if (!preg_match('/^\[/', $compareLink)) {
                throw $this->error('Expected link to compare page with previous version');
            }

            $previous = '\d+\.\d+\.\d+(-[\w\.]+)?';
            $url = preg_quote($this->gitUrl, '/');
            if ($this->service == 'GitHub') {
                $regex_compare = "/^ \[$current\]\: \s $url\/compare\/($previous)\.\.\.$current $/x";
                $regex_first_tag = "/^ \[$current\]\: \s $url\/releases\/tag\/$current $/x";
            } elseif ($this->service == 'GitLab') {
                $regex_compare = "/^ \[$current\]\: \s $url\/compare\/($previous)\.\.\.$current $/x";
                $regex_first_tag = "/^ \[$current\]\: \s $url\/tags\/$current $/x";
            }
            $regex_first_tag_unreleased = "/^ \[$current\]\: \s $url $/x";

            if (preg_match($regex_compare, $compareLink, $matches)) {
                $version->setPrevious($matches[1]);
            } elseif (!preg_match($regex_first_tag, $compareLink, $matches) && !preg_match($regex_first_tag_unreleased, $compareLink, $matches)) {
                throw $this->error('Error in compare link syntax');
            }

            $this->acceptEmptyLine();

            $typesCount = count($this->updateTypes);

            for ($i = 0; $i < $typesCount; $i++) {
                foreach ($this->updateTypes as $key => $type) {
                    if (preg_match('/^\#\#\# \s ' . $type . ' $/x', $this->current())) {
                        $this->next();

                        $version->setUpdate($type, $this->parseItems());

                        $this->acceptEmptyLine();

                        break;
                    }
                }
            }

            $this->acceptEmptyLine();

            return $version;
        }

        throw $this->error('Expected version');
    }

    /**
     * Parses the update items.
     *
     * @return array
     *   The array of parsed items.
     */
    private function parseItems()
    {
        $items = [];
        while (preg_match('/^\- (.+) $/x', $this->current(), $matches)) {
            $this->next();

            $item = new Item();
            $message = $matches[1];
            $reference_regex = '/\[ \#(\d+) \]/x';

            preg_match_all($reference_regex, $message, $matches);
            foreach ($matches[1] as $match) {
                $item->addReference($match);
            }

            $message = trim(preg_replace($reference_regex, '', $message));
            $item->setMessage($message);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Parses reference URLs.
     *
     * @return array
     *   An array of references.
     */
    private function parseReferences()
    {
        $references = [];
        $url = preg_quote($this->gitUrl, '/');
        while (preg_match('/^\[/', $this->current())) {
            if (preg_match("/^ \[\#(\d+)\]\: \s ($url\/issues\/\d+) $/x", $this->next(), $matches)) {
                $references[$matches[1]] = $matches[2];
            } else {
                throw $this->error('Error parsing reference');
            }
        }

        return $references;
    }

    /**
     * Accepts the current line as being empty and moves to the next.
     */
    private function acceptEmptyLine()
    {
        while (('' === $this->current() || preg_match('/^\s*$/', $this->current())) && count($this->tokens) > 0) {
            $this->next();
        }
    }

    /**
     * Accepts the current line as a line of arbitrary text.
     */
    private function acceptTextLine($text)
    {
        while ($text === $this->current() && count($this->tokens) > 0) {
            $this->next();
        }
    }

    /**
     * Returns the current line in the file.
     *
     * @return string
     *   The current line.
     */
    private function current()
    {
        if (count($this->tokens) === 0) {
            return '';
        }

        return $this->tokens[0];
    }

    /**
     * Returns the next line in the file.
     *
     * Also updates the current line number.
     *
     * @return string
     *   The value of the next line.
     */
    private function next()
    {
        if (count($this->tokens) === 0) {
            throw $this->error('Unexpected end of file');
        }

        $number = ++$this->lineNumber;
        $line = array_shift($this->tokens);

        $this->span[] = "    {$number}: $line";

        if (count($this->span) > 4) {
            array_shift($this->span);
        }

        return $line;
    }

    /**
     * Check if line is version header.
     *
     * @param string $line
     *   The provided line in file.
     * @param array $matches
     *   The matched string version.
     *
     * @return boolean
     *   Whether line is a version header or not.
     */
    private function isVersionString(string $line, array &$matches = null)
    {
        return preg_match('/^ \#\# \s \[( \d+\.\d+\.\d+(-[\w\.]+)? | Unreleased )\] (\s - \s)? (\d+-\d+-\d+)? /x', $line, $matches);
    }

    /**
     * Formats an error message in context of the recent lines in the file.
     *
     * @param string $message
     *   The error message.
     *
     * @return ParseException
     *   The Exception object.
     */
    private function error(string $message)
    {
        $count = count($this->span) - 1;
        $this->span[$count] = preg_replace('/^\s{4}/', ' -> ', $this->span[$count]);

        if (count($this->tokens) > 0) {
            $this->next();
        }

        return new ParseException($message, implode("\n", $this->span));
    }
}
