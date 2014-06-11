#!/bin/sh
#######################################################################
#######################################################################
###                                                                 ###
###    ActiveREST general example for test scripts                  ###
###                                                                 ###
#######################################################################
#######################################################################

# Please set root URL here, username and password
URL="http://activerest"
USERNAME="test"
PASSWORD="123"

echo
echo Checking for exists for test variable ID 777 
echo This request must return 404 error code
echo curl --digest --user ${USERNAME}:${PASSWORD} -X GET -H "Content-Type: application/json" ${URL}/test/?id=777
curl --digest --user ${USERNAME}:${PASSWORD} -X GET -H "Content-Type: application/json" ${URL}/test/?id=777

echo
echo Creating test variable ID 777 with value test
echo curl --digest --user ${USERNAME}:${PASSWORD} -X PUT -H "Content-Type: application/json" -d '{id:777,value:"test"}' ${URL}/test/
curl --digest --user ${USERNAME}:${PASSWORD} -X PUT -H "Content-Type: application/json" -d '{id:777,value:"test"}' ${URL}/test/

echo
echo Checking for exists for variable ID 777 for now
echo curl --digest --user ${USERNAME}:${PASSWORD} -X GET -H "Content-Type: application/json" ${URL}/test/?id=777
curl --digest --user ${USERNAME}:${PASSWORD} -X GET -H "Content-Type: application/json" ${URL}/test/?id=777

echo
echo Updating test variable ID 777 - set test777 to it\'s value
echo curl --digest --user ${USERNAME}:${PASSWORD} -X POST -H "Content-Type: application/json" -d '{id:777,value:"test777"}' ${URL}/test/
curl --digest --user ${USERNAME}:${PASSWORD} -X POST -H "Content-Type: application/json" -d '{id:777,value:"test777"}' ${URL}/test/

echo
echo Checking for changes made for test variable ID 777
echo curl --digest --user ${USERNAME}:${PASSWORD} -X GET -H "Content-Type: application/json" ${URL}/test/?id=777
curl --digest --user ${USERNAME}:${PASSWORD} -X GET -H "Content-Type: application/json" ${URL}/test/?id=777

echo
echo Remove test variable ID 777
echo curl --digest --user ${USERNAME}:${PASSWORD} -X DELETE -H "Content-Type: application/json" ${URL}/test/?id=777
curl --digest --user ${USERNAME}:${PASSWORD} -X DELETE -H "Content-Type: application/json" ${URL}/test/?id=777

echo
echo Checking for exists test variable ID 777 for now
echo curl --digest --user ${USERNAME}:${PASSWORD} -X GET -H "Content-Type: application/json" ${URL}/test/?id=777
curl --digest --user ${USERNAME}:${PASSWORD} -X GET -H "Content-Type: application/json" ${URL}/test/?id=777

echo
