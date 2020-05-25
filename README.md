# Contao Cleanup Bundle

This bundle offers a symfony command to find and remove unused objects in a Contao CMS instance.

**CAUTION: This bundle is still in ealy state and should only be used by experienced developers. We _can't_ take any responsibility for any data loss caused by the use of this bundle!**

## Features

- clean up your contao instance by removing objects not included anywhere in the CMS
- currently supported objects:
  - modules

## Installation

1. Install via composer: `composer require heimrichhannot/contao-cleanup-bundle`.
1. Run the desired command in your project's root.

## Commands

### Modules

`vendor/bin/contao-console cleanup:modules`

Parameters:

