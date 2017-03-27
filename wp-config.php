<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'happywordpress');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

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
define('AUTH_KEY',         'hoN{:0Y{aF|Z4o_jUKOsE<BH.5&<S|Mf@WouO]R:*4r+bb||}a^)(LHCi6 !nSb_');
define('SECURE_AUTH_KEY',  '4S{qL}4CqKg$^=D;Swc :-Laobh*CD+f{Z:l(TeRF9[Wx&}Q(AMG/eM*U}tCpqZm');
define('LOGGED_IN_KEY',    'j3JyP*jXXudua^t5ht}9o|5|;M&VhM/MC%b;t6WGqy%{dhO]k(,AUE./VN<kzufz');
define('NONCE_KEY',        ':lItpV_eA8zm_xyW4}0YA{5=7+=MOoBDo8IlSmX1Q0-(mv{x4uu[z<D{D-TgiJh>');
define('AUTH_SALT',        'n%JKUUP>yy-]Jq(<C6?#.Ay4N&6Cm{8*ew|m,@Ww$W^W!vTMj8^v$;MD]k`d#})W');
define('SECURE_AUTH_SALT', '=)225DOm<D+F2BlkNJ,)sgC*/dLr#&.pRyJJJ+)$jO}]~jIgZ#UK7f1LbIq#z2BV');
define('LOGGED_IN_SALT',   '59ON X@kox`B0x)7F?q>5o7D(2@~O)iFU;TIA|/#5Jtb5=yL%-kqgfJ&7O*)(Js5');
define('NONCE_SALT',       '&AL4v&3aIF>nlc9~vv|6TcCN/e0o{wQ98FM`^4|dZ%906+az/JIa<%m#(Vo-WgdY');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
