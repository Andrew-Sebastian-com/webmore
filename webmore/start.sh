#!/bin/sh
set -eu
umask 077
cd "$(dirname "$0")"
exec php -S 127.0.0.1:8000 -t public
