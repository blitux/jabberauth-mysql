#!/usr/bin/python

# Imports
import sys
import logging
from hashlib import sha256
from struct import pack
from struct import unpack
import MySQLdb

# Config
#
# error_log_file: filename where errors are written.
# logformat: format for log messages.
# logfile: filename where log messages are written.
# domain: actual jabber server domain

error_log_file = '/var/log/ejabberd/extauth.err.log'
logformat = '%(asctime)s %(levelname)s %(message)s'
logfile = '/var/log/ejabberd/extauth.log'
loglevel = logging.DEBUG
domain  = '@domain'

dbuser = 'db_user'
dbpass = 'db_password'
dbhost = 'localhost'
dbname = 'jabber'


class EjabberdInputError(Exception):
    def __init__(self, value):
        self.value = value
    def __str__(self):
        return repr(self.value)

def log_result(op, in_user, bool):
    if bool:
        logging.info("%s successful for %s"%(op, in_user))
    else:
        logging.info("%s unsuccessful for %s"%(op, in_user))

def db_connect():
    try:
        database=MySQLdb.connect(dbhost, dbuser, dbpass, dbname)
    except:
        logging.debug("Unable to initialize database, check settings!")
        return False
    return (database.cursor(),database)


def db_close(dbcur,database):
    dbcur.close()
    database.close()

def query_user(user):
    dbcur,database = db_connect()
    dbcur.execute("SELECT username,password,prefs FROM users WHERE username ='%s'" % user)
    register = dbcur.fetchone()
    dbcur.close()
    database.close()
    return register

def query(query):
    dbcur.execute(query)
    dbcur.fetchone()


# Function get_auth_data()
# Function to get data from ejabberd, from stdin
#
# @params void
# @return array Data from ejabberd. [operation,username,servername,password]
#
def get_auth_data():
    logging.debug('Trying to get 2 bytes from ejabberd @ ejabberd_read()')
    try:
        input_len = sys.stdin.read(2)
    except IOError:
        logging.debug('ERROR: IOError @ ejabberd_read()')

    if len(input_len) is not 2:
        logging.debug('ERROR: Wrong input @ ejabberd_read()')
        raise EjabberdInputError('ERROR: Wrong input from ejabberd @ ejabberd_read()')

    logging.debug('Got 2 bytes from stdin: %s' % input_len)
    (size,) = unpack('>h', input_len)
    logging.debug('Size of input: %i' % size)
    data = sys.stdin.read(size).split(':')
    logging.debug('Data from ejabberd: %s' % data)
    return data


# Function return_op_result()
# Function to write data to ejabberd.
#
# @params bool op_result Using true for success and false for fail.
# @return bytes(?) Binary data. \x00\x02\x00\x01 for success. \x00\x02\x00\x00 for fail.
#
def return_op_result(op_result):
    logging.debug('Auth result: %s' % op_result)
    answer = 0
    if op_result:
        answer = 1
    token = pack('>hh',2,answer)
    logging.debug("Sent bytes: %#x %#x %#x %#x" % (ord(token[0]), ord(token[1]), ord(token[2]), ord(token[3])))
    sys.stdout.write(token)
    sys.stdout.flush()

def isuser(user,host):
    return_value = False
    userdata = query_user(user)
    if userdata == None:
        logging.info('Wrong username: %s' % user)
        return_value = False
    elif user+"@"+host == userdata[0]+domain:
        return_value = True
    return return_value

def auth(user,host,password):
    return_value = False
    data = query_user(user)

    if data == None:
        logging.info('Wrong username: %s' % user)
        return_value = False
    elif user+"@"+host == data[0]+domain:
        if sha256(data[2]+':'+user+':'+password).hexdigest() == data[1]:
            logging.info('Authentication Granted for user: %s' % user)
            return_value = True
        else:
            logging.info('Authentication Denied for user: %s' % user)
            return_value = True
    else:
        logging.info('Authentication Denied for user: not none %s' % domain )
        return_value = False

    return return_value


sys.stderr = open(logfile,'a')
logging.basicConfig(level=loglevel,format=logformat,filename=logfile,filemode='a')

logging.info('extauth script started')

# Main Loop

while True:
    try:
        request = get_auth_data()

    except EjabberdInputError, inst:
        logging.info('ERROR: Exception ocurred: %s', inst)
        break

    logging.debug('DEBUG: Operation: %s' % request[0])
    op_result = False

    if request[0] == 'auth':
        op_result = auth(request[1], request[2], request[3])

    elif request[0] == 'isuser':
        op_result = isuser(request[1], request[2])

    elif request[0] == 'setpass':
        op_result=False

    return_op_result(op_result)
    log_result(request[0], request[1], op_result)