// @wordpress/env calls yaml.safeLoad(); js-yaml 4 removed it because load() is now safe by default.
const v4 = require( 'js-yaml-v4' );

module.exports = Object.assign( {}, v4, { safeLoad: v4.load, safeLoadAll: v4.loadAll, safeDump: v4.dump } );
