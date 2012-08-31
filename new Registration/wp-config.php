<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'lifestrokes_com_6');

/** MySQL database username */
define('DB_USER', 'lifestrokescom6');

/** MySQL database password */
define('DB_PASSWORD', 'U^2mXBFa');

/** MySQL hostname */
define('DB_HOST', 'mysql.lifestrokes.com');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         's~~V7;Jvma1k?;2VdtRU3Hl&U*#+H_{eQpu+n"/~7}t-f(100@`2{nu63NwpzV;l');
define('SECURE_AUTH_KEY',  '/+HBu?vlzsOh4B/3{UY+6keZ;N`EHd3J6}q6RKpF@D+mh#-?|c{_;S3f^eLWXaP|');
define('LOGGED_IN_KEY',    '=!bW8l|nYcFxkGm"a7aFcMecol@(vNXZuKUZeu1YtHnXYWx8JrD^7hrd55Y&qdxT');
define('NONCE_KEY',        'fYYx|4UAy$w_mp]:2AqCEJsOsZ-1?b}r%:k0ETxTpJ895)DZepVB^vDSA6%MpQbP');
define('AUTH_SALT',        'jzuy?|Q^Y=H(PExeFFp~ejg=Z(REVQ+%cW}G:4uOf:=0hlq@:6=af)hGsbO40;cB');
define('SECURE_AUTH_SALT', '/c)[%]%e[;A=Q)_/IGzf)=OtJk-&Lbo??A=soL0A/ozwX8#v44mZVUP/-eoHr*{p');
define('LOGGED_IN_SALT',   'F8lz)bJC8f9yJ=sz!BY*2WD)b+welUW{J|;!_|6#$~6#^YUVV(ObY"xOe!TZFS7$');
define('NONCE_SALT',       '(R!yOf#eu~5(bmm2D{%]42)K_P4/e?h`P1;?0pJ7}=EH^pY9d%`5rl1!8M_egFm"');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_zwfpq6_';

/**
 * Limits total Post Revisions saved per Post/Page.
 * Change or comment this line out if you would like to increase or remove the limit.
 */
define('WP_POST_REVISIONS',  10);

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

