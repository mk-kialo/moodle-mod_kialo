# Plugin Development Guide

## Run Moodle locally

This starts Moodle locally on port 88 with MariaDB running on port 3366.
This is using non-default ports to avoid conflicts with already running services.

It locally mounts moodle in the folder `moodle`. You can copy the plugin into the `moodle/mod` folder,
to test changes.

```shell
docker compose up
```

## IDE Setup

* https://docs.moodle.org/dev/Setting_up_PhpStorm
* https://docs.moodle.org/dev/Setting_up_VSCode
* https://docs.moodle.org/dev/Setting_up_ViM

# Moodle 3 / 4 compatibility

* A single branch can be used to support both Moodle 3x and 4x activity plugins by including icon.svg for Moodle 3x and monologo.svg for Moodle 4x.
* in `kialo_supports` when defining a MOD_PURPOSE_, `if (defined('FEATURE_MOD_PURPOSE') && $feature === FEATURE_MOD_PURPOSE) {
  return MOD_PURPOSE_CONTENT` to ensure Moodle 3 compatibility.

# Global Moodle vars

* `$CFG`: This global variable contains configuration values of the Moodle setup, such as the root directory, data directory, database details, and other config values.
* ```$SESSION`: Moodle's wrapper round PHP's `$_SESSION`.
* `$USER`: Holds the user table record for the current user. This will be the 'guest' user record for people who are not logged in.
* `$SITE`: Frontpage course record. This is the course record with id=1.
* `$COURSE`: This global variable holds the current course details. An alias for `$PAGE->course`.
* `$PAGE`: This is a central store of information about the current page we are generating in response to the user's request.
* `$OUTPUT`: `$OUTPUT `is an instance of core_renderer or one of its subclasses. It is used to generate HTML for output.
* `$DB`: This holds the database connection details. It is used for all access to the database.

# Docs

* https://registry.hub.docker.com/r/bitnami/moodle - setting up Moodle locally
* https://docs.moodle.org/dev/Automatic_class_loading
* https://docs.moodle.org/dev/Plugin_contribution_checklist

# TODOs

* go through the plugin checklist linked above
* the plugin content should be at the root of the repo, not its own folder
* the git repo should be called `moodle-mod_kialo`