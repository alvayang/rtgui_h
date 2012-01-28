#!/bin/sh



# the full path of the file need to be convert.
fle=$1
# the full path of the file saved after converted
dest=$2
# saved name
sav=$3
rm -f $dest/"$sav.mp4"
ffmpeg -i "$fle"  -ac 2 -ab 160k -vcodec libx264 -vpre slow -vpre ipod640 -b 1200k -f mp4 -threads 10 $dest/"$sav.mp4" >> /tmp/convertlog 2>&1 &

