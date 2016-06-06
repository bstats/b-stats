# b-stats

Pretty good imageboard archival website software.

## What is it?
Initially conceived as a website to track board statistics, b-stats has
since eschewed such a goal and is now an archive for some 4chan boards.

The "reference implementation" (if you can call it that) is currently
located at https://archive.b-stats.org/. Most of the time, it's running
exactly what you see in the repo, although I usually deploy code there
prior to committing, to make sure it works in a production environment.

## Installing

b-stats is written to work on Linux and Windows servers, with minimal
setup required. It has been tested on both Windows and Linux using
Apache 2.2 and 2.4, MySQL 5.5 and MariaDB 10, and currently uses the
features of PHP 7.

### Basic Requirements
- Web Server supporting URL rewriting (Apache 2.4 is known to work well)
- PHP 7 (with PDO, cURL and GD enabled)
- MariaDB Server (slight modification would be required to use MySQL instead)

For Windows, `psexec` from Sysinternals is required to use the archive
part, and should be in a directory in the system `PATH`. I'm looking for
a better way, but PHP on Windows is somewhat limited.

Surprisingly little RAM (under 30MB) is needed to run the archive
script, although that does not take into account the amount of memory
used by MariaDB, which can be in the hundreds of megabytes.

### Setup

- Make a directory, set up your web server to point there.
- `git clone https://github.com/bstats/b-stats.git .` 
- `cd backend`
- `php ./setUp.php`
- Go to your website
- TODO: easy way to add boards
  - (there's an API for it at /admin/addBoard)

## Project Goals

TODO