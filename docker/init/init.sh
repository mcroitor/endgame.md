#!/bin/bash

# remove old data
echo "Removing old data"
rm -rf /database/*
rm -rf /site/*
echo "Old data removed"

# copy new data
echo "Copying new data"
cp -rf /src/database/* /database
cp -rf /src/site/* /site
echo "Data copied"

# echo set owner www-data
echo "Setting owner www-data"
chown -R www-data:www-data /database
chown -R www-data:www-data /site
echo "Owner set"

echo "Init done"
