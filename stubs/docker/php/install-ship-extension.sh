#!/bin/sh
set -eu

extension=$1
version=$2

if [ -z "$version" ]; then
    case "$extension" in
        redis) version=6.3.0 ;;
        xdebug) version=3.5.3 ;;
    esac

    # These are the first upstream releases with PHP 8.6 support.
    if php -r 'exit(PHP_VERSION_ID >= 80600 ? 0 : 1);'; then
        case "$extension" in
            redis) version=6.4.0RC1 ;;
            # The 3.6.0alpha1 preview is not published to the PECL channel.
            xdebug) exec install-php-extensions xdebug/xdebug@7377b07e5bef3ce7457128424c9d1334066daf79 ;;
        esac
    fi
fi

exec install-php-extensions "$extension-$version"
