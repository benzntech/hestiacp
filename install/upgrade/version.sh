#!/bin/bash

# Add release branch system configuration if non-existent
release_branch_check=$(cat $HESTIA/conf/hestia.conf | grep RELEASE_BRANCH)
if [ -z "$release_branch_check" ]; then
    echo "(*) Adding global release branch variable to system configuration..."
    sed -i "/RELEASE_BRANCH/d" $HESTIA/conf/hestia.conf
    echo "RELEASE_BRANCH='release'" >> $HESTIA/conf/hestia.conf
fi

# Step through version upgrade scripts in order as necessary to ensure that systems
# are properly upgraded if skipping versions.
if [ $VERSION = "$version" ]; then
    echo "(!) The latest version of Hestia Control Panel ($version) is already installed."
    echo "    Verifying configuration..."
    echo ""
    source /usr/local/hestia/install/upgrade/versions/$version.sh
    VERSION="$version"
fi
if  [ $VERSION = "0.9.8-25" ] || [ $VERSION = "0.9.8-26" ] || [ $VERSION = "0.9.8-27" ] || [ $VERSION = "0.9.8-28" ]; then
    source /usr/local/hestia/install/upgrade/versions/0.9.8-29.sh
    VERSION="0.9.8-29"
fi
if [ $VERSION = "0.9.8-29" ]; then
    source /usr/local/hestia/install/upgrade/versions/1.00.0-190618.sh
    VERSION="1.00.0-190618"
fi
if [ $VERSION = "0.10.00" ] || [ $VERSION = "1.00.0-190618" ] || [ $VERSION = "1.00.0-190621" ]; then
    source /usr/local/hestia/install/upgrade/versions/1.0.1.sh
    VERSION="1.0.1"
fi
if [ $VERSION = "1.0.1" ]; then
    source /usr/local/hestia/install/upgrade/versions/1.0.2.sh
    VERSION="1.0.2"
fi

if [ $VERSION = "1.0.2" ]; then
    source /usr/local/hestia/install/upgrade/versions/1.0.3.sh
    VERSION="1.0.3"
fi

if [ $VERSION = "1.0.3" ]; then
    source /usr/local/hestia/install/upgrade/versions/1.0.4.sh
    VERSION="1.0.4"
fi

if [ $VERSION = "1.0.4" ]; then
    source /usr/local/hestia/install/upgrade/versions/1.0.4.sh
    VERSION="1.0.5"
fi

if [ $VERSION = "1.0.5" ]; then
    source /usr/local/hestia/install/upgrade/versions/$version.sh
    VERSION="$version"
fi
