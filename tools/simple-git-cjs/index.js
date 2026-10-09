// @wordpress/env calls require( 'simple-git' )( options ); simple-git 4 only exports { simpleGit }.
const v4 = require( 'simple-git-v4' );

module.exports = Object.assign( ( ...args ) => v4.simpleGit( ...args ), v4 );
