#!/usr/bin/python
#####################################################################################

import re
import os
import os.path
import sys
import stat
import urllib
import string
import time

#####################################################################################

gnuchess = '/usr/games/gnuchess'
phalanx  = '/usr/games/phalanx -l- -o-'
crafty   = '/usr/games/crafty learn=0 log=off'

mysite = sys.argv[1]
myname = sys.argv[2]
mypass = sys.argv[3]
mymode = sys.argv[4]
mydept = sys.argv[5]

tmpdir   = '/tmp'
fprefix  = 'mcc_robot_'
lockfile = '/tmp/chess_robot.lock'

login_url = mysite + 'index.php?mygames=1'
pgn_url   = mysite + 'pgnformat.php?gameid=%s'
chess_url = mysite + 'chess.php?gameid=%s'

debug     = 0

#####################################################################################

def log ( p_text ):
	print time.asctime() + " - " + p_text

#####################################################################################

try:
	os.open(lockfile, os.O_CREAT|os.O_EXCL)
except Exception, e:
	log("Another robot is already running...")
	log("Check file %s" % lockfile)
	sys.exit(0)

#####################################################################################

class MyURLopener(urllib.URLopener):
	def open ( self, p_url, p_data = '' ):
		try:
			result = urllib.URLopener.open(self, p_url, p_data)
			return result
		except IOError, err:
			print "IOError while opening " + p_url
			os.unlink(lockfile)
			raise err



#####################################################################################

login_data = {
	'login_username': myname,
	'login_password': mypass
} 

agent = MyURLopener()

p_overview = agent.open(login_url, urllib.urlencode(login_data))
p_overview_data = p_overview.read()

cookies = p_overview.info()['set-cookie']
cookie  = cookies.split(';')[0]
agent.addheader('Cookie', cookie)

re_gameids   = re.compile('src="images/lamp_(\w+?)\.png".*?href="chess\.php\?gameid=(.+?)[&"]', re.IGNORECASE | re.DOTALL)
re_htmlpre   = re.compile('.*<pre>',  re.IGNORECASE | re.DOTALL)
re_htmlafter = re.compile('</pre>.*', re.IGNORECASE | re.DOTALL)
re_mymovegc  = re.compile("\nmy move is *: ([a-zA-Z0-9_-]+)", re.IGNORECASE)
re_mymoveph  = re.compile("\nmy move is ([a-zA-Z0-9_-]+)", re.IGNORECASE)
re_mymovecr  = re.compile("\nmove ([a-zA-Z0-9_-]+)", re.IGNORECASE)
re_comnt_gc  = re.compile('(.*\=.*)', re.IGNORECASE)
#re_comnt_ph  = re.compile('(.*\\.\..*)', re.IGNORECASE)
re_comnt_ph  = re.compile('(.*\=.*)', re.IGNORECASE)
re_comnt_cr  = re.compile('(.*\=.*)', re.IGNORECASE)
#re_players   = re.compile('\d+-(\w+)-(\w+)-')


for gamestatus, gameid in re_gameids.findall(p_overview_data):
	log('------------------ ' + gameid)

	if gamestatus != 'green':
		log("Not my turn...")
		continue

	#players = list(re_players.findall(gameid)[0])

	pgndata = agent.open(pgn_url % gameid).read()
	pgndata = re_htmlpre.sub('', pgndata)
	pgndata = re_htmlafter.sub('', pgndata)
	pgndata = pgndata.lstrip()

	pgnmoves = []

	for pgnline in pgndata.split("\n"):
		if len(pgnline) > 0 and pgnline[0] in "0123456789":
			fields = pgnline.split(' ')
			for f in fields:
				if len(f)>0 and f[0] not in "0123456789":
					pgnmoves.append(f)

	if mymode == 'gnuchess':
		file_gc_orders = '%s/%s%s.gc_orders' % (tmpdir, fprefix, gameid)
		file_gnuchess  = '%s/%s%s.gnuchess'  % (tmpdir, fprefix, gameid)

		orders = "xboard\ndepth " + str(mydept) + "\nforce\n" + string.join(pgnmoves, "\n") + "\ngo\nexit\n";
		open(file_gc_orders, 'w').write(orders)

		os.system('%s < %s > %s' % ( gnuchess, file_gc_orders, file_gnuchess))

		gcreply = open(file_gnuchess, 'r').read()
		gcmoves = re_mymovegc.findall(gcreply)
		if len(gcmoves) == 0:
			move = None
		else:
			move = gcmoves[0]

		comment = string.join(re_comnt_gc.findall(gcreply), "\n")
		#comment = '' 

		if not debug:
			os.unlink(file_gc_orders)
			os.unlink(file_gnuchess)

	elif mymode == 'phalanx':
		file_ph_orders = '%s/%s%s.ph_orders' % (tmpdir, fprefix, gameid)
		file_phalanx   = '%s/%s%s.phalanx'   % (tmpdir, fprefix, gameid)

		orders = "post\ndepth " + str(mydept) + "\nforce\n" + string.join(pgnmoves, "\n") + "\ngo\nscore\nexit\n";
		open(file_ph_orders, 'w').write(orders)

		if mydept == 1:
			phalanx_params = ' -e 50'
		else:
			phalanx_params = ''

		os.system('%s %s < %s > %s' % ( phalanx, phalanx_params, file_ph_orders, file_phalanx))

		phreply = open(file_phalanx, 'r').read()
		phmoves = re_mymoveph.findall(phreply)
		if len(phmoves) == 0:
			move = None
		else:
			move = phmoves[0]

		comment = string.join(re_comnt_ph.findall(phreply), "\n")
		#comment = '' 

		if not debug:
			os.unlink(file_ph_orders)
			os.unlink(file_phalanx)

	elif mymode == 'crafty':
		file_cr_orders = '%s/%s%s.cr_orders' % (tmpdir, fprefix, gameid)
		file_crafty    = '%s/%s%s.crafty'    % (tmpdir, fprefix, gameid)

		orders = "xboard\ndepth " + str(mydept) + "\nforce\n" + string.join(pgnmoves, "\n") + "\ngo\n";
		open(file_cr_orders, 'w').write(orders)

		os.system('%s < %s > %s' % ( crafty, file_cr_orders, file_crafty))

		crreply = open(file_crafty, 'r').read()
		crmoves = re_mymovecr.findall(crreply)
		if len(crmoves) == 0:
			move = None
		else:
			move = crmoves[0]

		comment = string.join(re_comnt_cr.findall(crreply), "\n")
		#comment = '' 

		if not debug:
			os.unlink(file_cr_orders)
			os.unlink(file_crafty)


	if move is None:
		move_data = {
			'move_to_archive': 'Move To Archive'
		}
	else:
		#if move == 'O-O':
		#	move = '0-0'

		#if move == 'O-O-O':
		#	move = '0-0-0'

		move_data = {
			'chessmove':      move,
			'chesscomment':   comment,
			'move_chessman':  'Move!'
		}

	log(str(move_data))
	submit_html = agent.open(chess_url % gameid, urllib.urlencode(move_data)).read()
	#p = os.popen('grep " LOG: "', 'w')
	#p.write(submit_html)
	#p.close()

	if 'Move To Archive' in submit_html:
		move_data = {
			'move_to_archive': 'Move To Archive'
		}

		log(str(move_data))
		submit_html = agent.open(chess_url % gameid, urllib.urlencode(move_data)).read()

	if debug:
		file_html = '%s/%s.html'      % (tmpdir, gameid)
		open(file_html, 'w').write(submit_html)


#####################################################################################

os.unlink(lockfile)

#####################################################################################
