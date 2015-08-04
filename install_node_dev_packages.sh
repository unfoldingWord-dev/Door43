#!/bin/bash
#
# This will install node dependencies for Door43 needed for javascript unit testing
#
# Use the command `sudo ./install_node_dev_packages.sh` to run this script
#

# install karma modules globally
npm install -g karma
npm install -g karma-chrome-launcher
npm install -g karma-cli
npm install -g karma-firefox-launcher
npm install -g karma-jasmine
npm install -g phantomjs
npm install -g karma-phantomjs-launcher
