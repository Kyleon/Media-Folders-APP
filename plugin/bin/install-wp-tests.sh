#!/usr/bin/env bash
# Instala la suite de tests oficial de WordPress en /tmp para los tests
# de integración (suite=integration en phpunit.xml).
#
# Uso:
#   bin/install-wp-tests.sh <db-name> [db-user] [db-pass] [db-host] [wp-version] [skip-database-creation]
#
# Ejemplo:
#   bin/install-wp-tests.sh wordpress_test root '' localhost latest
#
# (Adaptación del script estándar de wp-cli/scaffold)

if [ $# -lt 1 ]; then
    echo "uso: $0 <db-name> [db-user] [db-pass] [db-host] [wp-version] [skip-db]"
    exit 1
fi

DB_NAME=$1
DB_USER=${2-root}
DB_PASS=${3-}
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo "$TMPDIR" | sed -e "s/\/$//")
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress/}

download() {
    if command -v curl >/dev/null 2>&1; then
        curl -s "$1" > "$2"
    elif command -v wget >/dev/null 2>&1; then
        wget -nv -O "$2" "$1"
    fi
}

install_wp() {
    if [ -d "$WP_CORE_DIR" ]; then return; fi
    mkdir -p "$WP_CORE_DIR"
    if [ "$WP_VERSION" = 'latest' ]; then
        local ARCHIVE_NAME='latest'
    else
        local ARCHIVE_NAME="wordpress-$WP_VERSION"
    fi
    download "https://wordpress.org/${ARCHIVE_NAME}.tar.gz" "$TMPDIR/wordpress.tar.gz"
    tar --strip-components=1 -zxmf "$TMPDIR/wordpress.tar.gz" -C "$WP_CORE_DIR"
    download "https://raw.githubusercontent.com/markoheijnen/wp-mysqli/master/db.php" "$WP_CORE_DIR/wp-content/db.php"
}

install_test_suite() {
    if [ ! -d "$WP_TESTS_DIR" ]; then
        mkdir -p "$WP_TESTS_DIR"
        svn co --quiet "https://develop.svn.wordpress.org/tags/${WP_VERSION}/tests/phpunit/includes/" "$WP_TESTS_DIR/includes"
        svn co --quiet "https://develop.svn.wordpress.org/tags/${WP_VERSION}/tests/phpunit/data/" "$WP_TESTS_DIR/data"
    fi
    if [ ! -f "$WP_TESTS_DIR/wp-tests-config.php" ]; then
        download "https://develop.svn.wordpress.org/tags/${WP_VERSION}/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config.php"
        sed -i.bak "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" "$WP_TESTS_DIR/wp-tests-config.php"
        sed -i.bak "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
        sed -i.bak "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR/wp-tests-config.php"
        sed -i.bak "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR/wp-tests-config.php"
        sed -i.bak "s|localhost|$DB_HOST|" "$WP_TESTS_DIR/wp-tests-config.php"
    fi
}

install_db() {
    if [ "${SKIP_DB_CREATE}" = "true" ]; then return; fi
    local PARTS=(${DB_HOST//\:/ })
    local DB_HOSTNAME=${PARTS[0]}
    local DB_SOCK_OR_PORT=${PARTS[1]}
    local EXTRA=""
    if ! [ -z "$DB_HOSTNAME" ] ; then
        if [ "$(echo "$DB_SOCK_OR_PORT" | grep -e '^[0-9]\{1,\}$')" ]; then
            EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
        elif ! [ -z "$DB_SOCK_OR_PORT" ] ; then
            EXTRA=" --socket=$DB_SOCK_OR_PORT"
        elif ! [ -z "$DB_HOSTNAME" ] ; then
            EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
        fi
    fi
    mysqladmin create "$DB_NAME" --user="$DB_USER" --password="$DB_PASS"$EXTRA
}

install_wp
install_test_suite
install_db
