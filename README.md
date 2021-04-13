# ChangeLogger

Based on a similar system that can be found in the [Deployer codebase](https://github.com/deployphp/deployer/blob/master/bin/changelog), Changelogger provides a way to easily manage a changelog file. By providing a CLI tool, Changelogger aims to ease the updating of changelog files while maintaining consistency.

This system assumes adherance to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) will be used in the project it will be leveraged in.

## Installation
This is a composer package. It can be installed to any composer-based project by running `composer require --dev sirjohn96/changelogger`.

## Usage
The CLI tool can be leverage for changelog maintenance. The following commands can be used:
- `./vendor/bin/changelogger update` - Adds notes to a new unreleased version with the specified types.
- `./vendor/bin/changelogger release` - Updates the unreleased notes to the updated version specified and genrates the appropriate diff link.

## Compatibility
This system is currently not compatible with GitLab repositories. This will be added in a future release.
