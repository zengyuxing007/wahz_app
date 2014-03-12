baselogdir=/usr/local/nginx/logs

selfpath=$(cd "$(dirname "$0")"; pwd) 
rotatelogs=$selfpath/rotatelogs

#while [ 1 ] 
#do 
echo `date +"%F %T"`" rotatelogs access start" 
$rotatelogs $baselogdir/access_%Y%m%d.log 86400 480 < $baselogdir/access_log & 
$rotatelogs $baselogdir/error_%Y%m%d.log 86400 480 < $baselogdir/error_log &
echo `date +"%F %T"`" rotatelogs access stop" 
sleep 1; 
#done 

