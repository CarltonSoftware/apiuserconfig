#!/bin/bash

echo Updating property.css
lessc less/property.less > css/property.css
echo property.css updated!