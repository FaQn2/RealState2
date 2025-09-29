const path = require('path');

module.exports = {
  entry: './assets/js/swup-bundle.js',
  output: {
    filename: 'swup-bundle.js',
    path: path.resolve(__dirname, 'assets/js'),
  },
  mode: 'production'
};